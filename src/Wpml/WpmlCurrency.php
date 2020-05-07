<?php
namespace JtlWooCommerceConnector\Wpml;

use jtl\Connector\Model\Currency;
use jtl\Connector\Model\Identity;

/**
 * Class WpmlCurrency
 * @package JtlWooCommerceConnector\Wpml
 */
class WpmlCurrency
{

    /**
     * @return bool
     */
    public static function canUseMultiCurrency()
    {
        return
            WpmlUtils::canUseWcml() &&
            WpmlUtils::isMultiCurrencyEnabled();
    }

    /**
     * @return Currency[]
     */
    public function getCurrencies(): array
    {
        $jtlCurrencies = [];

        $wcml = WpmlUtils::getWcml();
        $currencies = $wcml->multi_currency->get_currencies('include_default = true');

        $defaultCurrencyIso = \get_woocommerce_currency();

        foreach($currencies as $currencyIso => $currency){
            $jtlCurrencies[] = (new Currency())
                ->setId(new Identity(strtolower($currencyIso)))
                ->setName($currencyIso)
                ->setDelimiterCent($currency['decimal_sep'])
                ->setDelimiterThousand($currency['thousand_sep'])
                ->setIso($currencyIso)
                ->setNameHtml($currencyIso)
                ->setHasCurrencySignBeforeValue($currency['position'] === 'left')
                ->setIsDefault($defaultCurrencyIso === $currencyIso);
        }

        return $jtlCurrencies;
    }
}