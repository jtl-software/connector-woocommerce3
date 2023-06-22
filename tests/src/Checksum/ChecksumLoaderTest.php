<?php

namespace JtlWooCommerceConnector\Tests\Checksum;

use Jtl\Connector\Core\Definition\IdentityType;
use JtlWooCommerceConnector\Tests\AbstractTestCase;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\CannotUseOnlyMethodsException;
use PHPUnit\Framework\MockObject\ClassAlreadyExistsException;
use PHPUnit\Framework\MockObject\ClassIsFinalException;
use PHPUnit\Framework\MockObject\ClassIsReadonlyException;
use PHPUnit\Framework\MockObject\DuplicateMethodException;
use PHPUnit\Framework\MockObject\IncompatibleReturnValueException;
use PHPUnit\Framework\MockObject\InvalidMethodNameException;
use PHPUnit\Framework\MockObject\MethodCannotBeConfiguredException;
use PHPUnit\Framework\MockObject\MethodNameAlreadyConfiguredException;
use PHPUnit\Framework\MockObject\OriginalConstructorInvocationRequiredException;
use PHPUnit\Framework\MockObject\ReflectionException;
use PHPUnit\Framework\MockObject\RuntimeException;
use PHPUnit\Framework\MockObject\UnknownTypeException;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

class ChecksumLoader extends AbstractTestCase
{
    /**
     * @throws MethodCannotBeConfiguredException
     * @throws InvalidMethodNameException
     * @throws RuntimeException
     * @throws CannotUseOnlyMethodsException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws InvalidArgumentException
     * @throws ClassIsFinalException
     * @throws \PHPUnit\Framework\InvalidArgumentException
     * @throws ExpectationFailedException
     * @throws DuplicateMethodException
     * @throws MethodNameAlreadyConfiguredException
     * @throws ClassIsReadonlyException
     * @throws \Psr\Log\InvalidArgumentException
     * @throws ReflectionException
     * @throws UnknownTypeException
     * @throws IncompatibleReturnValueException
     * @throws ClassAlreadyExistsException
     */
    public function testRead(): void
    {
        $checksumId = \uniqid('', true);

        $db = $this->createDbMock(['queryOne']);
        $db->expects($this->once())->method('queryOne')->willReturn($checksumId);

        $checksumLoader = $this->getChecksumLoaderMock($db, ['getChecksumRead']);
        $checksumLoader->expects($this->once())->method('getChecksumRead');

        $result = $checksumLoader->read('c_1', IdentityType::CATEGORY);

        $this->assertEquals($checksumId, $result);
    }

    /**
     * @throws MethodCannotBeConfiguredException
     * @throws InvalidMethodNameException
     * @throws RuntimeException
     * @throws CannotUseOnlyMethodsException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws InvalidArgumentException
     * @throws ClassIsFinalException
     * @throws \PHPUnit\Framework\InvalidArgumentException
     * @throws ExpectationFailedException
     * @throws DuplicateMethodException
     * @throws MethodNameAlreadyConfiguredException
     * @throws ClassIsReadonlyException
     * @throws ReflectionException
     * @throws \Psr\Log\InvalidArgumentException
     * @throws UnknownTypeException
     * @throws IncompatibleReturnValueException
     * @throws ClassAlreadyExistsException
     */
    public function testWrite(): void
    {
        $checksumId = \uniqid('', true);
        $db         = $this->createDbMock(['query']);
        $db->expects($this->once())->method('query')->willReturn([]);

        $checksumLoader = $this->getChecksumLoaderMock($db, ['getChecksumWrite']);
        $checksumLoader->expects($this->once())->method('getChecksumWrite');

        $result = $checksumLoader->write('c_1', IdentityType::CATEGORY, $checksumId);
        $this->assertEquals([], $result);
    }

    /**
     * @throws MethodCannotBeConfiguredException
     * @throws InvalidMethodNameException
     * @throws RuntimeException
     * @throws CannotUseOnlyMethodsException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws InvalidArgumentException
     * @throws ClassIsFinalException
     * @throws \PHPUnit\Framework\InvalidArgumentException
     * @throws ExpectationFailedException
     * @throws DuplicateMethodException
     * @throws ClassIsReadonlyException
     * @throws MethodNameAlreadyConfiguredException
     * @throws \Psr\Log\InvalidArgumentException
     * @throws ReflectionException
     * @throws UnknownTypeException
     * @throws IncompatibleReturnValueException
     * @throws ClassAlreadyExistsException
     */
    public function testDelete(): void
    {
        $db = $this->createDbMock(['query']);
        $db->expects($this->once())->method('query')->willReturn([]);

        $checksumLoader = $this->getChecksumLoaderMock($db, ['getChecksumDelete']);
        $checksumLoader->expects($this->once())->method('getChecksumDelete');

        $result = $checksumLoader->delete('c_1', IdentityType::CATEGORY);
        $this->assertEquals([], $result);
    }

    /**
     * @throws InvalidMethodNameException
     * @throws ClassIsFinalException
     * @throws \PHPUnit\Framework\InvalidArgumentException
     * @throws DuplicateMethodException
     * @throws ClassIsReadonlyException
     * @throws RuntimeException
     * @throws CannotUseOnlyMethodsException
     * @throws ReflectionException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws UnknownTypeException
     * @throws ClassAlreadyExistsException
     */
    protected function getChecksumLoaderMock($dbMock, array $onlyMethods = [])
    {
        return $this->getMockBuilder(\JtlWooCommerceConnector\Checksum\ChecksumLoader::class)
            ->setConstructorArgs([$dbMock])
            ->onlyMethods($onlyMethods)
            ->getMock();
    }
}
