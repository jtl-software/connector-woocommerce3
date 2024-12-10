<?php

namespace JtlWooCommerceConnector\Tests\Controllers\Product;

use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\Product as ProductModel;
use JtlWooCommerceConnector\Controllers\Product\ProductSpecialPriceController;
use JtlWooCommerceConnector\Tests\AbstractTestCase;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;
use PHPUnit\Framework\TestCase;

class ProductSpecialPriceTest extends AbstractTestCase
{

    /**
     * @param ProductModel $product
     * @param string $productType
     * @return void
     * @dataProvider setPostMetaKeyDataProvider
     * @throws \Exception
     */
    public function testSetPostMetaKey(
        string $productId,
        $pluginVersion,
        $expectedMetaKeyValue
    ): void {
        $db   = $this->getMockBuilder(Db::class)->disableOriginalConstructor()->getMock();
        $util = $this->getMockBuilder(Util::class)->disableOriginalConstructor()->getMock();

        #$productSpecialPriceController = $this->getMockBuilder(ProductSpecialPriceController::class)
        #    ->setConstructorArgs([$db, $util])
        #   ->getMock();

        $productSpecialPriceController = new ProductSpecialPriceController($db, $util);

        $reflection = new \ReflectionClass($productSpecialPriceController);
        $method     = $reflection->getMethod('setPostMetaKey');
        $method->setAccessible(true);

        $result = $method->invoke($productSpecialPriceController, $productId, null);
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


        $pluginVersion = '1.0.8.1';

        return [
            ['1111', $pluginVersion, null]
        ];
    }
}
