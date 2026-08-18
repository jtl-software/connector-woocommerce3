<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Telemetry\Event;

use JtlWooCommerceConnector\Telemetry\Event\ConnectorType;
use JtlWooCommerceConnector\Telemetry\Event\Context;
use JtlWooCommerceConnector\Telemetry\Event\Environment;
use JtlWooCommerceConnector\Telemetry\Event\TransactionEvent;
use JtlWooCommerceConnector\Telemetry\Event\TransactionStatus;
use JtlWooCommerceConnector\Tests\AbstractTestCase;

/**
 * Class TransactionEventTest
 *
 * @package JtlWooCommerceConnector\Tests\Telemetry\Event
 */
class TransactionEventTest extends AbstractTestCase
{
    /**
     * Context matching the tenant block used across docs/telemetry/CO-3395-dummy-data.sql.
     *
     * @return Context
     */
    private function fixtureContext(): Context
    {
        return new Context(
            tenantId: 'tenant-100',
            tenantName: 'Mustermann GmbH',
            connectorType: ConnectorType::Shopify,
            connectorVersion: '2.4.1',
            environment: Environment::Production,
            wawiType: 'JTL-Wawi',
            wawiVersion: '1.9.4',
        );
    }

    /**
     * @covers \JtlWooCommerceConnector\Telemetry\Event\TransactionEvent::__construct
     * @return void
     */
    public function testConstructibleAndExposesAllFields(): void
    {
        $context = $this->fixtureContext();
        $event   = new TransactionEvent(
            transactionId: 'a1b2c3d4-0001-4000-8000-000000000001',
            orderId: 'shopify-7001',
            orderNumber: '#7001',
            sourceSystem: ConnectorType::Shopify,
            status: TransactionStatus::Completed,
            totalAmount: 149.97,
            currency: 'EUR',
            itemCount: 3,
            totalQuantity: 5,
            createdAt: '2026-04-30 09:12:00.000',
            syncedAt: '2026-04-30 09:15:02.000',
            context: $context,
        );

        $this->assertSame('a1b2c3d4-0001-4000-8000-000000000001', $event->transactionId);
        $this->assertSame('shopify-7001', $event->orderId);
        $this->assertSame('#7001', $event->orderNumber);
        $this->assertSame(ConnectorType::Shopify, $event->sourceSystem);
        $this->assertSame(TransactionStatus::Completed, $event->status);
        $this->assertSame(149.97, $event->totalAmount);
        $this->assertSame('EUR', $event->currency);
        $this->assertSame(3, $event->itemCount);
        $this->assertSame(5, $event->totalQuantity);
        $this->assertSame('2026-04-30 09:12:00.000', $event->createdAt);
        $this->assertSame('2026-04-30 09:15:02.000', $event->syncedAt);
        $this->assertSame($context, $event->context);
    }

    /**
     * Diff the serialized output field-by-field against the transactions row for order #7001 in
     * docs/telemetry/CO-3395-dummy-data.sql.
     *
     * @covers \JtlWooCommerceConnector\Telemetry\Event\TransactionEvent::toArray
     * @covers \JtlWooCommerceConnector\Telemetry\Event\TransactionEvent::jsonSerialize
     * @return void
     */
    public function testToArrayMatchesDataModelFieldNamesAndValues(): void
    {
        $event = new TransactionEvent(
            transactionId: 'a1b2c3d4-0001-4000-8000-000000000001',
            orderId: 'shopify-7001',
            orderNumber: '#7001',
            sourceSystem: ConnectorType::Shopify,
            status: TransactionStatus::Completed,
            totalAmount: 149.97,
            currency: 'EUR',
            itemCount: 3,
            totalQuantity: 5,
            createdAt: '2026-04-30 09:12:00.000',
            syncedAt: '2026-04-30 09:15:02.000',
            context: $this->fixtureContext(),
        );

        $expected = [
            'transaction_id'    => 'a1b2c3d4-0001-4000-8000-000000000001',
            'order_id'          => 'shopify-7001',
            'order_number'      => '#7001',
            'source_system'     => 'shopify',
            'status'            => 'completed',
            'total_amount'      => 149.97,
            'currency'          => 'EUR',
            'item_count'        => 3,
            'total_quantity'    => 5,
            'created_at'        => '2026-04-30 09:12:00.000',
            'synced_at'         => '2026-04-30 09:15:02.000',
            'tenant_id'         => 'tenant-100',
            'tenant_name'       => 'Mustermann GmbH',
            'connector_type'    => 'shopify',
            'connector_version' => '2.4.1',
            'environment'       => 'production',
            'wawi_type'         => 'JTL-Wawi',
            'wawi_version'      => '1.9.4',
        ];

        $this->assertSame($expected, $event->toArray());
        $this->assertSame($expected, $event->jsonSerialize());
        $this->assertSame(
            \json_encode($expected),
            \json_encode($event),
            'JsonSerializable output must equal the serialized array.'
        );
    }

    /**
     * @covers \JtlWooCommerceConnector\Telemetry\Event\TransactionEvent::__construct
     * @return void
     */
    public function testMissingRequiredFieldThrowsTypeError(): void
    {
        $this->expectException(\TypeError::class);

        // Intentionally omit required arguments to assert PHP enforces the contract at construction.
        $args = ['transactionId' => 'a1b2c3d4-0001-4000-8000-000000000001'];
        new TransactionEvent(...$args);
    }
}
