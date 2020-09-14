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
        $vatRate = $this->invokeMethodFromObject(new CustomerOrderItem(), 'calculateVat', $priceNet, $priceGross);
        $this->assertEquals($expectedVatRate, $vatRate);
    }

    /**
     * @return array
     */
    public function calculateVatDataProvider(): array
    {
        return [
            [10, 11.9, 19.],
            [4412.45928385451, 5250.826547787, 19.0],
            [4412.45928385451, 0, 0.],
            [0, 8.21, 0.],
            [100, 100, 0.],
            [5, 5.54, 11.],
            [7.66666666, 8.21, 7.],
            [0, 0, 0.],
            [2, 2, 0.],
            [9.99, 12, 20.],
            [9.99, 11.99, 20.],
            [9.95, 11.95, 20.],
        ];
    }
}
