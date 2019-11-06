<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Product;

use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\Model\ProductI18n as ProductI18nModel;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Utilities\Germanized;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;

class ProductI18n extends BaseController
{
    public function pullData(\WC_Product $product, ProductModel $model)
    {
        $i18n = (new ProductI18nModel())
            ->setProductId($model->getId())
            ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage())
            ->setName($this->name($product))
            ->setDescription($product->get_description())
            ->setShortDescription($product->get_short_description())
            ->setUrlPath($product->get_slug());
        
        if ((SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO))
            && Germanized::getInstance()->hasUnitProduct($product)) {
            $i18n->setMeasurementUnitName(Germanized::getInstance()->getUnit($product));
        }
        
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO_PREMIUM)) {
            $tmpMeta = ProductMetaSeo::getInstance()->pullData($product, $model);
            if ( ! is_null($tmpMeta) && count($tmpMeta) > 0) {
                /*   'title'
                   'metaDesc'
                   'keywords'
                   'permlink'
                */
                $i18n->setMetaDescription(is_array($tmpMeta['metaDesc']) ? '' : $tmpMeta['metaDesc'])
                     ->setMetaKeywords(is_array($tmpMeta['keywords']) ? '' : $tmpMeta['keywords'])
                     ->setTitleTag(is_array($tmpMeta['titleTag']) ? '' : $tmpMeta['titleTag'])
                     ->setUrlPath(is_array($tmpMeta['permlink']) ? '' : $tmpMeta['permlink']);
            }
        }
        
        return $i18n;
    }
    
    private function name(\WC_Product $product)
    {
        if ($product instanceof \WC_Product_Variation) {
            switch (\get_option(\JtlConnectorAdmin::OPTIONS_VARIATION_NAME_FORMAT, '')) {
                case 'space':
                    return $product->get_name() . ' ' . \wc_get_formatted_variation($product, true);
                case 'brackets':
                    return sprintf('%s (%s)', $product->get_name(), \wc_get_formatted_variation($product, true));
                case 'space_parent':
                    $parent = \wc_get_product($product->get_parent_id());
                    
                    return $parent->get_title() . ' ' . \wc_get_formatted_variation($product, true);
                case 'brackets_parent':
                    $parent = \wc_get_product($product->get_parent_id());
                    
                    return sprintf('%s (%s)', $parent->get_title(), \wc_get_formatted_variation($product, true));
            }
        }
        
        return $product->get_name();
    }
}
