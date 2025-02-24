<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Utilities;

use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;
use phpmock\MockBuilder;
use phpmock\MockEnabledException;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

class UtilTest extends TestCase
{
    /** @var array<int, mixed> */
    protected array $mockedFunctions = [];

    /**
     * @dataProvider bulkPricesProvider
     *
     * @param array<int, int|float|string>             $bulkPricesInput
     * @param array<int, array<int, int|float|string>> $expectedOutput
     * @covers Util::setBulkPricesQuantityTo
     * @return void
     */
    public function testSetBulkPricesQuantityTo(array $bulkPricesInput, array $expectedOutput): void
    {
        $bulkPrices = [];
        foreach ($bulkPricesInput as $quantityFrom) {
            $bulkPrices[] = [
                'bulk_price_from' => $quantityFrom,
                'bulk_price_to' => ''
            ];
        }
        $expected = [];
        foreach ($expectedOutput as $quantity) {
            $expected[] = [
                'bulk_price_from' => $quantity[0],
                'bulk_price_to' => $quantity[1]
            ];
        }

        $actual = Util::setBulkPricesQuantityTo($bulkPrices);

        $this->assertSame($expected, $actual);
    }

    /**
     * @return array<int, array<int, array<int, array<int, string>|int|float|string>>>
     */
    public function bulkPricesProvider(): array
    {
        return [
            [
                [],
                []
            ],
            [
                [0],
                [['0', '']]
            ],
            [
                [20],
                [['20', '']]
            ],
            [
                [20, 40],
                [['20', '39'], ['40', '']]
            ],
            [
                [20, '40', 0],
                [['0', '19'], ['20', '39'], ['40', '']]
            ],
            [
                [2.5, 2.5],
                [['2.5', '1.5'], ['2.5', '']]
            ],
            [
                ['10', '1', '5'],
                [['1', '4'], ['5', '9'], ['10', '']]
            ],
            [
                [11, 1, 5, 10, 15],
                [['1', '4'], ['5', '9'], ['10', '10'], ['11', '14'], ['15', '']]
            ]
        ];
    }

    /**
     * @return void
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws MockEnabledException
     * @covers Util::findVatId
     */
    public function testFindVatId(): void
    {
        $expectedVatId = 'DE123456789';
        $returnOnKeys  = ['_billing_vat_id' => $expectedVatId, '_shipping_vat_id' => 'DE0000000'];

        $getMetaField = function ($id, $metaKey) use ($expectedVatId, $returnOnKeys) {
            return \in_array($metaKey, \array_keys($returnOnKeys)) ? $returnOnKeys[$metaKey] : false;
        };

        $enabledPlugins = [
            'woocommerce-germanized-pro/woocommerce-germanized-pro.php'
            => ['Name' => SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO],
            'b2b-market/b2b-market.php' => ['Name' => SupportedPlugins::PLUGIN_B2B_MARKET],
        ];
        $this->enablePlugins($enabledPlugins);

        $vatPlugins = [
            'b2b_uid' => SupportedPlugins::PLUGIN_B2B_MARKET,
            'billing_vat' => SupportedPlugins::PLUGIN_GERMAN_MARKET,
            '_billing_vat_id' => SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO,
            '_shipping_vat_id' => SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO,
        ];

        $foundVatId = Util::findVatId(1, $vatPlugins, $getMetaField);

        $this->assertSame($expectedVatId, $foundVatId);
    }

    /**
     * @return void
     * @throws ExpectationFailedException
     * @throws \InvalidArgumentException
     * @throws InvalidArgumentException
     * @throws MockEnabledException
     * @covers Util::findVatId
     */
    public function testFindVatIdNotFound(): void
    {
        $getMetaField = function ($id, $metaKey) {
            return false;
        };

        $enabledPlugins = [
            'woocommerce-germanized-pro/woocommerce-germanized-pro.php'
            => ['Name' => SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO],
            'b2b-market/b2b-market.php' => ['Name' => SupportedPlugins::PLUGIN_B2B_MARKET],
        ];
        $this->enablePlugins($enabledPlugins);

        $vatPlugins = [
            'b2b_uid' => SupportedPlugins::PLUGIN_B2B_MARKET,
            'billing_vat' => SupportedPlugins::PLUGIN_GERMAN_MARKET,
            '_billing_vat_id' => SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO,
            '_shipping_vat_id' => SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO,
        ];

        $foundVatId = Util::findVatId(1, $vatPlugins, $getMetaField);

        $this->assertSame('', $foundVatId);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        foreach ($this->mockedFunctions as $function) {
            $function->disable();
        }

        parent::tearDown();
    }

    /**
     * @param mixed $enabledPlugins
     * @return void
     * @throws \phpmock\MockEnabledException|\InvalidArgumentException
     */
    protected function enablePlugins(mixed $enabledPlugins): void
    {
        $builder    = new MockBuilder();
        $getPlugins = $builder->setNamespace('JtlWooCommerceConnector\Utilities')
            ->setName('get_plugins')
            ->setFunction(function () use ($enabledPlugins) {
                return $enabledPlugins;
            })->build();

        $getPlugins->enable();
        $this->mockedFunctions[] = $getPlugins;

        $getPlugins = $builder->setNamespace('JtlWooCommerceConnector\Utilities')
            ->setName('is_plugin_active')
            ->setFunction(function () {
                return true;
            })->build();

        $getPlugins->enable();
        $this->mockedFunctions[] = $getPlugins;
    }

    /**
     * @dataProvider getDecimalPrecisionDataProvider
     *
     * @param float $number
     * @param int   $expectedPrecision
     * @covers Util::getDecimalPrecision
     * @return void
     */
    public function testGetDecimalPrecision(float $number, int $expectedPrecision): void
    {
        $precision = Util::getDecimalPrecision($number);
        $this->assertSame($expectedPrecision, $precision);
    }

    /**
     * @return array<int, array<int, int|float>>
     */
    public function getDecimalPrecisionDataProvider(): array
    {
        return [
            [1.123, 3],
            [0.1 + 0.2 - 0.3, 17],
            [1, 2],
            [1.1231, 4],
            [0, 2],
            [1.00004, 5],
            [-1.00004, 5]
        ];
    }

    /**
     * @dataProvider checkIfTrueDataProvider
     * @param string $value
     * @param bool   $expectedResult
     * @covers Util::isTrue
     * @return void
     */
    public function testCheckIfTrue(string $value, bool $expectedResult): void
    {
        $this->assertSame($expectedResult, Util::isTrue($value));
    }

    /**
     * @return array<int, array<int, bool|string>>
     */
    public function checkIfTrueDataProvider(): array
    {
        return [
            ['1', true],
            ['0', false],
            ['', false],
            [' ', false],
            [' 1', true],
            ['YeS', true],
            ['no', false],
            ['false', false],
            [' TruE ', true],
        ];
    }

    /**
     * @dataProvider mapLanguageIsoDataProvider
     * @param string $locale
     * @param string $expectedResult
     * @return void
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws \Exception
     * @covers Util::mapLanguageIso
     */
    public function testMapLanguageIso(string $locale, string $expectedResult): void
    {
        $iso = Util::mapLanguageIso($locale);
        $this->assertEquals($expectedResult, $iso);
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function mapLanguageIsoDataProvider(): array
    {
        return [
            ['de_de', 'de'],
            ['de', 'de'],
            ['de_ch', 'de']
        ];
    }
}
