<?php

/** @noinspection PhpIllegalPsrClassPathInspection */

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Regression\CO2909 {

    use Jtl\Connector\Core\Model\Product;
    use JtlWooCommerceConnector\Controllers\Product\ProductGermanizedFieldsController;
    use JtlWooCommerceConnector\Utilities\Db;
    use JtlWooCommerceConnector\Utilities\Util;
    use phpmock\Mock;
    use phpmock\MockBuilder;
    use phpmock\MockEnabledException;
    use PHPUnit\Framework\TestCase;

    class ProductGermanizedFieldsTest extends TestCase
    {
        protected Mock $getLocale;

        /**
         * @return void
         * @throws \InvalidArgumentException
         * @throws MockEnabledException
         */
        protected function setUp(): void
        {
            parent::setUp();
            $this->getLocale = (new MockBuilder())->setNamespace('JtlWooCommerceConnector\Utilities')
                ->setName('get_locale')
                ->setFunction(
                    function () {
                        return 'de_DE';
                    }
                )->build();

            $this->getLocale->enable();
        }

        /**
         * @return void
         */
        protected function tearDown(): void
        {
            parent::tearDown();
            $this->getLocale->disable();
        }

        /**
         * @return void
         * @throws \ReflectionException
         */
        public function testCanUpdateGermanizedGpsrData(): void
        {
            self::expectNotToPerformAssertions();
            $product = new Product();

            $db   = $this->getMockBuilder(Db::class)->disableOriginalConstructor()->getMock();
            $util = $this->getMockBuilder(Util::class)->disableOriginalConstructor()->getMock();

            $germanizedController = new ProductGermanizedFieldsController($db, $util);

            $controller = new \ReflectionClass($germanizedController);
            $update     = $controller->getMethod('updateGermanizedGpsrData');

            $update->invoke($germanizedController, $product);
        }
    }
}

// phpcs:disable
namespace {
    class WP_Error {}

    function wp_get_object_terms($object_ids, $taxonomies, $args = [])
    {
        return new \WP_Error();
    }

    function update_post_meta($post_id, $meta_key, $meta_value, $prev_value = '')
    {
        return true;
    }

    function wp_remove_object_terms($object_id, $terms, $taxonomy)
    {
        throw new \RuntimeException('This method should never be called');
    }
}
// phpcs:enable