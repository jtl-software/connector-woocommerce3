<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests;

/**
 * Class TestCase
 *
 * @package JtlWooCommerceConnector\Tests
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     * @throws \Exception
     */
    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
