<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\PerfectWooCommerceBrands;

use jtl\Connector\Model\Manufacturer;
use jtl\Connector\Model\ManufacturerI18n as ManufacturerI18nModel;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractPlugin;
use JtlWooCommerceConnector\Integrations\Plugins\YoastSeo\YoastSeo;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;

/**
 * Class PerfectWooCommerceBrands
 * @package JtlWooCommerceConnector\Integrations\Plugins\PerfectWooCommerceBrands
 */
class PerfectWooCommerceBrands extends AbstractPlugin
{
    /**
     * @return bool
     */
    public function canBeUsed(): bool
    {
        return SupportedPlugins::isActive(SupportedPlugins::PLUGIN_PERFECT_WOO_BRANDS);
    }

    /**
     * @param Manufacturer $manufacturer
     * @param string $languageIso
     * @param string $description
     * @param int $termId
     * @return ManufacturerI18nModel
     * @throws \Exception
     */
    public function createManufacturerI18n(
        Manufacturer $manufacturer,
        string $languageIso,
        string $description,
        int $termId
    ): ManufacturerI18nModel {
        $i18n = (new ManufacturerI18nModel())
            ->setManufacturerId($manufacturer->getId())
            ->setLanguageISO($languageIso)
            ->setDescription($description);

        /** @var YoastSeo $yoastSeo */
        $yoastSeo = $this->getPluginsManager()->get(YoastSeo::class);
        if ($yoastSeo->canBeUsed()) {
            $seoData = $yoastSeo->findManufacturerSeoData($termId);
            if (!empty($seoData)) {
                $i18n->setMetaDescription(isset($seoData['wpseo_desc']) ? $seoData['wpseo_desc'] : '')
                    ->setMetaKeywords(isset($seoData['wpseo_focuskw']) ? $seoData['wpseo_focuskw'] : $manufacturer->getName())
                    ->setTitleTag(isset($seoData['wpseo_title']) ? $seoData['wpseo_title'] : $manufacturer->getName());
            }
        }

        return $i18n;
    }

    /**
     * @param int $limit
     * @return array
     */
    public function getManufacturers(int $limit): array
    {
        $sql = SqlHelper::manufacturerPull($limit);
        return $this->getPluginsManager()->getDatabase()->query($sql);
    }
}
