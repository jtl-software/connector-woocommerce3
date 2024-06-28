<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests;

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

class ClassInitializationTest extends AbstractTestCase
{
    /**
     * @throws InvalidMethodNameException
     * @throws RuntimeException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws InvalidArgumentException
     * @throws ClassIsFinalException
     * @throws \PHPUnit\Framework\InvalidArgumentException
     * @throws ExpectationFailedException
     * @throws DuplicateMethodException
     * @throws ClassIsReadonlyException
     * @throws ReflectionException
     * @throws UnknownTypeException
     * @throws Exception
     * @throws ClassAlreadyExistsException
     */
    public function testClassInitialization(): void
    {
        $srcPath   = \sprintf('%s/src', \dirname(__DIR__, 2));
        $classList = $this->findClasses($srcPath);

        $classList = \array_filter($classList, static function ($classPath) {
            return \str_contains($classPath, 'Traits') === false;
        });

        foreach ($classList as $classPath) {
            $classFile = new \SplFileInfo($classPath);
            $className = \str_replace(
                \sprintf('.%s', $classFile->getExtension()),
                '',
                $classFile->getFilename()
            );

            $namespace = \str_replace([$srcPath, '/'], ['', '\\'], $classFile->getPath());

            $classNameWithNamespace = \sprintf('JtlWooCommerceConnector%s\%s', $namespace, $className);

            $mock = $this->getMockBuilder($classNameWithNamespace)->disableOriginalConstructor()->getMock();

            $this->assertInstanceOf($classNameWithNamespace, $mock);
        }
    }

    protected function findClasses(string $srcPath): array
    {
        return \glob(\sprintf("%s/{,*/,*/*/,*/*/*/}*.php", $srcPath), \GLOB_BRACE);
    }
}
