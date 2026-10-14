<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Security;

use JtlWooCommerceConnector\Utilities\SqlHelper;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Regression tests for CVE-2026-78193 (unauthenticated SQL injection through the
 * identity endpoint, e.g. product.push). The integer primary-key-mapping queries
 * must never interpolate the attacker-controlled endpoint id unescaped/unquoted.
 *
 * @covers \JtlWooCommerceConnector\Utilities\SqlTraits\PrimaryKeyMappingTrait
 */
class PrimaryKeyMappingSqlInjectionTest extends TestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        global $wpdb;

        $wpdb         = Mockery::mock('WPDB');
        $wpdb->prefix = 'wp_';
        $wpdb->shouldReceive('_escape')->andReturnUsing(static fn($value) => $value);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function maliciousEndpointIdProvider(): array
    {
        return [
            'time based'    => ['1 OR SLEEP(5)'],
            'union select'  => ['0 UNION SELECT user_pass FROM wp_users'],
            'stacked query' => ['1); DROP TABLE wp_users; --'],
            'quote breakout' => ["1' OR '1'='1"],
        ];
    }

    /**
     * @dataProvider maliciousEndpointIdProvider
     * @param string $endpointId
     * @return void
     */
    public function testHostIntegerQueryNeutralisesInjection(string $endpointId): void
    {
        $query    = SqlHelper::primaryKeyMappingHostInteger($endpointId, 'jtl_connector_link_product');
        $expected = (int)$endpointId;

        $this->assertStringEndsWith("WHERE endpoint_id = {$expected}", \rtrim($query));
        $this->assertStringNotContainsStringIgnoringCase('SLEEP', $query);
        $this->assertStringNotContainsStringIgnoringCase('UNION', $query);
        $this->assertStringNotContainsStringIgnoringCase('DROP', $query);
        $this->assertStringNotContainsString("'", $query);
    }

    /**
     * @dataProvider maliciousEndpointIdProvider
     * @param string $endpointId
     * @return void
     */
    public function testSaveIntegerQueryNeutralisesInjection(string $endpointId): void
    {
        $query    = SqlHelper::primaryKeyMappingSaveInteger($endpointId, 42, 'jtl_connector_link_product');
        $expected = (int)$endpointId;

        $this->assertStringContainsString("VALUES ({$expected}, 42)", $query);
        $this->assertStringNotContainsStringIgnoringCase('SLEEP', $query);
        $this->assertStringNotContainsStringIgnoringCase('UNION', $query);
        $this->assertStringNotContainsStringIgnoringCase('DROP', $query);
        $this->assertStringNotContainsString("'", $query);
    }

    /**
     * @return void
     */
    public function testNumericEndpointIdIsPreserved(): void
    {
        $this->assertStringContainsString(
            'WHERE endpoint_id = 1234',
            SqlHelper::primaryKeyMappingHostInteger('1234', 'jtl_connector_link_product')
        );
        $this->assertStringContainsString(
            'VALUES (1234, 42)',
            SqlHelper::primaryKeyMappingSaveInteger('1234', 42, 'jtl_connector_link_product')
        );
    }
}
