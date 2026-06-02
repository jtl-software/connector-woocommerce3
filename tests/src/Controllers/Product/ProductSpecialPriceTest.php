<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Controllers\Product;

use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\Product as ProductModel;
use Jtl\Connector\Core\Model\ProductSpecialPrice as ProductSpecialPriceModel;
use Jtl\Connector\Core\Model\ProductSpecialPriceItem as ProductSpecialPriceItemModel;
use JtlWooCommerceConnector\Controllers\GlobalData\CustomerGroupController;
use JtlWooCommerceConnector\Controllers\Product\ProductSpecialPriceController;
use JtlWooCommerceConnector\Tests\AbstractTestCase;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\Util;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\InvalidArgumentException;
use PHPUnit\Framework\MockObject\CannotUseOnlyMethodsException;
use PHPUnit\Framework\MockObject\ClassAlreadyExistsException;
use PHPUnit\Framework\MockObject\ClassIsFinalException;
use PHPUnit\Framework\MockObject\ClassIsReadonlyException;
use PHPUnit\Framework\MockObject\DuplicateMethodException;
use PHPUnit\Framework\MockObject\IncompatibleReturnValueException;
use PHPUnit\Framework\MockObject\InvalidMethodNameException;
use PHPUnit\Framework\MockObject\OriginalConstructorInvocationRequiredException;
use PHPUnit\Framework\MockObject\ReflectionException;
use PHPUnit\Framework\MockObject\RuntimeException;
use PHPUnit\Framework\MockObject\UnknownTypeException;
use WP_Mock;

