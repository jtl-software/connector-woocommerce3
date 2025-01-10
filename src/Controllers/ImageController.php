<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Controllers;

use Exception;
use http\Exception\RuntimeException;
use InvalidArgumentException;
use Jtl\Connector\Core\Controller\DeleteInterface;
use Jtl\Connector\Core\Controller\PullInterface;
use Jtl\Connector\Core\Controller\PushInterface;
use Jtl\Connector\Core\Controller\StatisticInterface;
use Jtl\Connector\Core\Definition\IdentityType;
use Jtl\Connector\Core\Exception\DefinitionException;
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
use JtlWooCommerceConnector\Logger\ErrorFormatter;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\Id;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;
use JtlWooCommerceConnector\Utilities\WordpressUtils;
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

    /** @var array<int, int|string> */
    private array $alreadyLinked = [];

    protected PrimaryKeyMapperInterface $primaryKeyMapper;

    /**
     * @param Db                        $db
     * @param Util                      $util
     * @param PrimaryKeyMapperInterface $primaryKeyMapper
     * @throws Exception
     */
    public function __construct(Db $db, Util $util, PrimaryKeyMapperInterface $primaryKeyMapper)
    {
        parent::__construct($db, $util);
        $this->primaryKeyMapper = $primaryKeyMapper;
    }


    // <editor-fold defaultstate="collapsed" desc="Pull">
    /**
     * @param QueryFilter $query
     * @return array<int, CategoryImage|ManufacturerImage|ProductImage>
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
     * @param array<int, array<string, bool|int|string|null>> $images
     * @param int                                             $type
     * @param int                                             $limit
     * @return array<int, ProductImage|CategoryImage|ManufacturerImage>
     * @throws \Psr\Log\InvalidArgumentException
     * @throws Exception
     */
    private function addNextImages(array $images, int $type, int $limit): array
    {
        $return = [];
// @param array<int, array<string, array<int, int>|int|string>> $images
        $language = $this->util->getWooCommerceLanguage();
        if ($this->wpml->canBeUsed() && $this->wpml->canWpmlMediaBeUsed()) {
            $language = $this->wpml->convertLanguageToWawi($this->wpml->getDefaultLanguage());
        }

        foreach ($images as $image) {
            /** @var int $imageId */
            $imageId = $image['ID'];
            /** @var string $imageLinkId */
            $imageLinkId = $image['id'];
            /** @var string $postName */
            $postName = $image['post_name'];
            /** @var int $parent */
            $parent = $image['parent'];
            /** @var string $guid */
            $guid = $image['guid'];
            /** @var int $sort */
            $sort = $image['sort'];

            $imgSrc = \wp_get_attachment_image_src($imageId, 'full');

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

            $model->setId(new Identity($imageLinkId))
                ->setName($postName)
                ->setForeignKey(new Identity((string)$parent))
                ->setRemoteUrl(isset($imgSrc[0]) ? (string)$imgSrc[0] : $guid)
                ->setSort($sort)
                ->setFilename(\wc_get_filename_from_url($guid));

            /** @var false|string $altText */
            $altText = \get_post_meta($imageId, '_wp_attachment_image_alt', true);

            $model
                ->addI18n((new ImageI18n())
                    ->setId(new Identity($imageLinkId))
                    ->setAltText(\substr($altText !== false ? $altText : '', 0, 254))
                    ->setLanguageISO($language));

            if ($this->wpml->canBeUsed() && $this->wpml->canWpmlMediaBeUsed()) {
                /** @var WpmlMedia $wpmlMedia */
                $wpmlMedia = $this->wpml->getComponent(WpmlMedia::class);
                $wpmlMedia->getTranslations($imageId, $model);
            }

            $return[] = $model;
            $limit--;
        }
        return $return;
    }

    /**
     * @param int|null $limit
     * @return array<int, array<string, bool|int|string|null>> The image entities.
     * @throws \Psr\Log\InvalidArgumentException
     * @throws \http\Exception\InvalidArgumentException
     */
    private function productImagePull(?int $limit = null): array
    {
        $imageCount  = 0;
        $attachments = [];

        /** @var string[] $linkedProductImages */
        $linkedProductImages = $this->db->queryList(SqlHelper::linkedProductImages());
        $this->alreadyLinked = $linkedProductImages;

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

                        if (!\is_int($postId)) {
                            throw new \http\Exception\InvalidArgumentException(
                                "Expected postId to be an integer but got " . \gettype($postId) . " instead."
                            );
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
     * @return array<int, int|string> An array with the image ids.
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
                /** @var array<int, string> $images */
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
     * @param array<int, int|string> $attachmentIds The image ids that should be checked.
     * @param int                    $postId        The product which is owner of the images.
     *
     * @return array<int, array<string, int|string|null>> The filtered image data.
     * @throws \Psr\Log\InvalidArgumentException
     */
    private function addProductImagesForPost(array $attachmentIds, int $postId): array
    {
        $attachmentIds = $this->filterAlreadyLinkedProducts($attachmentIds, $postId);
        return $this->fetchProductAttachments($attachmentIds, $postId);
    }

    /**
     * @param array<int, int|string> $attachmentIds
     * @param int                    $productId
     * @return array<int, array<string, int|string|null>>
     * @throws \Psr\Log\InvalidArgumentException
     */
    private function fetchProductAttachments(array $attachmentIds, int $productId): array
    {
        $sort        = 0;
        $attachments = [];

        if (empty($attachmentIds)) {
            return $attachments;
        }

        foreach ($attachmentIds as $attachmentId) {
            $attachedFile = \get_attached_file((int)$attachmentId);
            if (!\file_exists($attachedFile !== false ? $attachedFile : '')) {
                $this->logger->debug(
                    \sprintf('Image file does not exist: %s', \get_attached_file((int)$attachmentId))
                );

                continue;
            }

            /** @var array<string, int|string|null> $picture */
            $picture = \get_post((int)$attachmentId, \ARRAY_A);

            if (!\is_array($picture)) {
                continue;
            }

            $picture['id']     = Id::linkProductImage((int)$attachmentId, $productId);
            $picture['parent'] = $productId;

            if ((int)$attachmentId !== \get_post_thumbnail_id($productId) && $sort === 0) {
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
     * @param array<int, int|string> $productAttachments
     * @param int                    $productId
     * @return array<int, int|string>
     */
    private function filterAlreadyLinkedProducts(array $productAttachments, int $productId): array
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
     * @param int|null $limit
     * @return string
     * @throws \Psr\Log\InvalidArgumentException
     * @throws Exception
     */
    private function getCategoryImagePullQuery(?int $limit): string
    {
        if ($this->wpml->canBeUsed() && $this->wpml->canWpmlMediaBeUsed()) {
            /** @var WpmlMedia $wpmlMedia */
            $wpmlMedia          = $this->wpml->getComponent(WpmlMedia::class);
            $categoryImageQuery = $wpmlMedia->imageCategoryPull($limit);
        } else {
            $categoryImageQuery = SqlHelper::imageCategoryPull($limit);
        }

        return $categoryImageQuery;
    }

    /**
     * @param string $query
     * @return array<int, array<string, bool|int|string|null>>
     * @throws \Psr\Log\InvalidArgumentException
     * @throws \http\Exception\InvalidArgumentException
     */
    private function categoryImagePullByQuery(string $query): array
    {
        $result = [];

        /** @var array<int, array<string, int|string|null>> $images */
        $images = $this->db->query($query);

        if (!\is_array($images)) {
            throw new \http\Exception\InvalidArgumentException(
                "Expected images to be an array but got " . \gettype($images) . " instead."
            );
        }

        foreach ($images as $image) {
            $image['sort'] = 0;
            $result[]      = $image;
        }

        return $result;
    }

    /**
     * @param int|null $limit
     * @return string
     * @throws \Psr\Log\InvalidArgumentException
     * @throws Exception
     */
    private function getManufacturerImagePullQuery(?int $limit): string
    {
        if ($this->wpml->canBeUsed() && $this->wpml->canWpmlMediaBeUsed()) {
            /** @var WpmlMedia $wpmlMedia */
            $wpmlMedia               = $this->wpml->getComponent(WpmlMedia::class);
            $manufacturerImagesQuery = $wpmlMedia->imageManufacturerPull($limit);
        } else {
            $manufacturerImagesQuery = SqlHelper::imageManufacturerPull($limit);
        }

        return $manufacturerImagesQuery;
    }

    /**
     * @param string $query
     * @return array<int, array<string, bool|int|string|null>>
     * @throws \Psr\Log\InvalidArgumentException
     */
    private function manufacturerImagePull(string $query): array
    {
        $result = [];

        $images = $this->db->query($query) ?? [];

        /** @var array<string, int|string> $image */
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
        $imageCount += \count($this->db->query(SqlHelper::imageVariationCombinationPull()) ?? []);

        if ($this->wpml->canBeUsed() && $this->wpml->canWpmlMediaBeUsed()) {
            /** @var WpmlMedia $wpmlMedia */
            $wpmlMedia          = $this->wpml->getComponent(WpmlMedia::class);
            $imageCategoryQuery = $wpmlMedia->imageCategoryPull();
            $imageCount        += \count($this->db->query($imageCategoryQuery) ?? []);
        } else {
            $imageCount += \count($this->db->query(SqlHelper::imageCategoryPull()) ?? []);
        }

        if (SupportedPlugins::isPerfectWooCommerceBrandsActive()) {
            if ($this->wpml->canBeUsed() && $this->wpml->canWpmlMediaBeUsed()) {
                /** @var WpmlMedia $wpmlMedia */
                $wpmlMedia              = $this->wpml->getComponent(WpmlMedia::class);
                $imageManufacturerQuery = $wpmlMedia->imageManufacturerPull();
                $imageCount            += \count($this->db->query($imageManufacturerQuery) ?? []);
            } else {
                $imageCount += \count($this->db->query(SqlHelper::imageManufacturerPull()) ?? []);
            }
        }

        return $imageCount;
    }

    /**
     * @return int
     * @throws \Psr\Log\InvalidArgumentException
     * @throws Exception
     */
    private function masterProductImageStats(): int
    {
        $this->alreadyLinked = $this->db->queryList(SqlHelper::linkedProductImages());

        $count  = 0;
        $images = [];

        // Fetch unlinked product cover images
        if ($this->wpml->canBeUsed() && $this->wpml->canWpmlMediaBeUsed()) {
            /** @var WpmlMedia $wpmlMedia */
            $wpmlMedia  = $this->wpml->getComponent(WpmlMedia::class);
            $thumbnails = $this->db->query(
                $wpmlMedia->getImageProductThumbnailSql()
            ) ?? [];
        } else {
            $thumbnails = $this->db->query(SqlHelper::imageProductThumbnail()) ?? [];
        }

        /** @var array<string, int|string> $thumbnail */
        foreach ($thumbnails as $thumbnail) {
            $images[(int)$thumbnail['ID']] = (int)$thumbnail['meta_value'];
            $count++;
        }

        // Get all product gallery images
        if ($this->wpml->canBeUsed() && $this->wpml->canWpmlMediaBeUsed()) {
            /** @var WpmlMedia $wpmlMedia */
            $wpmlMedia             = $this->wpml->getComponent(WpmlMedia::class);
            $productImagesMappings = $this->db->query(
                $wpmlMedia->getImageProductGalleryStats()
            ) ?? [];
        } else {
            $productImagesMappings = $this->db->query(SqlHelper::imageProductGalleryStats()) ?? [];
        }

        /** @var array<string, int|string> $productImagesMapping */
        foreach ($productImagesMappings as $productImagesMapping) {
            $productId       = (int)$productImagesMapping['ID'];
            $galleryImageIds = \array_map('intval', \explode(',', (string)$productImagesMapping['meta_value']));
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
        /** @var AbstractImage $model */
        $foreignKey = $model->getForeignKey()->getEndpoint();

        if (!empty($foreignKey)) {
            $this->delete($model);

            if ($model instanceof ProductImage) {
                $model->getId()->setEndpoint($this->pushProductImage($model) ?? '');
            } elseif ($model instanceof CategoryImage) {
                $model->getId()->setEndpoint($this->pushCategoryImage($model) ?? '');
            } elseif ($model instanceof ManufacturerImage) {
                $model->getId()->setEndpoint($this->pushManufacturerImage($model) ?? '');
            }
        }

        return $model;
    }

    /**
     * @param AbstractImage $image
     * @return int|null
     * @throws DefinitionException
     * @throws \Psr\Log\InvalidArgumentException
     * @throws \RuntimeException
     * @throws \getid3_exception
     * @throws \http\Exception\InvalidArgumentException
     */
    private function saveImage(AbstractImage $image): ?int
    {
        $endpointId = $image->getId()->getEndpoint();
        $post       = null;

        $fileInfo  = \pathinfo($image->getFilename());
        $name      = $this->sanitizeImageName(
            !empty($image->getName()) ? $image->getName() : $fileInfo['filename']
        );
        $extension = (\is_array($fileInfo) && \array_key_exists('extension', $fileInfo))
            ? $fileInfo['extension']
            : '';
        $uploadDir = \wp_upload_dir();

        $attachment  = [];
        $relinkImage = false;
        $fileName    = $this->getNextAvailableImageFilename($fileInfo['filename'], $extension, $uploadDir['path']);
        if ($endpointId !== '') {
            $id         = Id::unlink($endpointId);
            $attachment = \get_post((int)$id[0], \ARRAY_A) ?? [];

            if (!empty($attachment)) {
                if ($this->isAttachmentUsedInOtherPlaces($attachment['ID'])) {
                    $attachment  = [];
                    $relinkImage = true;
                } else {
                    $attachedFile = \get_attached_file($attachment['ID']);
                    if (!\is_string($attachedFile)) {
                        throw new \http\Exception\InvalidArgumentException(
                            "File path of attachedFile not found. Got false instead of string"
                        );
                    }

                    $fileName = \basename($attachedFile);
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

            $post = \wp_insert_attachment($attachment, $destination, (int)$image->getForeignKey()->getEndpoint());

            if (!\is_int($post)) {
                $this->logger->error(ErrorFormatter::formatError($post));

                return null;
            } elseif ($post === 0) {
                $this->logger->error("Attachment post id is 0. Image could not be saved.");

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
                /** @var WpmlMedia $wpmlMedia */
                $wpmlMedia = $this->wpml->getComponent(WpmlMedia::class);
                $wpmlMedia->saveAttachmentTranslations($post, $image->getI18ns());
            }
        }

        return $post;
    }

    /**
     * @param int           $newEndpointId
     * @param AbstractImage $image
     * @return void
     * @throws DefinitionException
     * @throws \RuntimeException
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
        $name = \preg_replace('#[^A-Za-z0-9\-_ ]#', '-', (string)$name);
        $name = \preg_replace('#-{2,}#', '-', (string)$name);
        $name = \trim((string)$name, '-');

        return \mb_substr($name, 0, 180);
    }

    /**
     * @param string $name
     * @param string $extension
     * @param string $uploadDir
     * @return string
     */
    protected function getNextAvailableImageFilename(string $name, string $extension, string $uploadDir): string
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
     * @return string
     * @throws \Psr\Log\InvalidArgumentException
     * @throws \http\Exception\InvalidArgumentException
     */
    private function pushProductImage(ProductImage $image): string
    {
        $productId = (int)$image->getForeignKey()->getEndpoint();
        $wcProduct = \wc_get_product($productId);

        if (!$wcProduct instanceof WC_Product) {
            return '';
        }

        $attachmentId = $this->saveImage($image);

        if (\is_null($attachmentId)) {
            throw new \http\Exception\InvalidArgumentException(
                "Attachment id is null. Image could not be saved."
            );
        }

        if ($this->isCoverImage($image)) {
            $result = \set_post_thumbnail($productId, $attachmentId);
            if ($result instanceof \WP_Error) {
                $this->logger->error(ErrorFormatter::formatError($result));

                return '';
            }

            if ($this->wpml->canBeUsed()) {
                /** @var int[] $wpmlProductIds */
                $wpmlProductIds = $this->db->queryList(SqlHelper::getWpmlProductIds((int) $wcProduct->get_sku()));
                $wpmlProductIds = \array_diff($wpmlProductIds, [$productId]);

                foreach ($wpmlProductIds as $wpmlProductId) {
                    $wpmlResult = \set_post_thumbnail($wpmlProductId, $attachmentId);
                    if ($wpmlResult instanceof \WP_Error) {
                        $this->logger->error(ErrorFormatter::formatError($wpmlResult));

                        return '';
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
            $galleryImages[] = $attachmentId;
            $galleryImages   = \implode(self::GALLERY_DIVIDER, \array_unique($galleryImages));
            $result          = \update_post_meta($productId, self::GALLERY_KEY, $galleryImages);
            if ($result === false) {
                $this->logger->error(
                    "Updating post meta for product gallery images either failed ot the 
                    value passed is same as the one in the database."
                );

                return '';
            }
        }

        return Id::linkProductImage($attachmentId, $productId);
    }

    /**
     * @param CategoryImage $image
     * @return string
     * @throws Exception
     */
    private function pushCategoryImage(CategoryImage $image): string
    {
        $categoryId = (int)$image->getForeignKey()->getEndpoint();

        if (!\term_exists($categoryId)) {
            return '';
        }

        $attachmentId = $this->saveImage($image) ?? 0;
        \update_term_meta($categoryId, self::CATEGORY_THUMBNAIL, $attachmentId);

        return Id::linkCategoryImage($attachmentId);
    }

    /**
     * @param ManufacturerImage $image
     * @return string
     * @throws Exception
     */
    private function pushManufacturerImage(ManufacturerImage $image): string
    {
        $termId = (int)$image->getForeignKey()->getEndpoint();

        if (!\term_exists($termId)) {
            return '';
        }

        $attachmentId = $this->saveImage($image) ?? 0;
        \update_term_meta($termId, self::MANUFACTURER_KEY, $attachmentId);

        return Id::linkManufacturerImage($attachmentId);
    }
    // </editor-fold>

    // <editor-fold defaultstate="collapsed" desc="Delete">
    /**
     * @param AbstractModel $model
     * @param bool          $realDelete
     * @return AbstractModel
     * @throws Exception
     */
    public function deleteData(AbstractModel $model, bool $realDelete = true): AbstractModel
    {
        /** @var AbstractImage $model */
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
     * @param AbstractModel $model
     * @param bool $realDelete
     * @return AbstractModel
     * @throws Exception
     */
    public function delete(AbstractModel $model, bool $realDelete = true): AbstractModel
    {
        return $this->deleteData($model, $realDelete);
    }

    /**
     * @param AbstractImage $image
     * @param bool          $realDelete
     * @return void
     * @throws RuntimeException
     * @throws DefinitionException
     * @throws \Psr\Log\InvalidArgumentException
     * @throws \RuntimeException
     */
    private function deleteImageTermMeta(AbstractImage $image, bool $realDelete): void
    {
        $endpointId = $image->getId()->getEndpoint();
        switch (\get_class($image)) {
            case ManufacturerImage::class:
                $metaKey = self::MANUFACTURER_KEY;
                $id      = Id::unlinkManufacturerImage($endpointId);
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

        \delete_term_meta((int)$image->getForeignKey()->getEndpoint(), $metaKey);

        if ($realDelete) {
            if (empty($id) && !\str_contains($endpointId, "_")) {
                $id = $endpointId;
            }
            $this->deleteIfNotUsedByOthers((int) $id);
        }
    }

    /**
     * @param AbstractImage $image
     * @param bool          $realDelete
     * @return void
     * @throws \Psr\Log\InvalidArgumentException
     */
    private function deleteProductImage(AbstractImage $image, bool $realDelete): void
    {
        $imageEndpoint = $image->getId()->getEndpoint();
        $ids           = Id::unlink($imageEndpoint);

        if (\count($ids) !== 2) {
            return;
        }

        $attachmentId = (int)$ids[0];
        $productId    = (int)$ids[1];

        $wcProduct = $this->util->wcGetProduct($productId);
        if (!$wcProduct instanceof WC_Product) {
            return;
        }

        if ($image->getSort() === 0 && \strlen($imageEndpoint) === 0) {
            $this->deleteAllProductImages($productId);
            $this->db->query(SqlHelper::imageDeleteLinks($productId));
        } else {
            $this->db->query(SqlHelper::imageDeleteLink($attachmentId, $productId));
            if ($this->isCoverImage($image)) {
                \delete_post_thumbnail($productId);
            } else {
                if (
                    SupportedPlugins::isActive(
                        SupportedPlugins::PLUGIN_ADDITIONAL_VARIATION_IMAGES_GALLERY_FOR_WOOCOMMERCE
                    )
                ) {
                    if ($wcProduct->get_type() === 'variation') {
                        /** @var array<int, int|string> $newImages */
                        $newImages = $oldImages = \get_post_meta(
                            $wcProduct->get_id(),
                            'woo_variation_gallery_images',
                            true
                        );
                        if (\is_array($oldImages) && !empty($oldImages)) {
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
        if (empty($attachmentId) || \get_post($attachmentId) === null) {
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
     * @param int $productId
     * @return void
     * @throws \Psr\Log\InvalidArgumentException
     */
    private function deleteAllProductImages(int $productId): void
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
     * @param int $productId
     * @return array<int, int>
     */
    private function getGalleryImages(int $productId): array
    {
        /** @var string $galleryImages */
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
