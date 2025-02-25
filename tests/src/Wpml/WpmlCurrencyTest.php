<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Wpml;

use Jtl\Connector\Core\Model\Currency;
use Jtl\Connector\Core\Model\Identity;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\Wpml;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlCurrency;
use JtlWooCommerceConnector\Tests\TestCase;
use Mockery\Exception\RuntimeException;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\ExpectationFailedException;
use SebastianBergmann\RecursionContext\InvalidArgumentException;
use woocommerce_wpml;
use WPML\Auryn\InjectionException;

/**
 * Class WpmlCurrencyTest
 *
 * @package JtlWooCommerceConnector\Tests\Wpml
 */
class WpmlCurrencyTest extends TestCase
{
    /**
     * @throws \phpmock\MockEnabledException
     * @throws InjectionException
     * @covers WpmlCurrency::getCurrencies
     * @return void
     */
    public function testGetCurrencies(): void
    {
        $wcmlMock = \Mockery::mock(woocommerce_wpml::class);
        $wcmlMock->shouldReceive('get_multi_currency->get_default_currency')
            ->andReturn("USD");
        $wcmlMock->shouldReceive('get_multi_currency->get_currencies')
            ->andReturn([
                'EUR' => [
                    'languages' => [
                        'en' => 1,
                        'de' => 1
                    ],
                    'rate' => 0,
                    'position' => 'right',
                    'thousand_sep' => '.',
                    'decimal_sep' => ',',
                    'num_decimals' => '2',
                    'rounding' => 'disabled',
                    'rounding_increment' => 1,
                    'auto_subtract' => 0,
                ],
                'USD' => [
                    'languages' => [
                        'en' => 1,
                        'de' => 1
                    ],
                    'rate' => 0,
                    'position' => 'left',
                    'thousand_sep' => '.',
                    'decimal_sep' => ',',
                    'num_decimals' => '2',
                    'rounding' => 'disabled',
                    'rounding_increment' => 1,
                    'auto_subtract' => 0,
                ],

            ]);
        $wpmlPluginMock = \Mockery::mock(Wpml::class);
        $wpmlPluginMock->shouldReceive('getWcml')->andReturn($wcmlMock);

        $currency = new WpmlCurrency();
        $currency->setPlugin($wpmlPluginMock);
        $currencies = $currency->getCurrencies();

        $this->assertCount(2, $currencies);
        $this->assertEquals(true, $currencies[1]->getIsDefault());
    }

    /**
     * @throws RuntimeException
     * @throws ExpectationFailedException
     * @throws InjectionException
     * @throws \ReflectionException
     * @throws InvalidArgumentException
     * @throws Exception
     * @covers WpmlCurrency::setCurrencies
     * @return void
     */
    public function testSetCurrencies(): void
    {
        $wcmlMock = \Mockery::mock(woocommerce_wpml::class);
        $wcmlMock->shouldReceive('get_multi_currency->enable');
        $wcmlMock->shouldReceive('update_settings');

        $wpmlPluginMock = \Mockery::mock(Wpml::class);
        $wpmlPluginMock->shouldReceive('getWcml')->andReturn($wcmlMock);
        $wpmlPluginMock->shouldReceive('getActiveLanguages')->andReturn([
            'en' => [
                'code' => 'en'
            ],
            'de' => [
                'code' => 'de'
            ]
        ]);

        $jtlCurrencies = [
            (new Currency())->setId(new Identity(\strtolower('PLN')))
                ->setName('PLN')
                ->setDelimiterCent(',')
                ->setDelimiterThousand('.')
                ->setIso('PLN')
                ->setFactor((float)4.5)
                ->setNameHtml('PLN')
                ->setHasCurrencySignBeforeValue(false)
                ->setIsDefault(false),
            (new Currency())->setId(new Identity(\strtolower('EUR')))
                ->setName('EUR')
                ->setDelimiterCent(',')
                ->setDelimiterThousand('.')
                ->setIso('EUR')
                ->setFactor((float)1)
                ->setNameHtml('EUR')
                ->setHasCurrencySignBeforeValue(false)
                ->setIsDefault(true),
        ];

        $currency = new WpmlCurrency();
        $currency->setPlugin($wpmlPluginMock);
        $currencies = $currency->setCurrencies(...$jtlCurrencies);

        $this->assertCount(2, $currencies);
        $this->assertEquals(4.5, $currencies['PLN']['rate']);
    }
}
