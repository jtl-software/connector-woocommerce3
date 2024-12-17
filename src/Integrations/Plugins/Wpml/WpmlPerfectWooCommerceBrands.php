<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Integrations\Plugins\Wpml;

use Jtl\Connector\Core\Model\Manufacturer;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractComponent;
use JtlWooCommerceConnector\Integrations\Plugins\PerfectWooCommerceBrands\PerfectWooCommerceBrands;
use JtlWooCommerceConnector\Integrations\Plugins\RankMathSeo\RankMathSeo;
use JtlWooCommerceConnector\Integrations\Plugins\YoastSeo\YoastSeo;
use JtlWooCommerceConnector\Logger\ErrorFormatter;
use Psr\Log\InvalidArgumentException;

/**
 * Class WpmlPerfectWooCommerceBrands
 *
 * @package JtlWooCommerceConnector\Integrations\Plugins\Wpml
 */
class WpmlPerfectWooCommerceBrands extends AbstractComponent
{
    /**
     * @param int $limit
     * @return array<int, array<int|string, int|string|null>>
     * @throws InvalidArgumentException
     */
    public function getManufacturers(int $limit): array
    {
        /** @var Wpml $wpmlPlugin */
        $wpmlPlugin = $this->getCurrentPlugin();
        $wpdb       = $wpmlPlugin->getWpDb();

        $jclm         = $wpdb->prefix . 'jtl_connector_link_manufacturer';
        $translations = $wpdb->prefix . 'icl_translations';

        $sql = \sprintf(
            "
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
            $wpmlPlugin->getDefaultLanguage()
        );

        /** @var array<int, array<int|string, int|string|null>> $manufacturers */
        $manufacturers = $this->getCurrentPlugin()->getPluginsManager()->getDatabase()->query($sql) ?? [];

        return $manufacturers;
    }

    /**
     * @param Manufacturer $jtlManufacturer
     * @return void
     * @throws \InvalidArgumentException
     * @throws \http\Exception\InvalidArgumentException
     * @throws \Exception
     */
    public function saveTranslations(Manufacturer $jtlManufacturer): void
    {
        \remove_filter('pre_term_description', 'wp_filter_kses');
        $mainManufacturerId = (int)$jtlManufacturer->getId()->getEndpoint();

        /** @var Wpml $wpmlPlugin */
        $wpmlPlugin = $this->getCurrentPlugin();

        /** @var WpmlTermTranslation $termTranslations */
        $termTranslations = $wpmlPlugin->getComponent(WpmlTermTranslation::class);

        $elementType = 'tax_pwb-brand';

        /** @var false|\WP_Error|\WP_Term $manufacturerTerm */
        $manufacturerTerm = \get_term_by('id', $mainManufacturerId, 'pwb-brand');

        if (!$manufacturerTerm instanceof \WP_Term) {
            throw new \http\Exception\InvalidArgumentException(
                "Manufacturer with ID {$mainManufacturerId} not found."
            );
        }

        $trid = $wpmlPlugin->getElementTrid($manufacturerTerm->term_taxonomy_id, $elementType);

        $translation = $termTranslations->getTranslations((int)$trid, $elementType);

        /** @var PerfectWooCommerceBrands $perfectWooCommerceBrands */
        $perfectWooCommerceBrands = $wpmlPlugin->getPluginsManager()
            ->get(PerfectWooCommerceBrands::class);

        foreach ($jtlManufacturer->getI18ns() as $manufacturerI18n) {
            $languageCode = $wpmlPlugin->convertLanguageToWpml($manufacturerI18n->getLanguageISO());
            if ($languageCode === $wpmlPlugin->getDefaultLanguage()) {
                continue;
            }

            if (!isset($translation[$languageCode])) {
                $slug   = $perfectWooCommerceBrands->sanitizeSlug($jtlManufacturer->getName(), $languageCode);
                $result = $perfectWooCommerceBrands
                    ->createManufacturer($slug, $jtlManufacturer->getName(), $manufacturerI18n);
            } else {
                $termTranslations->disableGetTermAdjustId();
                $translatedManufacturerId = $translation[$languageCode]->term_id;
                $result                   = $perfectWooCommerceBrands
                    ->updateManufacturer($translatedManufacturerId, $jtlManufacturer->getName(), $manufacturerI18n);
                $termTranslations->enableGetTermAdjustId();
            }

            if ($result instanceof \WP_Error) {
                $this->logger->error(ErrorFormatter::formatError($result));
            } else {
                if (isset($result['term_id'])) {
                    $translatedManufacturerId = (int)$result['term_id'];

                    /** @var YoastSeo $yoastSeo */
                    $yoastSeo = $this->getCurrentPlugin()->getPluginsManager()->get(YoastSeo::class);
                    /** @var RankMathSeo $rankMathSeo */
                    $rankMathSeo = $this->getCurrentPlugin()->getPluginsManager()->get(RankMathSeo::class);
                    if ($yoastSeo->canBeUsed()) {
                        $yoastSeo->setManufacturerSeoData($translatedManufacturerId, $manufacturerI18n);
                    } elseif ($rankMathSeo->canBeUsed()) {
                        $rankMathSeo->updateWpSeoTaxonomyMeta($translatedManufacturerId, $manufacturerI18n);
                    }

                    $wpmlPlugin->getSitepress()->set_element_language_details(
                        (int)$result['term_taxonomy_id'],
                        $elementType,
                        (int)$trid,
                        $languageCode
                    );
                }
            }
        }
        \add_filter('pre_term_description', 'wp_filter_kses');
    }

    /**
     * @param int $manufacturerId
     * @return void
     * @throws \Exception
     */
    public function deleteTranslations(int $manufacturerId): void
    {
        $elementType = 'tax_pwb-brand';
        /** @var Wpml $wpmlPlugin */
        $wpmlPlugin = $this->getCurrentPlugin();

        $trid = $wpmlPlugin->getElementTrid($manufacturerId, $elementType);

        /** @var WpmlTermTranslation $wpmlTermTranslation */
        $wpmlTermTranslation = $wpmlPlugin->getComponent(WpmlTermTranslation::class);

        $translations = $wpmlTermTranslation
            ->getTranslations((int)$trid, $elementType, true);

        foreach ($translations as $translation) {
            \wp_delete_term($translation->term_id, 'pwb-brand');
        }
    }

    /**
     * @return int
     * @throws InvalidArgumentException
     */
    public function getStats(): int
    {
        /** @var Wpml $wpmlPlugin */
        $wpmlPlugin = $this->getCurrentPlugin();
        $wpdb       = $wpmlPlugin->getWpDb();
        $jclm       = $wpdb->prefix . 'jtl_connector_link_manufacturer';

        $sql = \sprintf(
            "
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
            $wpmlPlugin->getDefaultLanguage()
        );

        return (int)$this->getCurrentPlugin()->getPluginsManager()->getDatabase()->queryOne($sql);
    }
}
