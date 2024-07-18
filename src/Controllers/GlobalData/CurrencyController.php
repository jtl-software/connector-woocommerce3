<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Controllers\GlobalData;

use Exception;
use Jtl\Connector\Core\Model\Currency as CurrencyModel;
use Jtl\Connector\Core\Model\Identity;
use JtlWooCommerceConnector\Controllers\AbstractBaseController;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\Wpml;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlCurrency;

class CurrencyController extends AbstractBaseController
{
    public const ISO                = 'woocommerce_currency';
    public const SIGN_POSITION      = 'woocommerce_currency_pos';
    public const CENT_DELIMITER     = 'woocommerce_price_decimal_sep';
    public const THOUSAND_DELIMITER = 'woocommerce_price_thousand_sep';

    /**
     * @return CurrencyModel[]
     * @throws Exception
     */
    public function pull(): array
    {
        $currencies = [];

        /** @var WpmlCurrency $wpmlCurrency */
        $wpmlCurrency = $this->getPluginsManager()
            ->get(Wpml::class)
            ->getComponent(WpmlCurrency::class);

        if ($wpmlCurrency->canUseMultiCurrency()) {
            $currencies = $wpmlCurrency->getCurrencies();
        } else {
            $iso = \get_woocommerce_currency();

            $currencies[] = (new CurrencyModel())
                ->setId(new Identity(\strtolower($iso)))
                ->setName($iso)
                ->setDelimiterCent(
                    \is_string($centDelimiter = \get_option(self::THOUSAND_DELIMITER, ''))
                        ? $centDelimiter
                        : ''
                )
                ->setDelimiterThousand(
                    \is_string($thousandDelimiter = \get_option(self::CENT_DELIMITER, ''))
                    ? $thousandDelimiter
                    : ''
                )
                ->setIso($iso)
                ->setNameHtml(\get_woocommerce_currency_symbol())
                ->setHasCurrencySignBeforeValue(\get_option(self::SIGN_POSITION, '') === 'left')
                ->setIsDefault(true);
        }

        return $currencies;
    }

    /**
     * @param CurrencyModel[] $currencies
     * @return CurrencyModel[]
     * @throws Exception
     */
    public function push(array $currencies): array
    {
        foreach ($currencies as $currency) {
            if (!$currency->getIsDefault()) {
                continue;
            }

            \update_option(self::ISO, $currency->getIso(), true);
            \update_option(self::CENT_DELIMITER, $currency->getDelimiterCent(), true);
            \update_option(self::THOUSAND_DELIMITER, $currency->getDelimiterThousand(), true);
            \update_option(
                self::SIGN_POSITION,
                $currency->getHasCurrencySignBeforeValue() ? 'left' : 'right',
                true
            );

            break;
        }

        $wpml = $this->getPluginsManager()->get(Wpml::class);

        if ($wpml->canBeUsed()) {
            /** @var WpmlCurrency $wpmlCurrency */
            $wpmlCurrency = $wpml->getComponent(WpmlCurrency::class);
            $wpmlCurrency->setCurrencies(...$currencies);
        }

        return $currencies;
    }
}
