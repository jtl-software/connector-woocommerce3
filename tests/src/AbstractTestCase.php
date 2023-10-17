<?php

namespace JtlWooCommerceConnector\Tests;

use DI\Container;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\Util;
use PHPUnit\Framework\InvalidArgumentException;
use PHPUnit\Framework\MockObject\CannotUseOnlyMethodsException;
use PHPUnit\Framework\MockObject\ClassAlreadyExistsException;
use PHPUnit\Framework\MockObject\ClassIsFinalException;
use PHPUnit\Framework\MockObject\ClassIsReadonlyException;
use PHPUnit\Framework\MockObject\DuplicateMethodException;
use PHPUnit\Framework\MockObject\InvalidMethodNameException;
use PHPUnit\Framework\MockObject\OriginalConstructorInvocationRequiredException;
use PHPUnit\Framework\MockObject\ReflectionException;
use PHPUnit\Framework\MockObject\RuntimeException;
use PHPUnit\Framework\MockObject\UnknownTypeException;
use PHPUnit\Framework\TestCase;

/**
 * Class AbstractTestCase
 * @package JtlWooCommerceConnector\Tests
 */
abstract class AbstractTestCase extends TestCase
{
    /**
     * @param object $object
     * @param string $methodName
     * @throws \ReflectionException
     */
    protected function invokeMethodFromObject(object $object, string $methodName, ...$arguments)
    {
        $reflectionClass  = new \ReflectionClass($object);
        $reflectionMethod = $reflectionClass->getMethod($methodName);
        $reflectionMethod->setAccessible(true);
        return $reflectionMethod->invoke($object, ...$arguments);
    }

    /**
     * @param object $object
     * @param string $propertyName
     * @return void
     * @throws \ReflectionException
     */
    protected function setPropertyValueFromObject(object $object, string $propertyName, $value): void
    {
        $reflectionClass    = new \ReflectionClass($object);
        $reflectionProperty = $reflectionClass->getProperty($propertyName);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($object, $value);
    }

    /**
     * @throws InvalidMethodNameException
     * @throws ClassIsFinalException
     * @throws InvalidArgumentException
     * @throws DuplicateMethodException
     * @throws ClassIsReadonlyException
     * @throws RuntimeException
     * @throws CannotUseOnlyMethodsException
     * @throws ReflectionException
     * @throws UnknownTypeException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws ClassAlreadyExistsException
     */
    protected function createDbMock(array $onlyMethods = [])
    {
        return $this->getMockBuilder(Db::class)
            ->disableOriginalConstructor()
            ->onlyMethods($onlyMethods)
            ->getMock();
    }

    /**
     * @throws InvalidMethodNameException
     * @throws ClassIsFinalException
     * @throws InvalidArgumentException
     * @throws DuplicateMethodException
     * @throws ClassIsReadonlyException
     * @throws RuntimeException
     * @throws ReflectionException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws UnknownTypeException
     * @throws ClassAlreadyExistsException
     */
    protected function createContainerMock()
    {
        return $this->getMockBuilder(Container::class)
            ->getMock();
    }

    /**
     * @throws InvalidMethodNameException
     * @throws ClassIsFinalException
     * @throws InvalidArgumentException
     * @throws DuplicateMethodException
     * @throws ClassIsReadonlyException
     * @throws RuntimeException
     * @throws ReflectionException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws UnknownTypeException
     * @throws ClassAlreadyExistsException
     */
    protected function createUtilMock()
    {
        return $this->getMockBuilder(Util::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
