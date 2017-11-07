<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller;

use jtl\Connector\Drawing\ImageRelationType;
use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Image as ImageModel;
use jtl\Connector\Model\ImageI18n;
use jtl\Connector\WooCommerce\Controller\Traits\DeleteTrait;
use jtl\Connector\WooCommerce\Controller\Traits\PullTrait;
use jtl\Connector\WooCommerce\Controller\Traits\PushTrait;
use jtl\Connector\WooCommerce\Controller\Traits\StatsTrait;
use jtl\Connector\WooCommerce\Logger\WpErrorLogger;
use jtl\Connector\WooCommerce\Utility\Id;
use jtl\Connector\WooCommerce\Utility\SQL;
use jtl\Connector\WooCommerce\Utility\Util;

class Image extends BaseController
{
    use PullTrait, PushTrait, DeleteTrait, StatsTrait;

    const GALLERY_DIVIDER = ',';
    const PRODUCT_THUMBNAIL = '_thumbnail_id';
    const CATEGORY_THUMBNAIL = 'thumbnail_id';
    const GALLERY_KEY = '_product_image_gallery';

    private $alreadyLinked = [];

    private $pushMethods = [
        ImageRelationType::TYPE_PRODUCT => 'pushProductImage',
        ImageRelationType::TYPE_CATEGORY => 'pushCategoryImage',
    ];

    // <editor-fold defaultstate="collapsed" desc="Pull">
    public function pullData($limit)
    {
        $images = $this->productPull($limit);
        $productImages = $this->addNextImages($images, ImageRelationType::TYPE_PRODUCT, $limit);

        $images = $this->categoryPull($limit);
        $categoryImages = $this->addNextImages($images, ImageRelationType::TYPE_CATEGORY, $limit);

        return array_merge($productImages, $categoryImages);
    }

