<?php

namespace JtlWooCommerceConnector\Tests\Utilities;

use InvalidArgumentException;
use Jtl\Connector\Core\Mapper\PrimaryKeyMapperInterface;
use Jtl\Connector\Core\Model\AbstractImage;
use jtl\Connector\Core\Model\ImageI18n;
use Jtl\Connector\Core\Model\ProductImage;
use JtlWooCommerceConnector\Controllers\ImageController;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\Util;
use phpmock\MockBuilder;
use phpmock\MockEnabledException;
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
     * @dataprovider imageAltTextDataProvider
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
}
