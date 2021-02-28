<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Product;

use Exception;
use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\Model\ProductI18n as ProductI18nModel;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;

class ProductMetaSeo extends BaseController
{
    /**
     * @param $newPostId
     * @param ProductI18nModel $tmpMeta
     */
    public function pushData($newPostId, ProductI18nModel $tmpMeta)
    {
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO_PREMIUM)) {
            $this->setSeoValues($newPostId, $tmpMeta, '_yoast_wpseo_title', '_yoast_wpseo_metadesc', '_yoast_wpseo_focuskw');
        } elseif (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_RANK_MATH_SEO)) {
            $this->setSeoValues($newPostId, $tmpMeta, 'rank_math_title', 'rank_math_description', 'rank_math_focus_keyword');
        }
    }

    /**
     * @param \WC_Product $wcProduct
     * @param ProductModel $model
     * @return array|null
     */
    public function pullData(\WC_Product $wcProduct, ProductModel $model): ?array
    {
        $values = null;
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO_PREMIUM)) {
            $values = $this->getSeoValues($wcProduct, '_yoast_wpseo_title', '_yoast_wpseo_metadesc', '_yoast_wpseo_focuskw');
        } elseif (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_RANK_MATH_SEO)) {
            $values = $this->getSeoValues($wcProduct, 'rank_math_title', 'rank_math_description', 'rank_math_focus_keyword');
        }

        if (is_array($values)) {
            foreach ($values as $key => $value) {
                if (strcmp($key, 'permalink') === 0) {
                    continue;
                }
                if (is_array($value) && count($value) > 0) {
                    $values[$key] = $value[0];
                }
            }
        }

        return $values;
    }

    /**
     * @param int $productId
     * @param ProductI18nModel $tmpMeta
     * @param string $metaTitle
     * @param string $metaDescription
     * @param string $metaKeywords
     */
    protected function setSeoValues(int $productId, ProductI18nModel $tmpMeta, string $metaTitle, string $metaDescription, string $metaKeywords)
    {
        $wcProduct = \wc_get_product($productId);
        if (!$wcProduct instanceof \WC_Product) {
            return;
        }

        if ($wcProduct->get_slug() !== $tmpMeta->getUrlPath()) {
            $wcProduct->set_name($tmpMeta->getUrlPath());
        }

        update_post_meta($wcProduct->get_id(), $metaTitle, $tmpMeta->getTitleTag());
        update_post_meta($wcProduct->get_id(), $metaDescription, $tmpMeta->getMetaDescription());
        update_post_meta($wcProduct->get_id(), $metaKeywords, $tmpMeta->getMetaKeywords());
    }

    /**
     * @param \WC_Product $wcProduct
     * @param string $metaTitle
     * @param string $metaDescription
     * @param string $metaKeywords
     * @return array
     */
    protected function getSeoValues(\WC_Product $wcProduct, string $metaTitle, string $metaDescription, string $metaKeywords): array
    {
        return [
            'titleTag' => get_post_meta($wcProduct->get_id(), $metaTitle),
            'metaDesc' => get_post_meta($wcProduct->get_id(), $metaDescription),
            'keywords' => get_post_meta($wcProduct->get_id(), $metaKeywords),
            'permlink' => $wcProduct->get_slug()
        ];
    }
}
