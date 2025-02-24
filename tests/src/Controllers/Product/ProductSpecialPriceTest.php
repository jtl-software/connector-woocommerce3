<?php

namespace JtlWooCommerceConnector\Tests\Controllers\Product;

use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\Product as ProductModel;
use JtlWooCommerceConnector\Controllers\Product\ProductSpecialPriceController;
use JtlWooCommerceConnector\Tests\AbstractTestCase;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\Util;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\InvalidArgumentException;
use PHPUnit\Framework\MockObject\CannotUseOnlyMethodsException;
use PHPUnit\Framework\MockObject\ClassAlreadyExistsException;
use PHPUnit\Framework\MockObject\ClassIsFinalException;
use PHPUnit\Framework\MockObject\ClassIsReadonlyException;
use PHPUnit\Framework\MockObject\DuplicateMethodException;
use PHPUnit\Framework\MockObject\IncompatibleReturnValueException;
use PHPUnit\Framework\MockObject\InvalidMethodNameException;
use PHPUnit\Framework\MockObject\OriginalConstructorInvocationRequiredException;
use PHPUnit\Framework\MockObject\ReflectionException;
use PHPUnit\Framework\MockObject\RuntimeException;
use PHPUnit\Framework\MockObject\UnknownTypeException;
use PHPUnit\Framework\TestCase;

class ProductSpecialPriceTest extends AbstractTestCase
{

    /**
     * @param string $productId
     * @param string $pluginVersion
     * @param string $postName
     * @param string|null $expectedMetaKeyValue
     * @return void
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws CannotUseOnlyMethodsException
     * @throws ClassAlreadyExistsException
     * @throws ClassIsFinalException
     * @throws ClassIsReadonlyException
     * @throws DuplicateMethodException
     * @throws IncompatibleReturnValueException
     * @throws InvalidMethodNameException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws ReflectionException
     * @throws RuntimeException
     * @throws UnknownTypeException
     * @throws \ReflectionException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @dataProvider setPostMetaKeyDataProvider
     * @covers ProductSpecialPriceController::setPostMetaKey
     */
    public function testSetPostMetaKey(
        string $productId,
        string $pluginVersion,
        string $postName,
        ?string $expectedMetaKeyValue
    ): void {
        $db   = $this->getMockBuilder(Db::class)->disableOriginalConstructor()->getMock();
        $util = $this->getMockBuilder(Util::class)->disableOriginalConstructor()->getMock();

        $productSpecialPriceController = $this->getMockBuilder(ProductSpecialPriceController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['comparePluginVersion'])
            ->getMock();

        $pluginVersionSmaller = false;

        if ($pluginVersion < '1.0.8.0') {
            $pluginVersionSmaller = true;
        }

        $productSpecialPriceController->method('comparePluginVersion')
            ->willReturn($pluginVersionSmaller);

        $reflection = new \ReflectionClass($productSpecialPriceController);
        $method     = $reflection->getMethod('setPostMetaKey');
        $method->setAccessible(false);

        $result = $method->invoke($productSpecialPriceController, $productId, $postName);
        $this->assertSame($expectedMetaKeyValue, $result);
    }

    /**
     * @return array[]
     * @throws \JsonException
     * @throws \Jtl\Connector\Core\Exception\TranslatableAttributeException
     */
    public function setPostMetaKeyDataProvider(): array
    {
        $product = new ProductModel();
        $product->setId(new Identity(1, 1));

        return [
            ['1111', '1.0.8.1', 'customer', null],
            ['1234', '2.0.1', 'guest', null]
        ];
    }
}
