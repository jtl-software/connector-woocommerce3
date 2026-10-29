<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Controllers\Product;

use Jtl\Connector\Core\Model\CustomerGroup as CustomerGroupModel;
use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\Product as ProductModel;
use Jtl\Connector\Core\Model\ProductSpecialPrice;
use Jtl\Connector\Core\Model\ProductSpecialPriceItem;
use JtlWooCommerceConnector\Controllers\GlobalData\CustomerGroupController;
use JtlWooCommerceConnector\Controllers\Product\ProductSpecialPriceController;
use JtlWooCommerceConnector\Controllers\ProductController;
use JtlWooCommerceConnector\Tests\AbstractTestCase;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;
use phpmock\Mock;
use phpmock\MockBuilder;
use WP_Mock;

/**
 * CO-3619: _price must not be overwritten with 0 when no customer group price meta key exists
 * (B2B Market >= 1.0.8.0).
 *
 * Runs in separate processes: other tests declare global no-op stubs for update_post_meta() that WP_Mock
 * cannot override within the same process.
 *
 * @covers \JtlWooCommerceConnector\Controllers\Product\ProductSpecialPriceController
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ProductSpecialPricePriceOverwriteTest extends AbstractTestCase
{
    private const string PRODUCT_ID = '123';

    /** @var Mock[] */
    private array $mockedFunctions = [];

    /**
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        require_once __DIR__ . '/../../Security/WpUserRoleStubs.php';
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        WP_Mock::setUp();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        foreach ($this->mockedFunctions as $function) {
            $function->disable();
        }
        $this->mockedFunctions          = [];
        $GLOBALS['__jtlwcc_test_posts'] = [];

        WP_Mock::tearDown();
        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testUpdateCustomerGroupPostMetaDoesNotWritePriceWithoutGroupMetaKey(): void
    {
        $this->registerCustomerGroupPost(5, 'guest');
        WP_Mock::userFunction('get_post_meta')->andReturn('');
        WP_Mock::userFunction('delete_post_meta');
        WP_Mock::userFunction('wc_format_decimal')->andReturnUsing(static fn($value) => (string)$value);
        WP_Mock::userFunction('update_post_meta', ['times' => 0]);

        $controller = $this->createController(null);

        $controller->updateCustomerGroupPostMeta(
            [$this->createCustomerGroup('5')],
            self::PRODUCT_ID,
            new Identity(self::PRODUCT_ID),
            ProductController::TYPE_SINGLE,
            2
        );

        $this->addToAssertionCount(1);
    }

    /**
     * @return void
     */
    public function testUpdateCustomerGroupPostMetaWritesGroupPriceWhenMetaKeyExists(): void
    {
        $this->registerCustomerGroupPost(5, 'guest');
        WP_Mock::userFunction('get_post_meta')->andReturnUsing(
            static fn(int $id, string $key) => $key === '_jtlwcc_bm_guest_regular_price' ? '12.5' : ''
        );
        WP_Mock::userFunction('delete_post_meta');
        WP_Mock::userFunction('wc_format_decimal')->andReturnUsing(static fn($value) => (string)$value);
        WP_Mock::userFunction('update_post_meta', [
            'times' => 1,
            'args'  => [(int)self::PRODUCT_ID, 'bm_guest_price', '12.5', '']
        ]);

        $controller = $this->createController('bm_guest_price');

        $controller->updateCustomerGroupPostMeta(
            [$this->createCustomerGroup('5')],
            self::PRODUCT_ID,
            new Identity(self::PRODUCT_ID),
            ProductController::TYPE_SINGLE,
            2
        );

        $this->addToAssertionCount(1);
    }

    /**
     * @return void
     */
    public function testUpdateCustomerGroupPostMetaStillWritesPriceForDefaultGroup(): void
    {
        WP_Mock::userFunction('get_post_meta')->andReturnUsing(
            static fn(int $id, string $key) => $key === '_regular_price' ? '9.9' : ''
        );
        WP_Mock::userFunction('wc_format_decimal')->andReturnUsing(static fn($value) => (string)$value);
        WP_Mock::userFunction('update_post_meta', ['times' => 3]);
        WP_Mock::userFunction('update_post_meta', [
            'times' => 1,
            'args'  => [(int)self::PRODUCT_ID, '_price', '9.9', '']
        ]);

        $controller = $this->createController(null);

        $controller->updateCustomerGroupPostMeta(
            [$this->createCustomerGroup(CustomerGroupController::DEFAULT_GROUP)],
            self::PRODUCT_ID,
            new Identity(self::PRODUCT_ID),
            ProductController::TYPE_SINGLE,
            2
        );

        $this->addToAssertionCount(1);
    }

    /**
     * @return void
     */
    public function testUpdateSpecialPricesPostMetaDoesNotWritePriceWithoutGroupMetaKey(): void
    {
        $this->enableB2BMarket('2.2');
        WP_Mock::userFunction('wc_prices_include_tax')->andReturn(false);
        $this->registerCustomerGroupPost(5, 'guest');
        WP_Mock::userFunction('get_post_meta')->andReturn('');
        WP_Mock::userFunction('wc_format_decimal')->andReturnUsing(static fn($value) => (string)$value);
        WP_Mock::userFunction('update_post_meta', ['times' => 0]);

        $util = $this->createMock(Util::class);
        $util->method('isValidCustomerGroup')->willReturn(true);

        $controller = new class ($util) extends ProductSpecialPriceController {
            /**
             * @param Util $util
             */
            public function __construct(Util $util)
            {
                $this->util = $util;
            }
        };

        $item = (new ProductSpecialPriceItem())
            ->setCustomerGroupId(new Identity('5'))
            ->setPriceNet(8.0);

        $specialPrice = (new ProductSpecialPrice())
            ->setConsiderDateLimit(true)
            ->setActiveFromDate(new \DateTimeImmutable('+1 year'))
            ->addItem($item);

        $controller->updateSpecialPricesPostMeta(
            (new ProductModel())->setVat(19.0),
            [$specialPrice],
            self::PRODUCT_ID,
            new Identity(self::PRODUCT_ID),
            ProductController::TYPE_SINGLE,
            2
        );

        $this->addToAssertionCount(1);
    }

    /**
     * @param int    $id
     * @param string $postName
     * @return void
     */
    private function registerCustomerGroupPost(int $id, string $postName): void
    {
        $post            = new \WP_Post();
        $post->ID        = $id;
        $post->post_name = $postName;

        $GLOBALS['__jtlwcc_test_posts'][$id] = $post;
    }

    /**
     * @param string $version
     * @return void
     */
    private function enableB2BMarket(string $version): void
    {
        $plugins = [
            'b2b-market/b2b-market.php' => ['Name' => SupportedPlugins::PLUGIN_B2B_MARKET, 'Version' => $version]
        ];

        $builder = new MockBuilder();
        $builder->setNamespace('JtlWooCommerceConnector\Utilities');

        $getPlugins = $builder->setName('get_plugins')->setFunction(static fn() => $plugins)->build();
        $getPlugins->enable();
        $this->mockedFunctions[] = $getPlugins;

        $isActive = $builder->setName('is_plugin_active')->setFunction(static fn() => true)->build();
        $isActive->enable();
        $this->mockedFunctions[] = $isActive;
    }

    /**
     * @param string $endpoint
     * @return CustomerGroupModel
     */
    private function createCustomerGroup(string $endpoint): CustomerGroupModel
    {
        return (new CustomerGroupModel())->setId(new Identity($endpoint));
    }

    /**
     * @param string|null $priceMetaKey
     * @return ProductSpecialPriceController
     */
    private function createController(?string $priceMetaKey): ProductSpecialPriceController
    {
        $controller = $this->getMockBuilder(ProductSpecialPriceController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setPostMetaKey'])
            ->getMock();
        $controller->method('setPostMetaKey')->willReturn($priceMetaKey);

        return $controller;
    }
}
