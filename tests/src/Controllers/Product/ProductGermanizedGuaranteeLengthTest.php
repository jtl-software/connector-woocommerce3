<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Controllers\Product {

    use Jtl\Connector\Core\Model\Identity;
    use Jtl\Connector\Core\Model\Product as ProductModel;
    use Jtl\Connector\Core\Model\TranslatableAttribute as ProductAttrModel;
    use Jtl\Connector\Core\Model\TranslatableAttributeI18n as ProductAttrI18nModel;
    use JtlWooCommerceConnector\Controllers\Product\ProductAttrController;
    use JtlWooCommerceConnector\Controllers\Product\ProductVaSpeAttrHandlerController;
    use JtlWooCommerceConnector\Tests\AbstractTestCase;
    use JtlWooCommerceConnector\Utilities\Db;
    use JtlWooCommerceConnector\Utilities\Util;
    use Psr\Log\LoggerInterface;

    /**
     * CO-3605: transfer the Germanized guarantee length (EU GARAN label, value in months) from a
     * JTL-Wawi function attribute into the Germanized `_guarantee_length` product meta.
     */
    class ProductGermanizedGuaranteeLengthTest extends AbstractTestCase
    {
        /**
         * @return void
         */
        public static function setUpBeforeClass(): void
        {
            parent::setUpBeforeClass();

            require_once __DIR__ . '/GermanizedGuaranteeLengthStubs.php';
        }

        /**
         * @param string      $value
         * @param string|null $expected
         * @dataProvider normalizeGuaranteeLengthValueDataProvider
         * @covers       \JtlWooCommerceConnector\Controllers\Product\ProductAttrController::normalizeGuaranteeLengthValue
         * @return void
         * @throws \ReflectionException
         */
        public function testNormalizeGuaranteeLengthValue(string $value, ?string $expected): void
        {
            $db   = $this->getMockBuilder(Db::class)->disableOriginalConstructor()->getMock();
            $util = $this->getMockBuilder(Util::class)->disableOriginalConstructor()->getMock();

            $controller = new ProductAttrController($db, $util);

            /** @var string|null $result */
            $result = $this->invokeMethodFromObject($controller, 'normalizeGuaranteeLengthValue', $value);

            $this->assertSame($expected, $result);
        }

        /**
         * @return array<string, array{0: string, 1: string|null}>
         */
        public function normalizeGuaranteeLengthValueDataProvider(): array
        {
            return [
                'valid month value is kept'                  => ['36', '36'],
                'value on the garan label threshold is kept' => ['30', '30'],
                'surrounding whitespace is trimmed'          => [' 48 ', '48'],
                'decimal value is normalized to integer'     => ['30.0', '30'],
                'zero is a valid non-negative value'         => ['0', '0'],
                'empty string is skipped'                    => ['', null],
                'whitespace only is skipped'                 => ['   ', null],
                'non numeric value is skipped'               => ['two years', null],
            ];
        }

        /**
         * @param int|string $guaranteeLength
         * @param string     $languageIso
         * @param string     $expectedValue
         * @param string     $expectedLanguageIso
         * @dataProvider guaranteeLengthAttributeDataProvider
         * @covers       \JtlWooCommerceConnector\Controllers\Product\ProductVaSpeAttrHandlerController::getGuaranteeLengthAttribute
         * @return void
         * @throws \ReflectionException
         */
        public function testGetGuaranteeLengthAttribute(
            int|string $guaranteeLength,
            string $languageIso,
            string $expectedValue,
            string $expectedLanguageIso
        ): void {
            $db   = $this->getMockBuilder(Db::class)->disableOriginalConstructor()->getMock();
            $util = $this->getMockBuilder(Util::class)->disableOriginalConstructor()->getMock();

            $wcProduct = $this->getMockBuilder(\WC_Product::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['get_id'])
                ->getMock();
            $wcProduct->method('get_id')->willReturn(42);

            $gzdProduct = $this->getMockBuilder(\WC_GZD_Product::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['get_guarantee_length', 'get_wc_product'])
                ->getMock();
            $gzdProduct->method('get_guarantee_length')->willReturn($guaranteeLength);
            $gzdProduct->method('get_wc_product')->willReturn($wcProduct);

            $controller = new ProductVaSpeAttrHandlerController($db, $util);

            /** @var ProductAttrModel $attribute */
            $attribute = $this->invokeMethodFromObject(
                $controller,
                'getGuaranteeLengthAttribute',
                $gzdProduct,
                $languageIso
            );

            $this->assertInstanceOf(ProductAttrModel::class, $attribute);
            $this->assertFalse($attribute->getIsCustomProperty());
            $this->assertSame(
                '42_' . ProductVaSpeAttrHandlerController::GZD_GUARANTEE_LENGTH,
                $attribute->getId()->getEndpoint()
            );

            $i18ns = $attribute->getI18ns();
            $this->assertCount(1, $i18ns);

            /** @var ProductAttrI18nModel $i18n */
            $i18n = $i18ns[0];
            $this->assertSame(ProductVaSpeAttrHandlerController::GZD_GUARANTEE_LENGTH, $i18n->getName());
            $this->assertSame($expectedValue, $i18n->getValue());
            $this->assertSame($expectedLanguageIso, $i18n->getLanguageIso());
        }

        /**
         * @return array<string, array{0: int|string, 1: string, 2: string, 3: string}>
         */
        public function guaranteeLengthAttributeDataProvider(): array
        {
            return [
                'integer length is cast to string with language'  => [36, 'ger', '36', 'ger'],
                'garan label threshold is exposed'                => [30, 'eng', '30', 'eng'],
                'zero length round-trips'                         => [0, 'ger', '0', 'ger'],
                'missing language iso falls back to empty string' => [48, '', '48', ''],
                'string value is preserved'                       => ['24', 'ger', '24', 'ger'],
            ];
        }

        /**
         * @param int    $guaranteeLength
         * @param int    $manufacturerHost
         * @param string $ean
         * @param string $manufacturerNumber
         * @param bool   $expectsWarning
         * @param string $expectedMissing
         * @dataProvider garanLabelDataCompletenessDataProvider
         * @covers       \JtlWooCommerceConnector\Controllers\Product\ProductAttrController::logGaranLabelDataCompleteness
         * @return void
         * @throws \ReflectionException
         */
        public function testLogGaranLabelDataCompleteness(
            int $guaranteeLength,
            int $manufacturerHost,
            string $ean,
            string $manufacturerNumber,
            bool $expectsWarning,
            string $expectedMissing
        ): void {
            $db   = $this->getMockBuilder(Db::class)->disableOriginalConstructor()->getMock();
            $util = $this->getMockBuilder(Util::class)->disableOriginalConstructor()->getMock();

            $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

            if ($expectsWarning) {
                $logger->expects($this->once())
                    ->method('warning')
                    ->with($this->callback(function (string $message) use ($expectedMissing): bool {
                        return \str_contains($message, 'missing ' . $expectedMissing . ' in JTL-Wawi.');
                    }));
            } else {
                $logger->expects($this->never())->method('warning');
            }

            $controller = new ProductAttrController($db, $util);
            $controller->setLogger($logger);

            $product = new ProductModel();
            $product->setManufacturerId(new Identity('', $manufacturerHost));
            $product->setEan($ean);
            $product->setManufacturerNumber($manufacturerNumber);

            $this->invokeMethodFromObject(
                $controller,
                'logGaranLabelDataCompleteness',
                42,
                $guaranteeLength,
                $product
            );
        }

        /**
         * @return array<string, array{0: int, 1: int, 2: string, 3: string, 4: bool, 5: string}>
         */
        public function garanLabelDataCompletenessDataProvider(): array
        {
            return [
                'below threshold never warns'              => [24, 0, '', '', false, ''],
                'complete data on threshold does not warn' => [30, 5, '4006381333931', '', false, ''],
                'complete data via mpn does not warn'      => [36, 5, '', 'MODEL-123', false, ''],
                'missing manufacturer warns'               => [30, 0, '4006381333931', '', true, 'manufacturer'],
                'missing model number and gtin warns'      => [36, 5, '', '', true, 'model number/GTIN'],
                'missing both warns about both'            => [
                    48,
                    0,
                    '',
                    '',
                    true,
                    'manufacturer and model number/GTIN',
                ],
            ];
        }
    }
}
