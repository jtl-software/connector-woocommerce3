<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Telemetry\Event;

use JtlWooCommerceConnector\Telemetry\Event\SyncItemEvent;
use JtlWooCommerceConnector\Telemetry\Event\SyncItemResult;
use JtlWooCommerceConnector\Tests\AbstractTestCase;

/**
 * Class SyncItemEventTest
 *
 * @package JtlWooCommerceConnector\Tests\Telemetry\Event
 */
class SyncItemEventTest extends AbstractTestCase
{
    /**
     * @covers \JtlWooCommerceConnector\Telemetry\Event\SyncItemEvent::__construct
     * @return void
     */
    public function testConstructibleAndExposesAllFields(): void
    {
        $event = new SyncItemEvent(
            syncItemId: 'd1b2c3d4-0003-4000-8000-000000000003',
            syncRunId: 'c1b2c3d4-0001-4000-8000-000000000001',
            objectRef: 'Shopify Order #7003',
            result: SyncItemResult::Failed,
            durationMs: 3000,
            transactionId: 'a1b2c3d4-0003-4000-8000-000000000003',
            errorCode: 'RATE_LIMIT',
            errorMessage: 'Shopify API Rate Limit erreicht, Retry fehlgeschlagen',
        );

        $this->assertSame('d1b2c3d4-0003-4000-8000-000000000003', $event->syncItemId);
        $this->assertSame('c1b2c3d4-0001-4000-8000-000000000001', $event->syncRunId);
        $this->assertSame('a1b2c3d4-0003-4000-8000-000000000003', $event->transactionId);
        $this->assertSame('Shopify Order #7003', $event->objectRef);
        $this->assertSame(SyncItemResult::Failed, $event->result);
        $this->assertSame('RATE_LIMIT', $event->errorCode);
        $this->assertSame('Shopify API Rate Limit erreicht, Retry fehlgeschlagen', $event->errorMessage);
        $this->assertSame(3000, $event->durationMs);
    }

    /**
     * Optional transaction_id/error_code/error_message must default to '' (never null).
     *
     * @covers \JtlWooCommerceConnector\Telemetry\Event\SyncItemEvent::__construct
     * @covers \JtlWooCommerceConnector\Telemetry\Event\SyncItemEvent::toArray
     * @return void
     */
    public function testOptionalFieldsDefaultToEmptyString(): void
    {
        $event = new SyncItemEvent(
            syncItemId: 'd1b2c3d4-0001-4000-8000-000000000001',
            syncRunId: 'c1b2c3d4-0001-4000-8000-000000000001',
            objectRef: 'Shopify Order #7001',
            result: SyncItemResult::Success,
            durationMs: 1200,
            transactionId: 'a1b2c3d4-0001-4000-8000-000000000001',
        );

        $this->assertSame('', $event->errorCode);
        $this->assertSame('', $event->errorMessage);
        $array = $event->toArray();
        $this->assertSame('', $array['error_code']);
        $this->assertSame('', $array['error_message']);
    }

    /**
     * Diff against the successful sync_items row for order #7001 in
     * docs/telemetry/CO-3395-dummy-data.sql.
     *
     * @covers \JtlWooCommerceConnector\Telemetry\Event\SyncItemEvent::toArray
     * @covers \JtlWooCommerceConnector\Telemetry\Event\SyncItemEvent::jsonSerialize
     * @return void
     */
    public function testToArrayMatchesSuccessFixtureRow(): void
    {
        $event = new SyncItemEvent(
            syncItemId: 'd1b2c3d4-0001-4000-8000-000000000001',
            syncRunId: 'c1b2c3d4-0001-4000-8000-000000000001',
            objectRef: 'Shopify Order #7001',
            result: SyncItemResult::Success,
            durationMs: 1200,
            transactionId: 'a1b2c3d4-0001-4000-8000-000000000001',
        );

        $expected = [
            'sync_item_id'   => 'd1b2c3d4-0001-4000-8000-000000000001',
            'sync_run_id'    => 'c1b2c3d4-0001-4000-8000-000000000001',
            'transaction_id' => 'a1b2c3d4-0001-4000-8000-000000000001',
            'object_ref'     => 'Shopify Order #7001',
            'result'         => 'success',
            'error_code'     => '',
            'error_message'  => '',
            'duration_ms'    => 1200,
        ];

        $this->assertSame($expected, $event->toArray());
        $this->assertSame($expected, $event->jsonSerialize());
    }

    /**
     * Diff against the failed sync_items row for order #7003 in
     * docs/telemetry/CO-3395-dummy-data.sql (populated error_code/error_message).
     *
     * @covers \JtlWooCommerceConnector\Telemetry\Event\SyncItemEvent::toArray
     * @return void
     */
    public function testToArrayMatchesFailedFixtureRow(): void
    {
        $event = new SyncItemEvent(
            syncItemId: 'd1b2c3d4-0003-4000-8000-000000000003',
            syncRunId: 'c1b2c3d4-0001-4000-8000-000000000001',
            objectRef: 'Shopify Order #7003',
            result: SyncItemResult::Failed,
            durationMs: 3000,
            transactionId: 'a1b2c3d4-0003-4000-8000-000000000003',
            errorCode: 'RATE_LIMIT',
            errorMessage: 'Shopify API Rate Limit erreicht, Retry fehlgeschlagen',
        );

        $expected = [
            'sync_item_id'   => 'd1b2c3d4-0003-4000-8000-000000000003',
            'sync_run_id'    => 'c1b2c3d4-0001-4000-8000-000000000001',
            'transaction_id' => 'a1b2c3d4-0003-4000-8000-000000000003',
            'object_ref'     => 'Shopify Order #7003',
            'result'         => 'failed',
            'error_code'     => 'RATE_LIMIT',
            'error_message'  => 'Shopify API Rate Limit erreicht, Retry fehlgeschlagen',
            'duration_ms'    => 3000,
        ];

        $this->assertSame($expected, $event->toArray());
    }

    /**
     * @covers \JtlWooCommerceConnector\Telemetry\Event\SyncItemEvent::__construct
     * @return void
     */
    public function testMissingRequiredFieldThrowsTypeError(): void
    {
        $this->expectException(\TypeError::class);

        $args = ['syncItemId' => 'd1b2c3d4-0001-4000-8000-000000000001'];
        new SyncItemEvent(...$args);
    }
}
