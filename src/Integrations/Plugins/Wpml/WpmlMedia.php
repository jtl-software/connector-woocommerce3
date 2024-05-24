<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\Wpml;

use Jtl\Connector\Core\Definition\IdentityType;
use Jtl\Connector\Core\Model\AbstractImage;
use Jtl\Connector\Core\Model\ImageI18n;
use JtlWooCommerceConnector\Controllers\ImageController as ImageCtrl;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractComponent;
use JtlWooCommerceConnector\Utilities\Category as CategoryUtil;
use JtlWooCommerceConnector\Utilities\Id;

/**
 * Class WpmlMedia
 * @package JtlWooCommerceConnector\Integrations\Plugins\Wpml
 */
class WpmlMedia extends AbstractComponent
{
    public const
        ELEMENT_TYPE = 'post_attachment';

    /**
     * @param int $mediaId
     * @param AbstractImage $jtlImage
     */
    public function getTranslations(int $mediaId, AbstractImage $jtlImage): void
    {
        $translations = $this->getAttachmentTranslations($mediaId);

        foreach ($translations as $wpmlLanguageCode => $translation) {
            $wawiIsoCode = $this->getCurrentPlugin()->convertLanguageToWawi($wpmlLanguageCode);

            $altText = \get_post_meta($translation->element_id, '_wp_attachment_image_alt', true);

            $jtlImage->addI18n((new ImageI18n())
                ->setId($jtlImage->getId())
                ->setAltText((string)\substr($altText !== false ? $altText : '', 0, 254))
                ->setLanguageISO($wawiIsoCode));
        }
    }

    /**
     * @param int $mediaId
     * @return array
     */
    public function getAttachmentTranslations(int $mediaId): array
    {
        $trid = $this->getCurrentPlugin()->getElementTrid($mediaId, self::ELEMENT_TYPE);

        return $this
            ->getCurrentPlugin()
            ->getComponent(WpmlTermTranslation::class)
            ->getTranslations($trid, self::ELEMENT_TYPE);
    }

    /**
     * @param int $attachmentId
     * @param array $imageI18ns
     */
    public function saveAttachmentTranslations(int $attachmentId, array $imageI18ns): void
    {
        $translations  = $this->getAttachmentTranslations($attachmentId);
        $currentPlugin = $this->getCurrentPlugin();

        /** @var ImageI18n $i18n */
        foreach ($imageI18ns as $i18n) {
            if ($currentPlugin->isDefaultLanguage($i18n->getLanguageISO()) || empty($i18n->getAltText())) {
                continue;
            }
            $wpmlLanguage = $currentPlugin->convertLanguageToWpml($i18n->getLanguageISO());
            if (isset($translations[$wpmlLanguage])) {
                $translation = $translations[$wpmlLanguage];
                \update_post_meta($translation->element_id, '_wp_attachment_image_alt', $i18n->getAltText());
            }
        }
    }

    /**
     * @return string
     */
    public function getImageProductThumbnailSql(): string
    {
        $wpdb  = $this->getCurrentPlugin()->getWpDb();
        $jcli  = $wpdb->prefix . 'jtl_connector_link_image';
        $wpmlt = $wpdb->prefix . 'icl_translations';

        $sql = \sprintf(
            "
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
            IdentityType::PRODUCT,
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
        $wpdb  = $this->getCurrentPlugin()->getWpDb();
        $wpmlt = $wpdb->prefix . 'icl_translations';

        $sql = \sprintf(
            "
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
            IdentityType::CATEGORY,
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
            IdentityType::MANUFACTURER,
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
        string $prefix = Id::MANUFACTURER_PREFIX,
        string $taxonomy = 'pwb-brand',
        string $metaKey = ImageCtrl::MANUFACTURER_KEY,
        int $identityType = IdentityType::MANUFACTURER,
        int $limit = null
    ): string {
        $wpdb = $this->getCurrentPlugin()->getWpDb();

        $limitQuery = \is_null($limit) ? '' : 'LIMIT ' . $limit;
        $jcli       = $wpdb->prefix . 'jtl_connector_link_image';
        $wpmlt      = $wpdb->prefix . 'icl_translations';

        $sql = \sprintf(
            "
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
            AND wpmlt.element_type = '%s'
            AND wpmlt.source_language_code IS NULL
            AND wpmlt.language_code = '%s'
            {$limitQuery}",
            Id::SEPARATOR,
            $prefix,
            $identityType,
            $taxonomy,
            $metaKey,
            self::ELEMENT_TYPE,
            $this->getCurrentPlugin()->getDefaultLanguage()
        );

        return $sql;
    }
}
