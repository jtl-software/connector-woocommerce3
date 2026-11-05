<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Security;

use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\StatusChange;
use JtlWooCommerceConnector\Controllers\StatusChangeController;
use JtlWooCommerceConnector\Tests\AbstractTestCase;

/**
 * The customer order endpoint id is attacker-controlled (identity linking of the push request) and must never
 * reach the payment link queries unsanitized.
 *
 * @covers \JtlWooCommerceConnector\Controllers\StatusChangeController
 */
class StatusChangeControllerSqlInjectionTest extends AbstractTestCase
{
    private const string PAYLOAD = '1 OR (SELECT SLEEP(5))';

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $wpdb            = new \stdClass();
        $wpdb->prefix    = 'wp_';
        $GLOBALS['wpdb'] = $wpdb;
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        unset($GLOBALS['wpdb']);

        parent::tearDown();
    }

    /**
     * @param string|null $endpoint
     * @param string|null $paymentLink
     * @return array<int, string>
     * @throws \ReflectionException
     */
    private function runLinkIfPaymentIsNotLinked(?string $endpoint, ?string $paymentLink = null): array
    {
        $queries = [];
        $db      = $this->createDbMock(['queryOne', 'query']);
        $db->method('queryOne')->willReturnCallback(
            static function (string $query) use (&$queries, $paymentLink) {
                $queries[] = $query;
                return $paymentLink;
            }
        );
        $db->method('query')->willReturnCallback(
            static function (string $query) use (&$queries) {
                $queries[] = $query;
                return [];
            }
        );

        $controller = new StatusChangeController($db, $this->createUtilMock());

        $statusChange = new StatusChange();
        if ($endpoint !== null) {
            $statusChange->setCustomerOrderId(new Identity($endpoint, 1));
        }

        $this->invokeMethodFromObject($controller, 'linkIfPaymentIsNotLinked', $statusChange);

        return $queries;
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testInjectionPayloadIsNeutralizedInSelectAndInsert(): void
    {
        $queries = $this->runLinkIfPaymentIsNotLinked(self::PAYLOAD);

        $this->assertCount(2, $queries);
        $this->assertStringNotContainsString('SLEEP', $queries[0]);
        $this->assertStringNotContainsString('OR', $queries[0]);
        $this->assertStringContainsString('`endpoint_id` = 1', $queries[0]);
        $this->assertStringNotContainsString('SLEEP', $queries[1]);
        $this->assertStringContainsString('VALUES (1,0)', $queries[1]);
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testNumericEndpointIsUsedUnchanged(): void
    {
        $queries = $this->runLinkIfPaymentIsNotLinked('4711');

        $this->assertStringContainsString('`endpoint_id` = 4711', $queries[0]);
        $this->assertStringContainsString('VALUES (4711,0)', $queries[1]);
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testMissingIdentityFallsBackToZero(): void
    {
        $queries = $this->runLinkIfPaymentIsNotLinked(null);

        $this->assertStringContainsString('`endpoint_id` = 0', $queries[0]);
        $this->assertStringContainsString('VALUES (0,0)', $queries[1]);
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testExistingPaymentLinkSkipsInsert(): void
    {
        $queries = $this->runLinkIfPaymentIsNotLinked('4711', '1');

        $this->assertCount(1, $queries);
    }
}
