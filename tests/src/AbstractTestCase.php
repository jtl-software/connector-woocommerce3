<?php

declare(strict_types=1);

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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\OriginalConstructorInvocationRequiredException;
use PHPUnit\Framework\MockObject\ReflectionException;
use PHPUnit\Framework\MockObject\RuntimeException;
use PHPUnit\Framework\MockObject\UnknownTypeException;
use PHPUnit\Framework\TestCase;

/**
 * Class AbstractTestCase
 *
 * @package JtlWooCommerceConnector\Tests
 */
abstract class AbstractTestCase extends TestCase
{
    /**
     * @param object $object
     * @param string $methodName
     * @param mixed  ...$arguments
     * @return mixed
     * @throws \ReflectionException
     */
    protected function invokeMethodFromObject(object $object, string $methodName, mixed ...$arguments): mixed
    {
        $reflectionClass  = new \ReflectionClass($object);
        $reflectionMethod = $reflectionClass->getMethod($methodName);
        $reflectionMethod->setAccessible(true);
        return $reflectionMethod->invoke($object, ...$arguments);
    }

    /**
     * @param object $object
     * @param string $propertyName
     * @param mixed  $value
     * @return void
     * @throws \ReflectionException
     */
    protected function setPropertyValueFromObject(object $object, string $propertyName, mixed $value): void
    {
        $reflectionClass    = new \ReflectionClass($object);
        $reflectionProperty = $reflectionClass->getProperty($propertyName);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($object, $value);
    }

    /**
     * @param string[] $onlyMethods
     * @return MockObject
     * @throws CannotUseOnlyMethodsException
     * @throws ClassAlreadyExistsException
     * @throws ClassIsFinalException
     * @throws ClassIsReadonlyException
     * @throws DuplicateMethodException
     * @throws InvalidArgumentException
     * @throws InvalidMethodNameException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws ReflectionException
     * @throws RuntimeException
     * @throws UnknownTypeException
     */
    protected function createDbMock(array $onlyMethods = []): MockObject
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
     * @return MockObject
     */
    protected function createContainerMock(): MockObject
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
     * @return MockObject
     */
    protected function createUtilMock(): MockObject
    {
        return $this->getMockBuilder(Util::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
