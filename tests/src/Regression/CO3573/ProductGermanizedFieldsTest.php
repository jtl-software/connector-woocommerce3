<?php

/** @noinspection PhpIllegalPsrClassPathInspection */

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Regression\CO3573 {

    use Jtl\Connector\Core\Model\Identity;
    use Jtl\Connector\Core\Model\Product;
    use Jtl\Connector\Core\Model\ProductAttribute;
    use Jtl\Connector\Core\Model\TranslatableAttributeI18n;
    use JtlWooCommerceConnector\Controllers\Product\ProductGermanizedFieldsController;
    use JtlWooCommerceConnector\Tests\AbstractTestCase;
    use JtlWooCommerceConnector\Utilities\Db;
    use JtlWooCommerceConnector\Utilities\Util;

    /**
     * CO-3573: when the responsible person (GPSR) is removed in JTL-Wawi no
     * `gpsr_responsibleperson_*` attributes are sent anymore. The connector must then delete the
     * previously stored `formatted_eu_address` term meta from the manufacturer so that outdated GPSR
     * data no longer shows in the shop.
     */
    class ProductGermanizedFieldsTest extends AbstractTestCase
    {
        /**
         * @return void
         */
        public static function setUpBeforeClass(): void
        {
            parent::setUpBeforeClass();

            require_once __DIR__ . '/GpsrManufacturerStubs.php';
        }

        /**
         * @return void
         */
        protected function setUp(): void
        {
            parent::setUp();

            $GLOBALS['__co3573_calls']         = [];
            $GLOBALS['__co3573_existing_term'] = (object)['term_id' => 555];
        }

        /**
         * @return void
         * @throws \ReflectionException
         * @throws \Jtl\Connector\Core\Exception\TranslatableAttributeException
         * @covers \JtlWooCommerceConnector\Controllers\Product\ProductGermanizedFieldsController::updateGermanizedGpsrData
         */
        public function testResponsiblePersonRemovalDeletesEuAddressMeta(): void
        {
            $product = $this->createProduct([
                'gpsr_manufacturer_name'        => 'Manufacturer ABC',
                'gpsr_manufacturer_street'      => 'Main Street',
                'gpsr_manufacturer_housenumber' => '1',
            ]);

            $this->invokeGpsrUpdate($product);

            $this->assertContains(
                [555, 'formatted_eu_address'],
                $GLOBALS['__co3573_calls']['delete_term_meta'] ?? [],
                'The stored EU (responsible person) address must be deleted when no data is sent.'
            );

            foreach ($GLOBALS['__co3573_calls']['update_term_meta'] ?? [] as $call) {
                $this->assertNotSame(
                    'formatted_eu_address',
                    $call[1],
                    'formatted_eu_address must not be written when the responsible person is removed.'
                );
            }
        }

        /**
         * @return void
         * @throws \ReflectionException
         * @throws \Jtl\Connector\Core\Exception\TranslatableAttributeException
         * @covers \JtlWooCommerceConnector\Controllers\Product\ProductGermanizedFieldsController::updateGermanizedGpsrData
         */
        public function testResponsiblePersonPresentUpdatesEuAddressMeta(): void
        {
            $product = $this->createProduct([
                'gpsr_manufacturer_name'        => 'Manufacturer ABC',
                'gpsr_responsibleperson_name'   => 'John Doe',
                'gpsr_responsibleperson_street' => 'Responsible Street',
                'gpsr_responsibleperson_city'   => 'Responsible City',
            ]);

            $this->invokeGpsrUpdate($product);

            $euAddressWritten = false;
            foreach ($GLOBALS['__co3573_calls']['update_term_meta'] ?? [] as $call) {
                if ($call[1] === 'formatted_eu_address') {
                    $euAddressWritten = true;
                }
            }

            $this->assertTrue(
                $euAddressWritten,
                'formatted_eu_address must be written when the responsible person is present.'
            );

            $this->assertEmpty(
                $GLOBALS['__co3573_calls']['delete_term_meta'] ?? [],
                'formatted_eu_address must not be deleted when the responsible person is present.'
            );
        }

        /**
         * @param array<string, string> $attributes
         * @return Product
         */
        private function createProduct(array $attributes): Product
        {
            $product = new Product();
            $product->setId(new Identity('123', 1));

            foreach ($attributes as $name => $value) {
                $productAttribute = new ProductAttribute();
                $productAttribute->setI18ns(
                    (new TranslatableAttributeI18n())->setName($name)->setValue($value)->setLanguageIso('ger')
                );
                $product->addAttribute($productAttribute);
            }

            return $product;
        }

        /**
         * @param Product $product
         * @return void
         * @throws \ReflectionException
         */
        private function invokeGpsrUpdate(Product $product): void
        {
            $db   = $this->getMockBuilder(Db::class)->disableOriginalConstructor()->getMock();
            $util = $this->getMockBuilder(Util::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['isWooCommerceLanguage'])
                ->getMock();
            $util->method('isWooCommerceLanguage')->willReturn(true);

            $controller = new ProductGermanizedFieldsController($db, $util);

            $this->invokeMethodFromObject($controller, 'updateGermanizedGpsrData', $product);
        }
    }
}
