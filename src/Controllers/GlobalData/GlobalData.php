<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\GlobalData;

use jtl\Connector\Model\GlobalData as GlobalDataModel;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Controllers\Traits\PullTrait;
use JtlWooCommerceConnector\Controllers\Traits\PushTrait;
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
            ->setTaxRates((new TaxRate())->pullData());
        
        foreach ((new CustomerGroup)->pullData() as $group){
            $globalData->addCustomerGroup($group);
        }
        
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED)) {
            $globalData->setMeasurementUnits((new MeasurementUnit)->pullGermanizedData());
        }
        
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_GERMAN_MARKET)) {
            //Wawi steuert Grundpreisberechnung
            update_option('woocommerce_de_automatic_calculation_ppu', 'off', true);
            //Wawi steuert Lieferzeiten
            update_option('woocommerce_global_lieferzeit', '-1', true);
            
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
