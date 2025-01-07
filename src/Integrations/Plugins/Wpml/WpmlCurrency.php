<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Integrations\Plugins\Wpml;

use Jtl\Connector\Core\Model\Currency as CurrencyModel;
use Jtl\Connector\Core\Model\Identity;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractComponent;
use Psr\Log\InvalidArgumentException;
use WPML\Auryn\InjectionException;

/**
 * Class WpmlCurrency
 *
 * @package JtlWooCommerceConnector\Integrations\Plugins\Wpml
 */
class WpmlCurrency extends AbstractComponent
{
    /**
     * @return bool
     * @throws InvalidArgumentException
     */
    public function canUseMultiCurrency(): bool
    {
        /** @var Wpml $wpmlPlugin */
        $wpmlPlugin = $this->plugin;

        return (bool)($this->plugin->canBeUsed() &&
            $wpmlPlugin->isMultiCurrencyEnabled());
    }

    /**
     * @return CurrencyModel[]
     * @throws InjectionException
     */
    public function getCurrencies(): array
    {
        /** @var Wpml $wpmlPlugin */
        $wpmlPlugin = $this->plugin;
        $wcml       = $wpmlPlugin->getWcml();
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
     * @return array<string, array<string, array<int|string, int>|int|float|string>>
     * @throws InjectionException
     */
    public function setCurrencies(CurrencyModel ...$jtlCurrencies): array
    {
        /** @var Wpml $wpmlPlugin */
        $wpmlPlugin = $this->plugin;
        $wcml       = $wpmlPlugin->getWcml();
        $wcml->get_multi_currency()->enable();

        $activeLanguages = $wpmlPlugin->getActiveLanguages();
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
        /** @phpstan-ignore-next-line */
        $wcml->settings['currency_options'] = $wcmlCurrencies;
        $wcml->update_settings();

        return $wcmlCurrencies;
    }
}
