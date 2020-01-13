<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\GlobalData;

use jtl\Connector\Model\GlobalData as GlobalDataModel;
use JtlConnectorAdmin;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Controllers\Traits\PullTrait;
use JtlWooCommerceConnector\Controllers\Traits\PushTrait;
use JtlWooCommerceConnector\Utilities\Config;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;

class GlobalData extends BaseController
{
    use PullTrait, PushTrait;
    
    public function pullData()
    {
        $globalData = (new GlobalDataModel())
            ->addCurrency((new Currency())->pullData())
            ->addLanguage((new Language())->pullData())
            ->setProductTypes((new ProductType())->pullData())
            ->setShippingClasses((new ShippingClass())->pullData())
            ->setShippingMethods((new ShippingMethod())->pullData())
            ->setCrossSellingGroups((new CrossSellingGroups())->pullData())
            ->setTaxRates((new TaxRate())->pullData());
        
        foreach ((new CustomerGroup)->pullData() as $group) {
            $globalData->addCustomerGroup($group);
        }
        
        if (Config::get(JtlConnectorAdmin::OPTIONS_AUTO_WOOCOMMERCE_OPTIONS)) {
            //Wawi überträgt Netto
            //   \update_option('woocommerce_prices_include_tax', 'no', true);
            //Preise im Shop mit hinterlegter Steuer
            // \update_option('woocommerce_tax_display_shop', 'incl', true);   //MOVED PROD PUSH
            //Preise im Cart mit hinterlegter Steuer
            //\update_option('woocommerce_tax_display_cart', 'incl', true);
            
            /*\update_option('woocommerce_dimension_unit', 'cm', true);
            \update_option('woocommerce_weight_unit', 'kg', true);*/
        }
        
        if (
            (
                SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED)
                || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)
                || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO)
            )
            && !SupportedPlugins::isActive(SupportedPlugins::PLUGIN_GERMAN_MARKET)) {
            $globalData->setMeasurementUnits((new MeasurementUnit)->pullGermanizedData());
        }
        
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_GERMAN_MARKET)
            && !(
                SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED)
                || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)
                || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO)
            )
        ) {
            if (Config::get(JtlConnectorAdmin::OPTIONS_AUTO_GERMAN_MARKET_OPTIONS)) {
                //LIEFERZEITEN
                update_option('woocommerce_global_lieferzeit', '-1', true);
                //update_option('woocommerce_de_show_delivery_time_overview', 'off', true);
                update_option('woocommerce_de_show_delivery_time_product_page', 'on', true);
                update_option('woocommerce_de_show_delivery_time_checkout', 'on', true);
                update_option('woocommerce_de_show_delivery_time_order_summary', 'on', true);
                
                //GM STREICHPREISE DISABLE
                update_option('woocommerce_de_show_sale_label_overview', 'off', true);
                update_option('woocommerce_de_show_sale_label_product_page', 'off', true);
                
                //PRODUKTE
                update_option('german_market_attribute_in_product_name', 'off', true);
                update_option('gm_show_product_attributes', 'off', true);
                update_option('gm_show_single_price_of_order_items', 'on', true);
                
                update_option('german_market_product_images_in_order', 'on', true);
                update_option('german_market_product_images_in_cart', 'on', true);
                
                update_option('gm_gtin_activation', 'on', true);
                update_option('gm_gtin_product_pages', 'on', true);
                
                update_option('woocommerce_de_show_price_per_unit', 'on', true);
                update_option('woocommerce_de_automatic_calculation_ppu', 'on', true);
                update_option('woocommerce_de_automatic_calculation_use_wc_weight', 'off', true);
                update_option('woocommerce_de_automatic_calculation_use_wc_weight_scale_unit', 'kg', true);
                update_option('woocommerce_de_automatic_calculation_use_wc_weight_mult', '1', true);
                
                //Globale Optionen
                update_option('wgm_use_split_tax', 'on', true);
                update_option('gm_gross_shipping_costs_and_fees', 'off', true);
            }
            $globalData->setMeasurementUnits((new MeasurementUnit)->pullGermanMarketData());
        }
        
        return [$globalData];
    }
    
    public function pushData(GlobalDataModel $data)
    {
        (new Currency)->pushData($data->getCurrencies());
        (new ShippingClass)->pushData($data->getShippingClasses());
        
        return $data;
    }
}
