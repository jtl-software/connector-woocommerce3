<?php

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
                ->setDelimiterCent(\get_option(self::THOUSAND_DELIMITER, ''))
                ->setDelimiterThousand(\get_option(self::CENT_DELIMITER, ''))
                ->setIso($iso)
                ->setNameHtml(\get_woocommerce_currency_symbol())
                ->setHasCurrencySignBeforeValue(\get_option(self::SIGN_POSITION, '') === 'left')
                ->setIsDefault(true);
        }

        return $currencies;
    }

    /**
     * @param array $currencies
     * @return array
     * @throws Exception
     */
    public function push(array $currencies): array
    {
        /** @var CurrencyModel $currency */
        foreach ($currencies as $currency) {
            if (!$currency->getIsDefault()) {
                continue;
            }

            \update_option(self::ISO, $currency->getIso(), 'yes');
            \update_option(self::CENT_DELIMITER, $currency->getDelimiterCent(), 'yes');
            \update_option(self::THOUSAND_DELIMITER, $currency->getDelimiterThousand(), 'yes');
            \update_option(
                self::SIGN_POSITION,
                $currency->getHasCurrencySignBeforeValue() ? 'left' : 'right',
                'yes'
            );

            break;
        }

        $wpml = $this->getPluginsManager()->get(Wpml::class);

        if ($wpml->canBeUsed()) {
            $wpml->getComponent(WpmlCurrency::class)->setCurrencies(...$currencies);
        }

        return $currencies;
    }
}
