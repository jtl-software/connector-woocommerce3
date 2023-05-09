<?php

/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Product;

use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\Model\ProductI18n as ProductI18nModel;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Utilities\Config;
use JtlWooCommerceConnector\Utilities\Germanized;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;

class ProductI18n extends BaseController
{
    /**
     * @param \WC_Product $product
     * @param ProductModel $model
     * @return ProductI18nModel
     * @throws \InvalidArgumentException
     */
    public function pullData(\WC_Product $product, ProductModel $model): ProductI18nModel
    {
        $i18n = (new ProductI18nModel())
            ->setProductId($model->getId())
            ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage())
            ->setName($this->name($product))
            ->setDescription(\html_entity_decode($product->get_description()))
            ->setShortDescription(\html_entity_decode($product->get_short_description()))
            ->setUrlPath($product->get_slug());

        if (
            (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO))
            && Germanized::getInstance()->hasUnitProduct($product)
        ) {
            $i18n->setMeasurementUnitName(Germanized::getInstance()->getUnit($product));
        }

        if (
            SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO_PREMIUM)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_RANK_MATH_SEO)
        ) {
            $tmpMeta = ProductMetaSeo::getInstance()->pullData($product, $model);
            if (\is_array($tmpMeta)) {
                $this->setI18nSeoData($i18n, $tmpMeta);
            }
        }

        return $i18n;
    }

    /**
     * @param ProductI18nModel $i18n
     * @param array $tmpMeta
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function setI18nSeoData(ProductI18nModel $i18n, array $tmpMeta): void
    {
        $i18n->setMetaDescription(\is_array($tmpMeta['metaDesc']) ? '' : $tmpMeta['metaDesc'])
            ->setMetaKeywords(\is_array($tmpMeta['keywords']) ? '' : $tmpMeta['keywords'])
            ->setTitleTag(\is_array($tmpMeta['titleTag']) ? '' : $tmpMeta['titleTag'])
            ->setUrlPath(\is_array($tmpMeta['permlink']) ? '' : $tmpMeta['permlink']);
    }

    /**
     * @param \WC_Product $product
     * @return string
     */
    private function name(\WC_Product $product): string
    {
        if ($product instanceof \WC_Product_Variation) {
            switch (Config::get(Config::OPTIONS_VARIATION_NAME_FORMAT, '')) {
                case 'space':
                    return $product->get_name() . ' ' . \wc_get_formatted_variation($product, true);
                case 'brackets':
                    return \sprintf(
                        '%s (%s)',
                        $product->get_name(),
                        \wc_get_formatted_variation($product, true)
                    );
                case 'space_parent':
                    $parent = \wc_get_product($product->get_parent_id());

                    return $parent->get_title() . ' ' . \wc_get_formatted_variation($product, true);
                case 'brackets_parent':
                    $parent = \wc_get_product($product->get_parent_id());

                    return \sprintf(
                        '%s (%s)',
                        $parent->get_title(),
                        \wc_get_formatted_variation($product, true)
                    );
            }
        }

        return $product->get_name();
    }
}
