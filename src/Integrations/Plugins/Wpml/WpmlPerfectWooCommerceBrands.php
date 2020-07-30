<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\Wpml;

use jtl\Connector\Core\Utilities\Language;
use jtl\Connector\Model\Manufacturer;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractComponent;
use JtlWooCommerceConnector\Integrations\Plugins\PerfectWooCommerceBrands\PerfectWooCommerceBrands;
use JtlWooCommerceConnector\Integrations\Plugins\YoastSeo\YoastSeo;
use JtlWooCommerceConnector\Logger\WpErrorLogger;
use JtlWooCommerceConnector\Utilities\SqlHelper;

/**
 * Class WpmlPerfectWooCommerceBrands
 * @package JtlWooCommerceConnector\Integrations\Plugins\Wpml
 */
class WpmlPerfectWooCommerceBrands extends AbstractComponent
{
    /**
     * @param int $limit
     * @return array
     */
    public function getManufacturers(int $limit): array
    {
        $wpdb = $this->getCurrentPlugin()->getWpDb();

        $jclm = $wpdb->prefix . 'jtl_connector_link_manufacturer';
        $translations = $wpdb->prefix . 'icl_translations';

        $sql = sprintf("
            SELECT t.term_id, tt.parent, tt.description, t.name, t.slug, tt.count, wpmlt.trid
            FROM `{$wpdb->terms}` t
            LEFT JOIN `{$wpdb->term_taxonomy}` tt ON t.term_id = tt.term_id
            LEFT JOIN `%s` l ON t.term_id = l.endpoint_id
            LEFT JOIN `%s` wpmlt ON t.term_id = wpmlt.element_id
            WHERE tt.taxonomy = '%s' AND l.host_id IS NULL
              
                AND wpmlt.element_type = 'tax_pwb-brand'
                AND wpmlt.source_language_code IS NULL                 
                AND wpmlt.language_code = '%s'
            
            ORDER BY tt.parent ASC
            LIMIT {$limit}",
            $jclm,
            $translations,
            'pwb-brand',
            $this->getCurrentPlugin()->getDefaultLanguage()
        );

        return $this->getCurrentPlugin()->getPluginsManager()->getDatabase()->query($sql);
    }

    /**
     * @param Manufacturer $jtlManufacturer
     * @throws \Exception
     */
    public function saveTranslations(Manufacturer $jtlManufacturer)
    {
        $mainTermId = (int)$jtlManufacturer->getId()->getEndpoint();

        $termTranslations = $this->getCurrentPlugin()->getComponent(WpmlTermTranslation::class);

        $elementType = 'tax_pwb-brand';

        $trid = $this->getCurrentPlugin()->getElementTrid($mainTermId, $elementType);

        $translation = $termTranslations->getTranslations($trid, $elementType);

        foreach ($jtlManufacturer->getI18ns() as $manufacturerI18n) {

            $languageCode = $this->getCurrentPlugin()->convertLanguageToWpml($manufacturerI18n->getLanguageISO());
            if ($languageCode === $this->getCurrentPlugin()->getDefaultLanguage()) {
                continue;
            }

            $perfectWooCommerceBrands = $this->getCurrentPlugin()->getPluginsManager()->get(PerfectWooCommerceBrands::class);

            if (!isset($translation[$languageCode])) {
                $slug = $perfectWooCommerceBrands->sanitizeSlug($jtlManufacturer->getName(), $languageCode);
                $result = $perfectWooCommerceBrands->createManufacturer($slug, $jtlManufacturer->getName(),
                    $manufacturerI18n);
            } else {
                $manufacturerId = $translation[$languageCode]->term_id;

                $result = $perfectWooCommerceBrands
                    ->updateManufacturer($manufacturerId, $jtlManufacturer->getName(), $manufacturerI18n);
            }

            if ($result instanceof \WP_Error) {
                WpErrorLogger::getInstance()->logError($result);
            } else {
                if (isset($result['term_id'])) {
                    $manufacturerId = $result['term_id'];

                    $yoastSeo = $this->getCurrentPlugin()->getPluginsManager()->get(YoastSeo::class);
                    if ($yoastSeo->canBeUsed()) {
                        $yoastSeo->setManufacturerSeoData((int)$manufacturerId, $manufacturerI18n);
                    }

                    $this->getCurrentPlugin()->getSitepress()->set_element_language_details(
                        $manufacturerId,
                        $elementType,
                        $trid,
                        $languageCode
                    );
                }
            }
        }
    }

    /**
     * @param int $manufacturerId
     */
    public function deleteTranslations(int $manufacturerId)
    {
        $elementType = 'tax_pwb-brand';

        $trid = $this->getCurrentPlugin()->getElementTrid($manufacturerId, $elementType);

        $translations = $this->getCurrentPlugin()
            ->getComponent(WpmlTermTranslation::class)
            ->getTranslations($trid, $elementType, true);

        foreach ($translations as $translation) {
            wp_delete_term($translation->term_id, 'pwb-brand');
        }
    }

    /**
     * @return int
     */
    public function getStats(): int
    {
        $wpdb = $this->getCurrentPlugin()->getWpDb();
        $jclm = $wpdb->prefix . 'jtl_connector_link_manufacturer';

        $sql = sprintf("
            SELECT COUNT(tt.term_id)
            FROM `{$wpdb->term_taxonomy}` tt
            LEFT JOIN `%s` l ON tt.term_id = l.endpoint_id
            LEFT JOIN `%sicl_translations` wpmlt ON tt.term_id = wpmlt.element_id
            WHERE tt.taxonomy = '%s' 
            AND wpmlt.element_type = 'tax_pwb-brand'
            AND wpmlt.source_language_code IS NULL
            AND l.host_id IS NULL
            AND wpmlt.language_code = '%s'",
            $jclm,
            $wpdb->prefix,
            'pwb-brand',
            $this->getCurrentPlugin()->getDefaultLanguage()
        );

        return (int)$this->getCurrentPlugin()->getPluginsManager()->getDatabase()->queryOne($sql);
    }
}
