<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Security;

use Jtl\Connector\Core\Model\Checksum;
use JtlWooCommerceConnector\Checksum\ChecksumLoader;
use JtlWooCommerceConnector\Utilities\Db;
use PHPUnit\Framework\TestCase;
use WP_Mock;

/**
 * Regression tests for the unauthenticated SQL injection that reached the
 * product checksum queries. The variation endpoint id is interpolated into the
 * numeric product_id column, so it must be reduced to a pure integer and must
 * never let SQL payloads through.
 *
 * @covers \JtlWooCommerceConnector\Checksum\ChecksumLoader
 */
class ChecksumLoaderSqlInjectionTest extends TestCase
{
    private ChecksumLoader $loader;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        WP_Mock::setUp();

        WP_Mock::userFunction('esc_sql')->andReturnUsing(static fn($value) => $value);

        $wpdb            = new \stdClass();
        $wpdb->prefix    = 'wp_';
        $GLOBALS['wpdb'] = $wpdb;

        $db           = $this->getMockBuilder(Db::class)->disableOriginalConstructor()->getMock();
        $this->loader = new ChecksumLoader($db);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        WP_Mock::tearDown();
        unset($GLOBALS['wpdb']);
        parent::tearDown();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function maliciousEndpointProvider(): array
    {
        return [
            'time based'  => ['1 OR SLEEP(5)'],
            'union'       => ['1 UNION SELECT user_pass FROM wp_users'],
            'stacked'     => ['1; DROP TABLE wp_users'],
            'float trick' => ['12.5'],
            'quote'       => ["1' OR '1'='1"],
        ];
    }

    /**
     * @dataProvider maliciousEndpointProvider
     * @param string $endpointId
     * @return void
     */
    public function testReadQueryCastsEndpointToInteger(string $endpointId): void
    {
        $sql = $this->loader->getChecksumRead($endpointId, Checksum::TYPE_VARIATION);

        $this->assertStringContainsString('product_id = ' . (int)$endpointId, $sql);
        $this->assertPayloadNeutralised($sql);
    }

    /**
     * @dataProvider maliciousEndpointProvider
     * @param string $endpointId
     * @return void
     */
    public function testWriteQueryCastsEndpointToInteger(string $endpointId): void
    {
        $sql = $this->loader->getChecksumWrite($endpointId, Checksum::TYPE_VARIATION, 'abc');

        $this->assertStringContainsString('VALUES(' . (int)$endpointId . ',', $sql);
        $this->assertPayloadNeutralised($sql);
    }

    /**
     * @dataProvider maliciousEndpointProvider
     * @param string $endpointId
     * @return void
     */
    public function testDeleteQueryCastsEndpointToInteger(string $endpointId): void
    {
        $sql = $this->loader->getChecksumDelete($endpointId, Checksum::TYPE_VARIATION);

        $this->assertStringContainsString('`product_id` = ' . (int)$endpointId, $sql);
        $this->assertPayloadNeutralised($sql);
    }

    /**
     * @param string $sql
     * @return void
     */
    private function assertPayloadNeutralised(string $sql): void
    {
        foreach (['SLEEP', 'UNION', 'DROP', '0x', "'1'='1"] as $needle) {
            $this->assertStringNotContainsStringIgnoringCase($needle, $sql);
        }
    }
}
