<?php

declare(strict_types=1);

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

namespace JtlWooCommerceConnector\Tests;

use PHPUnit\Framework\TestCase;
use WP_Mock;

require_once __DIR__ . '/../../includes/JtlConnectorAdmin.php';

/**
 * @covers \JtlConnectorAdmin
 */
class JtlConnectorAdminTest extends TestCase
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
     * @covers \JtlConnectorAdmin::setDefaultWooCommerceTaxOptions
     * @return void
     * @throws \ReflectionException
     */
    public function testSetDefaultWooCommerceTaxOptionsSetsValuesWhenOptionsDoNotExistYet(): void
    {
        WP_Mock::userFunction('get_option', [
            'times' => 1,
            'args' => ['woocommerce_tax_display_shop'],
            'return' => false,
        ]);

        WP_Mock::userFunction('get_option', [
            'times' => 1,
            'args' => ['woocommerce_tax_display_cart'],
            'return' => false,
        ]);

        WP_Mock::userFunction('update_option', [
            'times' => 1,
            'args' => ['woocommerce_tax_display_shop', 'incl', true],
        ]);

        WP_Mock::userFunction('update_option', [
            'times' => 1,
            'args' => ['woocommerce_tax_display_cart', 'incl', true],
        ]);

        $reflection = new \ReflectionClass(\JtlConnectorAdmin::class);
        $method     = $reflection->getMethod('setDefaultWooCommerceTaxOptions');
        $method->invoke(null);

        $this->addToAssertionCount(1);
    }

    /**
     * Regression test for CO-3447: WooCommerce writes its own 'excl' default for these
     * options during its own installation, so get_option() never returns false on a real
     * install. The fix has to correct that pre-existing 'excl' value, not just handle the
     * (rarely occurring) case where the option is entirely unset.
     *
     * @covers \JtlConnectorAdmin::setDefaultWooCommerceTaxOptions
     * @return void
     * @throws \ReflectionException
     */
    public function testSetDefaultWooCommerceTaxOptionsCorrectsWooCommerceDefaultExclValue(): void
    {
        WP_Mock::userFunction('get_option', [
            'times' => 1,
            'args' => ['woocommerce_tax_display_shop'],
            'return' => 'excl',
        ]);

        WP_Mock::userFunction('get_option', [
            'times' => 1,
            'args' => ['woocommerce_tax_display_cart'],
            'return' => 'excl',
        ]);

        WP_Mock::userFunction('update_option', [
            'times' => 1,
            'args' => ['woocommerce_tax_display_shop', 'incl', true],
        ]);

        WP_Mock::userFunction('update_option', [
            'times' => 1,
            'args' => ['woocommerce_tax_display_cart', 'incl', true],
        ]);

        $reflection = new \ReflectionClass(\JtlConnectorAdmin::class);
        $method     = $reflection->getMethod('setDefaultWooCommerceTaxOptions');
        $method->invoke(null);

        $this->addToAssertionCount(1);
    }

    /**
     * @covers \JtlConnectorAdmin::setDefaultWooCommerceTaxOptions
     * @return void
     * @throws \ReflectionException
     */
    public function testSetDefaultWooCommerceTaxOptionsDoesNotOverwriteWhenAlreadyIncl(): void
    {
        WP_Mock::userFunction('get_option', [
            'times' => 1,
            'args' => ['woocommerce_tax_display_shop'],
            'return' => 'incl',
        ]);

        WP_Mock::userFunction('get_option', [
            'times' => 1,
            'args' => ['woocommerce_tax_display_cart'],
            'return' => 'incl',
        ]);

        WP_Mock::userFunction('update_option', [
            'times' => 0,
        ]);

        $reflection = new \ReflectionClass(\JtlConnectorAdmin::class);
        $method     = $reflection->getMethod('setDefaultWooCommerceTaxOptions');
        $method->invoke(null);

        \Mockery::close();
        $this->addToAssertionCount(1);
    }

    /**
     * @covers \JtlConnectorAdmin::isFirstInstall
     * @return void
     * @throws \ReflectionException
     */
    public function testIsFirstInstallReturnsTrueWhenNoVersionIsStoredYet(): void
    {
        WP_Mock::userFunction('get_option', [
            'times' => 1,
            'args' => ['jtlconnector_installed_version', null],
            'return' => null,
        ]);

        $reflection = new \ReflectionClass(\JtlConnectorAdmin::class);
        $method     = $reflection->getMethod('isFirstInstall');

        $this->assertTrue($method->invoke(null));
    }

    /**
     * @covers \JtlConnectorAdmin::isFirstInstall
     * @return void
     * @throws \ReflectionException
     */
    public function testIsFirstInstallReturnsFalseWhenVersionIsAlreadyStored(): void
    {
        WP_Mock::userFunction('get_option', [
            'times' => 1,
            'args' => ['jtlconnector_installed_version', null],
            'return' => '1.0.0',
        ]);

        $reflection = new \ReflectionClass(\JtlConnectorAdmin::class);
        $method     = $reflection->getMethod('isFirstInstall');

        $this->assertFalse($method->invoke(null));
    }
}
