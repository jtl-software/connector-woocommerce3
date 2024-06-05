<?php

namespace JtlWooCommerceConnector\Controllers;

use Exception;
use http\Exception\RuntimeException;
use InvalidArgumentException;
use Jtl\Connector\Core\Controller\DeleteInterface;
use Jtl\Connector\Core\Controller\PullInterface;
use Jtl\Connector\Core\Controller\PushInterface;
use Jtl\Connector\Core\Controller\StatisticInterface;
use Jtl\Connector\Core\Definition\IdentityType;
use Jtl\Connector\Core\Mapper\PrimaryKeyMapperInterface;
use Jtl\Connector\Core\Model\AbstractImage;
use Jtl\Connector\Core\Model\AbstractModel;
use Jtl\Connector\Core\Model\CategoryImage;
use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\ImageI18n;
use Jtl\Connector\Core\Model\ManufacturerImage;
use Jtl\Connector\Core\Model\ProductImage;
use Jtl\Connector\Core\Model\QueryFilter;
use JtlWooCommerceConnector\Controllers\ImageController as ImageCtrl;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlMedia;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlProduct;
use JtlWooCommerceConnector\Logger\ErrorFormatter;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\Id;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;
use WC_Product;

class ImageController extends AbstractBaseController implements
    PullInterface,
    StatisticInterface,
    PushInterface,
    DeleteInterface
{
    public const GALLERY_DIVIDER    = ',';
    public const PRODUCT_THUMBNAIL  = '_thumbnail_id';
    public const CATEGORY_THUMBNAIL = 'thumbnail_id';
    public const GALLERY_KEY        = '_product_image_gallery';
    public const MANUFACTURER_KEY   = 'pwb_brand_image';
    public const PRODUCT_IMAGE      = 'product';
    public const CATEGORY_IMAGE     = 'category';
    public const MANUFACTURER_IMAGE = 'manufacturer';

    private array $alreadyLinked = [];

    protected PrimaryKeyMapperInterface $primaryKeyMapper;

    public function __construct(Db $db, Util $util, PrimaryKeyMapperInterface $primaryKeyMapper)
    {
        parent::__construct($db, $util);
        $this->primaryKeyMapper = $primaryKeyMapper;
    }


    // <editor-fold defaultstate="collapsed" desc="Pull">
    /**
     * @param QueryFilter $query
     * @return array
     * @throws InvalidArgumentException
     * @throws \Psr\Log\InvalidArgumentException
     * @throws Exception
     */
    public function pull(QueryFilter $query): array
    {
        $limit = $query->getLimit();

        $images        = $this->productImagePull($limit);
        $productImages = $this->addNextImages($images, IdentityType::PRODUCT_IMAGE, $limit);

        $images         = $this->categoryImagePullByQuery($this->getCategoryImagePullQuery($limit));
        $categoryImages = $this->addNextImages($images, IdentityType::CATEGORY_IMAGE, $limit);

        $combinedArray = \array_merge($productImages, $categoryImages);

        if (SupportedPlugins::isPerfectWooCommerceBrandsActive()) {
            $images             = $this->manufacturerImagePull($this->getManufacturerImagePullQuery($limit));
            $manufacturerImages = $this->addNextImages($images, IdentityType::MANUFACTURER_IMAGE, $limit);
            $combinedArray      = \array_merge($combinedArray, $manufacturerImages);
        }

        return $combinedArray;
    }

    /**
     * @param $images
     * @param $type
     * @param $limit
     * @return array
     * @throws InvalidArgumentException
     * @throws Exception
     */
    private function addNextImages($images, $type, &$limit): array
    {
        $return = [];

        $language = $this->util->getWooCommerceLanguage();
        if ($this->wpml->canBeUsed() && $this->wpml->canWpmlMediaBeUsed()) {
            $language = $this->wpml->convertLanguageToWawi($this->wpml->getDefaultLanguage());
        }

        foreach ($images as $image) {
            $imgSrc = \wp_get_attachment_image_src($image['ID'], 'full');

            switch ($type) {
                case IdentityType::PRODUCT_IMAGE:
                    $model = new ProductImage();
                    break;
                case IdentityType::CATEGORY_IMAGE:
                    $model = new CategoryImage();
                    break;
                case IdentityType::MANUFACTURER_IMAGE:
                    $model = new ManufacturerImage();
                    break;
                default:
                    throw new \Exception(\sprintf("Invalid image type '%s'", $type));
            }

            $model->setId(new Identity($image['id']))
                ->setName((string)$image['post_name'])
                ->setForeignKey(new Identity($image['parent']))
                ->setRemoteUrl((string) isset($imgSrc[0]) ? $imgSrc[0] : $image['guid'])
                ->setSort((int)$image['sort'])
                ->setFilename((string)\wc_get_filename_from_url($image['guid']));

            $altText = \get_post_meta($image['ID'], '_wp_attachment_image_alt', true);

            $model
                ->addI18n((new ImageI18n())
                    ->setId(new Identity($image['id']))
                    ->setAltText((string)\substr($altText !== false ? $altText : '', 0, 254))
                    ->setLanguageISO($language));

            if ($this->wpml->canBeUsed() && $this->wpml->canWpmlMediaBeUsed()) {
                $this->wpml
                    ->getComponent(WpmlMedia::class)
                    ->getTranslations($image['ID'], $model);
            }

            $return[] = $model;
            $limit--;
        }
        return $return;
    }

    /**
     * Loop the products to get their images and validate and map them.
     *
     * @param null $limit The limit.
     *
     * @return array The image entities.
     * @throws \Psr\Log\InvalidArgumentException
     */
    private function productImagePull($limit = null): array
    {
        $imageCount  = 0;
        $attachments = [];

        $this->alreadyLinked = $this->db->queryList(SqlHelper::linkedProductImages());

        try {
            $page = 0;

            while ($imageCount < $limit) {
                $query = new \WP_Query([
                    'fields' => 'ids',
                    'post_type' => ['product', 'product_variation'],
                    'post_status' => ['future', 'draft', 'publish', 'inherit', 'private'],
                    'posts_per_page' => 50,
                    'orderby' => 'ID',
                    'paged' => $page++,
                ]);

                if ($query->have_posts()) {
                    foreach ($query->posts as $postId) {
                        $product = \wc_get_product($postId);

                        if (!$product instanceof WC_Product) {
                            continue;
                        }

                        $attachmentIds  = $this->fetchProductAttachmentIds($product);
                        $newAttachments = $this->addProductImagesForPost($attachmentIds, $postId);

                        if (empty($newAttachments)) {
                            continue;
                        }

                        $attachments = \array_merge($newAttachments, $attachments);
                        $imageCount += \count($newAttachments);

                        if ($imageCount >= $limit) {
                            return $imageCount <= $limit ? $attachments : \array_slice($attachments, 0, $limit);
                        }
                    }
                } else {
                    return $imageCount <= $limit ? $attachments : \array_slice($attachments, 0, $limit);
                }
            }
        } catch (Exception $ex) {
            return $imageCount <= $limit ? $attachments : \array_slice($attachments, 0, $limit);
        }

        return $imageCount <= $limit ? $attachments : \array_slice($attachments, 0, $limit);
    }

    /**
     * Fetch the cover image and the gallery images for a given product.
     *
     * @param WC_Product $product The product for which the cover image and gallery images should be fetched.
     *
     * @return array An array with the image ids.
     */
    private function fetchProductAttachmentIds(WC_Product $product): array
    {
        $attachmentIds = [];

        $pictureId = (int)$product->get_image_id('edit');

        if (!empty($pictureId)) {
            $attachmentIds[] = $pictureId;
        }

        if (!$product->is_type('variation')) {
            $imageIds = $product->get_gallery_image_ids();

            if (!empty($imageIds)) {
                $attachmentIds = \array_merge($attachmentIds, $imageIds);
            }
        }
        if (
            SupportedPlugins::isActive(
                SupportedPlugins::PLUGIN_ADDITIONAL_VARIATION_IMAGES_GALLERY_FOR_WOOCOMMERCE
            )
        ) {
            if ($product->is_type('variation')) {
                $images = \get_post_meta($product->get_id(), 'woo_variation_gallery_images', true);
                if (!empty($images)) {
                    $attachmentIds = \array_merge($attachmentIds, $images);
                }
            }
        }

        return $attachmentIds;
    }

    /**
     * Filter out images that are already linked and get image information.
     *
     * @param array $attachmentIds The image ids that should be checked.
     * @param int $postId The product which is owner of the images.
     *
     * @return array The filtered image data.
     * @throws \Psr\Log\InvalidArgumentException
     */
    private function addProductImagesForPost(array $attachmentIds, int $postId): array
    {
        $attachmentIds = $this->filterAlreadyLinkedProducts($attachmentIds, $postId);
        return $this->fetchProductAttachments($attachmentIds, $postId);
    }

    /**
     * @param $attachmentIds
     * @param $productId
     * @return array
     * @throws \Psr\Log\InvalidArgumentException
     */
    private function fetchProductAttachments($attachmentIds, $productId): array
    {
        $sort        = 0;
        $attachments = [];

        if (empty($attachmentIds)) {
            return $attachments;
        }

        foreach ($attachmentIds as $attachmentId) {
            if (!\file_exists(\get_attached_file($attachmentId))) {
                $this->logger->debug(
                    \sprintf('Image file does not exist: %s', \get_attached_file($attachmentId))
                );

                continue;
            }

            $picture = \get_post($attachmentId, \ARRAY_A);

            if (!\is_array($picture)) {
                continue;
            }

            $picture['id']     = Id::linkProductImage($attachmentId, $productId);
            $picture['parent'] = $productId;

            if ($attachmentId !== \get_post_thumbnail_id($productId) && $sort === 0) {
                $picture['sort'] = ++$sort;
            } else {
                $picture['sort'] = $sort;
            }

            ++$sort;

            $attachments[] = $picture;
        }

        return $attachments;
    }

    /**
     * @param $productAttachments
     * @param $productId
     * @return array
     */
    private function filterAlreadyLinkedProducts($productAttachments, $productId): array
    {
        $filtered      = [];
        $attachmentIds = $productAttachments;

        foreach ($attachmentIds as $attachmentId) {
            $endpointId = Id::link([$attachmentId, $productId]);

            if (!\in_array($endpointId, $this->alreadyLinked, true)) {
                $filtered[]            = $attachmentId;
                $this->alreadyLinked[] = $endpointId;
            }
        }

        return $filtered;
    }

    /**
     * @param $limit
     * @return string
     * @throws Exception
     */
    private function getCategoryImagePullQuery($limit): string
    {
        if ($this->wpml->canBeUsed() && $this->wpml->canWpmlMediaBeUsed()) {
            $categoryImageQuery = $this->wpml->getComponent(WpmlMedia::class)->imageCategoryPull($limit);
        } else {
            $categoryImageQuery = SqlHelper::imageCategoryPull($limit);
        }

        return $categoryImageQuery;
    }

    /**
     * @param $limit
     * @return array
     * @throws \Psr\Log\InvalidArgumentException
     */
    private function categoryImagePullByQuery($query): array
    {
        $result = [];

        $images = $this->db->query($query);

        foreach ($images as $image) {
            $image['sort'] = 0;
            $result[]      = $image;
        }

        return $result;
    }

    /**
     * @param $limit
     * @return string
     * @throws Exception
     */
    private function getManufacturerImagePullQuery($limit): string
    {
        if ($this->wpml->canBeUsed() && $this->wpml->canWpmlMediaBeUsed()) {
            $manufacturerImagesQuery = $this->wpml->getComponent(WpmlMedia::class)->imageManufacturerPull($limit);
        } else {
            $manufacturerImagesQuery = SqlHelper::imageManufacturerPull($limit);
        }

        return $manufacturerImagesQuery;
    }

    /**
     * @param $limit
     * @return array
     * @throws \Psr\Log\InvalidArgumentException
     */
    private function manufacturerImagePull($query): array
    {
        $result = [];

        $images = $this->db->query($query);

        foreach ($images as $image) {
            $image['sort'] = 0;
            $result[]      = $image;
        }

        return $result;
    }
    // </editor-fold>

    // <editor-fold defaultstate="collapsed" desc="Stats">
    /**
     * @param QueryFilter $query
     * @return int
     * @throws \Psr\Log\InvalidArgumentException
     * @throws Exception
     */
    public function statistic(QueryFilter $query): int
    {
        $imageCount  = $this->masterProductImageStats();
        $imageCount += \count($this->db->query(SqlHelper::imageVariationCombinationPull()));

        if ($this->wpml->canBeUsed() && $this->wpml->canWpmlMediaBeUsed()) {
            $imageCategoryQuery = $this->wpml->getComponent(WpmlMedia::class)->imageCategoryPull();
            $imageCount        += \count($this->db->query($imageCategoryQuery));
        } else {
            $imageCount += \count($this->db->query(SqlHelper::imageCategoryPull()));
        }

        if (SupportedPlugins::isPerfectWooCommerceBrandsActive()) {
            if ($this->wpml->canBeUsed() && $this->wpml->canWpmlMediaBeUsed()) {
                $imageManufacturerQuery = $this->wpml->getComponent(WpmlMedia::class)->imageManufacturerPull();
                $imageCount            += \count($this->db->query($imageManufacturerQuery));
            } else {
                $imageCount += \count($this->db->query(SqlHelper::imageManufacturerPull()));
            }
        }

        return $imageCount;
    }

    /**
     * @return int|null
     * @throws \Psr\Log\InvalidArgumentException
     * @throws Exception
     */
    private function masterProductImageStats(): ?int
    {
        $this->alreadyLinked = $this->db->queryList(SqlHelper::linkedProductImages());

        $count  = 0;
        $images = [];

        // Fetch unlinked product cover images
        if ($this->wpml->canBeUsed() && $this->wpml->canWpmlMediaBeUsed()) {
            $thumbnails = $this->db->query(
                $this->wpml->getComponent(WpmlMedia::class)->getImageProductThumbnailSql()
            );
        } else {
            $thumbnails = $this->db->query(SqlHelper::imageProductThumbnail());
        }

        foreach ($thumbnails as $thumbnail) {
            $images[(int)$thumbnail['ID']] = (int)$thumbnail['meta_value'];
            $count++;
        }

        // Get all product gallery images
        if ($this->wpml->canBeUsed() && $this->wpml->canWpmlMediaBeUsed()) {
            $productImagesMappings = $this->db->query(
                $this->wpml->getComponent(WpmlMedia::class)->getImageProductGalleryStats()
            );
        } else {
            $productImagesMappings = $this->db->query(SqlHelper::imageProductGalleryStats());
        }


        foreach ($productImagesMappings as $productImagesMapping) {
            $productId       = (int)$productImagesMapping['ID'];
            $galleryImageIds = \array_map('intval', \explode(',', $productImagesMapping['meta_value']));
            $galleryImageIds = \array_filter($galleryImageIds, function ($galleryId) {
                return $galleryId !== 0;
            });
            $galleryImageIds = $this->filterAlreadyLinkedProducts($galleryImageIds, $productId);

            $count += \count($galleryImageIds);

            if (isset($images[$productId]) && \in_array($images[$productId], $galleryImageIds)) {
                --$count;
            }
        }

        return $count;
    }
    // </editor-fold>

    // <editor-fold defaultstate="collapsed" desc="Push">
    /**
     * @param AbstractModel $model
     * @return AbstractModel
     * @throws Exception
     */
    public function push(AbstractModel $model): AbstractModel
    {
        $foreignKey = $model->getForeignKey()->getEndpoint();

        if (!empty($foreignKey)) {
            $this->delete($model, false);

            if ($model instanceof ProductImage) {
                $model->getId()->setEndpoint($this->pushProductImage($model));
            } elseif ($model instanceof CategoryImage) {
                $model->getId()->setEndpoint($this->pushCategoryImage($model));
            } elseif ($model instanceof ManufacturerImage) {
                $model->getId()->setEndpoint($this->pushManufacturerImage($model));
            }
        }

        return $model;
    }

    /**
     * @param AbstractImage $image
     * @return int|null
     * @throws Exception
     */
    private function saveImage(AbstractImage $image): ?int
    {
        $endpointId = $image->getId()->getEndpoint();
        $post       = null;

        $fileInfo  = \pathinfo($image->getFilename());
        $name      = $this->sanitizeImageName(!empty($image->getName()) ? $image->getName() : $fileInfo['filename']);
        $extension = $fileInfo['extension'];
        $uploadDir = \wp_upload_dir();

        $attachment  = [];
        $relinkImage = false;

        $fileName = $this->getNextAvailableImageFilename($name, $extension, $uploadDir['path']);
        if ($endpointId !== '') {
            $id         = Id::unlink($endpointId);
            $attachment = \get_post($id[0], \ARRAY_A) ?? [];

            if (!empty($attachment)) {
                if ($this->isAttachmentUsedInOtherPlaces($attachment['ID'])) {
                    $attachment  = [];
                    $relinkImage = true;
                } else {
                    $fileName = \basename(\get_attached_file($attachment['ID']));
                }
            }
        }

        $destination = self::createFilePath($uploadDir['path'], $fileName);

        if (\copy($image->getFilename(), $destination)) {
            $fileType = \wp_check_filetype(\basename($destination), null);

            $attachment = \array_merge($attachment, [
                'guid' => $uploadDir['url'] . '/' . $fileName,
                'post_mime_type' => $fileType['type'],
                'post_title' => \preg_replace('/\.[^.]+$/', '', $name),
                'post_status' => 'inherit',
            ]);

            $post = \wp_insert_attachment($attachment, $destination, $image->getForeignKey()->getEndpoint());

            if ($post instanceof \WP_Error) {
                $this->logger->error(ErrorFormatter::formatError($post));

                return null;
            }

            require_once(\ABSPATH . 'wp-admin/includes/image.php');
            $attachData = \wp_generate_attachment_metadata($post, $destination);
            \wp_update_attachment_metadata($post, $attachData);
            \update_post_meta($post, '_wp_attachment_image_alt', $this->getImageAlt($image));

            if ($relinkImage) {
                $this->relinkImage($post, $image);
            }

            if ($this->wpml->canWpmlMediaBeUsed()) {
                $this->wpml->getComponent(WpmlMedia::class)->saveAttachmentTranslations($post, $image->getI18ns());
            }
        }

        return $post;
    }

    /**
     * @param int $newEndpointId
     * @param AbstractImage $image
     * @return void
     * @throws Exception
     */
    protected function relinkImage(int $newEndpointId, AbstractImage $image): void
    {
        $primaryKeyMapper = $this->primaryKeyMapper;

        switch (\get_class($image)) {
            case ProductImage::class:
                $newEndpoint = Id::linkProductImage($newEndpointId, $image->getForeignKey()->getEndpoint());
                $type        = IdentityType::PRODUCT_IMAGE;
                break;
            case ManufacturerImage::class:
                $newEndpoint = Id::linkManufacturerImage($newEndpointId);
                $type        = IdentityType::MANUFACTURER_IMAGE;
                break;
            case CategoryImage::class:
                $newEndpoint = Id::linkCategoryImage($newEndpointId);
                $type        = IdentityType::CATEGORY_IMAGE;
                break;
            default:
                throw new \Exception(\sprintf('Relation type %s is not supported.', $image->getRelationType()));
        }

        $primaryKeyMapper->delete(
            $type,
            $image->getId()->getEndpoint(),
            $image->getId()->getHost()
        );
        $primaryKeyMapper->save($type, $newEndpoint, $image->getId()->getHost());

        $image->getId()->setEndpoint($newEndpoint);
    }


    /**
     * @param string $name
     * @return string
     */
    private function sanitizeImageName(string $name): string
    {
        $name = \iconv('utf-8', 'ascii//translit', $name);
        $name = \preg_replace('#[^A-Za-z0-9\-_ ]#', '-', $name);
        $name = \preg_replace('#-{2,}#', '-', $name);
        $name = \trim($name, '-');

        return \mb_substr($name, 0, 180);
    }

    /**
     * @param $name
     * @param $extension
     * @param $uploadDir
     * @return string
     */
    protected function getNextAvailableImageFilename($name, $extension, $uploadDir): string
    {
        $i            = 1;
        $originalName = $name;
        $name         = \preg_replace('#[^A-Za-z0-9\-_]#', '-', $name);
        do {
            $fileName     = \sprintf('%s.%s', $name, $extension);
            $fileFullPath = self::createFilePath($uploadDir, $fileName);
            if ($fileExists = \file_exists($fileFullPath)) {
                $name = \sprintf('%s-%s', $originalName, $i++);
            }
        } while ($fileExists);

        return $fileName;
    }

    /**
     * @param AbstractImage $image
     * @return string
     */
    protected function getImageAlt(AbstractImage $image): string
    {
        $altText = $image->getName();
        $i18ns   = $image->getI18ns();

        if (\count($i18ns) > 0) {
            foreach ($i18ns as $i18n) {
                if (
                    $this->util->isWooCommerceLanguage($i18n->getLanguageISO())
                    && !empty($i18n->getAltText())
                ) {
                    $altText = $i18n->getAltText();
                    break;
                }
            }
        }

        return $altText;
    }

    /**
     * @param ProductImage $image
     * @return string|null
     * @throws Exception
     */
    private function pushProductImage(ProductImage $image): ?string
    {
        $productId = (int)$image->getForeignKey()->getEndpoint();
        $wcProduct = \wc_get_product($productId);

        if (!$wcProduct instanceof WC_Product) {
            return null;
        }

        $attachmentId = $this->saveImage($image);

        if ($this->isCoverImage($image)) {
            $result = \set_post_thumbnail($productId, $attachmentId);
            if ($result instanceof \WP_Error) {
                $this->logger->error(ErrorFormatter::formatError($result));

                return null;
            }

            if ($this->wpml->canBeUsed()) {
                $wpmlProductIds = $this->db->queryList(SqlHelper::getWpmlProductIds((int) $wcProduct->get_sku()));
                $wpmlProductIds = \array_diff($wpmlProductIds, [$productId]);

                foreach ($wpmlProductIds as $wpmlProductId) {
                    $wpmlResult = \set_post_thumbnail($wpmlProductId, $attachmentId);
                    if ($wpmlResult instanceof \WP_Error) {
                        $this->logger->error(ErrorFormatter::formatError($wpmlResult));

                        return null;
                    }
                }
            }
        } else {
            if (
                SupportedPlugins::isActive(
                    SupportedPlugins::PLUGIN_ADDITIONAL_VARIATION_IMAGES_GALLERY_FOR_WOOCOMMERCE
                )
            ) {
                if ($wcProduct->get_type() === 'variation') {
                    $oldImages = \get_post_meta($wcProduct->get_id(), 'woo_variation_gallery_images', true);
                    if (!\is_array($oldImages)) {
                        $oldImages = [];
                    }
                    $newImages = \array_unique(\array_merge([$attachmentId], $oldImages));
                    \update_post_meta($wcProduct->get_id(), 'woo_variation_gallery_images', $newImages, $oldImages);
                }
            }

            $galleryImages   = $this->getGalleryImages($productId);
            $galleryImages[] = (int)$attachmentId;
            $galleryImages   = \implode(self::GALLERY_DIVIDER, \array_unique($galleryImages));
            $result          = \update_post_meta($productId, self::GALLERY_KEY, $galleryImages);
            if ($result instanceof \WP_Error) {
                $this->logger->error(ErrorFormatter::formatError($result));

                return null;
            }
        }

        return Id::linkProductImage($attachmentId, $productId);
    }

    /**
     * @param CategoryImage $image
     * @return string|null
     * @throws Exception
     */
    private function pushCategoryImage(CategoryImage $image): ?string
    {
        $categoryId = (int)$image->getForeignKey()->getEndpoint();

        if (!\term_exists($categoryId)) {
            return null;
        }

        $attachmentId = $this->saveImage($image);
        \update_term_meta($categoryId, self::CATEGORY_THUMBNAIL, $attachmentId);

        return Id::linkCategoryImage($attachmentId);
    }

    /**
     * @param ManufacturerImage $image
     * @return string|null
     * @throws Exception
     */
    private function pushManufacturerImage(ManufacturerImage $image): ?string
    {
        $termId = (int)$image->getForeignKey()->getEndpoint();

        if (!\term_exists($termId)) {
            return null;
        }

        $attachmentId = $this->saveImage($image);
        \update_term_meta($termId, self::MANUFACTURER_KEY, $attachmentId);

        return Id::linkManufacturerImage($attachmentId);
    }
    // </editor-fold>

    // <editor-fold defaultstate="collapsed" desc="Delete">
    /**
     * @param AbstractImage $model
     * @param bool $realDelete
     * @return AbstractModel
     * @throws Exception
     */
    public function deleteData(AbstractModel $model, bool $realDelete = true): AbstractModel
    {
        switch ($model->getRelationType()) {
            case self::PRODUCT_IMAGE:
                $this->deleteProductImage($model, $realDelete);
                break;
            case self::CATEGORY_IMAGE:
            case self::MANUFACTURER_IMAGE:
                $this->deleteImageTermMeta($model, $realDelete);
                break;
        }

        return $model;
    }

    /**
     * @param AbstractImage $model
     * @return AbstractModel
     * @throws Exception
     */
    public function delete(AbstractModel $model): AbstractModel
    {
        return $this->deleteData($model);
    }


    /**
     * @param AbstractImage $image
     * @param $realDelete
     * @return void
     * @throws Exception
     */
    private function deleteImageTermMeta(AbstractImage $image, $realDelete): void
    {
        $endpointId = $image->getId()->getEndpoint();
        switch (\get_class($image)) {
            case ManufacturerImage::class:
                $id = Id::unlinkManufacturerImage($endpointId);
                break;
            case CategoryImage::class:
                $metaKey = self::CATEGORY_THUMBNAIL;
                $id      = Id::unlinkCategoryImage($endpointId);
                break;
            default:
                throw new RuntimeException(
                    \sprintf(
                        "Invalid relation %s type for id %s when deleting image.",
                        $image->getRelationType(),
                        $endpointId
                    )
                );
        }

        \delete_term_meta($image->getForeignKey()->getEndpoint(), $metaKey);

        if ($realDelete) {
            if (empty($id) && \strpos($endpointId, "_") === false) {
                $id = $endpointId;
            }
            $this->deleteIfNotUsedByOthers((int) $id);
        }
    }

    /**
     * @param AbstractImage $image
     * @param $realDelete
     * @return void
     * @throws \Psr\Log\InvalidArgumentException
     */
    private function deleteProductImage(AbstractImage $image, $realDelete): void
    {
        $imageEndpoint = $image->getId()->getEndpoint();
        $ids           = Id::unlink($imageEndpoint);

        if (\count($ids) !== 2) {
            return;
        }

        $attachmentId = (int)$ids[0];
        $productId    = (int)$ids[1];

        $wcProduct = \wc_get_product($productId);
        if (!$wcProduct instanceof WC_Product) {
            return;
        }

        if ($image->getSort() === 0 && \strlen($imageEndpoint) === 0) {
            $this->deleteAllProductImages($productId);
            $this->db->query(SqlHelper::imageDeleteLinks($productId));
        } else {
            if ($this->isCoverImage($image)) {
                \delete_post_thumbnail($productId);
                $this->db->query(SqlHelper::imageDeleteLinks($productId));
            } else {
                if (
                    SupportedPlugins::isActive(
                        SupportedPlugins::PLUGIN_ADDITIONAL_VARIATION_IMAGES_GALLERY_FOR_WOOCOMMERCE
                    )
                ) {
                    if ($wcProduct->get_type() === 'variation') {
                        $newImages = $oldImages = \get_post_meta(
                            $wcProduct->get_id(),
                            'woo_variation_gallery_images',
                            true
                        );
                        if (!empty($oldImages)) {
                            $keyToRemove = \array_search($attachmentId, $oldImages);
                            if ($keyToRemove !== false) {
                                unset($newImages[$keyToRemove]);
                                \update_post_meta(
                                    $wcProduct->get_id(),
                                    'woo_variation_gallery_images',
                                    $newImages,
                                    $oldImages
                                );
                            }
                        }
                    }
                }

                $galleryImages = $this->getGalleryImages($productId);
                $galleryImages = \implode(self::GALLERY_DIVIDER, \array_diff($galleryImages, [$attachmentId]));
                \update_post_meta($productId, self::GALLERY_KEY, $galleryImages);
            }

            if ($realDelete) {
                $this->deleteIfNotUsedByOthers((int)$attachmentId);
            }
        }
    }

    /**
     * @param int $attachmentId
     * @return void
     * @throws \Psr\Log\InvalidArgumentException
     */
    private function deleteIfNotUsedByOthers(int $attachmentId): void
    {
        if (empty($attachmentId) || \get_post($attachmentId) === false) {
            return;
        }

        if ($this->isAttachmentUsedInOtherPlaces($attachmentId) === false) {
            if (\get_attached_file($attachmentId) !== false) {
                \wp_delete_attachment($attachmentId, true);
            }
        }
    }

    /**
     * @param int $attachmentId
     * @return bool
     * @throws \Psr\Log\InvalidArgumentException
     */
    protected function isAttachmentUsedInOtherPlaces(int $attachmentId): bool
    {
        $total = 0;

        $total += (int)$this->db->queryOne(
            SqlHelper::countRelatedProducts($attachmentId)
        );
        $total += (int)$this->db->queryOne(
            SqlHelper::countTermMetaImages($attachmentId, ImageCtrl::CATEGORY_THUMBNAIL)
        );
        $total += (int)$this->db->queryOne(
            SqlHelper::countTermMetaImages($attachmentId, ImageCtrl::MANUFACTURER_KEY)
        );

        return $total > 1;
    }

    /**
     * @param AbstractImage $image
     * @return bool
     */
    private function isCoverImage(AbstractImage $image): bool
    {
        return $image->getSort() === 1;
    }

    /**
     * @param $productId
     * @return void
     * @throws \Psr\Log\InvalidArgumentException
     */
    private function deleteAllProductImages($productId): void
    {
        $thumbnail = \get_post_thumbnail_id($productId);
        \set_post_thumbnail($productId, 0);
        $this->deleteIfNotUsedByOthers((int)$thumbnail);
        $galleryImages = $this->getGalleryImages($productId);
        \update_post_meta($productId, self::GALLERY_KEY, '');
        foreach ($galleryImages as $galleryImage) {
            $this->deleteIfNotUsedByOthers((int)$galleryImage);
        }
    }

    /**
     * @param $productId
     * @return array
     */
    private function getGalleryImages($productId): array
    {
        $galleryImages = \get_post_meta($productId, self::GALLERY_KEY, true);
        if (empty($galleryImages)) {
            return [];
        }

        return \array_map('intval', \explode(self::GALLERY_DIVIDER, $galleryImages));
    }
    // </editor-fold>

    /**
     * @param string $destinationDir
     * @param string $fileName
     * @return string
     */
    public static function createFilePath(string $destinationDir, string $fileName): string
    {
        return \sprintf('%s/%s', \rtrim($destinationDir, '/'), $fileName);
    }
}
