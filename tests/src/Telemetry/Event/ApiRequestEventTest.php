<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Telemetry\Event;

use JtlWooCommerceConnector\Telemetry\Event\ApiErrorCategory;
use JtlWooCommerceConnector\Telemetry\Event\ApiRequestEvent;
use JtlWooCommerceConnector\Telemetry\Event\ConnectorType;
use JtlWooCommerceConnector\Telemetry\Event\Context;
use JtlWooCommerceConnector\Telemetry\Event\Environment;
use JtlWooCommerceConnector\Telemetry\Event\HttpMethod;
use JtlWooCommerceConnector\Telemetry\Event\TargetSystem;
use JtlWooCommerceConnector\Tests\AbstractTestCase;

/**
 * Class ApiRequestEventTest
 *
 * ClickHouse encodes bool as UInt8 (0/1); the PHP DTO keeps native booleans, so the fixture's
 * `0`/`1` bool columns map to `false`/`true` here.
 *
 * @package JtlWooCommerceConnector\Tests\Telemetry\Event
 */
class ApiRequestEventTest extends AbstractTestCase
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
     * @covers \JtlWooCommerceConnector\Telemetry\Event\ApiRequestEvent::__construct
     * @return void
     */
    public function testConstructibleAndExposesAllFields(): void
    {
        $context = $this->fixtureContext();
        $event   = new ApiRequestEvent(
            requestId: 'e1b2c3d4-0004-4000-8000-000000000004',
            timestamp: '2026-04-30 09:15:02.000',
            targetSystem: TargetSystem::Shopify,
            httpMethod: HttpMethod::Get,
            endpoint: '/admin/api/2024-01/orders/7003.json',
            httpStatus: 429,
            durationMs: 85,
            isError: true,
            isRetry: false,
            retryAttempt: 0,
            isRateLimited: true,
            context: $context,
            syncRunId: 'c1b2c3d4-0001-4000-8000-000000000001',
            syncItemId: 'd1b2c3d4-0003-4000-8000-000000000003',
            requestSizeBytes: 128,
            responseSizeBytes: 64,
            errorCategory: ApiErrorCategory::RateLimit,
            errorMessage: 'Too Many Requests',
            rateLimitWaitMs: 2000,
        );

        $this->assertSame('e1b2c3d4-0004-4000-8000-000000000004', $event->requestId);
        $this->assertSame(TargetSystem::Shopify, $event->targetSystem);
        $this->assertSame(HttpMethod::Get, $event->httpMethod);
        $this->assertSame(429, $event->httpStatus);
        $this->assertTrue($event->isError);
        $this->assertTrue($event->isRateLimited);
        $this->assertSame(ApiErrorCategory::RateLimit, $event->errorCategory);
        $this->assertSame(2000, $event->rateLimitWaitMs);
        $this->assertSame($context, $event->context);
    }

    /**
     * Optional fields must default to '' / 0 / false / (no error_category) — never null in the
     * serialized output.
     *
     * @covers \JtlWooCommerceConnector\Telemetry\Event\ApiRequestEvent::__construct
     * @covers \JtlWooCommerceConnector\Telemetry\Event\ApiRequestEvent::toArray
     * @return void
     */
    public function testOptionalFieldsDefaultToNonNullSentinels(): void
    {
        $event = new ApiRequestEvent(
            requestId: 'e1b2c3d4-0001-4000-8000-000000000001',
            timestamp: '2026-04-30 09:15:00.100',
            targetSystem: TargetSystem::Shopify,
            httpMethod: HttpMethod::Get,
            endpoint: '/admin/api/2024-01/orders.json',
            httpStatus: 200,
            durationMs: 320,
            isError: false,
            isRetry: false,
            retryAttempt: 0,
            isRateLimited: false,
            context: $this->fixtureContext(),
        );

        $this->assertNull($event->errorCategory);
        $array = $event->toArray();
        $this->assertSame('', $array['sync_item_id']);
        $this->assertSame('', $array['error_category']);
        $this->assertSame('', $array['error_message']);
        $this->assertSame('', $array['retry_of_request_id']);
        $this->assertSame(0, $array['rate_limit_wait_ms']);
        $this->assertFalse($array['is_error']);
    }

    /**
     * Diff the rate-limited GET (order #7003, HTTP 429) field-by-field against
     * docs/telemetry/CO-3395-dummy-data.sql (request e1b2...0004).
     *
     * @covers \JtlWooCommerceConnector\Telemetry\Event\ApiRequestEvent::toArray
     * @covers \JtlWooCommerceConnector\Telemetry\Event\ApiRequestEvent::jsonSerialize
     * @return void
     */
    public function testToArrayMatchesRateLimitedFixtureRow(): void
    {
        $event = new ApiRequestEvent(
            requestId: 'e1b2c3d4-0004-4000-8000-000000000004',
            timestamp: '2026-04-30 09:15:02.000',
            targetSystem: TargetSystem::Shopify,
            httpMethod: HttpMethod::Get,
            endpoint: '/admin/api/2024-01/orders/7003.json',
            httpStatus: 429,
            durationMs: 85,
            isError: true,
            isRetry: false,
            retryAttempt: 0,
            isRateLimited: true,
            context: $this->fixtureContext(),
            syncRunId: 'c1b2c3d4-0001-4000-8000-000000000001',
            syncItemId: 'd1b2c3d4-0003-4000-8000-000000000003',
            requestSizeBytes: 128,
            responseSizeBytes: 64,
            errorCategory: ApiErrorCategory::RateLimit,
            errorMessage: 'Too Many Requests',
            rateLimitWaitMs: 2000,
        );

        $expected = [
            'request_id'          => 'e1b2c3d4-0004-4000-8000-000000000004',
            'sync_run_id'         => 'c1b2c3d4-0001-4000-8000-000000000001',
            'sync_item_id'        => 'd1b2c3d4-0003-4000-8000-000000000003',
            'timestamp'           => '2026-04-30 09:15:02.000',
            'target_system'       => 'shopify',
            'http_method'         => 'GET',
            'endpoint'            => '/admin/api/2024-01/orders/7003.json',
            'http_status'         => 429,
            'duration_ms'         => 85,
            'request_size_bytes'  => 128,
            'response_size_bytes' => 64,
            'is_error'            => true,
            'error_category'      => 'rate_limit',
            'error_message'       => 'Too Many Requests',
            'is_retry'            => false,
            'retry_attempt'       => 0,
            'retry_of_request_id' => '',
            'is_rate_limited'     => true,
            'rate_limit_wait_ms'  => 2000,
            'tenant_id'           => 'tenant-100',
            'tenant_name'         => 'Mustermann GmbH',
            'connector_type'      => 'shopify',
            'connector_version'   => '2.4.1',
            'environment'         => 'production',
            'wawi_type'           => 'JTL-Wawi',
            'wawi_version'        => '1.9.4',
        ];

        $this->assertSame($expected, $event->toArray());
        $this->assertSame($expected, $event->jsonSerialize());
    }

    /**
     * The retry (request e1b2...0005) must reference the original request via retry_of_request_id,
     * mirroring the retry chain in docs/telemetry/CO-3395-dummy-data.sql.
     *
     * @covers \JtlWooCommerceConnector\Telemetry\Event\ApiRequestEvent::toArray
     * @return void
     */
    public function testRetryRequestReferencesOriginalRequestId(): void
    {
        $event = new ApiRequestEvent(
            requestId: 'e1b2c3d4-0005-4000-8000-000000000005',
            timestamp: '2026-04-30 09:15:04.100',
            targetSystem: TargetSystem::Shopify,
            httpMethod: HttpMethod::Get,
            endpoint: '/admin/api/2024-01/orders/7003.json',
            httpStatus: 200,
            durationMs: 290,
            isError: false,
            isRetry: true,
            retryAttempt: 1,
            isRateLimited: false,
            context: $this->fixtureContext(),
            syncRunId: 'c1b2c3d4-0001-4000-8000-000000000001',
            syncItemId: 'd1b2c3d4-0003-4000-8000-000000000003',
            requestSizeBytes: 128,
            responseSizeBytes: 4800,
            retryOfRequestId: 'e1b2c3d4-0004-4000-8000-000000000004',
        );

        $array = $event->toArray();
        $this->assertTrue($array['is_retry']);
        $this->assertSame(1, $array['retry_attempt']);
        $this->assertSame('e1b2c3d4-0004-4000-8000-000000000004', $array['retry_of_request_id']);
        $this->assertSame('', $array['error_category']);
        $this->assertFalse($array['is_error']);
    }

    /**
     * @covers \JtlWooCommerceConnector\Telemetry\Event\ApiRequestEvent::__construct
     * @return void
     */
    public function testMissingRequiredFieldThrowsTypeError(): void
    {
        $this->expectException(\TypeError::class);

        $args = ['requestId' => 'e1b2c3d4-0004-4000-8000-000000000004'];
        new ApiRequestEvent(...$args);
    }
}
