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
use JtlWooCommerceConnector\Utilities\Germanized;

class GlobalData extends BaseController
{
    use PullTrait, PushTrait;

    public function pullData($limit)
    {
        $globalData = (new GlobalDataModel())
            ->addCurrency((new Currency())->pullData())
            ->addCustomerGroup((new CustomerGroup())->pullData())
            ->addLanguage((new Language())->pullData())
            ->setProductTypes((new ProductType())->pullData())
            ->setShippingClasses((new ShippingClass())->pullData())
            ->setShippingMethods((new ShippingMethod())->pullData())
            ->setTaxRates((new TaxRate())->pullData());

        if (Germanized::getInstance()->isActive()) {
            $globalData->setMeasurementUnits((new MeasurementUnit())->pullData());
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
