<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Telemetry\Event;

use JtlWooCommerceConnector\Telemetry\Event\ConnectorType;
use JtlWooCommerceConnector\Telemetry\Event\Context;
use JtlWooCommerceConnector\Telemetry\Event\Environment;
use JtlWooCommerceConnector\Telemetry\Event\SyncDirection;
use JtlWooCommerceConnector\Telemetry\Event\SyncObjectType;
use JtlWooCommerceConnector\Telemetry\Event\SyncResult;
use JtlWooCommerceConnector\Telemetry\Event\SyncRunEvent;
use JtlWooCommerceConnector\Telemetry\Event\SyncScope;
use JtlWooCommerceConnector\Telemetry\Event\SyncTriggeredBy;
use JtlWooCommerceConnector\Tests\AbstractTestCase;

/**
 * Class SyncRunEventTest
 *
 * @package JtlWooCommerceConnector\Tests\Telemetry\Event
 */
class SyncRunEventTest extends AbstractTestCase
{
    /**
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
     * @covers \JtlWooCommerceConnector\Telemetry\Event\SyncRunEvent::__construct
     * @return void
     */
    public function testConstructibleAndExposesAllFields(): void
    {
        $context = $this->fixtureContext();
        $event   = new SyncRunEvent(
            syncRunId: 'c1b2c3d4-0001-4000-8000-000000000001',
            triggeredBy: SyncTriggeredBy::WawiScheduled,
            direction: SyncDirection::Inbound,
            objectType: SyncObjectType::Order,
            scope: SyncScope::Delta,
            timestampStart: '2026-04-30 09:15:00.000',
            timestampEnd: '2026-04-30 09:15:05.000',
            durationMs: 5000,
            recordsTotal: 3,
            recordsSucceeded: 2,
            recordsFailed: 1,
            result: SyncResult::Partial,
            context: $context,
        );

        $this->assertSame('c1b2c3d4-0001-4000-8000-000000000001', $event->syncRunId);
        $this->assertSame(SyncTriggeredBy::WawiScheduled, $event->triggeredBy);
        $this->assertSame(SyncDirection::Inbound, $event->direction);
        $this->assertSame(SyncObjectType::Order, $event->objectType);
        $this->assertSame(SyncScope::Delta, $event->scope);
        $this->assertSame(5000, $event->durationMs);
        $this->assertSame(3, $event->recordsTotal);
        $this->assertSame(2, $event->recordsSucceeded);
        $this->assertSame(1, $event->recordsFailed);
        $this->assertSame(SyncResult::Partial, $event->result);
        $this->assertSame($context, $event->context);
    }

    /**
     * Diff the serialized output field-by-field against the sync_runs row in
     * docs/telemetry/CO-3395-dummy-data.sql (3 total, 2 succeeded, 1 failed, result = partial).
     *
     * @covers \JtlWooCommerceConnector\Telemetry\Event\SyncRunEvent::toArray
     * @covers \JtlWooCommerceConnector\Telemetry\Event\SyncRunEvent::jsonSerialize
     * @return void
     */
    public function testToArrayMatchesSyncRunFixtureRow(): void
    {
        $event = new SyncRunEvent(
            syncRunId: 'c1b2c3d4-0001-4000-8000-000000000001',
            triggeredBy: SyncTriggeredBy::WawiScheduled,
            direction: SyncDirection::Inbound,
            objectType: SyncObjectType::Order,
            scope: SyncScope::Delta,
            timestampStart: '2026-04-30 09:15:00.000',
            timestampEnd: '2026-04-30 09:15:05.000',
            durationMs: 5000,
            recordsTotal: 3,
            recordsSucceeded: 2,
            recordsFailed: 1,
            result: SyncResult::Partial,
            context: $this->fixtureContext(),
        );

        $expected = [
            'sync_run_id'       => 'c1b2c3d4-0001-4000-8000-000000000001',
            'triggered_by'      => 'wawi_scheduled',
            'direction'         => 'inbound',
            'object_type'       => 'order',
            'scope'             => 'delta',
            'timestamp_start'   => '2026-04-30 09:15:00.000',
            'timestamp_end'     => '2026-04-30 09:15:05.000',
            'duration_ms'       => 5000,
            'records_total'     => 3,
            'records_succeeded' => 2,
            'records_failed'    => 1,
            'result'            => 'partial',
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
    }

    /**
     * @covers \JtlWooCommerceConnector\Telemetry\Event\SyncRunEvent::__construct
     * @return void
     */
    public function testMissingRequiredFieldThrowsTypeError(): void
    {
        $this->expectException(\TypeError::class);

        $args = ['syncRunId' => 'c1b2c3d4-0001-4000-8000-000000000001'];
        new SyncRunEvent(...$args);
    }
}
