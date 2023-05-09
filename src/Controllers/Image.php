<?php

/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers;

use Exception;
use InvalidArgumentException;
use jtl\Connector\Drawing\ImageRelationType;
use jtl\Connector\Linker\IdentityLinker;
use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Image as ImageModel;
use jtl\Connector\Model\ImageI18n;
use JtlWooCommerceConnector\Controllers\Image as ImageCtrl;
use JtlWooCommerceConnector\Logger\ControllerLogger;
use JtlWooCommerceConnector\Logger\WpErrorLogger;
use JtlWooCommerceConnector\Utilities\Id;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;

class Image extends BaseController
{
    public const GALLERY_DIVIDER    = ',';
    public const PRODUCT_THUMBNAIL  = '_thumbnail_id';
    public const CATEGORY_THUMBNAIL = 'thumbnail_id';
    public const GALLERY_KEY        = '_product_image_gallery';
    public const MANUFACTURER_KEY   = 'pwb_brand_image';

    private $alreadyLinked = [];

    // <editor-fold defaultstate="collapsed" desc="Pull">

    /**
     * @param $limit
     * @return array
     * @throws InvalidArgumentException
     */
    public function pullData($limit): array
    {
        $images        = $this->productImagePull($limit);
        $productImages = $this->addNextImages($images, ImageRelationType::TYPE_PRODUCT, $limit);

        $images         = $this->categoryImagePull($limit);
        $categoryImages = $this->addNextImages($images, ImageRelationType::TYPE_CATEGORY, $limit);

        $combinedArray = \array_merge($productImages, $categoryImages);

        if (SupportedPlugins::isPerfectWooCommerceBrandsActive()) {
            $images             = $this->manufacturerImagePull($limit);
            $manufacturerImages = $this->addNextImages($images, ImageRelationType::TYPE_MANUFACTURER, $limit);
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
     */
    private function addNextImages($images, $type, &$limit): array
    {
        $return = [];

        foreach ($images as $image) {
            $imgSrc = \wp_get_attachment_image_src($image['ID'], 'full');
            $model  = (new ImageModel())
                ->setId(new Identity($image['id']))
                ->setName((string)$image['post_name'])
                ->setForeignKey(new Identity($image['parent']))
                ->setRemoteUrl((string)isset($imgSrc[0]) ? $imgSrc[0] : $image['guid'])
                ->setSort((int)$image['sort'])
                ->setFilename((string)\wc_get_filename_from_url($image['guid']));

            if ($model instanceof ImageModel) {
                $altText = \get_post_meta($image['ID'], '_wp_attachment_image_alt', true);

                $model
                    ->setRelationType($type)
                    ->addI18n((new ImageI18n())
                        ->setId(new Identity($image['id']))
                        ->setImageId(new Identity($image['id']))
                        ->setAltText((string)\substr($altText !== false ? $altText : '', 0, 254))
                        ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage()));

                $return[] = $model;
                $limit--;
            }
        }

        return $return;
    }

    /**
     * Loop the products to get their images and validate and map them.
     *
     * @param null $limit The limit.
     *
     * @return array The image entities.
     */
    private function productImagePull($limit = null): array
    {
        $imageCount  = 0;
        $attachments = [];

        $this->alreadyLinked = $this->database->queryList(SqlHelper::linkedProductImages());

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

                        if (!$product instanceof \WC_Product) {
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
     * @param \WC_Product $product The product for which the cover image and gallery images should be fetched.
     *
     * @return array An array with the image ids.
     */
    private function fetchProductAttachmentIds(\WC_Product $product): array
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
                ControllerLogger::getInstance()->writeLog(
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

            if (!\in_array($endpointId, $this->alreadyLinked)) {
                $filtered[]            = $attachmentId;
                $this->alreadyLinked[] = $endpointId;
            }
        }

        return $filtered;
    }

    /**
     * @param $limit
     * @return array
     */
    private function categoryImagePull($limit): array
    {
        $result = [];

        $images = $this->database->query(SqlHelper::imageCategoryPull($limit));

        foreach ($images as $image) {
            $image['sort'] = 0;

            $result[] = $image;
        }

        return $result;
    }

    /**
     * @param $limit
     * @return array
     */
    private function manufacturerImagePull($limit): array
    {
        $result = [];

        $images = $this->database->query(SqlHelper::imageManufacturerPull($limit));

        foreach ($images as $image) {
            $image['sort'] = 0;

            $result[] = $image;
        }

        return $result;
    }
    // </editor-fold>

    // <editor-fold defaultstate="collapsed" desc="Stats">
    /**
     * @return int|null
     */
    protected function getStats(): ?int
    {
        $imageCount  = $this->masterProductImageStats();
        $imageCount += \count($this->database->query(SqlHelper::imageVariationCombinationPull()));
        $imageCount += \count($this->database->query(SqlHelper::imageCategoryPull()));

        if (SupportedPlugins::isPerfectWooCommerceBrandsActive()) {
            $imageCount += \count($this->database->query(SqlHelper::imageManufacturerPull()));
        }

        return $imageCount;
    }

    /**
     * @return int|null
     */
    private function masterProductImageStats(): ?int
    {
        $this->alreadyLinked = $this->database->queryList(SqlHelper::linkedProductImages());

        $count  = 0;
        $images = [];

        // Fetch unlinked product cover images
        $thumbnails = $this->database->query(SqlHelper::imageProductThumbnail());

        foreach ($thumbnails as $thumbnail) {
            $images[(int)$thumbnail['ID']] = (int)$thumbnail['meta_value'];
            $count++;
        }

        // Get all product gallery images
        $productImagesMappings = $this->database->query(SqlHelper::imageProductGalleryStats());

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
     * @param ImageModel $image
     * @return ImageModel
     * @throws Exception
     */
    protected function pushData(ImageModel $image): ImageModel
    {
        $foreignKey = $image->getForeignKey()->getEndpoint();

        if (!empty($foreignKey)) {
            $id = null;
            // Delete image with the same id
            $this->deleteData($image, false);

            if (ImageRelationType::TYPE_PRODUCT === $image->getRelationType()) {
                $image->getId()->setEndpoint($this->pushProductImage($image));
            } elseif (ImageRelationType::TYPE_CATEGORY === $image->getRelationType()) {
                $image->getId()->setEndpoint($this->pushCategoryImage($image));
            } elseif (ImageRelationType::TYPE_MANUFACTURER === $image->getRelationType()) {
                $image->getId()->setEndpoint($this->pushManufacturerImage($image));
            }
        }

        return $image;
    }

    /**
     * @param ImageModel $image
     * @return int|null
     * @throws Exception
     */
    private function saveImage(ImageModel $image): ?int
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
                'post_title' => \preg_replace('/\.[^.]+$/', '', $fileName),
                'post_status' => 'inherit',
            ]);

            $post = \wp_insert_attachment($attachment, $destination, $image->getForeignKey()->getEndpoint());

            if ($post instanceof \WP_Error) {
                WpErrorLogger::getInstance()->logError($post);

                return null;
            }

            require_once(\ABSPATH . 'wp-admin/includes/image.php');
            $attachData = \wp_generate_attachment_metadata($post, $destination);
            \wp_update_attachment_metadata($post, $attachData);
            \update_post_meta($post, '_wp_attachment_image_alt', $this->getImageAlt($image));

            if ($relinkImage) {
                $this->relinkImage($post, $image);
            }
        }

