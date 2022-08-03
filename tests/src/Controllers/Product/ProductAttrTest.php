<?php

namespace JtlWooCommerceConnector\Tests\Controllers\Product;

use JtlWooCommerceConnector\Controllers\Product\ProductAttr;
use JtlWooCommerceConnector\Tests\AbstractTestCase;

class ProductAttrTest extends AbstractTestCase
{
    /**
     * @dataProvider hasWcAttributePrefixDataProvider
     * @param string $attributeName
     * @param bool $expectedResult
     * @return void
     * @throws \ReflectionException
     */
    public function testHasWcAttributePrefix(string $attributeName, bool $expectedResult): void
    {
        $result = $this->invokeMethod(new ProductAttr($this->createDbMock(), $this->createContainerMock()), 'hasWcAttributePrefix', $attributeName);
        $this->assertEquals($expectedResult, $result);
    }

    public function hasWcAttributePrefixDataProvider(): array
    {
        return [
            ['foo', false],
            ['wc_foo', true],
            ['wc__foo', true],
            ['wc-_foo', false]
        ];
    }

    /**
     * @dataProvider convertLegacyAttributeNameDataProvider
     * @param string $attributeName
     * @param string $expectedAttributeName
     * @return void
     * @throws \ReflectionException
     */
    public function testConvertLegacyAttributeName(string $attributeName, string $expectedAttributeName): void
    {
        $result = $this->invokeMethod(new ProductAttr($this->createDbMock(), $this->createContainerMock()), 'convertLegacyAttributeName', $attributeName);
        $this->assertEquals($expectedAttributeName, $result);
    }

    public function convertLegacyAttributeNameDataProvider(): array
    {
        return [
            ['payable', 'wc_payable'],
            ['nosearch', 'wc_nosearch'],
            ['otherattr', 'otherattr'],
            ['wc_gm_digital', 'wc_gm_digital']
        ];
    }

    /**
     * @dataProvider updateProductVisibilityDataProvider
     * @param string $visibilityType
     * @param array $expectedVisibilityArray
     * @return void
     * @throws \ReflectionException
     */
    public function testUpdateProductVisibility(string $visibilityType, array $expectedVisibilityArray): void
    {
        $productId = 100;

        $productAttrController = $this->getMockBuilder(ProductAttr::class)
            ->disableOriginalConstructor()
            ->setMethods(['wpRemoveObjectTerms', 'wpSetObjectTerms', 'updatePostMeta'])
            ->getMock();

        $productAttrController->expects($this->once())->method('wpRemoveObjectTerms')->with($productId, ['exclude-from-catalog', 'exclude-from-search'], 'product_visibility');
        $productAttrController->expects($this->once())->method('wpSetObjectTerms')->with($productId, $expectedVisibilityArray, 'product_visibility');
        $productAttrController->expects($this->once())->method('updatePostMeta')->with($productId, '_visibility', $visibilityType);

        $this->invokeMethod($productAttrController, 'updateProductVisibility', $visibilityType, $productId);
    }

    public function updateProductVisibilityDataProvider(): array
    {
        return [
            ['hidden', ['exclude-from-catalog', 'exclude-from-search']],
            ['catalog', ['exclude-from-search']],
            ['search', ['exclude-from-catalog']],
        ];
    }
}