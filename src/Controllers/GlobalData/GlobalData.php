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
use JtlWooCommerceConnector\Integrations\Plugins\Germanized\Germanized;
use JtlWooCommerceConnector\Integrations\Plugins\GermanMarket\GermanMarket;
use JtlWooCommerceConnector\Utilities\Config;

/**
 * Class GlobalData
 * @package JtlWooCommerceConnector\Controllers\GlobalData
 */
class GlobalData extends BaseController
{
    use PullTrait, PushTrait;

    /**
     * @return array
     * @throws \jtl\Connector\Core\Exception\LanguageException
     */
    public function pullData()
    {
        $globalData = (new GlobalDataModel())
            ->setCurrencies((new Currency())->pullData())
            ->setLanguages((new Language())->pullData())
            ->setProductTypes((new ProductType())->pullData())
            ->setShippingClasses((new ShippingClass())->pullData())
            ->setShippingMethods((new ShippingMethod())->pullData())
            ->setCrossSellingGroups((new CrossSellingGroups())->pullData())
            ->setTaxRates((new TaxRate())->pullData());

        foreach ((new CustomerGroup)->pullData() as $group) {
            $globalData->addCustomerGroup($group);
        }

        if ($this->getPluginsManager()->get(Germanized::class)->canBeUsed() && !$this->getPluginsManager()->get(GermanMarket::class)->canBeUsed()) {
            $globalData->setMeasurementUnits((new MeasurementUnit)->pullGermanizedData());
        }

        if ($this->getPluginsManager()->get(GermanMarket::class)->canBeUsed() && !$this->getPluginsManager()->get(Germanized::class)->canBeUsed()) {
            if (Config::get(JtlConnectorAdmin::OPTIONS_AUTO_GERMAN_MARKET_OPTIONS)) {
                $this->getPluginsManager()->get(GermanMarket::class)->setAutoOptions();
            }
            $globalData->setMeasurementUnits((new MeasurementUnit)->pullGermanMarketData());
        }

        return [$globalData];
    }

    /**
     * @param GlobalDataModel $data
     * @return GlobalDataModel
     * @throws \Exception
     */
    public function pushData(GlobalDataModel $data)
    {
        (new Currency)->pushData($data->getCurrencies());
        (new ShippingClass)->pushData($data->getShippingClasses());

        return $data;
    }
}
