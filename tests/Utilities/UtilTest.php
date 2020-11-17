<?php
namespace JtlWooCommerceConnector\Tests\Utilities;

use JtlWooCommerceConnector\Tests\TestCase;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;
use phpmock\MockBuilder;

class UtilTest extends TestCase
{
    /**
     * @var array
     */
    protected $mockedFunctions = [];

    /**
     * @dataProvider bulkPricesProvider
     *
     * @param $bulkPricesInput
     * @param $expectedOutput
     */
    public function testSetBulkPricesQuantityTo($bulkPricesInput, $expectedOutput)
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
     * @return array
     */
    public function bulkPricesProvider()
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
     *
     */
    public function testFindVatId()
    {
        $expectedVatId = 'DE123456789';
        $returnOnKeys = ['_billing_vat_id' => $expectedVatId, '_shipping_vat_id' => 'DE0000000'];

        $getMetaField = function ($id, $metaKey) use ($expectedVatId, $returnOnKeys) {
            return in_array($metaKey,array_keys($returnOnKeys)) ? $returnOnKeys[$metaKey] : false;
        };

        $enabledPlugins = [
            'woocommerce-germanized-pro/woocommerce-germanized-pro.php' => ['Name' => SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO],
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
     *
     */
    public function testFindVatIdNotFound()
    {
        $getMetaField = function ($id, $metaKey){
            return false;
        };

        $enabledPlugins = [
            'woocommerce-germanized-pro/woocommerce-germanized-pro.php' => ['Name' => SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO],
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
     *
     */
    protected function tearDown()
    {
        foreach ($this->mockedFunctions as $function) {
            $function->disable();
        }

        parent::tearDown();
    }

    /**
     * @param $enabledPlugins
     * @throws \phpmock\MockEnabledException
     */
    protected function enablePlugins($enabledPlugins)
    {
        $builder = new MockBuilder();
        $getPlugins = $builder->setNamespace('JtlWooCommerceConnector\Utilities')
            ->setName('get_plugins')
            ->setFunction(function () use ($enabledPlugins) {
                return $enabledPlugins;
            })->build();

        $getPlugins->enable();
        $this->mockedFunctions[] = $getPlugins;

        $getActiveAndValidPlugins = $builder->setNamespace('JtlWooCommerceConnector\Utilities')
            ->setName('wp_get_active_and_valid_plugins')
            ->setFunction(function () use ($enabledPlugins) {
                return array_keys($enabledPlugins);
            })->build();

        $getActiveAndValidPlugins->enable();
        $this->mockedFunctions[] = $getActiveAndValidPlugins;
    }
}
