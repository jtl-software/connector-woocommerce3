<?php

namespace JtlWooCommerceConnector\Controllers;

use Exception;
use Jtl\Connector\Core\Controller\PullInterface;
use Jtl\Connector\Core\Controller\PushInterface;
use Jtl\Connector\Core\Model\AbstractModel;
use jtl\Connector\Core\Model\CustomerGroup as CustomerGroupModel;
use Jtl\Connector\Core\Model\GlobalData as GlobalDataModel;
use Jtl\Connector\Core\Model\QueryFilter;
use JtlWooCommerceConnector\Controllers\GlobalData\CrossSellingGroups;
use JtlWooCommerceConnector\Controllers\GlobalData\CurrencyController;
use JtlWooCommerceConnector\Controllers\GlobalData\CustomerGroupController;
use JtlWooCommerceConnector\Controllers\GlobalData\LanguageController;
use JtlWooCommerceConnector\Controllers\GlobalData\MeasurementUnitController;
use JtlWooCommerceConnector\Controllers\GlobalData\ProductTypeController;
use JtlWooCommerceConnector\Controllers\GlobalData\ShippingClassController;
use JtlWooCommerceConnector\Controllers\GlobalData\ShippingMethodController;
use JtlWooCommerceConnector\Controllers\GlobalData\TaxRateController;
use JtlWooCommerceConnector\Utilities\Config;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use Psr\Log\InvalidArgumentException;

class GlobalDataController extends AbstractBaseController implements PullInterface, PushInterface
{
    /**
     * @return array<GlobalDataModel>
     * @throws Exception
     */
    public function pull(QueryFilter $query): array
    {
        $globalData = (new GlobalDataModel())
            ->setCurrencies(...(new CurrencyController($this->db, $this->util))->pull())
            ->setLanguages(...(new LanguageController($this->db, $this->util))->pull())
            ->setProductTypes(...(new ProductTypeController())->pull())
            ->setShippingClasses(...(new ShippingClassController($this->db, $this->util))->pull())
            ->setShippingMethods(...(new ShippingMethodController())->pull())
            ->setCrossSellingGroups(...(new CrossSellingGroups($this->db, $this->util))->pull())
            ->setTaxRates(...(new TaxRateController($this->db, $this->util))->pull());

        $hasDefaultCustomerGroup = false;
        foreach ((new CustomerGroupController($this->db, $this->util))->pull() as $group) {
            /** @var $group CustomerGroupModel */
            if ($group->getIsDefault() === true) {
                $hasDefaultCustomerGroup = true;
            }
            $globalData->addCustomerGroup($group);
        }

        if ($hasDefaultCustomerGroup === false) {
            throw new Exception(\__(
                "The default customer is not set. Please update the B2B-Market default customer group "
                . "in the JTL-Connector settings in the Wordpress admin panel.",
                \JTLWCC_TEXT_DOMAIN
            ));
        }

        if (Config::get(Config::OPTIONS_AUTO_WOOCOMMERCE_OPTIONS)) {
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
            && !SupportedPlugins::isActive(SupportedPlugins::PLUGIN_GERMAN_MARKET)
        ) {
            $globalData
                ->setMeasurementUnits(...(new MeasurementUnitController($this->db, $this->util))->pullGermanizedData());
            \update_option('woocommerce_gzd_shipments_auto_order_completed_shipped_enable', 'yes', true);
        }

        if (
            SupportedPlugins::isActive(SupportedPlugins::PLUGIN_GERMAN_MARKET)
            && !(
                SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED)
                || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)
                || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO)
            )
        ) {
            if (Config::get(Config::OPTIONS_AUTO_GERMAN_MARKET_OPTIONS)) {
                //LIEFERZEITEN
                \update_option('woocommerce_global_lieferzeit', '-1', true);
                //update_option('woocommerce_de_show_delivery_time_overview', 'off', true);
                \update_option('woocommerce_de_show_delivery_time_product_page', 'on', true);
                \update_option('woocommerce_de_show_delivery_time_checkout', 'on', true);
                \update_option('woocommerce_de_show_delivery_time_order_summary', 'on', true);

                //GM STREICHPREISE DISABLE
                \update_option('woocommerce_de_show_sale_label_overview', 'off', true);
                \update_option('woocommerce_de_show_sale_label_product_page', 'off', true);

                //PRODUKTE
                \update_option('german_market_attribute_in_product_name', 'off', true);
                \update_option('gm_show_product_attributes', 'off', true);
                \update_option('gm_show_single_price_of_order_items', 'on', true);

                \update_option('german_market_product_images_in_order', 'on', true);
                \update_option('german_market_product_images_in_cart', 'on', true);

                \update_option('gm_gtin_activation', 'on', true);
                \update_option('gm_gtin_product_pages', 'on', true);

                \update_option('woocommerce_de_show_price_per_unit', 'on', true);
                \update_option('woocommerce_de_automatic_calculation_ppu', 'on', true);
                \update_option('woocommerce_de_automatic_calculation_use_wc_weight', 'off', true);
                \update_option(
                    'woocommerce_de_automatic_calculation_use_wc_weight_scale_unit',
                    'kg',
                    true
                );
                \update_option(
                    'woocommerce_de_automatic_calculation_use_wc_weight_mult',
                    '1',
                    true
                );

                //Globale Optionen
                \update_option('wgm_use_split_tax', 'on', true);
                \update_option('gm_gross_shipping_costs_and_fees', 'off', true);
            }
            $globalData->setMeasurementUnits(
                ...(new MeasurementUnitController($this->db, $this->util))->pullGermanMarketData()
            );
        }

        return [$globalData];
    }

    /**
     * @param GlobalDataModel $model
     * @return GlobalDataModel
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function push(AbstractModel $model): AbstractModel
    {
        (new CurrencyController($this->db, $this->util))->push($model->getCurrencies());
        (new ShippingClassController($this->db, $this->util))->push($model->getShippingClasses());

        return $model;
    }
}
