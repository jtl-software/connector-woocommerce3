<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller\GlobalData;

use jtl\Connector\Model\Currency as CurrencyModel;
use jtl\Connector\Model\GlobalData;
use jtl\Connector\Model\Identity;
use jtl\Connector\WooCommerce\Controller\BaseController;

class Currency extends BaseController
{
    public function pullData()
    {
        return
            (new CurrencyModel())
                ->setId(new Identity(strtolower(\get_woocommerce_currency())))
                ->setName(\get_woocommerce_currency())
                ->setDelimiterCent(\get_option('woocommerce_price_thousand_sep', ''))
                ->setDelimiterThousand(\get_option('woocommerce_price_decimal_sep', ''))
                ->setIsDefault(true)
                ->setIso(\get_woocommerce_currency())
                ->setNameHtml(\get_woocommerce_currency_symbol())
                ->setHasCurrencySignBeforeValue(\get_option('woocommerce_currency_pos', '') === 'left');
    }

    public function pushData(GlobalData $globalData)
    {
        foreach ($globalData->getCurrencies() as $currency) {
            if (!$currency->getIsDefault()) {
                continue;
            }

            \update_option('woocommerce_currency', $currency->getIso(), 'yes');
            \update_option('woocommerce_price_decimal_sep', $currency->getDelimiterCent(), 'yes');
            \update_option('woocommerce_price_thousand_sep', $currency->getDelimiterThousand(), 'yes');
            \update_option('woocommerce_currency_pos', $currency->getHasCurrencySignBeforeValue() ? 'left' : 'right', 'yes');

            break;
        }
    }
}
