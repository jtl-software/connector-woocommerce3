<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers;

use jtl\Connector\Drawing\ImageRelationType;
use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Image as ImageModel;
use jtl\Connector\Model\ImageI18n;
use JtlWooCommerceConnector\Controllers\Image as ImageCtrl;
use JtlWooCommerceConnector\Controllers\Traits\DeleteTrait;
use JtlWooCommerceConnector\Controllers\Traits\PullTrait;
use JtlWooCommerceConnector\Controllers\Traits\PushTrait;
use JtlWooCommerceConnector\Controllers\Traits\StatsTrait;
use JtlWooCommerceConnector\Integrations\Plugins\PerfectWooCommerceBrands\PerfectWooCommerceBrands;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlMedia;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlTermTranslation;
use JtlWooCommerceConnector\Logger\WpErrorLogger;
use JtlWooCommerceConnector\Utilities\Id;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;

class Image extends BaseController
{
    use PullTrait, PushTrait, DeleteTrait, StatsTrait;

    const GALLERY_DIVIDER = ',';
    const PRODUCT_THUMBNAIL = '_thumbnail_id';
    const CATEGORY_THUMBNAIL = 'thumbnail_id';
    const GALLERY_KEY = '_product_image_gallery';
    const MANUFACTURER_KEY = 'pwb_brand_image';

    private $alreadyLinked = [];

    public function pullData($limit)
    {
        $images = $this->productImagePull($limit);
        $productImages = $this->addNextImages($images, ImageRelationType::TYPE_PRODUCT, $limit);

        if ($this->wpml->canBeUsed() && $this->wpml->canWpmlMediaBeUsed()) {
            $categoryImageQuery = $this->wpml->getComponent(WpmlMedia::class)->imageCategoryPull($limit);
        } else {
            $categoryImageQuery = SqlHelper::imageCategoryPull($limit);
        }

        $images = $this->imagePullByQuery($categoryImageQuery);
        $categoryImages = $this->addNextImages($images, ImageRelationType::TYPE_CATEGORY, $limit);

        $combinedArray = array_merge($productImages, $categoryImages);

        if ($this->getPluginsManager()->get(PerfectWooCommerceBrands::class)->canBeUsed()) {

            if ($this->wpml->canBeUsed() && $this->wpml->canWpmlMediaBeUsed()) {
                $manufacturerImagesQuery = $this->wpml->getComponent(WpmlMedia::class)->imageManufacturerPull($limit);
            } else {
                $manufacturerImagesQuery = SqlHelper::imageManufacturerPull($limit);
            }

            $images = $this->imagePullByQuery($manufacturerImagesQuery);
            $manufacturerImages = $this->addNextImages($images, ImageRelationType::TYPE_MANUFACTURER, $limit);
            $combinedArray = array_merge($combinedArray, $manufacturerImages);
        }
        
        return $combinedArray;
    }
    
