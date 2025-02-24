<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Controllers\Product;

use Exception;
use Jtl\Connector\Core\Model\Product as ProductModel;
use Jtl\Connector\Core\Model\ProductI18n as ProductI18nModel;
use JtlWooCommerceConnector\Controllers\AbstractBaseController;
use JtlWooCommerceConnector\Utilities\Config;
use JtlWooCommerceConnector\Utilities\Germanized;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use WC_Product;

class ProductI18nController extends AbstractBaseController
{
    /**
     * @param WC_Product   $product
     * @param ProductModel $model
     * @return ProductI18nModel
     * @throws Exception
     */
    public function pullData(WC_Product $product, ProductModel $model): ProductI18nModel
    {
        $i18n = (new ProductI18nModel())
            ->setLanguageISO($this->util->getWooCommerceLanguage())
            ->setName($this->name($product))
            ->setDescription(\html_entity_decode($product->get_description()))
            ->setShortDescription(\html_entity_decode($product->get_short_description()))
            ->setUrlPath($product->get_slug());

        if (
            (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO))
            && (new Germanized())->hasUnitProduct($product)
        ) {
            $measurementUnitName = (new Germanized())->getUnit($product);
            if (\is_string($measurementUnitName)) {
                $i18n->setMeasurementUnitName($measurementUnitName);
            }
        }

        if (
            SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO_PREMIUM)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_RANK_MATH_SEO)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_RANK_MATH_SEO_AI)
        ) {
            $tmpMeta = (new ProductMetaSeoController($this->db, $this->util))->pullData($product, $model);
            // array<int|string, array<int, string>|int|string>|null
            if (\is_array($tmpMeta)) {
                $this->setI18nSeoData($i18n, $tmpMeta);
                // array<string, array<int, string>|string>
            }
        }

        return $i18n;
    }

    /**
     * @param ProductI18nModel               $i18n
     * @param array<string, string|string[]> $tmpMeta
     * @return void
     */
    protected function setI18nSeoData(ProductI18nModel $i18n, array $tmpMeta): void
    {
        $i18n->setMetaDescription(\is_array($tmpMeta['metaDesc']) ? '' : $tmpMeta['metaDesc'])
            ->setMetaKeywords(\is_array($tmpMeta['keywords']) ? '' : $tmpMeta['keywords'])
            ->setTitleTag(\is_array($tmpMeta['titleTag']) ? '' : $tmpMeta['titleTag'])
            ->setUrlPath(\is_array($tmpMeta['permlink']) ? '' : $tmpMeta['permlink']);
    }

    /**
     * @param WC_Product $product
     * @return string
     * @throws \InvalidArgumentException
     */
    private function name(WC_Product $product): string
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

                    if (!$parent instanceof \WC_Product) {
                        throw new \InvalidArgumentException(
                            "Parent with ID {$product->get_parent_id()} not found."
                        );
                    }

                    return $parent->get_title() . ' ' . \wc_get_formatted_variation($product, true);
                case 'brackets_parent':
                    $parent = \wc_get_product($product->get_parent_id());

                    if (!$parent instanceof \WC_Product) {
                        throw new \InvalidArgumentException(
                            "Parent with ID {$product->get_parent_id()} not found."
                        );
                    }

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
