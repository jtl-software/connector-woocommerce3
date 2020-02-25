<?php
namespace JtlWooCommerceConnector\Tests\Utilities;

use JtlWooCommerceConnector\Utilities\Util;
use PHPUnit\Framework\TestCase;

class UtilTest extends TestCase
{
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
}
