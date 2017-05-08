<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller\GlobalData;

use jtl\Connector\Model\GlobalData as GlobalDataModel;
use jtl\Connector\WooCommerce\Controller\BaseController;
use jtl\Connector\WooCommerce\Controller\Traits\PullTrait;
use jtl\Connector\WooCommerce\Controller\Traits\PushTrait;
use jtl\Connector\WooCommerce\Utility\UtilGermanized;

class GlobalData extends BaseController
{
    use PullTrait, PushTrait;

    public function pullData($limit)
    {
        $globalData = (new GlobalDataModel())
            ->addCurrency(Currency::getInstance()->pullData())
            ->addCustomerGroup(CustomerGroup::getInstance()->pullData())
            ->addLanguage(Language::getInstance()->pullData())
            ->setProductTypes(ProductType::getInstance()->pullData())
            ->setShippingClasses(ShippingClass::getInstance()->pullData())
            ->setShippingMethods(ShippingMethod::getInstance()->pullData())
            ->setTaxRates(TaxRate::getInstance()->pullData());

        if (UtilGermanized::getInstance()->isActive()) {
            $globalData->setMeasurementUnits(MeasurementUnit::getInstance()->pullData());
        }

        return [$globalData];
    }

    public function pushData(GlobalDataModel $data)
    {
        Currency::getInstance()->pushData($data->getCurrencies());
        ShippingClass::getInstance()->pushData($data->getShippingClasses());

        return $data;
    }
}