    private function addNextImages($images, $type, &$limit)
    {
        $return = [];

        $language = Util::getInstance()->getWooCommerceLanguage();
        if ($this->wpml->canBeUsed() && $this->wpml->canWpmlMediaBeUsed()) {
            $language = $this->wpml->convertLanguageToWawi($this->wpml->getDefaultLanguage());
        }

        foreach ($images as $image) {
            $imgSrc = \wp_get_attachment_image_src($image['ID'], 'full');
            $model = (new ImageModel())
                ->setId(new Identity($image['id']))
                ->setName((string)$image['post_name'])
                ->setForeignKey(new Identity($image['parent']))
                ->setRemoteUrl((string)isset($imgSrc[0]) ? $imgSrc[0] : $image['guid'])
                ->setSort((int)$image['sort'])
                ->setFilename((string)\wc_get_filename_from_url($image['guid']));


            $altText = \get_post_meta($image['ID'], '_wp_attachment_image_alt', true);

            $model
                ->setRelationType($type)
                ->addI18n((new ImageI18n())
                    ->setId(new Identity($image['id']))
                    ->setImageId(new Identity($image['id']))
                    ->setAltText((string)substr($altText !== false ? $altText : '', 0, 254))
                    ->setLanguageISO($language)
                );

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
     */
    private function productImagePull($limit = null)
    {
        $imageCount = 0;
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
                    'paged' => $page++,
                ]);

                if ($query->have_posts()) {
                    foreach ($query->posts as $postId) {
                        $product = \wc_get_product($postId);

                        if (!$product instanceof \WC_Product) {
                            continue;
                        }

                        $attachmentIds = $this->fetchProductAttachmentIds($product);
                        $newAttachments = $this->addProductImagesForPost($attachmentIds, $postId);

                        if (empty($newAttachments)) {
                            continue;
                        }

                        $attachments = array_merge($newAttachments, $attachments);
                        $imageCount += count($newAttachments);

                        if ($imageCount >= $limit) {
                            return $imageCount <= $limit ? $attachments : array_slice($attachments, 0, $limit);
                        }
                    }
                } else {
                    return $imageCount <= $limit ? $attachments : array_slice($attachments, 0, $limit);
                }
            }
        } catch (\Exception $ex) {
            return $imageCount <= $limit ? $attachments : array_slice($attachments, 0, $limit);
        }

        return $imageCount <= $limit ? $attachments : array_slice($attachments, 0, $limit);
    }

    /**
     * Fetch the cover image and the gallery images for a given product.
     *
     * @param \WC_Product $product The product for which the cover image and gallery images should be fetched.
     *
     * @return array An array with the image ids.
     */
    private function fetchProductAttachmentIds(\WC_Product $product)
    {
        $attachmentIds = [];

        $pictureId = (int)$product->get_image_id('edit');

        if (!empty($pictureId)) {
            $attachmentIds[] = $pictureId;
        }

        if (!$product->is_type('variation')) {
            $imageIds = $product->get_gallery_image_ids();

            if (!empty($imageIds)) {
                $attachmentIds = array_merge($attachmentIds, $imageIds);
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
    private function addProductImagesForPost($attachmentIds, $postId)
    {
        $attachmentIds = $this->filterAlreadyLinkedProducts($attachmentIds, $postId);
        $newAttachments = $this->fetchProductAttachments($attachmentIds, $postId);

        return $newAttachments;
    }

    private function fetchProductAttachments($attachmentIds, $productId)
    {
        $sort = 0;
        $attachments = [];

        if (empty($attachmentIds)) {
            return $attachments;
        }

        foreach ($attachmentIds as $attachmentId) {
            if (!file_exists(\get_attached_file($attachmentId))) {
                continue;
            }

            $picture = \get_post($attachmentId, ARRAY_A);

            if (!is_array($picture)) {
                continue;
            }

            $picture['id'] = Id::linkProductImage($attachmentId, $productId);
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

    private function filterAlreadyLinkedProducts($productAttachments, $productId)
    {
        $filtered = [];
        $attachmentIds = $productAttachments;

        foreach ($attachmentIds as $attachmentId) {
            $endpointId = Id::link([$attachmentId, $productId]);
            
            if ( ! in_array($endpointId, $this->alreadyLinked)) {
                $filtered[]            = $attachmentId;
                $this->alreadyLinked[] = $endpointId;
            }
        }

        return $filtered;
    }

    private function imagePullByQuery(string $query)
    {
        $result = [];
        $images = $this->database->query($query);

        foreach ($images as $image) {
            $image['sort'] = 0;
            $result[] = $image;
        }

        return $result;
    }

    protected function getStats()
    {
        $imageCount = $this->masterProductImageStats();
        $imageCount += count($this->database->query(SqlHelper::imageVariationCombinationPull()));

        if ($this->wpml->canBeUsed() && $this->wpml->canWpmlMediaBeUsed()) {
            $imageCount += count($this->wpml->getComponent(WpmlMedia::class)->imageCategoryPull());
        } else {
            $imageCount += count($this->database->query(SqlHelper::imageCategoryPull()));
        }

        if ($this->getPluginsManager()->get(PerfectWooCommerceBrands::class)->canBeUsed()) {
            if ($this->wpml->canBeUsed() && $this->wpml->canWpmlMediaBeUsed()) {
                $imageCount += count($this->wpml->getComponent(WpmlMedia::class)->imageManufacturerPull());
            } else {
                $imageCount += count($this->database->query(SqlHelper::imageManufacturerPull()));
            }
        }

        return $imageCount;
    }

    private function masterProductImageStats()
    {
        $this->alreadyLinked = $this->database->queryList(SqlHelper::linkedProductImages());

        $count = 0;
        $images = [];

        // Fetch unlinked product cover images
        if ($this->wpml->canBeUsed() && $this->wpml->canWpmlMediaBeUsed()) {
            $thumbnails = $this->database->query(
                $this->wpml->getComponent(WpmlMedia::class)->getImageProductThumbnailSql()
            );
        } else {
            $thumbnails = $this->database->query(SqlHelper::imageProductThumbnail());
        }

        foreach ($thumbnails as $thumbnail) {
            $images[(int)$thumbnail['ID']] = (int)$thumbnail['meta_value'];
            $count++;
        }

        // Get all product gallery images
        if ($this->wpml->canBeUsed() && $this->wpml->canWpmlMediaBeUsed()) {
            $productImagesMappings = $this->database->query(
                $this->wpml->getComponent(WpmlMedia::class)->getImageProductGalleryStats()
            );
        }else{
            $productImagesMappings = $this->database->query(SqlHelper::imageProductGalleryStats());
        }

        foreach ($productImagesMappings as $productImagesMapping) {
            $productId = (int)$productImagesMapping['ID'];
            $galleryImageIds = array_map('intval', explode(',', $productImagesMapping['meta_value']));
            $galleryImageIds = array_filter($galleryImageIds, function ($galleryId) {
                return $galleryId !== 0;
            });
            $galleryImageIds = $this->filterAlreadyLinkedProducts($galleryImageIds, $productId);

            $count += count($galleryImageIds);

            if (isset($images[$productId]) && in_array($images[$productId], $galleryImageIds)) {
                --$count;
            }
        }

        return $count;
    }

    protected function pushData(ImageModel $image)
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

    private function saveImage(ImageModel $image)
    {
        $endpointId = $image->getId()->getEndpoint();
        $post = null;

        $nameInfo = pathinfo($image->getName());
        $fileNameInfo = pathinfo($image->getFilename());

        if (empty($nameInfo['filename'])) {
            $name = html_entity_decode($fileNameInfo['filename'], ENT_QUOTES, 'UTF-8');
        } else {
            $name = html_entity_decode($nameInfo['filename']);
        }

        $name = $this->sanitizeImageName($name);

        if (empty($nameInfo['extension'])) {
            $extension = $fileNameInfo['extension'];
        } else {
            $extension = $nameInfo['extension'];
        }

        $fileName = $name . '.' . $extension;

        $uploadDir = \wp_upload_dir();

        if (empty($endpointId)) {
            $fileName = $this->getNextAvailableImageFilename($name, $extension, $uploadDir['path']);
        }

        $destination = $uploadDir['path'] . DIRECTORY_SEPARATOR . $fileName;

        if (copy($image->getFilename(), $destination)) {
            $fileType = \wp_check_filetype(basename($destination), null);

            $attachment = [
                'guid' => $uploadDir['url'] . '/' . $fileName,
                'post_mime_type' => $fileType['type'],
                'post_title' => preg_replace('/\.[^.]+$/', '', $fileName),
                'post_content' => '',
                'post_status' => 'inherit',
            ];

            if (!empty($endpointId)) {
                $attachment['ID'] = $endpointId;
            }

            $post = \wp_insert_attachment($attachment, $destination, $image->getForeignKey()->getEndpoint());

            if ($post instanceof \WP_Error) {
                WpErrorLogger::getInstance()->logError($post);

                return null;
            }

            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attachData = \wp_generate_attachment_metadata($post, $destination);
            \wp_update_attachment_metadata($post, $attachData);
            \update_post_meta($post, '_wp_attachment_image_alt', $this->getImageAlt($image));
        }

        return $post;
    }

    /**
     * @param $name
     * @return false|string\
     */
    private function sanitizeImageName($name): string
    {
        $name = iconv('utf-8', 'ascii//translit', $name);
        $name = preg_replace('#[^A-Za-z0-9\-_]#', '-', $name);
        $name = preg_replace('#-{2,}#', '-', $name);
        $name = trim($name, '-');

        return mb_substr($name, 0, 180);
    }

    /**
     * @param $name
     * @param $extension
     * @param $uploadDir
     * @return string
     */
    protected function getNextAvailableImageFilename($name, $extension, $uploadDir)
    {
        $i = 1;
        $originalName = $name;
        do {
            $fileName = sprintf('%s.%s', $name, $extension);

            $fileFullPath = sprintf('%s%s%s', $uploadDir, DIRECTORY_SEPARATOR, $fileName);

            if ($fileExists = file_exists($fileFullPath)) {
                $name = sprintf('%s-%s', $originalName, $i++);
            }
        } while ($fileExists);

        return $fileName;
    }

    /**
     * @param ImageModel $image
     * @return string
     */
    protected function getImageAlt(ImageModel $image)
    {
        $altText = $image->getName();
        $i18ns = $image->getI18ns();

        if (count($i18ns) > 0) {
            foreach ($i18ns as $i18n) {

                if (Util::getInstance()->isWooCommerceLanguage($i18n->getLanguageISO()) && !empty($i18n->getAltText())) {
                    $altText = $i18n->getAltText();
                    break;
                }
            }
        }

        return $altText;
    }

    private function pushProductImage(ImageModel $image)
    {
        $productId = (int)$image->getForeignKey()->getEndpoint();

        if (!\wc_get_product($productId) instanceof \WC_Product) {
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
            $galleryImages = $this->getGalleryImages($productId);
            $galleryImages[] = (int)$attachmentId;
            $galleryImages = implode(self::GALLERY_DIVIDER, array_unique($galleryImages));
            $result = \update_post_meta($productId, self::GALLERY_KEY, $galleryImages);
            if ($result instanceof \WP_Error) {
                WpErrorLogger::getInstance()->logError($result);

                return null;
            }
        }

        return Id::linkProductImage($attachmentId, $productId);
    }

    private function pushCategoryImage(ImageModel $image)
    {
        $categoryId = (int)$image->getForeignKey()->getEndpoint();

        if (!\term_exists($categoryId)) {
            return null;
        }

        $attachmentId = $this->saveImage($image);
        \update_term_meta($categoryId, self::CATEGORY_THUMBNAIL, $attachmentId);

        return Id::linkCategoryImage($attachmentId);
    }

    private function pushManufacturerImage(ImageModel $image)
    {
        $termId = (int)$image->getForeignKey()->getEndpoint();

        if (!\term_exists($termId)) {
            return null;
        }

        $attachmentId = $this->saveImage($image);
        \update_term_meta($termId, self::MANUFACTURER_KEY, $attachmentId);

        return Id::linkManufacturerImage($attachmentId);
    }

    protected function deleteData(ImageModel $image, $realDelete = true)
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
     * @throws \Exception
     */
    private function deleteImageTermMeta(ImageModel $image, $realDelete)
    {
        $endpointId = $image->getId()->getEndpoint();
        switch ($image->getRelationType()) {
            case ImageRelationType::TYPE_MANUFACTURER:
                $metaKey = self::MANUFACTURER_KEY;
                $id = Id::unlinkCategoryImage($endpointId);
                break;
            case ImageRelationType::TYPE_CATEGORY:
                $metaKey = self::CATEGORY_THUMBNAIL;
                $id = Id::unlinkManufacturerImage($endpointId);
                break;
            default:
                throw new \Exception(sprintf("Invalid relation %s type for id %s when deleting image.", $image->getRelationType(), $endpointId));
        }

        \delete_term_meta($image->getForeignKey()->getEndpoint(), $metaKey);

        if ($realDelete) {
            $this->deleteIfNotUsedByOthers($image, $id);
        }
    }

    private function deleteProductImage(ImageModel $image, $realDelete)
    {
        $imageEndpoint = $image->getId()->getEndpoint();
        $ids = Id::unlink($imageEndpoint);

        if (count($ids) !== 2) {
            return;
        }

        $productId = (int)$ids[1];
        $attachmentId = (int)$ids[0];

        if ($image->getSort() === 0 && strlen($imageEndpoint) === 0) {
            $this->deleteAllProductImages($image, $productId);
            $this->database->query(SqlHelper::imageDeleteLinks($productId));
        } else {
            if ($this->isCoverImage($image)) {
                \set_post_thumbnail($productId, 0);
            } else {
                $galleryImages = $this->getGalleryImages($productId);
                $galleryImages = implode(self::GALLERY_DIVIDER, array_diff($galleryImages, [$attachmentId]));
                \update_post_meta($productId, self::GALLERY_KEY, $galleryImages);
            }

            if ($realDelete) {
                $this->deleteIfNotUsedByOthers($image, $attachmentId);
            }
        }
    }

    private function deleteIfNotUsedByOthers(ImageModel $image, $attachmentId)
    {
        if (empty($attachmentId) || \get_post($attachmentId) === false) {
            return;
        }

        switch ($image->getRelationType()) {
            case ImageRelationType::TYPE_PRODUCT:
                $query = SqlHelper::countRelatedProducts($attachmentId);
                break;
            case ImageRelationType::TYPE_CATEGORY:
                $query = SqlHelper::countTermMetaImages($attachmentId, ImageCtrl::CATEGORY_THUMBNAIL);
                break;
            case ImageRelationType::TYPE_MANUFACTURER:
                $query = SqlHelper::countTermMetaImages($attachmentId, ImageCtrl::MANUFACTURER_KEY);
                break;
            default:
                throw new \Exception(sprintf("Cannot find relation %s for attachement id %s when deleting image", $image->getRelationType(), $attachmentId));
        }

        if ((int)$this->database->queryOne($query) <= 1) {
            if (\get_attached_file($attachmentId) !== false) {
                \wp_delete_attachment($attachmentId, true);
            }
        }
    }

    private function isCoverImage(ImageModel $image)
    {
        return $image->getSort() === 1;
    }

    private function deleteAllProductImages(ImageModel $image, $productId)
    {
        $thumbnail = \get_post_thumbnail_id($productId);
        \set_post_thumbnail($productId, 0);
        $this->deleteIfNotUsedByOthers($image, $thumbnail);
        $galleryImages = $this->getGalleryImages($productId);
        \update_post_meta($productId, self::GALLERY_KEY, '');
        foreach ($galleryImages as $galleryImage) {
            $this->deleteIfNotUsedByOthers($image, $galleryImage);
        }
    }

    private function getGalleryImages($productId)
    {
        $galleryImages = \get_post_meta($productId, self::GALLERY_KEY, true);
        if (empty($galleryImages)) {
            return [];
        }

        return array_map('intval', explode(self::GALLERY_DIVIDER, $galleryImages));
    }
}