class ProductSpecialPriceTest extends AbstractTestCase
{
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
        WP_Mock::tearDown();
        parent::tearDown();
    }

    /**
     * @param string      $productId
     * @param string      $pluginVersion
     * @param string      $postName
     * @param string|null $expectedMetaKeyValue
     * @return void
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws CannotUseOnlyMethodsException
     * @throws ClassAlreadyExistsException
     * @throws ClassIsFinalException
     * @throws ClassIsReadonlyException
     * @throws DuplicateMethodException
     * @throws IncompatibleReturnValueException
     * @throws InvalidMethodNameException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws ReflectionException
     * @throws RuntimeException
     * @throws UnknownTypeException
     * @throws \ReflectionException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @dataProvider setPostMetaKeyDataProvider
     * @covers ProductSpecialPriceController::setPostMetaKey
     */
    public function testSetPostMetaKey(
        string $productId,
        string $pluginVersion,
        string $postName,
        ?string $expectedMetaKeyValue
    ): void {
        $db   = $this->getMockBuilder(Db::class)->disableOriginalConstructor()->getMock();
        $util = $this->getMockBuilder(Util::class)->disableOriginalConstructor()->getMock();

        $productSpecialPriceController = $this->getMockBuilder(ProductSpecialPriceController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['comparePluginVersion'])
            ->getMock();

        $pluginVersionSmaller = false;

        if ($pluginVersion < '1.0.8.0') {
            $pluginVersionSmaller = true;
        }

        $productSpecialPriceController->method('comparePluginVersion')
            ->willReturn($pluginVersionSmaller);

        $reflection = new \ReflectionClass($productSpecialPriceController);
        $method     = $reflection->getMethod('setPostMetaKey');
        $method->setAccessible(false);

        $result = $method->invoke($productSpecialPriceController, $productId, $postName);
        $this->assertSame($expectedMetaKeyValue, $result);
    }

    /**
     * @return array<int, array<int, string|null>>
     * @throws \JsonException
     */
    public function setPostMetaKeyDataProvider(): array
    {
        $product = new ProductModel();
        $product->setId(new Identity('1', 1));

        return [
            ['1111', '1.0.8.1', 'customer', null],
            ['1234', '2.0.1', 'guest', null]
        ];
    }

    /**
     * Given an expired special price (considerDateLimit=true, end date in the past),
     * assert that the sale meta is cleared and _price is reset to the regular price.
     *
     * @return void
     * @throws \ReflectionException
     * @covers ProductSpecialPriceController::updateSpecialPricesPostMeta
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testUpdateSpecialPricesPostMetaClearsExpiredDefaultGroupSale(): void
    {
        $productId = '4711';
        $pd        = 2;

        $product = (new ProductModel())
            ->setId(new Identity($productId))
            ->setVat(19.0);

        $specialPrice = (new ProductSpecialPriceModel())
            ->setId(new Identity($productId))
            ->setIsActive(false)
            ->setConsiderDateLimit(true)
            ->setActiveFromDate(new \DateTimeImmutable('-10 days'))
            ->setActiveUntilDate(new \DateTimeImmutable('-1 day'))
            ->addItem(
                (new ProductSpecialPriceItemModel())
                    ->setCustomerGroupId(new Identity(CustomerGroupController::DEFAULT_GROUP))
                    ->setPriceNet(80.0)
            );

        $updateCalls = [];

        WP_Mock::userFunction('wc_prices_include_tax', ['return' => false]);
        WP_Mock::userFunction('wc_format_decimal', [
            'return' => static fn($value): string => \number_format((float)$value, 2, '.', ''),
        ]);
        WP_Mock::userFunction('get_post_meta', ['return' => '']);
        WP_Mock::userFunction('update_post_meta', [
            'return' => static function (
                int $id,
                string $key,
                mixed $value,
                mixed $previous = null
            ) use (&$updateCalls): bool {
                $updateCalls[] = [$id, $key, $value];
                return true;
            },
        ]);

        $controller = $this->buildControllerWithValidCustomerGroup();

        $this->invokeMethodFromObject(
            $controller,
            'updateSpecialPricesPostMeta',
            $product,
            [$specialPrice],
            $productId,
            new Identity('0'),
            'simple',
            $pd
        );

        $this->assertContains([(int)$productId, '_sale_price', ''], $updateCalls);
        $this->assertContains([(int)$productId, '_sale_price_dates_to', ''], $updateCalls);
        $this->assertContains([(int)$productId, '_sale_price_dates_from', ''], $updateCalls);
        $this->assertContains([(int)$productId, '_price', '0.00'], $updateCalls);

        // _sale_price must never be written with the sale price during the expired path.
        foreach ($updateCalls as $call) {
            if ($call[1] === '_sale_price') {
                $this->assertSame('', $call[2], 'Expired sale must clear _sale_price');
            }
        }
    }

    /**
     * Given an active special price (end date in the future), assert the sale meta
     * keeps being written with the sale price values (regression guard).
     *
     * @return void
     * @throws \ReflectionException
     * @covers ProductSpecialPriceController::updateSpecialPricesPostMeta
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testUpdateSpecialPricesPostMetaPreservesActiveSale(): void
    {
        $productId = '4712';
        $pd        = 2;

        $product = (new ProductModel())
            ->setId(new Identity($productId))
            ->setVat(19.0);

        $futureEnd = new \DateTimeImmutable('+10 days');

        $specialPrice = (new ProductSpecialPriceModel())
            ->setId(new Identity($productId))
            ->setIsActive(true)
            ->setConsiderDateLimit(true)
            ->setActiveFromDate(new \DateTimeImmutable('-1 day'))
            ->setActiveUntilDate($futureEnd)
            ->addItem(
                (new ProductSpecialPriceItemModel())
                    ->setCustomerGroupId(new Identity(CustomerGroupController::DEFAULT_GROUP))
                    ->setPriceNet(80.0)
            );

        $updateCalls = [];

        WP_Mock::userFunction('wc_prices_include_tax', ['return' => false]);
        WP_Mock::userFunction('wc_format_decimal', [
            'return' => static fn($value): string => \number_format((float)$value, 2, '.', ''),
        ]);
        WP_Mock::userFunction('get_post_meta', ['return' => '']);
        WP_Mock::userFunction('update_post_meta', [
            'return' => static function (
                int $id,
                string $key,
                mixed $value,
                mixed $previous = null
            ) use (&$updateCalls): bool {
                $updateCalls[] = [$id, $key, $value];
                return true;
            },
        ]);

        $controller = $this->buildControllerWithValidCustomerGroup();

        $this->invokeMethodFromObject(
            $controller,
            'updateSpecialPricesPostMeta',
            $product,
            [$specialPrice],
            $productId,
            new Identity('0'),
            'simple',
            $pd
        );

        // Sale meta must be written with the actual sale price, NOT empty strings.
        $this->assertContains([(int)$productId, '_sale_price', '80.00'], $updateCalls);
        $this->assertContains([(int)$productId, '_price', '80.00'], $updateCalls);

        $datesTo = $this->extractCallValue($updateCalls, '_sale_price_dates_to');
        $this->assertIsString($datesTo);
        $this->assertNotSame('', $datesTo, 'Active sale must keep _sale_price_dates_to populated');

        $datesFrom = $this->extractCallValue($updateCalls, '_sale_price_dates_from');
        $this->assertIsInt($datesFrom);
        $this->assertGreaterThan(0, $datesFrom, 'Active sale must keep _sale_price_dates_from populated');
    }

    /**
     * Given a special price that has not started yet (dateFrom in the future, dateTo
     * even further in the future), assert that sale meta is preserved (not cleared)
     * and _price falls back to the regular price. This proves we do not confuse
     * "not yet started" with "expired".
     *
     * @return void
     * @throws \ReflectionException
     * @covers ProductSpecialPriceController::updateSpecialPricesPostMeta
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testUpdateSpecialPricesPostMetaPreservesNotYetStartedSale(): void
    {
        $productId = '4713';
        $pd        = 2;

        $product = (new ProductModel())
            ->setId(new Identity($productId))
            ->setVat(19.0);

        $specialPrice = (new ProductSpecialPriceModel())
            ->setId(new Identity($productId))
            ->setIsActive(false)
            ->setConsiderDateLimit(true)
            ->setActiveFromDate(new \DateTimeImmutable('+1 day'))
            ->setActiveUntilDate(new \DateTimeImmutable('+10 days'))
            ->addItem(
                (new ProductSpecialPriceItemModel())
                    ->setCustomerGroupId(new Identity(CustomerGroupController::DEFAULT_GROUP))
                    ->setPriceNet(80.0)
            );

        $updateCalls = [];

        WP_Mock::userFunction('wc_prices_include_tax', ['return' => false]);
        WP_Mock::userFunction('wc_format_decimal', [
            'return' => static fn($value): string => \number_format((float)$value, 2, '.', ''),
        ]);
        WP_Mock::userFunction('get_post_meta', ['return' => '']);
        WP_Mock::userFunction('update_post_meta', [
            'return' => static function (
                int $id,
                string $key,
                mixed $value,
                mixed $previous = null
            ) use (&$updateCalls): bool {
                $updateCalls[] = [$id, $key, $value];
                return true;
            },
        ]);

        $controller = $this->buildControllerWithValidCustomerGroup();

        $this->invokeMethodFromObject(
            $controller,
            'updateSpecialPricesPostMeta',
            $product,
            [$specialPrice],
            $productId,
            new Identity('0'),
            'simple',
            $pd
        );

        // Sale meta is preserved (written with the sale price), NOT cleared.
        $this->assertContains([(int)$productId, '_sale_price', '80.00'], $updateCalls);

        // _price falls back to regular price (mocked as '' -> formatted to '0.00'),
        // proving the "not yet started" branch did NOT take the active-sale path.
        $this->assertContains([(int)$productId, '_price', '0.00'], $updateCalls);

        // The expired-path empty clear must not happen here.
        foreach ($updateCalls as $call) {
            if ($call[1] === '_sale_price') {
                $this->assertSame(
                    '80.00',
                    $call[2],
                    '_sale_price must remain populated for a not-yet-started sale'
                );
            }
        }
    }

    /**
     * @param array<int, array<int, mixed>> $calls
     * @param string                        $metaKey
     * @return mixed
     */
    private function extractCallValue(array $calls, string $metaKey): mixed
    {
        foreach ($calls as $call) {
            if ($call[1] === $metaKey) {
                return $call[2];
            }
        }
        return null;
    }

    /**
     * Build a ProductSpecialPriceController whose Util mock reports the default
     * customer group as valid. Constructor is bypassed; the util property is
     * injected via reflection (the controller's __construct triggers integrations
     * wiring that requires a real WP environment).
     *
     * @return ProductSpecialPriceController
     * @throws \ReflectionException
     */
    private function buildControllerWithValidCustomerGroup(): ProductSpecialPriceController
    {
        $db   = $this->createDbMock();
        $util = $this->createUtilMock();
        $util->method('isValidCustomerGroup')->willReturn(true);

        $controller = $this->getMockBuilder(ProductSpecialPriceController::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $this->setPropertyValueFromObject($controller, 'db', $db);
        $this->setPropertyValueFromObject($controller, 'util', $util);

        return $controller;
    }
}
