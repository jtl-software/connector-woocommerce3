<?php

namespace JtlWooCommerceConnector\Tests\Controllers\Product {

    use Jtl\Connector\Core\Model\ProductI18n;
    use JtlWooCommerceConnector\Controllers\Product\ProductAttrController;
    use JtlWooCommerceConnector\Tests\AbstractTestCase;
    use PHPUnit\Framework\ExpectationFailedException;
    use PHPUnit\Framework\MockObject\RuntimeException;

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
            $result = $this->invokeMethodFromObject(
                new ProductAttrController($this->createDbMock(), $this->createUtilMock()),
                'hasWcAttributePrefix',
                $attributeName
            );
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
            $result = $this->invokeMethodFromObject(
                new ProductAttrController($this->createDbMock(), $this->createUtilMock()),
                'convertLegacyAttributeName',
                $attributeName
            );
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
         * @throws RuntimeException
         */
        public function testUpdateProductVisibility(string $visibilityType, array $expectedVisibilityArray): void
        {
            $productId = 100;

            $productAttrController = $this->getMockBuilder(ProductAttrController::class)
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

            $this->invokeMethodFromObject(
                $productAttrController,
                'updateProductVisibility',
                $visibilityType,
                $productId
            );
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
            $attrI18n = new ProductI18n();
            $attrI18n->setName('foo');

            $this->assertEquals(
                $attrI18n->getName(),
                $this->invokeMethodFromObject(
                    new ProductAttrController($this->createDbMock(), $this->createUtilMock()),
                    'wcSanitizeTaxonomyName',
                    $attrI18n->getName()
                )
            );
            $this->assertEquals(
                $attrI18n->getName(),
                $this->invokeMethodFromObject(
                    new ProductAttrController($this->createDbMock(), $this->createUtilMock()),
                    'wcSanitizeTaxonomyName',
                    $attrI18n
                )
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
