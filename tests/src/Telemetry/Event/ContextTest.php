<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Telemetry\Event;

use JtlWooCommerceConnector\Telemetry\Event\ConnectorType;
use JtlWooCommerceConnector\Telemetry\Event\Context;
use JtlWooCommerceConnector\Telemetry\Event\Environment;
use JtlWooCommerceConnector\Tests\AbstractTestCase;

/**
 * Class ContextTest
 *
 * @package JtlWooCommerceConnector\Tests\Telemetry\Event
 */
class ContextTest extends AbstractTestCase
{
    /**
     * @covers \JtlWooCommerceConnector\Telemetry\Event\Context::toArray
     * @covers \JtlWooCommerceConnector\Telemetry\Event\Context::jsonSerialize
     * @return void
     */
    public function testToArrayMatchesDataModelFieldNames(): void
    {
        $context = new Context(
            tenantId: 'tenant-100',
            tenantName: 'Mustermann GmbH',
            connectorType: ConnectorType::WooCommerce,
            connectorVersion: '3.2.0',
            environment: Environment::Production,
            wawiType: 'JTL-Wawi',
            wawiVersion: '1.9.4',
        );

        $expected = [
            'tenant_id'         => 'tenant-100',
            'tenant_name'       => 'Mustermann GmbH',
            'connector_type'    => 'woocommerce',
            'connector_version' => '3.2.0',
            'environment'       => 'production',
            'wawi_type'         => 'JTL-Wawi',
            'wawi_version'      => '1.9.4',
        ];

        $this->assertSame($expected, $context->toArray());
        $this->assertSame($expected, $context->jsonSerialize());
    }

    /**
     * Optional wawi_type/wawi_version must default to '' (never null).
     *
     * @covers \JtlWooCommerceConnector\Telemetry\Event\Context::toArray
     * @return void
     */
    public function testOptionalWawiFieldsDefaultToEmptyString(): void
    {
        $context = new Context(
            tenantId: 'tenant-200',
            tenantName: 'Beispiel AG',
            connectorType: ConnectorType::WooCommerce,
            connectorVersion: '3.2.0',
            environment: Environment::Development,
        );

        $this->assertSame('', $context->wawiType);
        $this->assertSame('', $context->wawiVersion);
        $array = $context->toArray();
        $this->assertSame('', $array['wawi_type']);
        $this->assertSame('', $array['wawi_version']);
    }
}
