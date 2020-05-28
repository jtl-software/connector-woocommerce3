<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\WooCommerce;

use jtl\Connector\Model\Product;
use jtl\Connector\Model\ProductI18n as ProductI18nModel;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractComponent;
use JtlWooCommerceConnector\Integrations\Plugins\Germanized\Germanized;
use JtlWooCommerceConnector\Integrations\Plugins\YoastSeo\YoastSeo;

/**
 * Class WooCommerceProduct
 * @package JtlWooCommerceConnector\Integrations\Plugins\WooCommerce
 */
class WooCommerceProduct extends AbstractComponent
{
    /**
     * @param \WC_Product $wcProduct
     * @param Product $jtlProduct
     * @param string $languageIso
     * @return ProductI18nModel
     * @throws \Exception
     */
    public function getI18ns(\WC_Product $wcProduct, Product $jtlProduct, string $languageIso): ProductI18nModel
    {
        $i18n = (new ProductI18nModel())
            ->setProductId($jtlProduct->getId())
            ->setLanguageISO($languageIso)
            ->setName($this->name($wcProduct))
            ->setDescription($wcProduct->get_description())
            ->setShortDescription($wcProduct->get_short_description())
            ->setUrlPath($wcProduct->get_slug());

        $germanized = $this->getPluginsManager()->get(Germanized::class);
        if ($germanized->canBeUsed() && $germanized->hasUnitProduct($wcProduct)) {
            $i18n->setMeasurementUnitName($germanized->getUnit($wcProduct));
        }

        $yoastSeo = $this->getPluginsManager()->get(YoastSeo::class);
        if ($yoastSeo->canBeUsed()) {
            $tmpMeta = $yoastSeo->findProductSeoData($wcProduct);
            if (!empty($tmpMeta) && count($tmpMeta) > 0) {
                $i18n->setMetaDescription(is_array($tmpMeta['metaDesc']) ? '' : $tmpMeta['metaDesc'])
                    ->setMetaKeywords(is_array($tmpMeta['keywords']) ? '' : $tmpMeta['keywords'])
                    ->setTitleTag(is_array($tmpMeta['titleTag']) ? '' : $tmpMeta['titleTag'])
                    ->setUrlPath(is_array($tmpMeta['permlink']) ? '' : $tmpMeta['permlink']);
            }
        }

        return $i18n;
    }


    /**
     * @param \WC_Product $product
     * @return string
     */
    private function name(\WC_Product $product): string
    {
        $name = $product->get_name();
        if ($product instanceof \WC_Product_Variation) {
            switch (\get_option(\JtlConnectorAdmin::OPTIONS_VARIATION_NAME_FORMAT, '')) {
                case 'space':
                    $name = $product->get_name() . ' ' . \wc_get_formatted_variation($product, true);
                    break;
                case 'brackets':
                    $name = sprintf('%s (%s)', $product->get_name(), \wc_get_formatted_variation($product, true));
                    break;
                case 'space_parent':
                    $parent = \wc_get_product($product->get_parent_id());
                    $name = $parent->get_title() . ' ' . \wc_get_formatted_variation($product, true);
                    break;
                case 'brackets_parent':
                    $parent = \wc_get_product($product->get_parent_id());
                    $name = sprintf('%s (%s)', $parent->get_title(), \wc_get_formatted_variation($product, true));
                    break;
            }
        }

        return $name;
    }
}
