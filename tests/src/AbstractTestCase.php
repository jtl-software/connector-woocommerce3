<?php

namespace JtlWooCommerceConnector\Tests;

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
}
