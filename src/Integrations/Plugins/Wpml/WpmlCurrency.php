<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\Wpml;

use Jtl\Connector\Core\Model\Currency as CurrencyModel;
use Jtl\Connector\Core\Model\Identity;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractComponent;

/**
 * Class WpmlCurrency
 * @package JtlWooCommerceConnector\Integrations\Plugins\Wpml
 */
class WpmlCurrency extends AbstractComponent
{
    /**
     * @return bool
     */
    public function canUseMultiCurrency(): bool
    {
        return (bool)($this->plugin->canBeUsed() &&
            $this->plugin->isMultiCurrencyEnabled());
    }

    /**
     * @return CurrencyModel[]
     */
    public function getCurrencies(): array
    {
        $wcml       = $this->plugin->getWcml();
        $currencies = $wcml->get_multi_currency()->get_currencies(true);

        $defaultCurrencyIso = $wcml->get_multi_currency()->get_default_currency();
        $jtlCurrencies      = [];

        foreach ($currencies as $currencyIso => $currency) {
            $jtlCurrencies[] = (new CurrencyModel())
                ->setId(new Identity(\strtolower($currencyIso)))
                ->setName($currencyIso)
                ->setDelimiterCent($currency['decimal_sep'])
                ->setDelimiterThousand($currency['thousand_sep'])
                ->setIso($currencyIso)
                ->setFactor((float)$currency['rate'])
                ->setNameHtml($currencyIso)
                ->setHasCurrencySignBeforeValue($currency['position'] === 'left')
                ->setIsDefault($defaultCurrencyIso === $currencyIso);
        }

        return $jtlCurrencies;
    }

    /**
     * @param CurrencyModel ...$jtlCurrencies
     * @return array
     */
    public function setCurrencies(CurrencyModel ...$jtlCurrencies): array
    {
        $wcml = $this->plugin->getWcml();
        $wcml->get_multi_currency()->enable();

        $activeLanguages = $this->plugin->getActiveLanguages();
        $languages       = [];
        foreach ($activeLanguages as $activeLanguage) {
            $languages[$activeLanguage['code']] = 1;
        }

        $wcmlCurrencies = [];

        foreach ($jtlCurrencies as $currency) {
            $wcmlCurrencies[$currency->getIso()] = [
                'rate' => $currency->getFactor(),
                'position' => $currency->getHasCurrencySignBeforeValue() === true ? 'left' : 'right',
                'thousand_sep' => $currency->getDelimiterThousand(),
                'decimal_sep' => $currency->getDelimiterCent(),
                'num_decimals' => 2,
                'rounding' => 'disabled',
                'rounding_increment' => 1,
                'auto_subtract' => 0,
                'languages' => $languages
            ];
        }

        $wcml->settings['currency_options'] = $wcmlCurrencies;
        $wcml->update_settings();

        return $wcmlCurrencies;
    }
}
