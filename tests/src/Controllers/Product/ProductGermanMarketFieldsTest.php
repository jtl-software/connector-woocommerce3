<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Controllers\Product {

    use Jtl\Connector\Core\Model\Identity;
    use Jtl\Connector\Core\Model\Product;
    use Jtl\Connector\Core\Model\Product as ProductModel;
    use Jtl\Connector\Core\Model\ProductAttribute;
    use Jtl\Connector\Core\Model\TranslatableAttributeI18n;
    use JtlWooCommerceConnector\Controllers\Product\ProductGermanMarketFieldsController;
    use JtlWooCommerceConnector\Tests\AbstractTestCase;
    use JtlWooCommerceConnector\Utilities\Db;
    use JtlWooCommerceConnector\Utilities\Util;

    class ProductGermanMarketFieldsTest extends AbstractTestCase
    {
        /**
         * @param ProductModel       $product
         * @param array<int, string> $expectedAdresses
         * @dataProvider createManufacturerAndResponsibleStringsDataProvider
         * @covers ProductGermanMarketFieldsController::createManufacturerAndResponsibleStrings
         * @return void
         */
        public function testCreateManufacturerAndResponsibleStrings(
            ProductModel $product,
            array $expectedAdresses
        ): void {
            $db   = $this->getMockBuilder(Db::class)->disableOriginalConstructor()->getMock();
            $util = $this->getMockBuilder(Util::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['isWooCommerceLanguage'])
                ->getMock();

            $util->method('isWooCommerceLanguage')->willReturn(true);

            $germanMarketController = new ProductGermanMarketFieldsController($db, $util);

            $controller                 = new \ReflectionClass($germanMarketController);
            $updateGermanMarketGpsrData = $controller->getMethod('createManufacturerAndResponsibleStrings');
            $updateGermanMarketGpsrData->setAccessible(true);

            $result = $updateGermanMarketGpsrData->invoke($germanMarketController, $product);

            $this->assertEquals($expectedAdresses[0], $result[0]);
            $this->assertEquals($expectedAdresses[1], $result[1]);
        }

        /**
         * @return array<int, array<int, Product|array<int, string>>>
         * @throws \JsonException
         * @throws \Jtl\Connector\Core\Exception\TranslatableAttributeException
         */
        public function createManufacturerAndResponsibleStringsDataProvider(): array
        {
            $product = new ProductModel();
            $product->setId(new Identity(1, 1));

            $attributes = [
                ['gpsr_manufacturer_name', 'Manufacturer ABC'],
                ['gpsr_manufacturer_street', 'Hauptstraße'],
                ['gpsr_manufacturer_housenumber', '123'],
                ['gpsr_manufacturer_postalcode', '12345'],
                ['gpsr_manufacturer_city', 'Berlin'],
                ['gpsr_manufacturer_state', 'Berlin'],
                ['gpsr_manufacturer_country', 'Deutschland'],
                ['gpsr_manufacturer_email', 'abc@mail.com'],
                ['gpsr_manufacturer_homepage', 'www.abc.com'],
                ['gpsr_responsibleperson_name', 'Verantwortlicher ABC'],
                ['gpsr_responsibleperson_street', 'Nebenstraße'],
                ['gpsr_responsibleperson_housenumber', '456'],
                ['gpsr_responsibleperson_postalcode', '12123'],
                ['gpsr_responsibleperson_city', 'Berlin'],
                ['gpsr_responsibleperson_state', 'Berlin'],
                ['gpsr_responsibleperson_country', 'Deutschland'],
                ['gpsr_responsibleperson_email', 'verantwortlich@mail.com'],
                ['gpsr_responsibleperson_homepage', 'www.verantwortlich.com'],
            ];

            foreach ($attributes as [$name, $value]) {
                $attribute = new ProductAttribute();
                $attribute->setI18ns((new TranslatableAttributeI18n())->setName($name)->setValue($value));
                $product->addAttribute($attribute);
            }

            $expectedManufacturerAdress = 'Manufacturer ABC' . "\n"
                . 'Hauptstraße 123' . "\n"
                . '12345 Berlin' . "\n"
                . 'Berlin Deutschland' . "\n"
                . 'abc@mail.com' . "\n"
                . 'www.abc.com';

            $expectedResponsiblePersonAdress = 'Verantwortlicher ABC' . "\n"
                . 'Nebenstraße 456' . "\n"
                . '12123 Berlin' . "\n"
                . 'Berlin Deutschland' . "\n"
                . 'verantwortlich@mail.com' . "\n"
                . 'www.verantwortlich.com';

            return [
                [$product, [$expectedManufacturerAdress, $expectedResponsiblePersonAdress]],
            ];
        }
    }
}