    private function addNextImages($images, $type, &$limit)
    {
        $return = [];

        foreach ($images as $image) {
            $model = (new ImageModel())
                ->setId(new Identity($image['id']))
                ->setName($image['post_name'])
                ->setForeignKey(new Identity($image['parent']))
                ->setRemoteUrl($image['guid'])
                ->setSort((int)$image['sort'])
                ->setFilename(\wc_get_filename_from_url($image['guid']));

            if ($model instanceof ImageModel) {
                $altText = \get_post_meta($image['ID'], '_wp_attachment_image_alt', true);

                $model
                    ->setRelationType($type)
                    ->addI18n((new ImageI18n())
                        ->setId(new Identity($image['id']))
                        ->setImageId(new Identity($image['id']))
                        ->setAltText($altText !== false ? $altText : '')
                        ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage())
                    );

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
    private function productPull($limit = null)
    {
        $imageCount = 0;
        $attachments = [];

        $this->alreadyLinked = $this->database->queryList(SQL::linkedProductImages());

        try {
            $page = 0;

            while ($imageCount < $limit) {
                $query = new \WP_Query([
                    'fields' => 'ids',
                    'post_type' => ['product', 'product_variation'],
                    'post_status' => 'publish',
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

        $pictureId = (int)$product->get_image_id();

        if (!empty($pictureId)) {
            $attachmentIds[] = $pictureId;
        }

        if (!$product->is_type('variation')) {
            $imageIds = $product->get_gallery_image_ids();

            if (!empty($imageIds)) {
                $attachmentIds = array_merge($attachmentIds, $product->get_gallery_image_ids());
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

            if (!in_array($endpointId, $this->alreadyLinked)) {
                $filtered[] = $attachmentId;
                $this->alreadyLinked[] = $endpointId;
            }
        }

        return $filtered;
    }

    private function categoryPull($limit)
    {
        $result = [];

        $images = $this->database->query(SQL::imageCategoryPull($limit));

        foreach ($images as $image) {
            $image['sort'] = 0;

            $result[] = $image;
        }

        return $result;
    }
    // </editor-fold>

    // <editor-fold defaultstate="collapsed" desc="Stats">
    protected function getStats()
    {
        $imageCount = $this->productGalleryStats();
        $imageCount += count($this->database->query(SQL::imageVariationCombinationPull()));
        $imageCount += count($this->database->query(SQL::imageCategoryPull()));

        return $imageCount;
    }

    private function productGalleryStats()
    {
        $this->alreadyLinked = $this->database->queryList(SQL::linkedProductImages());

        $count = 0;
        $images = [];

        // Fetch the cover images
        $thumbnails = $this->database->query(SQL::imageProductThumbnail());

        foreach ($thumbnails as $thumbnail) {
            $images[(int)$thumbnail['ID']] = (int)$thumbnail['meta_value'];
        }

        // add the gallery images
        $productImagesMappings = $this->database->query(SQL::imageProductGalleryStats());

        foreach ($productImagesMappings as $productImagesMapping) {
            $productId = (int)$productImagesMapping['ID'];
            $attachmentIds = array_map('intval', explode(',', $productImagesMapping['meta_value']));

            if (isset($images[$productId])) {
                $attachmentIds[] = $images[$productId];
            }

            // Count unique product images
            $count += count(array_unique($this->filterAlreadyLinkedProducts($attachmentIds, $productId)));
        }

        return $count;
    }
    // </editor-fold>

    // <editor-fold defaultstate="collapsed" desc="Push">
    protected function pushData(ImageModel $image)
    {
        $foreignKey = $image->getForeignKey()->getEndpoint();
        if (!empty($foreignKey)) {
            // Relation exists
            $id = null;
            // Delete image with the same id
            $this->deleteData($image, false);
            // Relation type support check
            if (isset($this->pushMethods[$image->getRelationType()])) {
                $methodName = $this->pushMethods[$image->getRelationType()];
                $id = $this->{$methodName}($image);
            }
            $image->getId()->setEndpoint($id);
        }

        return $image;
    }

    private function saveImage(ImageModel $image)
    {
        $result = null;

        $nameInfo = pathinfo($image->getName());
        $fileNameInfo = pathinfo($image->getFilename());

        if (empty($nameInfo['filename'])) {
            $name = html_entity_decode($fileNameInfo['filename'], ENT_QUOTES, 'UTF-8');
        } else {
            $name = html_entity_decode($nameInfo['filename']);
        }

        if (empty($nameInfo['extension'])) {
            $extension = $fileNameInfo['extension'];
        } else {
            $extension = $nameInfo['extension'];
        }

        $fileName = $name . '.' . $extension;

        $uploadDir = \wp_upload_dir();
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

            $endpointId = $image->getId()->getEndpoint();

            if (!empty($endpointId)) {
                $attachment['ID'] = $endpointId;
            }

            $result = \wp_insert_attachment($attachment, $destination, $image->getForeignKey()->getEndpoint());

            if ($result instanceof \WP_Error) {
                WpErrorLogger::getInstance()->logError($result);

                return null;
            }

            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attachData = \wp_generate_attachment_metadata($result, $destination);
            \wp_update_attachment_metadata($result, $attachData);
        }

        return $result;
    }

    private function pushProductImage(ImageModel $image)
    {
        $productId = (int)$image->getForeignKey()->getEndpoint();

        if (\wc_get_product($productId) === false) {
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
    // </editor-fold>

    // <editor-fold defaultstate="collapsed" desc="Delete">
    protected function deleteData(ImageModel $image, $realDelete = true)
    {
        if ($image->getRelationType() === ImageRelationType::TYPE_PRODUCT) {
            $this->deleteProductImage($image, $realDelete);
        } elseif ($image->getRelationType() === ImageRelationType::TYPE_CATEGORY) {
            $this->deleteCategoryImage($image, $realDelete);
        }

        return $image;
    }

    private function deleteCategoryImage(ImageModel $image, $realDelete)
    {
        \delete_term_meta($image->getForeignKey()->getEndpoint(), self::CATEGORY_THUMBNAIL);

        if ($realDelete) {
            $this->deleteIfNotUsedByOthers(Id::unlinkCategoryImage($image->getId()->getEndpoint()));
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
            $this->deleteAllProductImages($productId);
            $this->database->query(SQL::imageDeleteLinks($productId));
        } else {
            if ($this->isCoverImage($image)) {
                \set_post_thumbnail($productId, 0);
            } else {
                $galleryImages = $this->getGalleryImages($productId);
                $galleryImages = implode(self::GALLERY_DIVIDER, array_diff($galleryImages, [$attachmentId]));
                \update_post_meta($productId, self::GALLERY_KEY, $galleryImages);
            }

            if ($realDelete) {
                $this->deleteIfNotUsedByOthers($attachmentId);
            }
        }
    }

    private function deleteIfNotUsedByOthers($attachmentId)
    {
        if (empty($attachmentId) || \get_post($attachmentId) === false) {
            return;
        }
        if (((int)$this->database->queryOne(SQL::imageProductDelete($attachmentId))) !== 0) {
            // Used by any other product
            return;
        }
        if ((int)$this->database->queryOne(SQL::imageCategoryDelete($attachmentId)) === 0) {
            // Not used by either product or category
            if (\get_attached_file($attachmentId) !== false) {
                \wp_delete_attachment($attachmentId, true);
            }
        }
    }

    private function isCoverImage(ImageModel $image)
    {
        return $image->getSort() === 1;
    }

    private function deleteAllProductImages($productId)
    {
        $thumbnail = \get_post_thumbnail_id($productId);
        \set_post_thumbnail($productId, 0);
        $this->deleteIfNotUsedByOthers($thumbnail);
        $galleryImages = $this->getGalleryImages($productId);
        \update_post_meta($productId, self::GALLERY_KEY, '');
        foreach ($galleryImages as $galleryImage) {
            $this->deleteIfNotUsedByOthers($galleryImage);
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
    // </editor-fold>
}
