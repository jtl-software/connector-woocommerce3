<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Integrations\Plugins\YoastSeo;

use Jtl\Connector\Core\Definition\Model;
use Jtl\Connector\Core\Model\AbstractI18n;
use Jtl\Connector\Core\Model\CategoryI18n;
use Jtl\Connector\Core\Model\ManufacturerI18n;
use Jtl\Connector\Core\Model\ProductI18n;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractPlugin;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;

/**
 * Class YoastSeo
 *
 * @package JtlWooCommerceConnector\Integrations\Plugins\YoastSeo
 */
class YoastSeo extends AbstractPlugin
{
    /** @var array<string, array<int|string, array<string, string>>>|false */
    protected array|bool|null $wpSeoTaxonomyMeta;

    /**
     * @return bool
     */
    public function canBeUsed(): bool
    {
        return (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO_PREMIUM));
    }

    /**
     * @param int                           $taxonomyId
     * @param CategoryI18n|ManufacturerI18n $i18nModel
     * @param string                        $type
     *
     * @return void
     */
    protected function updateWpSeoTaxonomyMeta(
        int $taxonomyId,
        CategoryI18n|ManufacturerI18n $i18nModel,
        string $type
    ): void {
        $taxonomySeo = $this->getSeoTaxonomyMeta();

        if ($taxonomySeo === false) {
            $taxonomySeo = [$type => []];
        }

        if (!isset($taxonomySeo[$type])) {
            $taxonomySeo[$type] = [];
        }
        $exists = false;

        if (!empty($taxonomySeo[$type])) {
            foreach ($taxonomySeo[$type] as $catKey => $seoData) {
                if ((int)$catKey === $taxonomyId) {
                    $exists                                       = true;
                    $taxonomySeo[$type][$catKey]['wpseo_desc']    = $i18nModel->getMetaDescription();
                    $taxonomySeo[$type][$catKey]['wpseo_focuskw'] = $i18nModel->getMetaKeywords();
                    $taxonomySeo[$type][$catKey]['wpseo_title']   = \strcmp(
                        $i18nModel->getTitleTag(),
                        ''
                    ) === 0 && \method_exists(
                        $i18nModel,
                        'getName'
                    ) ? $i18nModel->getName() : $i18nModel->getTitleTag();
                }
            }
            if ($exists === false) {
                $taxonomySeo[$type][(int)$taxonomyId] = [
                    'wpseo_desc' => $i18nModel->getMetaDescription(),
                    'wpseo_focuskw' => $i18nModel->getMetaKeywords(),
                    'wpseo_title' => \strcmp(
                        $i18nModel->getTitleTag(),
                        ''
                    ) === 0 && \method_exists(
                        $i18nModel,
                        'getName'
                    ) ? $i18nModel->getName() : $i18nModel->getTitleTag(),
                ];
            }
        }

        \update_option('wpseo_taxonomy_meta', $taxonomySeo, true);
    }

    /**
     * @param ProductI18n|CategoryI18n|ManufacturerI18n $i18n
     * @param int                                       $termId
     * @param string                                    $type
     *
     * @return void
     */
    public function setSeoData(ProductI18n|CategoryI18n|ManufacturerI18n $i18n, int $termId, string $type): void
    {
        $seoData = $this->findSeoTranslationData($termId, $type);
        if (!empty($seoData)) {
            $i18n->setMetaDescription($seoData['wpseo_desc'] ?? '')
                ->setMetaKeywords($seoData['wpseo_focuskw'] ?? '')
                ->setTitleTag($seoData['wpseo_title'] ?? '');
        }
    }

    /**
     * @param int    $termId
     * @param string $type
     * @return array<string, string>
     */
    protected function findSeoTranslationData(int $termId, string $type): array
    {
        $seoData     = [];
        $taxonomySeo = $this->getSeoTaxonomyMeta();

        if (isset($taxonomySeo[$type])) {
            foreach ($taxonomySeo[$type] as $elementId => $wpSeoData) {
                if ((int)$elementId === $termId) {
                    $seoData = $wpSeoData;
                    break;
                }
            }
        }
        return $seoData;
    }

    /**
     * @param int          $categoryId
     * @param CategoryI18n $categoryI18n
     * @return void
     */
    public function setCategorySeoData(int $categoryId, CategoryI18n $categoryI18n): void
    {
        $this->updateWpSeoTaxonomyMeta($categoryId, $categoryI18n, 'product_cat');
    }

    /**
     * @param int              $manufacturerId
     * @param ManufacturerI18n $manufacturerI18n
     * @return void
     */
    public function setManufacturerSeoData(int $manufacturerId, ManufacturerI18n $manufacturerI18n): void
    {
        $this->updateWpSeoTaxonomyMeta($manufacturerId, $manufacturerI18n, 'pwb-brand');
    }

    /**
     * @param int $categoryId
     * @return array<string, string>
     */
    public function findCategorySeoData(int $categoryId): array
    {
        return $this->findSeoTranslationData($categoryId, 'product_cat');
    }

    /**
     * @param int $manufacturerId
     * @return array<string, string>
     */
    public function findManufacturerSeoData(int $manufacturerId): array
    {
        return $this->findSeoTranslationData($manufacturerId, 'pwb-brand');
    }

    /**
     * @param \WC_Product $product
     * @return array<string, array<int, string>|string>
     */
    public function findProductSeoData(\WC_Product $product): array
    {
        $productId = $product->get_id();

        $values = [
            'titleTag' => \get_post_meta($productId, '_yoast_wpseo_title'),
            'metaDesc' => \get_post_meta($productId, '_yoast_wpseo_metadesc'),
            'keywords' => \get_post_meta($productId, '_yoast_wpseo_focuskw'),
            'permlink' => $product->get_slug()
        ];

        foreach ($values as $key => $value) {
            if (\strcmp($key, 'permalink') === 0) {
                continue;
            }
            if (\is_array($value) && \count($value) > 0) {
                $values[$key] = $value[0];
            }
        }

        return $values;
    }

    /**
     * @return array<string, array<int|string, array<string, string>>>|false
     */
    protected function getSeoTaxonomyMeta(): array|bool
    {
        if (!isset($this->wpSeoTaxonomyMeta)) {
            /** @var array<string, array<int|string, array<string, string>>>|false $wpseoTaxonomyMeta */
            $wpseoTaxonomyMeta       = \get_option('wpseo_taxonomy_meta', []);
            $this->wpSeoTaxonomyMeta = $wpseoTaxonomyMeta;
        }
        return $this->wpSeoTaxonomyMeta;
    }
}
