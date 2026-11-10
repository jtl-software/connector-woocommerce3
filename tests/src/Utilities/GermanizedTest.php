<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Utilities;

use JtlWooCommerceConnector\Utilities\Germanized;
use PHPUnit\Framework\TestCase;

class GermanizedTest extends TestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../WcOrderStub.php';
        \WP_Mock::setUp();
        \WP_Mock::userFunction('__')->andReturnArg(0);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        \WP_Mock::tearDown();
        parent::tearDown();
    }

    /**
     * @dataProvider orderSalutationProvider
     *
     * @param string $prefix
     * @param string $metaKey
     * @param mixed  $metaValue
     * @param string $expected
     * @return void
     * @covers \JtlWooCommerceConnector\Utilities\Germanized::getSalutationFromOrder
     */
    public function testGetSalutationFromOrderReadsMetaViaOrderObject(
        string $prefix,
        string $metaKey,
        mixed $metaValue,
        string $expected
    ): void {
        $order = $this->createMock(\WC_Order::class);
        $order->expects($this->once())
            ->method('get_meta')
            ->with($metaKey, true)
            ->willReturn($metaValue);

        $this->assertSame($expected, (new Germanized())->getSalutationFromOrder($order, $prefix));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function orderSalutationProvider(): array
    {
        return [
            'billing male'      => ['billing', '_billing_title', '1', 'Mr.'],
            'billing female'    => ['billing', '_billing_title', '2', 'Ms.'],
            'shipping female'   => ['shipping', '_shipping_title', '2', 'Ms.'],
            'empty'             => ['billing', '_billing_title', '', ''],
            'unknown index'     => ['billing', '_billing_title', '9', ''],
            'non scalar result' => ['billing', '_billing_title', false, ''],
        ];
    }
}
