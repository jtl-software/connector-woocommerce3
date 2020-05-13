<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2018 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers;

use jtl\Connector\Core\Utilities\Language;
use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Manufacturer as ManufacturerModel;
use jtl\Connector\Model\ManufacturerI18n as ManufacturerI18nModel;
use JtlWooCommerceConnector\Controllers\Traits\DeleteTrait;
use JtlWooCommerceConnector\Controllers\Traits\PullTrait;
use JtlWooCommerceConnector\Controllers\Traits\PushTrait;
use JtlWooCommerceConnector\Controllers\Traits\StatsTrait;
use JtlWooCommerceConnector\Integrations\Plugins\PerfectWooCommerceBrands\PerfectWooCommerceBrands;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\Wpml;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlPerfectWooCommerceBrands;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlTaxonomyTranslation;
use JtlWooCommerceConnector\Integrations\Plugins\YoastSeo\YoastSeo;
use JtlWooCommerceConnector\Logger\WpErrorLogger;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;
use WP_Error;

/**
 * Class Manufacturer
 * @package JtlWooCommerceConnector\Controllers
 */
class Manufacturer extends BaseController
{
    use PullTrait, PushTrait, DeleteTrait, StatsTrait;

    /**
     * @var array
     */
    private static $idCache = [];

    /**
     * @param $limit
     * @return array
     * @throws \Exception
     */
    protected function pullData($limit)
    {
        $manufacturers = [];
        $perfectWooCommerceBrands = $this->getPluginsManager()->get(PerfectWooCommerceBrands::class);

        if ($perfectWooCommerceBrands->canBeUsed()) {

            $manufacturerData = $this->getManufacturersData((int)$limit);

            foreach ($manufacturerData as $manufacturerDataSet) {

                $manufacturer = (new ManufacturerModel)
                    ->setId(new Identity($manufacturerDataSet['term_id']))
                    ->setName($manufacturerDataSet['name']);

                $i18n = $perfectWooCommerceBrands->createManufacturerI18n(
                    $manufacturer,
                    Util::getInstance()->getWooCommerceLanguage(),
                    $manufacturerDataSet['description'],
                    (int)$manufacturerDataSet['term_id']
                );
                $manufacturer->addI18n($i18n);

                if ($this->getWpml()->canBeUsed()) {

                    $wpmlTaxonomyTranslations = $this->getPluginsManager()
                        ->get(Wpml::class)
                        ->getComponent(WpmlTaxonomyTranslation::class);

                    $manufacturerTranslations = $wpmlTaxonomyTranslations
                        ->getTranslations((int)$manufacturerDataSet['trid'], 'tax_pwb-brand');

                    foreach ($manufacturerTranslations as $languageCode => $translation) {
                        if ($languageCode === $this->getWpml()->getDefaultLanguage()) {
                            continue;
                        }

                        $term = $wpmlTaxonomyTranslations->getTranslatedTerm(
                            (int)$translation->term_id,
                            'pwb-brand'
                        );

                        if ($term instanceof \WP_Term) {
                            $i18n = $this
                                ->getPluginsManager()
                                ->get(PerfectWooCommerceBrands::class)
                                ->createManufacturerI18n(
                                    $manufacturer,
                                    Language::convert($translation->language_code),
                                    $term->description,
                                    (int)$term->id
                                );
                            $manufacturer->addI18n($i18n);
                        }
                    }
                }

                $manufacturers[] = $manufacturer;
            }
        }

        return $manufacturers;
    }

    /**
     * @param $limit
     * @return array|null
     * @throws \Exception
     */
    protected function getManufacturersData(int $limit)
    {
        if ($this->getPluginsManager()->get(Wpml::class)->canBeUsed()) {
            $manufacturerData = $this
                ->getPluginsManager()
                ->get(Wpml::class)
                ->getComponent(WpmlPerfectWooCommerceBrands::class)
                ->getManufacturers($limit);

        } else {
            $sql = SqlHelper::manufacturerPull($limit);
            $manufacturerData = $this->database->query($sql);
        }

        return $manufacturerData;
    }

    protected function pushData(ManufacturerModel $manufacturer)
    {
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_PERFECT_WOO_BRANDS)) {
            $meta = null;
            $defaultAvailable = false;

            foreach ($manufacturer->getI18ns() as $i18n) {
                $languageSet = Util::getInstance()->isWooCommerceLanguage($i18n->getLanguageISO());

                if (strcmp($i18n->getLanguageISO(), 'ger') === 0) {
                    $defaultAvailable = true;
                }

                if ($languageSet) {
                    $meta = $i18n;
                    break;
                }
            }

            //Fallback 'ger' if incorrect language code was given
            if ($meta === null && $defaultAvailable) {
                foreach ($manufacturer->getI18ns() as $i18n) {
                    if (strcmp($i18n->getLanguageISO(), 'ger') === 0) {
                        $meta = $i18n;
                    }
                }
            }

            if ($meta !== null) {

                $name = wc_sanitize_taxonomy_name(substr(trim($manufacturer->getName()), 0, 27));

                $term = get_term_by('slug', $name, 'pwb-brand');

                remove_filter('pre_term_description', 'wp_filter_kses');

                if ($term === false) {
                    //Add term
                    /** @var \WP_Term $newTerm */
                    $newTerm = \wp_insert_term(
                        $manufacturer->getName(),
                        'pwb-brand',
                        [
                            'description' => $meta->getDescription(),
                            'slug' => $name,
                        ]
                    );

                    if ($newTerm instanceof WP_Error) {
                        //  var_dump($newTerm);
                        // die();
                        $error = new WP_Error('invalid_taxonomy', 'Could not create manufacturer.');
                        WpErrorLogger::getInstance()->logError($error);
                        WpErrorLogger::getInstance()->logError($newTerm);
                    }
                    $term = $newTerm;

                    if (!$term instanceof \WP_Term) {
                        if (array_key_exists('term_id', $term)) {
                            $term = get_term_by('id', $term['term_id'], 'pwb-brand');
                        }
                    }

                } else {

                    wp_update_term($term->term_id, 'pwb-brand', [
                        'name' => $manufacturer->getName(),
                        'description' => $meta->getDescription(),
                    ]);
                }

                add_filter('pre_term_description', 'wp_filter_kses');

                if ($term instanceof \WP_Term) {
                    $manufacturer->getId()->setEndpoint($term->term_id);
                    foreach ($manufacturer->getI18ns() as $i18n) {
                        /** @var ManufacturerI18nModel $i18n */
                        $i18n->getManufacturerId()->setEndpoint($term->term_id);
                    }

                    $yoastSeo = $this->getPluginsManager()->get(YoastSeo::class);
                    if ($yoastSeo->canBeUsed() && isset($i18n)) {
                        $yoastSeo->setManufacturerSeoData((int)$term->term_id, $i18n);
                    }
                }
            }
        }

        return $manufacturer;
    }

    protected function deleteData(ManufacturerModel $manufacturer)
    {
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_PERFECT_WOO_BRANDS)) {
            $manufacturerId = (int)$manufacturer->getId()->getEndpoint();

            if (!empty($manufacturerId)) {

                unset(self::$idCache[$manufacturer->getId()->getHost()]);

                wp_delete_term($manufacturerId, 'pwb-brand');
            }
        }

        return $manufacturer;
    }

    protected function getStats()
    {
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_PERFECT_WOO_BRANDS)) {
            return $this->database->queryOne(SqlHelper::manufacturerStats());
        } else {
            return 0;
        }
    }
}
