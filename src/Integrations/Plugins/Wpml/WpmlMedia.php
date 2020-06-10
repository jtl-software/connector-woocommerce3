<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\Wpml;

use jtl\Connector\Linker\IdentityLinker;
use jtl\Connector\Model\Image;
use jtl\Connector\Model\ImageI18n;
use JtlWooCommerceConnector\Controllers\Image as ImageCtrl;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractComponent;
use JtlWooCommerceConnector\Utilities\Category as CategoryUtil;
use JtlWooCommerceConnector\Utilities\Id;

/**
 * Class WpmlMedia
 * @package JtlWooCommerceConnector\Integrations\Plugins\Wpml
 */
class WpmlMedia extends AbstractComponent
{
    /**
     * @param int $mediaId
     * @param Image $jtlImage
     * @return mixed
     */
    public function getTranslations(int $mediaId, Image $jtlImage)
    {
        $type = 'post_attachment';
        $trid = $this->getCurrentPlugin()->getElementTrid($mediaId, $type);

        $translations = $this
            ->getCurrentPlugin()
            ->getComponent(WpmlTermTranslation::class)
            ->getTranslations($trid, $type);

        foreach ($translations as $wpmlLanguageCode => $translation) {

            $wawiIsoCode = $this->getCurrentPlugin()->convertLanguageToWawi($wpmlLanguageCode);

            $altText = \get_post_meta($translation->element_id, '_wp_attachment_image_alt', true);

            $jtlImage->addI18n((new ImageI18n())
                ->setId($jtlImage->getId())
                ->setImageId($jtlImage->getForeignKey())
                ->setAltText((string)substr($altText !== false ? $altText : '', 0, 254))
                ->setLanguageISO($wawiIsoCode)
            );
        }
    }

    /**
     * @return string
     */
    public function getImageProductThumbnailSql(): string
    {
        $wpdb = $this->getCurrentPlugin()->getWpDb();
        $jcli = $wpdb->prefix . 'jtl_connector_link_image';
        $wpmlt = $wpdb->prefix . 'icl_translations';

        $sql = sprintf("
            SELECT p.ID, pm.meta_value
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm
            ON p.ID = pm.post_id
            LEFT JOIN {$jcli} l ON SUBSTRING_INDEX(l.endpoint_id, '%s', -1) = pm.post_id  AND l.type = %d
            LEFT JOIN {$wpmlt} wpmlt ON p.ID = wpmlt.element_id        
            WHERE p.post_type = 'product'
            AND p.post_status IN ('draft', 'future', 'publish', 'inherit', 'private')
            AND pm.meta_key = '%s'
            AND pm.meta_value != 0
            AND l.host_id IS NULL
            AND wpmlt.element_type = 'post_product'
            AND wpmlt.source_language_code IS NULL
            AND wpmlt.language_code = '%s'        
            GROUP BY p.ID, pm.meta_value",
            Id::SEPARATOR,
            IdentityLinker::TYPE_PRODUCT,
            ImageCtrl::PRODUCT_THUMBNAIL,
            $this->getCurrentPlugin()->getDefaultLanguage()
        );

        return $sql;
    }

    /**
     * @return string
     */
    public function getImageProductGalleryStats(): string
    {
        $wpdb = $this->getCurrentPlugin()->getWpDb();
        $wpmlt = $wpdb->prefix . 'icl_translations';

        $sql = sprintf("
            SELECT p.ID, pm.meta_value
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            LEFT JOIN {$wpmlt} wpmlt ON p.ID = wpmlt.element_id
            WHERE p.post_type = 'product'
            AND wpmlt.element_type = 'post_product'
            AND wpmlt.source_language_code IS NULL
            AND wpmlt.language_code = '%s'
            AND p.post_status IN ('future', 'draft', 'publish', 'inherit', 'private')
            AND pm.meta_key = '%s' 
            AND pm.meta_value != 0",
            $this->getCurrentPlugin()->getDefaultLanguage(),
            ImageCtrl::GALLERY_KEY
        );

        return $sql;
    }

    /**
     * @param int|null $limit
     * @return string
     */
    public function imageCategoryPull(int $limit = null): string
    {
        return $this->buildImageQueryPull(
            Id::CATEGORY_PREFIX,
            CategoryUtil::TERM_TAXONOMY,
            ImageCtrl::CATEGORY_THUMBNAIL,
            IdentityLinker::TYPE_CATEGORY,
            $limit
        );
    }

    /**
     * @param int|null $limit
     * @return string
     */
    public function imageManufacturerPull(int $limit = null): string
    {
        return $this->buildImageQueryPull(
            Id::MANUFACTURER_PREFIX,
            'pwb-brand',
            ImageCtrl::MANUFACTURER_KEY,
            IdentityLinker::TYPE_MANUFACTURER,
            $limit
        );
    }

    /**
     * @param string $prefix
     * @param string $taxonomy
     * @param string $metaKey
     * @param int $identityType
     * @param int|null $limit
     * @return string
     */
    protected function buildImageQueryPull(
        $prefix = Id::MANUFACTURER_PREFIX,
        $taxonomy = 'pwb-brand',
        $metaKey = ImageCtrl::MANUFACTURER_KEY,
        $identityType = IdentityLinker::TYPE_MANUFACTURER,
        int $limit = null
    ): string
    {
        $wpdb = $this->getCurrentPlugin()->getWpDb();

        $limitQuery = is_null($limit) ? '' : 'LIMIT ' . $limit;
        $jcli = $wpdb->prefix . 'jtl_connector_link_image';
        $wpmlt = $wpdb->prefix . 'icl_translations';

        $sql = sprintf("
            SELECT CONCAT_WS('%s', '%s', p.ID) as id, p.ID as ID, tt.term_id as parent, p.guid
            FROM {$wpdb->term_taxonomy} tt
            RIGHT JOIN {$wpdb->termmeta} tm ON tt.term_id = tm.term_id
            LEFT JOIN {$wpdb->posts} p ON p.ID = tm.meta_value
            LEFT JOIN {$jcli} l ON l.endpoint_id = p.ID AND type = %d
            LEFT JOIN {$wpmlt} wpmlt ON p.ID = wpmlt.element_id        
            WHERE l.host_id IS NULL
            AND tt.taxonomy = '%s'
            AND tm.meta_key = '%s'
            AND tm.meta_value != 0
            AND wpmlt.element_type = 'post_attachment'
            AND wpmlt.source_language_code IS NULL
            AND wpmlt.language_code = '%s'
            {$limitQuery}",
            Id::SEPARATOR,
            $prefix,
            $identityType,
            $taxonomy,
            $metaKey,
            $this->getCurrentPlugin()->getDefaultLanguage()
        );

        return $sql;
    }
}