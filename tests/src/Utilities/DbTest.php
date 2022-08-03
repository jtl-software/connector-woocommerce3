<?php

namespace JtlWooCommerceConnector\Tests\Utilities;

use JtlWooCommerceConnector\Tests\AbstractTestCase;
use JtlWooCommerceConnector\Utilities\Db;

class DbTest extends AbstractTestCase
{
    public function testInitialization()
    {
        $wpDb = $this->getMockBuilder('\wpdb')->getMock();
        $db = new Db($wpDb);

        $reflection = new \ReflectionClass($db);
        $wpDbProperty = $reflection->getProperty('wpDb');
        $wpDbProperty->setAccessible(true);
        $wpDbPropertyValue = $wpDbProperty->getValue($db);

        $this->assertInstanceOf('\wpdb', $wpDbPropertyValue);
    }
}