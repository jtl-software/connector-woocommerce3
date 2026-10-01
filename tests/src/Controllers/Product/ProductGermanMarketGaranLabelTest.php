<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Controllers\Product {

    use Jtl\Connector\Core\Model\Identity;
    use Jtl\Connector\Core\Model\Product as ProductModel;
    use Jtl\Connector\Core\Model\ProductAttribute;
    use Jtl\Connector\Core\Model\TranslatableAttributeI18n;
    use JtlWooCommerceConnector\Controllers\Product\ProductGermanMarketFieldsController;
    use JtlWooCommerceConnector\Tests\AbstractTestCase;
    use JtlWooCommerceConnector\Utilities\Db;
    use JtlWooCommerceConnector\Utilities\Util;

    /**
     * CO-3606: transfer the German Market warranty label (EU GARAN label) values from JTL-Wawi
     * function attributes into the corresponding German Market meta keys.
     */
    class ProductGermanMarketGaranLabelTest extends AbstractTestCase
    {
        /**
         * @param array<int, array{0: string, 1: string, 2?: string}> $attributes
         * @param array<string, string>                               $expected
         * @param bool                                                $wooCommerceLanguage
         * @dataProvider collectGaranLabelMetaValuesDataProvider
         * @covers       \JtlWooCommerceConnector\Controllers\Product\ProductGermanMarketFieldsController::collectGaranLabelMetaValues
         * @return void
         * @throws \ReflectionException
         */
        public function testCollectGaranLabelMetaValues(
            array $attributes,
            array $expected,
            bool $wooCommerceLanguage = true
        ): void {
            $db   = $this->getMockBuilder(Db::class)->disableOriginalConstructor()->getMock();
            $util = $this->getMockBuilder(Util::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['isWooCommerceLanguage'])
                ->getMock();

            $util->method('isWooCommerceLanguage')->willReturn($wooCommerceLanguage);

            $controller = new ProductGermanMarketFieldsController($db, $util);

            $product = new ProductModel();
            $product->setId(new Identity('1', 1));

            foreach ($attributes as $attribute) {
                $name  = $attribute[0];
                $value = $attribute[1];
                $iso   = $attribute[2] ?? 'ger';

                $productAttribute = new ProductAttribute();
                $productAttribute->setI18ns(
                    (new TranslatableAttributeI18n())->setName($name)->setValue($value)->setLanguageIso($iso)
                );
                $product->addAttribute($productAttribute);
            }

            /** @var array<string, string> $result */
            $result = $this->invokeMethodFromObject($controller, 'collectGaranLabelMetaValues', $product);

            $this->assertSame($expected, $result);
        }

        /**
         * @return array<string, array<int, mixed>>
         */
        public function collectGaranLabelMetaValuesDataProvider(): array
        {
            return [
                'full data is mapped and warranty years normalized' => [
                    [
                        ['jtl_garan_label_producer', 'Manufacturer ABC'],
                        ['jtl_garan_label_model_identifier', 'MODEL-123'],
                        ['jtl_garan_label_warranty_years', '3'],
                    ],
                    [
                        '_german_market_garan_label_producer' => 'Manufacturer ABC',
                        '_german_market_garan_label_model_identifier' => 'MODEL-123',
                        '_german_market_garan_label_warranty_years' => '3',
                    ],
                ],
                'empty producer is skipped, other values are kept' => [
                    [
                        ['jtl_garan_label_producer', ''],
                        ['jtl_garan_label_model_identifier', 'MODEL-123'],
                        ['jtl_garan_label_warranty_years', '5'],
                    ],
                    [
                        '_german_market_garan_label_model_identifier' => 'MODEL-123',
                        '_german_market_garan_label_warranty_years' => '5',
                    ],
                ],
                'all empty values produce no meta updates' => [
                    [
                        ['jtl_garan_label_producer', ''],
                        ['jtl_garan_label_model_identifier', '   '],
                        ['jtl_garan_label_warranty_years', ''],
                    ],
                    [],
                ],
                'non numeric warranty years is skipped' => [
                    [
                        ['jtl_garan_label_producer', 'Manufacturer ABC'],
                        ['jtl_garan_label_warranty_years', 'three'],
                    ],
                    [
                        '_german_market_garan_label_producer' => 'Manufacturer ABC',
                    ],
                ],
                'decimal warranty years is normalized to integer string' => [
                    [
                        ['jtl_garan_label_warranty_years', '3.0'],
                    ],
                    [
                        '_german_market_garan_label_warranty_years' => '3',
                    ],
                ],
                'unrelated attributes are ignored' => [
                    [
                        ['gpsr_manufacturer_name', 'Manufacturer ABC'],
                        ['some_other_attribute', 'value'],
                        ['jtl_garan_label_model_identifier', 'MODEL-123'],
                    ],
                    [
                        '_german_market_garan_label_model_identifier' => 'MODEL-123',
                    ],
                ],
                'variation with only a model identifier maps just that field' => [
                    [
                        ['jtl_garan_label_model_identifier', 'VARIANT-987'],
                    ],
                    [
                        '_german_market_garan_label_model_identifier' => 'VARIANT-987',
                    ],
                ],
                'attributes in a non woocommerce language are ignored' => [
                    [
                        ['jtl_garan_label_producer', 'Manufacturer ABC'],
                        ['jtl_garan_label_model_identifier', 'MODEL-123'],
                    ],
                    [],
                    false,
                ],
            ];
        }
    }
}
