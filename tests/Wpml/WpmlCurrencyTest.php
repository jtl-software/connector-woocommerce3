<?php

namespace JtlWooCommerceConnector\Tests\Wpml;

use JtlWooCommerceConnector\Wpml\WpmlCurrency;
use JtlWooCommerceConnector\Wpml\WpmlUtils;
use phpmock\MockBuilder;
use woocommerce_wpml;

/**
 * Class WpmlCurrencyTest
 * @package JtlWooCommerceConnector\Tests\Wpml
 */
class WpmlCurrencyTest extends WpmlTestCase
{
    /**
     * @throws \phpmock\MockEnabledException
     */
    public function testGetCurrencies()
    {
        $builder = new MockBuilder();
        $defaultCurrency = $builder->setNamespace('JtlWooCommerceConnector\Wpml')
            ->setName('wcml_get_woocommerce_currency_option')
            ->setFunction(function () {
                return 'USD';
            })->build();
        $defaultCurrency->enable();

        $wcmlMock = \Mockery::mock(woocommerce_wpml::class);
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
        $wpmlUtilsMock = \Mockery::mock("alias:" . WpmlUtils::class);
        $wpmlUtilsMock->shouldReceive('getWcml')->andReturn($wcmlMock);

        $currency = new WpmlCurrency();
        $currencies = $currency->getCurrencies();

        $this->assertCount(2, $currencies);
        $this->assertEquals(true, $currencies[1]->getIsDefault());
    }
}