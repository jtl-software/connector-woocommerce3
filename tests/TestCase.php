<?php

namespace JtlWooCommerceConnector\Tests;

/**
 * Class TestCase
 * @package JtlWooCommerceConnector\Tests
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     *
     */
    protected function tearDown()
    {
        \Mockery::close();
        parent::tearDown();
    }
}