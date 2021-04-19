<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2018 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers;

use jtl\Connector\Core\Exception\LanguageException;
use jtl\Connector\Core\Utilities\Language;
use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Manufacturer as ManufacturerModel;
use JtlWooCommerceConnector\Controllers\Traits\DeleteTrait;
use JtlWooCommerceConnector\Controllers\Traits\PullTrait;
use JtlWooCommerceConnector\Controllers\Traits\PushTrait;
use JtlWooCommerceConnector\Controllers\Traits\StatsTrait;
use JtlWooCommerceConnector\Integrations\Plugins\PerfectWooCommerceBrands\PerfectWooCommerceBrands;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\Wpml;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlPerfectWooCommerceBrands;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlTermTranslation;
use JtlWooCommerceConnector\Utilities\Util;

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

            if ($this->wpml->canBeUsed()) {
                $manufacturerData = $this->wpml
                    ->getComponent(WpmlPerfectWooCommerceBrands::class)
                    ->getManufacturers((int)$limit);

            } else {
                $manufacturerData = $perfectWooCommerceBrands->getManufacturers((int)$limit);
            }


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

                if ($this->wpml->canBeUsed()) {

                    $wpmlTaxonomyTranslations = $this->wpml
                        ->getComponent(WpmlTermTranslation::class);

                    $manufacturerTranslations = $wpmlTaxonomyTranslations
                        ->getTranslations((int)$manufacturerDataSet['trid'], 'tax_pwb-brand');

                    foreach ($manufacturerTranslations as $languageCode => $translation) {

                        $term = $wpmlTaxonomyTranslations->getTranslatedTerm(
                            (int)$translation->term_id,
                            'pwb-brand'
                        );

                        if (isset($term['term_id'])) {
                            $i18n = $this
                                ->getPluginsManager()
                                ->get(PerfectWooCommerceBrands::class)
                                ->createManufacturerI18n(
                                    $manufacturer,
                                    Language::convert($translation->language_code),
                                    $term['description'],
                                    (int)$term['term_id']
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
     * @param ManufacturerModel $jtlManufacturer
     * @return ManufacturerModel
     * @throws LanguageException
     * @throws \Exception
     */
    protected function pushData(ManufacturerModel $jtlManufacturer)
    {
        $perfectWooCommerceBrands = $this->getPluginsManager()->get(PerfectWooCommerceBrands::class);

        if ($perfectWooCommerceBrands->canBeUsed()) {
            $defaultLanguage = null;
            foreach ($jtlManufacturer->getI18ns() as $i18n) {
                if ($this->wpml->canBeUsed()) {
                    if ($this->wpml->getDefaultLanguage() === Language::convert(null, $i18n->getLanguageISO())) {
                        $defaultLanguage = $i18n;
                        break;
                    }
                } else {
                    if (Util::getInstance()->getWooCommerceLanguage() === $i18n->getLanguageISO()) {
                        $defaultLanguage = $i18n;
                        break;
                    }
                }
            }

            if ($defaultLanguage !== null) {
                $perfectWooCommerceBrands->saveManufacturer($jtlManufacturer, $defaultLanguage);

                if ($this->wpml->canBeUsed()) {
                    $this->wpml
                        ->getComponent(WpmlPerfectWooCommerceBrands::class)
                        ->saveTranslations($jtlManufacturer);
                }
            }
        }

        return $jtlManufacturer;
    }

    /**
     * @param ManufacturerModel $manufacturer
     * @return ManufacturerModel
     * @throws \Exception
     */
    protected function deleteData(ManufacturerModel $manufacturer)
    {
        $perfectWooCommerceBrands = $this->getPluginsManager()->get(PerfectWooCommerceBrands::class);
        if ($perfectWooCommerceBrands->canBeUsed()) {
            $manufacturerId = (int)$manufacturer->getId()->getEndpoint();

            if (!empty($manufacturerId)) {

                unset(self::$idCache[$manufacturer->getId()->getHost()]);

                if ($this->wpml->canBeUsed()) {
                    $this->wpml
                        ->getComponent(WpmlPerfectWooCommerceBrands::class)
                        ->deleteTranslations($manufacturerId);
                }

                wp_delete_term($manufacturerId, 'pwb-brand');
            }
        }

        return $manufacturer;
    }

    /**
     * @return int
     * @throws \Exception
     */
    protected function getStats()
    {
        $perfectWooCommerceBrands = $this->getPluginsManager()->get(PerfectWooCommerceBrands::class);
        if ($perfectWooCommerceBrands->canBeUsed()) {
            if ($this->wpml->canBeUsed()) {
                $total = $this->wpml->getComponent(WpmlPerfectWooCommerceBrands::class)->getStats();
            } else {
                $total = $perfectWooCommerceBrands->getStats();
            }
        } else {
            $total = 0;
        }

        return $total;
    }
}
