<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Controllers\Order;

use JtlWooCommerceConnector\Controllers\Order\CustomerOrderItemController;
use JtlWooCommerceConnector\Tests\AbstractTestCase;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\Util;

/**
 * Class CustomerOrderItemTest
 *
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
     * @return void
     * @throws \ReflectionException
     * @covers CustomerOrderItemController::calculateVat
     */
    public function testCalculateVat(float $priceNet, float $priceGross, float $expectedVatRate): void
    {
        $vatRate = $this->invokeMethodFromObject(
            new CustomerOrderItemController($this->createDbMock(), $this->createUtilMock()),
            'calculateVat',
            $priceNet,
            $priceGross
        );
        $this->assertEquals($expectedVatRate, $vatRate);
    }

    /**
     * IEEE 754 float drift: round(ratio, n) stored as a double slightly below the
     * mathematical value. Multiplying by 100 amplifies that drift so the intermediate
     * result falls just below the .XX5 rounding boundary, causing round(..., 2) to
     * give the wrong VAT rate.
     *
     * The fix adds an intermediate round(..., vatRoundPrecision) to snap the drifted
     * value before the final round(..., 2) is applied:
     *
     *   OLD: round(round(4.4444/4.1234, 5) * 100 - 100, 2)          = 7.78 (wrong)
     *   FIX: round(round(round(4.4444/4.1234, 5) * 100 - 100, 5), 2) = 7.79 (correct)
     *
     * @return void
     * @throws \ReflectionException
     * @covers CustomerOrderItemController::calculateVat
     */
    public function testCalculateVatIeeeFloatDriftFixed(): void
    {
        $sut = new CustomerOrderItemController($this->createDbMock(), $this->createUtilMock());

        $vatRate = $this->invokeMethodFromObject($sut, 'calculateVat', 4.1234, 4.4444);

        $this->assertEquals(7.79, $vatRate);
    }

    /**
     * @return array<int|string, array<int, float|int>>
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
            [9.99, 11.99, 20],
            [9.95, 11.94, 20.],
            [3.2750, 3.8973, 19],
            [13.4, 15.54, 16],
            [1.7155, 1.99, 16],
            [1.2845, 1.49, 16],
            [0, 100, 0],
            [100, 0, 0],
            [100.14526, 119, 18.83],
            [0.08, 0.0952, 19.],
            [3.89899, 4.1719193333333, 7.],
            [9.19, 9.897799, 7.7],
            [9.19, 9.9, 7.7],
            [5.571, 6, 7.7],
            // vatRoundPrecision=3: 2-decimal approximation overshoots, 3-decimal converges
            'vatRoundPrecision_3' => [10.0, 10.79, 7.9],
            // vatRoundPrecision=4: ratio needs 4-digit precision to reproduce the gross
            'vatRoundPrecision_4_a' => [1.2345, 1.3208, 6.99],
            'vatRoundPrecision_4_b' => [3.33, 3.5628, 6.99],
        ];
    }
}
