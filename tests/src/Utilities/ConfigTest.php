<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Utilities;

use JtlWooCommerceConnector\Tests\AbstractTestCase;
use JtlWooCommerceConnector\Utilities\Config;
use WP_Mock;

/**
 * Class ConfigTest
 *
 * @package JtlWooCommerceConnector\Tests\Utilities
 */
class ConfigTest extends AbstractTestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        WP_Mock::setUp();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        WP_Mock::tearDown();
        parent::tearDown();
    }

    /**
     * @covers \JtlWooCommerceConnector\Utilities\Config::get
     * @return void
     */
    public function testTelemetryEnabledDefaultsToFalseWhenUnset(): void
    {
        WP_Mock::userFunction('get_option', [
            'times'  => 1,
            'args'   => [Config::OPTIONS_TELEMETRY_ENABLED, false],
            'return' => false,
        ]);

        $this->assertFalse(Config::get(Config::OPTIONS_TELEMETRY_ENABLED, false));
    }

    /**
     * @covers \JtlWooCommerceConnector\Utilities\Config::get
     * @return void
     */
    public function testTelemetryEndpointDefaultResolvesToConfiguredDefaultString(): void
    {
        $default = Config::JTLWCC_CONFIG_DEFAULTS[Config::OPTIONS_TELEMETRY_ENDPOINT];

        $this->assertSame('', $default);

        WP_Mock::userFunction('get_option', [
            'times'  => 1,
            'args'   => [Config::OPTIONS_TELEMETRY_ENDPOINT, $default],
            'return' => $default,
        ]);

        $this->assertSame('', Config::get(Config::OPTIONS_TELEMETRY_ENDPOINT, $default));
    }

    /**
     * @dataProvider telemetryEnabledProvider
     * @covers \JtlWooCommerceConnector\Utilities\Config::get
     * @param bool $stored
     * @return void
     */
    public function testTelemetryEnabledRoundTripsThroughGet(bool $stored): void
    {
        WP_Mock::userFunction('get_option', [
            'times'  => 1,
            'args'   => [Config::OPTIONS_TELEMETRY_ENABLED, false],
            'return' => $stored,
        ]);

        $this->assertSame($stored, Config::get(Config::OPTIONS_TELEMETRY_ENABLED, false));
    }

    /**
     * @return array<string, array{0: bool}>
     */
    public static function telemetryEnabledProvider(): array
    {
        return [
            'enabled'  => [true],
            'disabled' => [false],
        ];
    }

    /**
     * @covers \JtlWooCommerceConnector\Utilities\Config::get
     * @return void
     */
    public function testTelemetryDefaultsAndCastMapAreRegistered(): void
    {
        $this->assertArrayHasKey(
            Config::OPTIONS_TELEMETRY_ENABLED,
            Config::JTLWCC_CONFIG_DEFAULTS
        );
        $this->assertFalse(Config::JTLWCC_CONFIG_DEFAULTS[Config::OPTIONS_TELEMETRY_ENABLED]);

        $this->assertArrayHasKey(
            Config::OPTIONS_TELEMETRY_ENDPOINT,
            Config::JTLWCC_CONFIG_DEFAULTS
        );

        $this->assertSame('bool', Config::JTLWCC_CONFIG[Config::OPTIONS_TELEMETRY_ENABLED]);
        $this->assertSame('string', Config::JTLWCC_CONFIG[Config::OPTIONS_TELEMETRY_ENDPOINT]);
    }
}
