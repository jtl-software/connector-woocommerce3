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
    public function testSetDefaultWooCommerceTaxOptionsSetsValuesOnFirstActivation(): void
    {
        WP_Mock::userFunction('get_option', [
            'times' => 1,
            'args' => ['woocommerce_tax_display_shop', false],
            'return' => false,
        ]);

        WP_Mock::userFunction('get_option', [
            'times' => 1,
            'args' => ['woocommerce_tax_display_cart', false],
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
     * @covers \JtlConnectorAdmin::setDefaultWooCommerceTaxOptions
     * @return void
     * @throws \ReflectionException
     */
    public function testSetDefaultWooCommerceTaxOptionsDoesNotOverwriteOnReActivation(): void
    {
        WP_Mock::userFunction('get_option', [
            'times' => 1,
            'args' => ['woocommerce_tax_display_shop', false],
            'return' => 'incl',
        ]);

        WP_Mock::userFunction('get_option', [
            'times' => 1,
            'args' => ['woocommerce_tax_display_cart', false],
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
}
