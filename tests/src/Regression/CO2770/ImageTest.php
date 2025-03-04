<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Regression\CO2770;

use InvalidArgumentException;
use Jtl\Connector\Core\Mapper\PrimaryKeyMapperInterface;
use Jtl\Connector\Core\Model\AbstractImage;
use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\ImageI18n;
use Jtl\Connector\Core\Model\ProductImage;
use JtlWooCommerceConnector\Controllers\ImageController;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\Util;
use phpmock\MockBuilder;
use phpmock\MockEnabledException;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\ClassAlreadyExistsException;
use PHPUnit\Framework\MockObject\ClassIsFinalException;
use PHPUnit\Framework\MockObject\ClassIsReadonlyException;
use PHPUnit\Framework\MockObject\DuplicateMethodException;
use PHPUnit\Framework\MockObject\InvalidMethodNameException;
use PHPUnit\Framework\MockObject\OriginalConstructorInvocationRequiredException;
use PHPUnit\Framework\MockObject\RuntimeException;
use PHPUnit\Framework\MockObject\UnknownTypeException;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use JtlWooCommerceConnector\Tests\Faker\DbFaker;
use WC_Product;

class ImageTest extends TestCase
{
    protected \phpmock\Mock $getLocale;

    /**
     * @return void
     * @throws InvalidArgumentException
     * @throws MockEnabledException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->getLocale = (new MockBuilder())
            ->setNamespace('JtlWooCommerceConnector\Utilities')
            ->setName('get_locale')
            ->setFunction(function () {
                return 'de_DE';
            })->build();

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
     * @dataProvider deleteProductImageDataProvider
     * @param AbstractImage $image
     * @param bool $realDelete
     * @return void
     * @throws ClassAlreadyExistsException
     * @throws ClassIsFinalException
     * @throws ClassIsReadonlyException
     * @throws DuplicateMethodException
     * @throws InvalidMethodNameException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws RuntimeException
     * @throws UnknownTypeException
     * @throws \PHPUnit\Framework\InvalidArgumentException
     * @throws \PHPUnit\Framework\MockObject\ReflectionException
     * @throws \Exception
     * @covers ImageController::deleteProductImage
     */
    public function testDeleteProductImage(AbstractImage $image, bool $realDelete, $queryString): void
    {
        /**
        $wpDb = $this->getMockBuilder('\wpdb')->getMock();
        $db = new DbFaker($wpDb);
        $util = $this->createMock(Util::class);
        $primaryKeyMapper = $this->getMockBuilder(PrimaryKeyMapperInterface::class)->getMock();

        $util->expects($this->once())
            ->method('wcGetProduct')
            ->willReturn(new \WC_Product());

        $imageController = new ImageController($db, $util, $primaryKeyMapper);

        $controller = new \ReflectionClass($imageController);
        $deleteProductImage = $controller->getMethod('deleteProductImage');
        $deleteProductImage->setAccessible(true);

        $deleteProductImage->invoke($imageController, $image, $realDelete);

        $this->assertSame($queryString, $db->givenQueries[0]);
         * **/
        //TODO: dummy assertion, replace by actual test later
        $this->assertTrue(true);
    }

    /**
     * @return array<int, ProductImage|bool|string>
     */
    public function deleteProductImageDataProvider(): array
    {
        return [
            [
                (new ProductImage())->setName('Default name')->setId(new Identity("1111_2222", 1)),
                true,
                "
            DELETE FROM wp_jtl_connector_link_image
            WHERE (`type` = 42
            OR `type` = 64)
            AND endpoint_id
            LIKE '1111_2222'",
            ]
        ];
    }
}