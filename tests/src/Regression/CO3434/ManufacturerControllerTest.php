<?php

/** @noinspection PhpIllegalPsrClassPathInspection */

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Regression\CO3434 {

    use Jtl\Connector\Core\Model\Identity;
    use Jtl\Connector\Core\Model\Manufacturer;
    use Jtl\Connector\Core\Model\ManufacturerI18n;
    use JtlWooCommerceConnector\Controllers\ManufacturerController;
    use JtlWooCommerceConnector\Integrations\Plugins\PluginsManager;
    use JtlWooCommerceConnector\Integrations\Plugins\Wpml\Wpml;
    use JtlWooCommerceConnector\Utilities\Db;
    use JtlWooCommerceConnector\Utilities\Util;
    use phpmock\Mock;
    use phpmock\MockBuilder;
    use phpmock\MockEnabledException;
    use PHPUnit\Framework\TestCase;

    class ManufacturerControllerTest extends TestCase
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
            $this->getLocale = (new MockBuilder())
                ->setNamespace('JtlWooCommerceConnector\Utilities')
                ->setName('get_locale')
                ->setFunction(function () {
                    return 'en_US';
                })
                ->build();

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
         * @throws \Exception
         * @covers \JtlWooCommerceConnector\Controllers\ManufacturerController::push
         */
        public function testNewManufacturerEndpointIdIsSetAfterInsert(): void
        {
            $db   = $this->getMockBuilder(Db::class)->disableOriginalConstructor()->getMock();
            $util = $this->getMockBuilder(Util::class)->disableOriginalConstructor()->getMock();
            $util->method('isWooCommerceLanguage')->willReturn(true);

            $wpml = $this->getMockBuilder(Wpml::class)->disableOriginalConstructor()->getMock();
            $wpml->method('canBeUsed')->willReturn(false);

            $pluginsManager = $this->getMockBuilder(PluginsManager::class)
                ->disableOriginalConstructor()
                ->getMock();

            $reflectionClass = new \ReflectionClass(ManufacturerController::class);
            /** @var ManufacturerController $controller */
            $controller = $reflectionClass->newInstanceWithoutConstructor();

            $dbProp = $reflectionClass->getProperty('db');
            $dbProp->setAccessible(true);
            $dbProp->setValue($controller, $db);

            $utilProp = $reflectionClass->getProperty('util');
            $utilProp->setAccessible(true);
            $utilProp->setValue($controller, $util);

            $wpmlProp = $reflectionClass->getProperty('wpml');
            $wpmlProp->setAccessible(true);
            $wpmlProp->setValue($controller, $wpml);

            $pmProp = $reflectionClass->getProperty('pluginsManager');
            $pmProp->setAccessible(true);
            $pmProp->setValue($controller, $pluginsManager);

            $i18n = (new ManufacturerI18n())
                ->setLanguageISO('eng')
                ->setDescription('Test description');

            $manufacturer = (new Manufacturer())
                ->setId(new Identity('', 42))
                ->setName('Test Brand');
            $manufacturer->addI18n($i18n);

            $result = $controller->push($manufacturer);

            $this->assertCount(1, $result);
            /** @var Manufacturer $pushed */
            $pushed = $result[0];
            $this->assertSame(
                '99',
                $pushed->getId()->getEndpoint(),
                'Endpoint ID must be set for newly inserted manufacturers'
            );
        }
    }
}

// phpcs:disable
namespace {
    if (!\class_exists('WP_Error')) {
        class WP_Error
        {
        }
    }

    if (!\class_exists('WP_Term')) {
        class WP_Term
        {
            /** @var int */
            public int $term_id;
            /** @var string */
            public string $taxonomy;
            /** @var string */
            public string $name;
            /** @var string */
            public string $slug;

            public function __construct(int $termId = 0, string $taxonomy = '', string $name = '', string $slug = '')
            {
                $this->term_id  = $termId;
                $this->taxonomy = $taxonomy;
                $this->name     = $name;
                $this->slug     = $slug;
            }
        }
    }

    /**
     * @param string $slug
     * @param string $taxonomy
     * @return array<string, int>|WP_Error
     */
    function wp_insert_term(string $name, string $taxonomy, array $args = []): array|WP_Error
    {
        return ['term_id' => 99, 'term_taxonomy_id' => 99];
    }

    /**
     * @param string     $field
     * @param int|string $value
     * @param string     $taxonomy
     * @param string     $output
     * @param string     $filter
     * @return WP_Term|false
     */
    function get_term_by(
        string $field,
        int|string $value,
        string $taxonomy = '',
        string $output = 'OBJECT',
        string $filter = 'raw'
    ): WP_Term|false {
        if ($field === 'slug') {
            return false;
        }
        if ($field === 'id' && $value === 99) {
            $term           = new WP_Term();
            $term->term_id  = 99;
            $term->taxonomy = $taxonomy;
            return $term;
        }
        return false;
    }

    if (!\function_exists('get_plugins')) {
        /**
         * @return array<string, array<string, string>>
         */
        function get_plugins(): array
        {
            return [
                'perfect-woocommerce-brands/perfect-woocommerce-brands.php' => [
                    'Name' => 'Perfect WooCommerce Brands',
                ],
            ];
        }
    }

    if (!\function_exists('is_plugin_active')) {
        function is_plugin_active(string $plugin): bool
        {
            return true;
        }
    }

    if (!\function_exists('wc_sanitize_taxonomy_name')) {
        function wc_sanitize_taxonomy_name(string $taxonomy): string
        {
            return strtolower($taxonomy);
        }
    }

    if (!\function_exists('remove_filter')) {
        function remove_filter(string $hook, callable|string $callback, int $priority = 10): bool
        {
            return true;
        }
    }

    if (!\function_exists('add_filter')) {
        function add_filter(string $hook, callable|string $callback, int $priority = 10, int $args = 1): bool
        {
            return true;
        }
    }
}
// phpcs:enable
