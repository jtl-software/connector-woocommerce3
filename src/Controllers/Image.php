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
use JtlWooCommerceConnector\Controllers\Traits\DeleteTrait;
use JtlWooCommerceConnector\Controllers\Traits\PullTrait;
use JtlWooCommerceConnector\Controllers\Traits\PushTrait;
use JtlWooCommerceConnector\Controllers\Traits\StatsTrait;
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
    
    // <editor-fold defaultstate="collapsed" desc="Pull">
    public function pullData($limit)
    {
        $images        = $this->productImagePull($limit);
        $productImages = $this->addNextImages($images, ImageRelationType::TYPE_PRODUCT, $limit);
        
        $images         = $this->categoryImagePull($limit);
        $categoryImages = $this->addNextImages($images, ImageRelationType::TYPE_CATEGORY, $limit);
    
        $combinedArray = array_merge($productImages, $categoryImages);
        
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_PERFECT_WOO_BRANDS)) {
            $images             = $this->manufacturerImagePull($limit);
            $manufacturerImages = $this->addNextImages($images, ImageRelationType::TYPE_MANUFACTURER, $limit);
            $combinedArray = array_merge($combinedArray, $manufacturerImages);
        }
        
        return $combinedArray;
    }
    
    private function addNextImages($images, $type, &$limit)
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
                        ->setAltText((string)substr($altText !== false ? $altText : '', 0, 254 ))
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
    private function productImagePull($limit = null)
    {
        $imageCount  = 0;
        $attachments = [];
        
        $this->alreadyLinked = $this->database->queryList(SqlHelper::linkedProductImages());
        
        try {
            $page = 0;
            
            while ($imageCount < $limit) {
                $query = new \WP_Query([
                    'fields'         => 'ids',
                    'post_type'      => ['product', 'product_variation'],
                    'post_status'    => ['publish', 'private'],
                    'posts_per_page' => 50,
                    'paged'          => $page++,
                ]);
                
                if ($query->have_posts()) {
                    foreach ($query->posts as $postId) {
                        $product = \wc_get_product($postId);
                        
                        if ( ! $product instanceof \WC_Product) {
                            continue;
                        }
                        
                        $attachmentIds  = $this->fetchProductAttachmentIds($product);
                        $newAttachments = $this->addProductImagesForPost($attachmentIds, $postId);
                        
                        if (empty($newAttachments)) {
                            continue;
                        }
                        
                        $attachments = array_merge($newAttachments, $attachments);
                        $imageCount  += count($newAttachments);
                        
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
        
        if ( ! empty($pictureId)) {
            $attachmentIds[] = $pictureId;
        }
        
        if ( ! $product->is_type('variation')) {
            $imageIds = $product->get_gallery_image_ids();
            
            if ( ! empty($imageIds)) {
                $attachmentIds = array_merge($attachmentIds, $product->get_gallery_image_ids());
            }
        }
        
        return $attachmentIds;
    }
    
    /**
     * Filter out images that are already linked and get image information.
     *
     * @param array $attachmentIds The image ids that should be checked.
     * @param int   $postId The product which is owner of the images.
     *
     * @return array The filtered image data.
     */
    private function addProductImagesForPost($attachmentIds, $postId)
    {
        $attachmentIds  = $this->filterAlreadyLinkedProducts($attachmentIds, $postId);
        $newAttachments = $this->fetchProductAttachments($attachmentIds, $postId);
        
        return $newAttachments;
    }
    
    private function fetchProductAttachments($attachmentIds, $productId)
    {
        $sort        = 0;
        $attachments = [];
        
        if (empty($attachmentIds)) {
            return $attachments;
        }
        
        foreach ($attachmentIds as $attachmentId) {
            if ( ! file_exists(\get_attached_file($attachmentId))) {
                continue;
            }
            
            $picture = \get_post($attachmentId, ARRAY_A);
            
            if ( ! is_array($picture)) {
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
    
    private function filterAlreadyLinkedProducts($productAttachments, $productId)
    {
        $filtered      = [];
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
    
    private function categoryImagePull($limit)
    {
        $result = [];
        
        $images = $this->database->query(SqlHelper::imageCategoryPull($limit));
        
        foreach ($images as $image) {
            $image['sort'] = 0;
            
            $result[] = $image;
        }
        
        return $result;
    }
    
    private function manufacturerImagePull($limit)
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
    protected function getStats()
    {
        $imageCount = $this->masterProductImageStats();
        $imageCount += count($this->database->query(SqlHelper::imageVariationCombinationPull()));
        $imageCount += count($this->database->query(SqlHelper::imageCategoryPull()));
        
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_PERFECT_WOO_BRANDS)) {
            $imageCount += count($this->database->query(SqlHelper::imageManufacturerPull()));
        }
        
        return $imageCount;
    }
    
    private function masterProductImageStats()
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
            $galleryImageIds = array_map('intval', explode(',', $productImagesMapping['meta_value']));
            $galleryImageIds = $this->filterAlreadyLinkedProducts($galleryImageIds, $productId);
            
            $count += count($galleryImageIds);
            
            if (isset($images[$productId]) && in_array($images[$productId], $galleryImageIds)) {
                --$count;
            }
        }
        
        return $count;
    }
    // </editor-fold>
    
    // <editor-fold defaultstate="collapsed" desc="Push">
    protected function pushData(ImageModel $image)
    {
        $foreignKey = $image->getForeignKey()->getEndpoint();
        
        if ( ! empty($foreignKey)) {
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
        $post = null;
        
        $nameInfo     = pathinfo($image->getName());
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
        
        $uploadDir   = \wp_upload_dir();
        $destination = $uploadDir['path'] . DIRECTORY_SEPARATOR . $fileName;
        
        if (copy($image->getFilename(), $destination)) {
            $fileType = \wp_check_filetype(basename($destination), null);
            
            $attachment = [
                'guid'           => $uploadDir['url'] . '/' . $fileName,
                'post_mime_type' => $fileType['type'],
                'post_title'     => preg_replace('/\.[^.]+$/', '', $fileName),
                'post_content'   => '',
                'post_status'    => 'inherit',
            ];
            
            $endpointId = $image->getId()->getEndpoint();
            
            if ( ! empty($endpointId)) {
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
            //ALTERNATIVTEXT
            $altText = null;
            $i18ns   = $image->getI18ns();
            
            if (count($i18ns) > 0) {
                foreach ($i18ns as $i18n) {
                    if (Util::getInstance()->isWooCommerceLanguage($i18n->getLanguageISO())
                        && ! empty($i18n->getAltText())
                    ) {
                        $altText = $i18n->getAltText();
                    }
                }
            } else {
                if ( ! empty($image->getName())) {
                    $altText = $image->getName();
                } else {
                    $altText = $image->getFilename();
                }
            }
            
            if ( ! is_null($altText)) {
                \update_post_meta($post, '_wp_attachment_image_alt', $altText);
            }
            //ALTERNATIVTEXT ENDE
        }
        
        return $post;
    }
    
    private function pushProductImage(ImageModel $image)
    {
        $productId = (int)$image->getForeignKey()->getEndpoint();
        
        if ( ! \wc_get_product($productId) instanceof \WC_Product) {
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
            $galleryImages   = $this->getGalleryImages($productId);
            $galleryImages[] = (int)$attachmentId;
            $galleryImages   = implode(self::GALLERY_DIVIDER, array_unique($galleryImages));
            $result          = \update_post_meta($productId, self::GALLERY_KEY, $galleryImages);
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
        
        if ( ! \term_exists($categoryId)) {
            return null;
        }
        
        $attachmentId = $this->saveImage($image);
        \update_term_meta($categoryId, self::CATEGORY_THUMBNAIL, $attachmentId);
        
        return Id::linkCategoryImage($attachmentId);
    }
    
    private function pushManufacturerImage(ImageModel $image)
    {
        $termId = (int)$image->getForeignKey()->getEndpoint();
        
        if ( ! \term_exists($termId)) {
            return null;
        }
        
        $attachmentId = $this->saveImage($image);
        \update_term_meta($termId, self::MANUFACTURER_KEY, $attachmentId);
        
        return Id::linkManufacturerImage($attachmentId);
    }
    // </editor-fold>
    
    // <editor-fold defaultstate="collapsed" desc="Delete">
    protected function deleteData(ImageModel $image, $realDelete = true)
    {
        if ($image->getRelationType() === ImageRelationType::TYPE_PRODUCT) {
            $this->deleteProductImage($image, $realDelete);
        } elseif ($image->getRelationType() === ImageRelationType::TYPE_CATEGORY) {
            $this->deleteCategoryImage($image, $realDelete);
        } elseif ($image->getRelationType() === ImageRelationType::TYPE_MANUFACTURER) {
            $this->deleteManufacturerImage($image, $realDelete);
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
    
    private function deleteManufacturerImage(ImageModel $image, $realDelete)
    {
        \delete_term_meta($image->getForeignKey()->getEndpoint(), self::MANUFACTURER_KEY);
        
        if ($realDelete) {
            $this->deleteIfNotUsedByOthers(Id::unlinkManufacturerImage($image->getId()->getEndpoint()));
        }
    }
    
    private function deleteProductImage(ImageModel $image, $realDelete)
    {
        $imageEndpoint = $image->getId()->getEndpoint();
        $ids           = Id::unlink($imageEndpoint);
        
        if (count($ids) !== 2) {
            return;
        }
        
        $productId    = (int)$ids[1];
        $attachmentId = (int)$ids[0];
        
        if ($image->getSort() === 0 && strlen($imageEndpoint) === 0) {
            $this->deleteAllProductImages($productId);
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
                $this->deleteIfNotUsedByOthers($attachmentId);
            }
        }
    }
    
    private function deleteIfNotUsedByOthers($attachmentId)
    {
        if (empty($attachmentId) || \get_post($attachmentId) === false) {
            return;
        }
        if (((int)$this->database->queryOne(SqlHelper::imageProductDelete($attachmentId))) !== 0) {
            // Used by any other product
            return;
        }
        if ((int)$this->database->queryOne(SqlHelper::imageCategoryDelete($attachmentId)) === 0) {
            // Not used by either product or category
            if (\get_attached_file($attachmentId) !== false) {
                \wp_delete_attachment($attachmentId, true);
            }
        }
        if ((int)$this->database->queryOne(SqlHelper::imageManufacturerDelete($attachmentId)) === 0) {
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
