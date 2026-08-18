<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Telemetry\Event;

use JtlWooCommerceConnector\Telemetry\Event\TransactionItemEvent;
use JtlWooCommerceConnector\Telemetry\Event\TransactionItemStatus;
use JtlWooCommerceConnector\Tests\AbstractTestCase;

/**
 * Class TransactionItemEventTest
 *
 * @package JtlWooCommerceConnector\Tests\Telemetry\Event
 */
class TransactionItemEventTest extends AbstractTestCase
{
    /**
     * @covers \JtlWooCommerceConnector\Telemetry\Event\TransactionItemEvent::__construct
     * @return void
     */
    public function testConstructibleAndExposesAllFields(): void
    {
        $event = new TransactionItemEvent(
            itemId: 'b1b2c3d4-0001-4000-8000-000000000001',
            transactionId: 'a1b2c3d4-0001-4000-8000-000000000001',
            sku: 'SKU-1001',
            productName: 'T-Shirt Blau Gr. M',
            quantity: 2,
            unitPrice: 29.99,
            totalPrice: 59.98,
            status: TransactionItemStatus::Synced,
        );

        $this->assertSame('b1b2c3d4-0001-4000-8000-000000000001', $event->itemId);
        $this->assertSame('a1b2c3d4-0001-4000-8000-000000000001', $event->transactionId);
        $this->assertSame('SKU-1001', $event->sku);
        $this->assertSame('T-Shirt Blau Gr. M', $event->productName);
        $this->assertSame(2, $event->quantity);
        $this->assertSame(29.99, $event->unitPrice);
        $this->assertSame(59.98, $event->totalPrice);
        $this->assertSame(TransactionItemStatus::Synced, $event->status);
    }

    /**
     * The optional error_message must default to '' (never null) per the no-Nullable convention.
     *
     * @covers \JtlWooCommerceConnector\Telemetry\Event\TransactionItemEvent::__construct
     * @covers \JtlWooCommerceConnector\Telemetry\Event\TransactionItemEvent::toArray
     * @return void
     */
    public function testOptionalErrorMessageDefaultsToEmptyString(): void
    {
        $event = new TransactionItemEvent(
            itemId: 'b1b2c3d4-0001-4000-8000-000000000001',
            transactionId: 'a1b2c3d4-0001-4000-8000-000000000001',
            sku: 'SKU-1001',
            productName: 'T-Shirt Blau Gr. M',
            quantity: 2,
            unitPrice: 29.99,
            totalPrice: 59.98,
            status: TransactionItemStatus::Synced,
        );

        $this->assertSame('', $event->errorMessage);
        $this->assertSame('', $event->toArray()['error_message']);
        $this->assertNotNull($event->toArray()['error_message']);
    }

    /**
     * Diff the serialized output against the synced transaction_items row for SKU-1001 in
     * docs/telemetry/CO-3395-dummy-data.sql.
     *
     * @covers \JtlWooCommerceConnector\Telemetry\Event\TransactionItemEvent::toArray
     * @covers \JtlWooCommerceConnector\Telemetry\Event\TransactionItemEvent::jsonSerialize
     * @return void
     */
    public function testToArrayMatchesSyncedFixtureRow(): void
    {
        $event = new TransactionItemEvent(
            itemId: 'b1b2c3d4-0001-4000-8000-000000000001',
            transactionId: 'a1b2c3d4-0001-4000-8000-000000000001',
            sku: 'SKU-1001',
            productName: 'T-Shirt Blau Gr. M',
            quantity: 2,
            unitPrice: 29.99,
            totalPrice: 59.98,
            status: TransactionItemStatus::Synced,
        );

        $expected = [
            'item_id'        => 'b1b2c3d4-0001-4000-8000-000000000001',
            'transaction_id' => 'a1b2c3d4-0001-4000-8000-000000000001',
            'sku'            => 'SKU-1001',
            'product_name'   => 'T-Shirt Blau Gr. M',
            'quantity'       => 2,
            'unit_price'     => 29.99,
            'total_price'    => 59.98,
            'status'         => 'synced',
            'error_message'  => '',
        ];

        $this->assertSame($expected, $event->toArray());
        $this->assertSame($expected, $event->jsonSerialize());
    }

    /**
     * Diff the serialized output against the failed transaction_items row for SKU-3001 in
     * docs/telemetry/CO-3395-dummy-data.sql (populated error_message).
     *
     * @covers \JtlWooCommerceConnector\Telemetry\Event\TransactionItemEvent::toArray
     * @return void
     */
    public function testToArrayMatchesFailedFixtureRow(): void
    {
        $event = new TransactionItemEvent(
            itemId: 'b1b2c3d4-0005-4000-8000-000000000005',
            transactionId: 'a1b2c3d4-0003-4000-8000-000000000003',
            sku: 'SKU-3001',
            productName: 'Winterjacke Schwarz L',
            quantity: 1,
            unitPrice: 189.50,
            totalPrice: 189.50,
            status: TransactionItemStatus::Failed,
            errorMessage: 'Timeout beim Schreiben in WaWi',
        );

        $expected = [
            'item_id'        => 'b1b2c3d4-0005-4000-8000-000000000005',
            'transaction_id' => 'a1b2c3d4-0003-4000-8000-000000000003',
            'sku'            => 'SKU-3001',
            'product_name'   => 'Winterjacke Schwarz L',
            'quantity'       => 1,
            'unit_price'     => 189.50,
            'total_price'    => 189.50,
            'status'         => 'failed',
            'error_message'  => 'Timeout beim Schreiben in WaWi',
        ];

        $this->assertSame($expected, $event->toArray());
    }

    /**
     * @covers \JtlWooCommerceConnector\Telemetry\Event\TransactionItemEvent::__construct
     * @return void
     */
    public function testMissingRequiredFieldThrowsTypeError(): void
    {
        $this->expectException(\TypeError::class);

        $args = ['itemId' => 'b1b2c3d4-0001-4000-8000-000000000001'];
        new TransactionItemEvent(...$args);
    }
}
