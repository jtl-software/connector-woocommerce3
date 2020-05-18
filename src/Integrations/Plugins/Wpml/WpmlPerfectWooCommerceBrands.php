<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\Wpml;

use jtl\Connector\Core\Utilities\Language;
use jtl\Connector\Model\Manufacturer;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractComponent;
use JtlWooCommerceConnector\Integrations\Plugins\PerfectWooCommerceBrands\PerfectWooCommerceBrands;
use JtlWooCommerceConnector\Integrations\Plugins\YoastSeo\YoastSeo;
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
        $wpdb = $this->getPlugin()->getWpDb();

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
            $this->getPlugin()->getDefaultLanguage()
        );

        return $this->getPlugin()->getPluginsManager()->getDatabase()->query($sql);
    }

    /**
     * @param Manufacturer $jtlManufacturer
     * @throws \Exception
     */
    public function saveTranslations(Manufacturer $jtlManufacturer)
    {
        $mainTermId = (int)$jtlManufacturer->getId()->getEndpoint();

        $termTranslations = $this->getPlugin()->getComponent(WpmlTermTranslation::class);

        $elementType = 'tax_pwb-brand';

        $trid = $this->getPlugin()->getElementTrid($mainTermId, $elementType);

        $translation = $termTranslations->getTranslations($trid, $elementType);

        foreach ($jtlManufacturer->getI18ns() as $manufacturerI18n) {

            $languageCode = Language::convert(null, $manufacturerI18n->getLanguageISO());
            if ($languageCode === $this->getPlugin()->getDefaultLanguage()) {
                continue;
            }

            if (!isset($translation[$languageCode])) {
                $result = $this->getPlugin()->getPluginsManager()->get(PerfectWooCommerceBrands::class)
                    ->createManufacturer($jtlManufacturer->getName(), $manufacturerI18n);
            } else {
                $manufacturerId = $translation[$languageCode]->term_id;

                $result = $this->getPlugin()->getPluginsManager()->get(PerfectWooCommerceBrands::class)
                    ->updateManufacturer($manufacturerId, $jtlManufacturer->getName(), $manufacturerI18n);
            }

            if (isset($result['term_id'])) {
                $manufacturerId = $result['term_id'];

                $yoastSeo = $this->getPlugin()->getPluginsManager()->get(YoastSeo::class);
                if ($yoastSeo->canBeUsed()) {
                    $yoastSeo->setManufacturerSeoData((int)$manufacturerId, $manufacturerI18n);
                }

                $this->getPlugin()->getSitepress()->set_element_language_details(
                    $manufacturerId,
                    $elementType,
                    $trid,
                    $languageCode
                );
            }
        }
    }

    /**
     * @param int $manufacturerId
     */
    public function deleteTranslations(int $manufacturerId)
    {
        $elementType = 'tax_pwb-brand';

        $trid = $this->getPlugin()->getElementTrid($manufacturerId, $elementType);

        $translations = $this->getPlugin()
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
        $wpdb = $this->getPlugin()->getWpDb();
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
            $this->getPlugin()->getDefaultLanguage()
        );

        return (int)$this->getPlugin()->getPluginsManager()->getDatabase()->queryOne($sql);
    }
}
