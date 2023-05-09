<?php

namespace JtlWooCommerceConnector\Tests\Controllers\Product {

    use jtl\Connector\Model\ProductAttrI18n;
    use Jtl\UnitTest\TestCase;
    use JtlWooCommerceConnector\Controllers\Product\ProductAttr;
    use PHPUnit\Framework\ExpectationFailedException;
    use PHPUnit\Framework\MockObject\RuntimeException;

    class ProductAttrTest extends TestCase
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
            $result = $this->invokeMethod(new ProductAttr(), 'hasWcAttributePrefix', $attributeName);
            $this->assertEquals($expectedResult, $result);
        }

        public function hasWcAttributePrefixDataProvider(): array
        {
            return [
                ['foo', false],
                ['wc_foo', true],
                ['wc__foo', true],
                ['wc-_foo', false],
                [111, false]
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
            $result = $this->invokeMethod(new ProductAttr(), 'convertLegacyAttributeName', $attributeName);
            $this->assertEquals($expectedAttributeName, $result);
        }

        public function convertLegacyAttributeNameDataProvider(): array
        {
            return [
                ['payable', 'wc_payable'],
                ['nosearch', 'wc_nosearch'],
                ['otherattr', 'otherattr'],
                ['wc_gm_digital', 'wc_gm_digital'],
                [100, 100]
            ];
        }

        /**
         * @dataProvider updateProductVisibilityDataProvider
         * @param string $visibilityType
         * @param array $expectedVisibilityArray
         * @return void
         * @throws \ReflectionException
         * @throws RuntimeException
         */
        public function testUpdateProductVisibility(string $visibilityType, array $expectedVisibilityArray): void
        {
            $productId = 100;

            $productAttrController = $this->getMockBuilder(ProductAttr::class)
                ->disableOriginalConstructor()
                ->setMethods(['wpRemoveObjectTerms', 'wpSetObjectTerms', 'updatePostMeta'])
                ->getMock();

            $productAttrController->expects($this->once())->method('wpRemoveObjectTerms')->with(
                $productId,
                ['exclude-from-catalog', 'exclude-from-search'],
                'product_visibility'
            );
            $productAttrController->expects($this->once())->method('wpSetObjectTerms')->with(
                $productId,
                $expectedVisibilityArray,
                'product_visibility'
            );
            $productAttrController->expects($this->once())->method('updatePostMeta')->with(
                $productId,
                '_visibility',
                $visibilityType
            );

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

        /**
         * @return void
         * @throws \ReflectionException
         * @throws \InvalidArgumentException
         * @throws ExpectationFailedException
         */
        public function testWcSanitizeTaxonomyName(): void
        {
            $attrI18n = new ProductAttrI18n();
            $attrI18n->setName('foo');
            $attrI18n->setValue('bar');


            $this->assertEquals(
                $attrI18n->getName(),
                $this->invokeMethod(new ProductAttr(), 'wcSanitizeTaxonomyName', $attrI18n->getName())
            );
            $this->assertEquals(
                $attrI18n->getName(),
                $this->invokeMethod(new ProductAttr(), 'wcSanitizeTaxonomyName', $attrI18n)
            );
        }
    }
}

namespace {
    if (!\function_exists('wc_sanitize_taxonomy_name')) {
        function wc_sanitize_taxonomy_name(string $name): string
        {
            return $name;
        }
    }
}
