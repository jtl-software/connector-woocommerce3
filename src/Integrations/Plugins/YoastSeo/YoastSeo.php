<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\YoastSeo;

use jtl\Connector\Core\Model\Model;
use jtl\Connector\Model\CategoryI18n;
use jtl\Connector\Model\ManufacturerI18n;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractPlugin;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;

/**
 * Class YoastSeo
 * @package JtlWooCommerceConnector\Integrations\Plugins\YoastSeo
 */
class YoastSeo extends AbstractPlugin
{
    /**
     * @var null|bool|array
     */
    protected $wpSeoTaxonomyMeta;

    /**
     * @return bool
     */
    public function canBeUsed(): bool
    {
        return (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO_PREMIUM));
    }

    /**
     * @param int $taxonomyId
     * @param Model $i18nModel
     * @param string $type
     */
    protected function updateWpSeoTaxonomyMeta(int $taxonomyId, Model $i18nModel, string $type): void
    {
        $taxonomySeo = $this->getSeoTaxonomyMeta();

        if ($taxonomySeo === false) {
            $taxonomySeo = [$type => []];
        }

        if (!isset($taxonomySeo[$type])) {
            $taxonomySeo[$type] = [];
        }
        $exists = false;

        foreach ($taxonomySeo[$type] as $catKey => $seoData) {
            if ($catKey === (int)$taxonomyId) {
                $exists = true;
                $taxonomySeo[$type][$catKey]['wpseo_desc'] = $i18nModel->getMetaDescription();
                $taxonomySeo[$type][$catKey]['wpseo_focuskw'] = $i18nModel->getMetaKeywords();
                $taxonomySeo[$type][$catKey]['wpseo_title'] = strcmp($i18nModel->getTitleTag(),
                    '') === 0 && method_exists($i18nModel,
                    'getName') ? $i18nModel->getName() : $i18nModel->getTitleTag();
            }
        }
        if ($exists === false) {
            $taxonomySeo[$type][(int)$taxonomyId] = [
                'wpseo_desc' => $i18nModel->getMetaDescription(),
                'wpseo_focuskw' => $i18nModel->getMetaKeywords(),
                'wpseo_title' => strcmp($i18nModel->getTitleTag(),
                    '') === 0 && method_exists($i18nModel,
                    'getName') ? $i18nModel->getName() : $i18nModel->getTitleTag(),
            ];
        }

        update_option('wpseo_taxonomy_meta', $taxonomySeo, true);
    }

    /**
     * @param Model $i18n
     * @param int $termId
     * @param string $type
     */
    public function setSeoData(Model $i18n, int $termId, string $type)
    {
        $seoData = $this->findSeoTranslationData($termId, $type);
        if (!empty($seoData)) {
            $i18n->setMetaDescription(isset($seoData['wpseo_desc']) ? $seoData['wpseo_desc'] : '')
                ->setMetaKeywords(isset($seoData['wpseo_focuskw']) ? $seoData['wpseo_focuskw'] : $i18n->getName())
                ->setTitleTag(isset($seoData['wpseo_title']) ? $seoData['wpseo_title'] : $i18n->getName());
        }
    }

    /**
     * @param int $termId
     * @param string $type
     * @return array
     */
    protected function findSeoTranslationData(int $termId, string $type): array
    {
        $seoData = [];
        $taxonomySeo = $this->getSeoTaxonomyMeta();

        if (isset($taxonomySeo[$type]) && is_array($taxonomySeo[$type])) {
            foreach ($taxonomySeo[$type] as $elementId => $wpSeoData) {
                if ($elementId === $termId) {
                    $seoData = $wpSeoData;
                    break;
                }
            }
        }
        return $seoData;
    }

    /**
     * @param int $categoryId
     * @param CategoryI18n $categoryI18n
     */
    public function setCategorySeoData(int $categoryId, CategoryI18n $categoryI18n): void
    {
        $this->updateWpSeoTaxonomyMeta($categoryId, $categoryI18n, 'product_cat');
    }

    /**
     * @param int $manufacturerId
     * @param ManufacturerI18n $manufacturerI18n
     */
    public function setManufacturerSeoData(int $manufacturerId, ManufacturerI18n $manufacturerI18n): void
    {
        $this->updateWpSeoTaxonomyMeta($manufacturerId, $manufacturerI18n, 'pwb-brand');
    }

    /**
     * @param int $categoryId
     * @return array
     */
    public function findCategorySeoData(int $categoryId): array
    {
        return $this->findSeoTranslationData($categoryId, 'product_cat');
    }

    /**
     * @param int $manufacturerId
     * @return array
     */
    public function findManufacturerSeoData(int $manufacturerId): array
    {
        return $this->findSeoTranslationData($manufacturerId, 'pwb-brand');
    }

    /**
     * @param \WC_Product $product
     * @return array
     */
    public function findProductSeoData(\WC_Product $product): array
    {
        $productId = $product->get_id();

        $values = [
            'titleTag' => get_post_meta($productId, '_yoast_wpseo_title'),
            'metaDesc' => get_post_meta($productId, '_yoast_wpseo_metadesc'),
            'keywords' => get_post_meta($productId, '_yoast_wpseo_focuskw'),
            'permlink' => $product->get_slug()
        ];

        foreach ($values as $key => $value) {
            if (strcmp($key, 'permalink') === 0) {
                continue;
            }
            if (is_array($value) && count($value) > 0) {
                $values[$key] = $value[0];
            }
        }

        return $values;
    }

    /**
     * @return array|bool|mixed|void|null
     */
    protected function getSeoTaxonomyMeta()
    {
        if (!isset($this->wpSeoTaxonomyMeta)) {
            $this->wpSeoTaxonomyMeta = get_option('wpseo_taxonomy_meta', []);
        }
        return $this->wpSeoTaxonomyMeta;
    }
}
