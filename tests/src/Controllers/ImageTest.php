<?php

namespace JtlWooCommerceConnector\Tests\Utilities;

use Jtl\Connector\Core\Mapper\PrimaryKeyMapperInterface;
use Jtl\Connector\Core\Model\ImageI18n;
use Jtl\Connector\Core\Model\ProductImage;
use JtlWooCommerceConnector\Controllers\ImageController;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\Util;
use phpmock\MockBuilder;
use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase
{
    protected $getLocale;

    /**
     * @throws \phpmock\MockEnabledException
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
     *
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->getLocale->disable();
    }

    /**
     * @dataProvider imageAltTextDataProvider
     *
     * @param ProductImage $image
     * @param $expectedAltText
     * @throws \ReflectionException
     */
    public function testGetImageAltText(ProductImage $image, $expectedAltText)
    {
        $db = $this->getMockBuilder(Db::class)->disableOriginalConstructor()->getMock();
        $util = $this->getMockBuilder(Util::class)->disableOriginalConstructor()->getMock();

        $return = [false];
        if (count($image->getI18ns())) {
            $return = [false, true];
        }

        $util->expects($this->exactly(count($image->getI18ns())))->method('isWooCommerceLanguage')->willReturnOnConsecutiveCalls(...$return);

        $primaryKeyMapper = $this->getMockBuilder(PrimaryKeyMapperInterface::class)->getMock();

        $imageController = new ImageController($db, $util, $primaryKeyMapper);

        $controller = new \ReflectionClass($imageController);
        $getImageAlt = $controller->getMethod('getImageAlt');
        $getImageAlt->setAccessible(true);

        $result = $getImageAlt->invoke($imageController, $image);
        $this->assertSame($expectedAltText, $result);
    }

    /**
     * @return array
     */
    public function imageAltTextDataProvider()
    {
        return [
            [
                (new ProductImage())->setName('Default name')->setI18ns(...[
                    (new ImageI18n())->setAltText('Alt text default')->setLanguageISO('ger'),
                    (new ImageI18n())->setAltText('Alt text default')->setLanguageISO('eng'),
                ]),
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
