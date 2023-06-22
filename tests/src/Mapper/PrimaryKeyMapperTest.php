<?php

namespace JtlWooCommerceConnector\Tests\Mapper;

use Jtl\Connector\Core\Definition\IdentityType;
use JtlWooCommerceConnector\Mapper\PrimaryKeyMapper;
use JtlWooCommerceConnector\Tests\AbstractTestCase;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\InvalidArgumentException;
use PHPUnit\Framework\MockObject\CannotUseOnlyMethodsException;
use PHPUnit\Framework\MockObject\ClassAlreadyExistsException;
use PHPUnit\Framework\MockObject\ClassIsFinalException;
use PHPUnit\Framework\MockObject\ClassIsReadonlyException;
use PHPUnit\Framework\MockObject\DuplicateMethodException;
use PHPUnit\Framework\MockObject\IncompatibleReturnValueException;
use PHPUnit\Framework\MockObject\InvalidMethodNameException;
use PHPUnit\Framework\MockObject\MethodCannotBeConfiguredException;
use PHPUnit\Framework\MockObject\MethodNameAlreadyConfiguredException;
use PHPUnit\Framework\MockObject\MethodNameNotConfiguredException;
use PHPUnit\Framework\MockObject\MethodParametersAlreadyConfiguredException;
use PHPUnit\Framework\MockObject\OriginalConstructorInvocationRequiredException;
use PHPUnit\Framework\MockObject\ReflectionException;
use PHPUnit\Framework\MockObject\RuntimeException;
use PHPUnit\Framework\MockObject\UnknownTypeException;

class PrimaryKeyMapperTest extends AbstractTestCase
{
    /**
     * @throws MethodCannotBeConfiguredException
     * @throws RuntimeException
     * @throws ClassIsFinalException
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     * @throws DuplicateMethodException
     * @throws ClassIsReadonlyException
     * @throws \Psr\Log\InvalidArgumentException
     * @throws ClassAlreadyExistsException
     * @throws InvalidMethodNameException
     * @throws CannotUseOnlyMethodsException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws MethodNameAlreadyConfiguredException
     * @throws ReflectionException
     * @throws UnknownTypeException
     * @throws IncompatibleReturnValueException
     */
    public function testGetHostId(): void
    {
        $db = $this->createDbMock(['queryOne']);
        $db->expects($this->once())->method('queryOne')->willReturn(1);

        $sqlHelper = $this->createSqlHelperMock();

        $primaryKeyMapper = $this->createPrimaryKeyMapperMock([$db, $sqlHelper]);

        $result = $primaryKeyMapper->getHostId(IdentityType::CATEGORY, 'c_1');

        $this->assertEquals(1, $result);
    }

    /**
     * @throws MethodCannotBeConfiguredException
     * @throws RuntimeException
     * @throws ClassIsFinalException
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws DuplicateMethodException
     * @throws ClassIsReadonlyException
     * @throws \Psr\Log\InvalidArgumentException
     * @throws ClassAlreadyExistsException
     * @throws InvalidMethodNameException
     * @throws CannotUseOnlyMethodsException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws MethodNameAlreadyConfiguredException
     * @throws ReflectionException
     * @throws UnknownTypeException
     * @throws IncompatibleReturnValueException
     */
    public function testGetEndpointId(): void
    {
        $db = $this->createDbMock(['queryOne']);
        $db->expects($this->once())->method('queryOne')->willReturn('c_1');

        $sqlHelper = $this->createSqlHelperMock();

        $primaryKeyMapper = $this->createPrimaryKeyMapperMock([$db, $sqlHelper]);

        $result = $primaryKeyMapper->getEndpointId(IdentityType::CATEGORY, 1);

        $this->assertEquals('c_1', $result);
    }

    /**
     * @dataProvider saveDifferentIdentitiesDataProvider
     * @param int $type
     * @param string $endpointId
     * @param int $hostId
     * @return void
     * @throws IncompatibleReturnValueException
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function testSave(int $type, string $endpointId, int $hostId): void
    {
        $db = $this->createDbMock(['query']);
        $db->expects($this->once())->method('query')->willReturn([1]);

        $sqlHelper = $this->createSqlHelperMock();

        $primaryKeyMapper = $this->createPrimaryKeyMapperMock([$db, $sqlHelper]);

        $result = $primaryKeyMapper->save($type, $endpointId, $hostId);

        $this->assertTrue($result);
    }

    public function saveDifferentIdentitiesDataProvider(): array
    {
        return [
            [IdentityType::PRODUCT_IMAGE, 'p_11_10', 1],
            [IdentityType::CATEGORY_IMAGE, 'c_11', 1],
            [IdentityType::CUSTOMER_GROUP, 'customer', 1],
            [IdentityType::CUSTOMER, '1', 1],
            [IdentityType::CROSS_SELLING_GROUP, '1', 1],
        ];
    }

    /**
     * @throws MethodCannotBeConfiguredException
     * @throws RuntimeException
     * @throws MethodNameNotConfiguredException
     * @throws MethodParametersAlreadyConfiguredException
     * @throws ClassIsFinalException
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     * @throws DuplicateMethodException
     * @throws ClassIsReadonlyException
     * @throws \Psr\Log\InvalidArgumentException
     * @throws Exception
     * @throws ClassAlreadyExistsException
     * @throws InvalidMethodNameException
     * @throws CannotUseOnlyMethodsException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws MethodNameAlreadyConfiguredException
     * @throws ReflectionException
     * @throws UnknownTypeException
     * @throws IncompatibleReturnValueException
     */
    public function testDelete(): void
    {
        $db = $this->createDbMock(['query']);
        $db->expects($this->once())->method('query')->willReturn(1);

        $where = "WHERE endpoint_id = 'c_1' AND host_id = 1 AND type = 1";
        $table = 'jtl_connector_link_category';

        $sqlHelper = $this->createSqlHelperMock(['primaryKeyMappingDelete']);
        $sqlHelper->expects($this->once())->method('primaryKeyMappingDelete')->with($where, $table);

        $primaryKeyMapper = $this->createPrimaryKeyMapperMock([$db, $sqlHelper]);

        $result = $primaryKeyMapper->delete(IdentityType::CATEGORY, 'c_1', 1);

        $this->assertTrue($result);
    }

    /**
     * @throws InvalidMethodNameException
     * @throws ClassIsFinalException
     * @throws InvalidArgumentException
     * @throws DuplicateMethodException
     * @throws RuntimeException
     * @throws ClassIsReadonlyException
     * @throws CannotUseOnlyMethodsException
     * @throws ReflectionException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws UnknownTypeException
     * @throws ClassAlreadyExistsException
     */
    protected function createPrimaryKeyMapperMock(array $constructorArgs = [], array $onlyMethods = [])
    {
        return $this->getMockBuilder(PrimaryKeyMapper::class)
            ->setConstructorArgs($constructorArgs)
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
     * @throws CannotUseOnlyMethodsException
     * @throws ReflectionException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws UnknownTypeException
     * @throws ClassAlreadyExistsException
     */
    protected function createSqlHelperMock(array $onlyMethods = [])
    {
        return $this->getMockBuilder(SqlHelper::class)
            ->onlyMethods($onlyMethods)
            ->disableOriginalConstructor()
            ->getMock();
    }
}