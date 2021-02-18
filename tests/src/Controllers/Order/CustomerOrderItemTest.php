<?php

namespace JtlWooCommerceConnector\Tests\Controllers\Order;

use JtlWooCommerceConnector\Controllers\Order\CustomerOrderItem;
use JtlWooCommerceConnector\Tests\AbstractTestCase;

/**
 * Class CustomerOrderItemTest
 * @package JtlWooCommerceConnector\Tests\Controllers\Order
 */
class CustomerOrderItemTest extends AbstractTestCase
{
    /**
     * @dataProvider calculateVatDataProvider
     *
     * @param float $priceNet
     * @param float $priceGross
     * @param float $expectedVatRate
     * @throws \ReflectionException
     */
    public function testCalculateVat(float $priceNet, float $priceGross, float $expectedVatRate)
    {
        $grossPricePrecision = strlen(substr((string)$priceGross, strpos((string)$priceGross, '.'))) - 1;
        $grossPricePrecision = $grossPricePrecision < 2 ? 2 : $grossPricePrecision;

        $vatRate = $this->invokeMethodFromObject(new CustomerOrderItem(), 'calculateVat', $priceNet, $priceGross, $grossPricePrecision);
        $this->assertEquals($expectedVatRate, $vatRate);
    }

    /**
     * @return array
     */
    public function calculateVatDataProvider(): array
    {
        return [
            [100, 120, 20],
            [10, 11.9, 19],
            [4.12, 4.44, 7.8],
            [4.1234, 4.4444, 7.79],
            [4.7565, 5.0181, 5.5],
            [4.75, 5.01, 5.5],
            [4.5300, 4.7565, 5],
            [4.5, 4.73, 5],
            [10, 11.9, 19.],
            [4412.45928385451, 5250.826547787, 19.0],
            [4412.45928385451, 0, 0.],
            [0, 8.21, 0.],
            [100, 100, 0.],
            [5.00, 5.54, 10.8],
            [7.66, 8.21, 7.2],
            [0, 0, 0.],
            [2, 2, 0.],
            [9.99, 11.99, 20.],
            [9.95, 11.94, 20.],
        ];
    }
}
