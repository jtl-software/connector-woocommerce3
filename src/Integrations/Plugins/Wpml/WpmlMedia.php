<?php

declare(strict_types=1);

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
 *
 * @package JtlWooCommerceConnector\Integrations\Plugins\Wpml
 */
class WpmlMedia extends AbstractComponent
{
    public const
        ELEMENT_TYPE = 'post_attachment';

    /**
     * @param int           $mediaId
     * @param AbstractImage $jtlImage
     * @return void
     * @throws \Exception
     */
    public function getTranslations(int $mediaId, AbstractImage $jtlImage): void
    {
        /** @var Wpml $wpmlPlugin */
        $wpmlPlugin   = $this->getCurrentPlugin();
        $translations = $this->getAttachmentTranslations($mediaId);

        foreach ($translations as $wpmlLanguageCode => $translation) {
            $wawiIsoCode = $wpmlPlugin->convertLanguageToWawi($wpmlLanguageCode);

            /** @var int|false|string $altText */
            $altText = \get_post_meta($translation->element_id, '_wp_attachment_image_alt', true);

            $jtlImage->addI18n((new ImageI18n())
                ->setId($jtlImage->getId())
                ->setAltText(\substr($altText !== false ? (string)$altText : '', 0, 254))
                ->setLanguageISO($wawiIsoCode));
        }
    }

    /**
     * @param int $mediaId
     * @return \stdClass[]
     */
    public function getAttachmentTranslations(int $mediaId): array
    {
        /** @var Wpml $wpmlPlugin */
        $wpmlPlugin = $this->getCurrentPlugin();
        $trid       = $wpmlPlugin->getElementTrid($mediaId, self::ELEMENT_TYPE);

        /** @var WpmlTermTranslation $wpmlTermTranslation */
        $wpmlTermTranslation = $this->getCurrentPlugin()->getComponent(WpmlTermTranslation::class);

        return $wpmlTermTranslation
            ->getTranslations((int)$trid, self::ELEMENT_TYPE);
    }

    /**
     * @param int         $attachmentId
     * @param ImageI18n[] $imageI18ns
     * @return void
     * @throws \Exception
     */
    public function saveAttachmentTranslations(int $attachmentId, array $imageI18ns): void
    {
        /** @var Wpml $wpmlPlugin */
        $wpmlPlugin   = $this->getCurrentPlugin();
        $translations = $this->getAttachmentTranslations($attachmentId);

        /** @var ImageI18n $i18n */
        foreach ($imageI18ns as $i18n) {
            if ($wpmlPlugin->isDefaultLanguage($i18n->getLanguageISO()) || empty($i18n->getAltText())) {
                continue;
            }
            $wpmlLanguage = $wpmlPlugin->convertLanguageToWpml($i18n->getLanguageISO());
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
        /** @var Wpml $wpmlPlugin */
        $wpmlPlugin = $this->getCurrentPlugin();
        $wpdb       = $wpmlPlugin->getWpDb();
        $jcli       = $wpdb->prefix . 'jtl_connector_link_image';
        $wpmlt      = $wpdb->prefix . 'icl_translations';

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
            $wpmlPlugin->getDefaultLanguage()
        );

        return $sql;
    }

    /**
     * @return string
     */
    public function getImageProductGalleryStats(): string
    {
        /** @var Wpml $wpmlPlugin */
        $wpmlPlugin = $this->getCurrentPlugin();
        $wpdb       = $wpmlPlugin->getWpDb();
        $wpmlt      = $wpdb->prefix . 'icl_translations';

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
            $wpmlPlugin->getDefaultLanguage(),
            ImageCtrl::GALLERY_KEY
        );

        return $sql;
    }

    /**
     * @param int|null $limit
     * @return string
     */
    public function imageCategoryPull(?int $limit = null): string
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
    public function imageManufacturerPull(?int $limit = null): string
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
     * @param string   $prefix
     * @param string   $taxonomy
     * @param string   $metaKey
     * @param int      $identityType
     * @param int|null $limit
     * @return string
     */
    protected function buildImageQueryPull(
        string $prefix = Id::MANUFACTURER_PREFIX,
        string $taxonomy = 'pwb-brand',
        string $metaKey = ImageCtrl::MANUFACTURER_KEY,
        int $identityType = IdentityType::MANUFACTURER,
        ?int $limit = null
    ): string {
        /** @var Wpml $wpmlPlugin */
        $wpmlPlugin = $this->getCurrentPlugin();
        $wpdb       = $wpmlPlugin->getWpDb();

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
            $wpmlPlugin->getDefaultLanguage()
        );

        return $sql;
    }
}
