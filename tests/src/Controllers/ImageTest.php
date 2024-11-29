<?php

namespace JtlWooCommerceConnector\Tests\Utilities;

use InvalidArgumentException;
use Jtl\Connector\Core\Mapper\PrimaryKeyMapperInterface;
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

class ImageTest extends TestCase
{
    protected $getLocale;

    /**
     * @return void
     * @throws InvalidArgumentException
     * @throws MockEnabledException
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->getLocale = (new MockBuilder())->setNamespace('JtlWooCommerceConnector\Utilities')
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
     * @dataProvider imageAltTextDataProvider
     * @param ProductImage $image
     * @param $expectedAltText
     * @return void
     * @throws ReflectionException
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testGetImageAltText(ProductImage $image, $expectedAltText)
    {
        $db   = $this->getMockBuilder(Db::class)->disableOriginalConstructor()->getMock();
        $util = $this->getMockBuilder(Util::class)->disableOriginalConstructor()->getMock();

        $return = [false];
        if (\count($image->getI18ns())) {
            $return = [false, true];
        }

        $util->expects($this->exactly(\count($image->getI18ns())))
            ->method('isWooCommerceLanguage')->willReturnOnConsecutiveCalls(...$return);

        $primaryKeyMapper = $this->getMockBuilder(PrimaryKeyMapperInterface::class)->getMock();

        $imageController = new ImageController($db, $util, $primaryKeyMapper);

        $controller  = new \ReflectionClass($imageController);
        $getImageAlt = $controller->getMethod('getImageAlt');
        $getImageAlt->setAccessible(true);

        $result = $getImageAlt->invoke($imageController, $image);
        $this->assertSame($expectedAltText, $result);
    }

    /**
     * @return array[]
     */
    public function imageAltTextDataProvider(): array
    {
        return [
            [
                (new ProductImage())->setName('Default name')->setI18ns(
                    (new ImageI18n())->setAltText('Alt text default')->setLanguageISO('ger'),
                    (new ImageI18n())->setAltText('Alt text default')->setLanguageISO('eng')
                ),
                'Alt text default'
            ],
            [
                (new ProductImage())->setName("Default name"),
                'Default name'
            ],
            [
                (new ProductImage())->setName(''),
                ''
            ]
        ];
    }

    /**
     * Get next available image filename if image file doesn't exist ($fileExists = false)
     *
     * @dataProvider getNextAvailableImageFileNameFileNotExistingDataProvider
     * @param string $name
     * @param string $extension
     * @param string $uploadDir
     * @param string $expectedFileName
     *
     * @throws RuntimeException
     * @throws ClassIsFinalException
     * @throws \PHPUnit\Framework\InvalidArgumentException
     * @throws ExpectationFailedException
     * @throws DuplicateMethodException
     * @throws ClassIsReadonlyException
     * @throws ClassAlreadyExistsException
     * @throws InvalidMethodNameException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws \PHPUnit\Framework\MockObject\ReflectionException
     * @throws UnknownTypeException
     * @throws ReflectionException
     * @throws \Exception
     */
    public function testGetNextAvailableImageFilenameFileNotExisting(
        string $name,
        string $extension,
        string $uploadDir,
        string $expectedFileName
    ) {
        $db   = $this->getMockBuilder(Db::class)->disableOriginalConstructor()->getMock();
        $util = $this->getMockBuilder(Util::class)->disableOriginalConstructor()->getMock();

        $primaryKeyMapper = $this->getMockBuilder(PrimaryKeyMapperInterface::class)->getMock();

        $imageController = new ImageController($db, $util, $primaryKeyMapper);

        $controller  = new \ReflectionClass($imageController);
        $getImageAlt = $controller->getMethod('getNextAvailableImageFilename');

        $result = $getImageAlt->invoke($imageController, $name, $extension, $uploadDir);
        $this->assertSame($expectedFileName, $result);
    }

    public function getNextAvailableImageFileNameFileNotExistingDataProvider(): array
    {
        return [
            [
                '1111_Product',
                'jpg',
                '/var/www/html/wordpress/wp-content/uploads/2024/11',
                '1111_Product.jpg'
            ]
        ];
    }
}