        return $post;
    }

    /**
     * @param int $newEndpointId
     * @param ImageModel $image
     * @return void
     * @throws Exception
     */
    protected function relinkImage(int $newEndpointId, ImageModel $image): void
    {
        $primaryKeyMapper = \Application()->getConnector()->getPrimaryKeyMapper();

        switch ($image->getRelationType()) {
            case ImageRelationType::TYPE_PRODUCT:
                $newEndpoint = Id::linkProductImage($newEndpointId, $image->getForeignKey()->getEndpoint());
                break;
            case ImageRelationType::TYPE_MANUFACTURER:
                $newEndpoint = Id::linkManufacturerImage($newEndpointId);
                break;
            case ImageRelationType::TYPE_CATEGORY:
                $newEndpoint = Id::linkCategoryImage($newEndpointId);
                break;
            default:
                throw new \Exception(\sprintf('Relation type %s is not supported.', $image->getRelationType()));
        }

        $primaryKeyMapper->delete(
            $image->getId()->getEndpoint(),
            $image->getId()->getHost(),
            IdentityLinker::TYPE_IMAGE
        );
        $primaryKeyMapper->save($newEndpoint, $image->getId()->getHost(), IdentityLinker::TYPE_IMAGE);

        $image->getId()->setEndpoint($newEndpoint);
    }


    /**
     * @param string $name
     * @return string
     */
    private function sanitizeImageName(string $name): string
    {
        $name = \iconv('utf-8', 'ascii//translit', $name);
        $name = \preg_replace('#[^A-Za-z0-9\-_]#', '-', $name);
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
     * @param ImageModel $image
     * @return string
     */
    protected function getImageAlt(ImageModel $image): string
    {
        $altText = $image->getName();
        $i18ns   = $image->getI18ns();

        if (\count($i18ns) > 0) {
            foreach ($i18ns as $i18n) {
                if (
                    Util::getInstance()->isWooCommerceLanguage($i18n->getLanguageISO())
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
     * @param ImageModel $image
     * @return string|null
     * @throws Exception
     */
    private function pushProductImage(ImageModel $image): ?string
    {
        $productId = (int)$image->getForeignKey()->getEndpoint();
        $wcProduct = \wc_get_product($productId);

        if (!$wcProduct instanceof \WC_Product) {
            return null;
        }

        $attachmentId = $this->saveImage($image);

        if ($this->isCoverImage($image)) {
            $result = \set_post_thumbnail($productId, $attachmentId);
            if ($result instanceof \WP_Error) {
                WpErrorLogger::getInstance()->logError($result);

                return null;
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
                WpErrorLogger::getInstance()->logError($result);

                return null;
            }
        }

        return Id::linkProductImage($attachmentId, $productId);
    }

    /**
     * @param ImageModel $image
     * @return string|null
     * @throws Exception
     */
    private function pushCategoryImage(ImageModel $image): ?string
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
     * @param ImageModel $image
     * @return string|null
     * @throws Exception
     */
    private function pushManufacturerImage(ImageModel $image): ?string
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
     * @param ImageModel $image
     * @param bool $realDelete
     * @return ImageModel
     * @throws Exception
     */
    protected function deleteData(ImageModel $image, bool $realDelete = true): ImageModel
    {
        switch ($image->getRelationType()) {
            case ImageRelationType::TYPE_PRODUCT:
                $this->deleteProductImage($image, $realDelete);
                break;
            case ImageRelationType::TYPE_CATEGORY:
            case ImageRelationType::TYPE_MANUFACTURER:
                $this->deleteImageTermMeta($image, $realDelete);
                break;
        }

        return $image;
    }

    /**
     * @param ImageModel $image
     * @param $realDelete
     * @return void
     * @throws Exception
     */
    private function deleteImageTermMeta(ImageModel $image, $realDelete): void
    {
        $endpointId = $image->getId()->getEndpoint();
        switch ($image->getRelationType()) {
            case ImageRelationType::TYPE_MANUFACTURER:
                $metaKey = self::MANUFACTURER_KEY;
                $id      = Id::unlinkCategoryImage($endpointId);
                break;
            case ImageRelationType::TYPE_CATEGORY:
                $metaKey = self::CATEGORY_THUMBNAIL;
                $id      = Id::unlinkManufacturerImage($endpointId);
                break;
            default:
                throw new Exception(
                    \sprintf(
                        "Invalid relation %s type for id %s when deleting image.",
                        $image->getRelationType(),
                        $endpointId
                    )
                );
        }

        \delete_term_meta($image->getForeignKey()->getEndpoint(), $metaKey);

        if ($realDelete) {
            $this->deleteIfNotUsedByOthers((int) $id);
        }
    }

    /**
     * @param ImageModel $image
     * @param $realDelete
     * @return void
     */
    private function deleteProductImage(ImageModel $image, $realDelete): void
    {
        $imageEndpoint = $image->getId()->getEndpoint();
        $ids           = Id::unlink($imageEndpoint);

        if (\count($ids) !== 2) {
            return;
        }

        $attachmentId = (int)$ids[0];
        $productId    = (int)$ids[1];

        $wcProduct = \wc_get_product($productId);
        if (!$wcProduct instanceof \WC_Product) {
            return;
        }

        if ($image->getSort() === 0 && \strlen($imageEndpoint) === 0) {
            $this->deleteAllProductImages($productId);
            $this->database->query(SqlHelper::imageDeleteLinks($productId));
        } else {
            if ($this->isCoverImage($image)) {
                \delete_post_thumbnail($productId);
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
     */
    protected function isAttachmentUsedInOtherPlaces(int $attachmentId): bool
    {
        $total = 0;

        $total += (int)$this->database->queryOne(
            SqlHelper::countRelatedProducts($attachmentId)
        );
        $total += (int)$this->database->queryOne(
            SqlHelper::countTermMetaImages($attachmentId, ImageCtrl::CATEGORY_THUMBNAIL)
        );
        $total += (int)$this->database->queryOne(
            SqlHelper::countTermMetaImages($attachmentId, ImageCtrl::MANUFACTURER_KEY)
        );

        return $total > 1;
    }

    /**
     * @param ImageModel $image
     * @return bool
     */
    private function isCoverImage(ImageModel $image): bool
    {
        return $image->getSort() === 1;
    }

    /**
     * @param $productId
     * @return void
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
