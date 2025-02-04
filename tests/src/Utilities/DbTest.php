<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Utilities;

use JtlWooCommerceConnector\Tests\AbstractTestCase;
use JtlWooCommerceConnector\Utilities\Db;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\ClassAlreadyExistsException;
use PHPUnit\Framework\MockObject\ClassIsFinalException;
use PHPUnit\Framework\MockObject\ClassIsReadonlyException;
use PHPUnit\Framework\MockObject\DuplicateMethodException;
use PHPUnit\Framework\MockObject\InvalidMethodNameException;
use PHPUnit\Framework\MockObject\OriginalConstructorInvocationRequiredException;
use PHPUnit\Framework\MockObject\ReflectionException;
use PHPUnit\Framework\MockObject\RuntimeException;
use PHPUnit\Framework\MockObject\UnknownTypeException;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

class DbTest extends AbstractTestCase
{
    /**
     * @return void
     * @throws InvalidMethodNameException
     * @throws RuntimeException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws InvalidArgumentException
     * @throws ClassIsFinalException
     * @throws ExpectationFailedException
     * @throws \PHPUnit\Framework\InvalidArgumentException
     * @throws DuplicateMethodException
     * @throws ClassIsReadonlyException
     * @throws ReflectionException
     * @throws UnknownTypeException
     * @throws Exception
     * @throws ClassAlreadyExistsException
     * @covers Db::__construct
     */
    public function testInitialization(): void
    {
        $wpDb = $this->getMockBuilder('\wpdb')->getMock();
        $db   = new Db($wpDb);

        $reflection   = new \ReflectionClass($db);
        $wpDbProperty = $reflection->getProperty('wpDb');
        $wpDbProperty->setAccessible(true);
        $wpDbPropertyValue = $wpDbProperty->getValue($db);

        $this->assertInstanceOf('\wpdb', $wpDbPropertyValue);
    }
}
