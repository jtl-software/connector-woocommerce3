<?php
namespace JtlWooCommerceConnector\Tests\Utilities;

use jtl\Connector\Model\Image;
use jtl\Connector\Model\ImageI18n;
use phpmock\MockBuilder;
use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase
{
    protected $getLocale;

    /**
     * @throws \phpmock\MockEnabledException
     */
    protected function setUp()
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
    protected function tearDown()
    {
        parent::tearDown();
        $this->getLocale->disable();
    }

    /**
     * @dataProvider imageAltTextDataProvider
     *
     * @param Image $image
     * @param $expectedAltText
     * @throws \ReflectionException
     * @throws \phpmock\MockEnabledException
     */
    public function testGetImageAltText(Image $image, $expectedAltText)
    {
        $imageController = new \JtlWooCommerceConnector\Controllers\Image();

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
                (new Image())->setName('Default name')->setI18ns([
                    (new ImageI18n())->setAltText('Alt text default')->setLanguageISO('ger'),
                    (new ImageI18n())->setAltText('Alt text default')->setLanguageISO('eng'),
                ]),
                'Alt text default'
            ],
            [
                (new Image())->setName("Default name"),
                'Default name'
            ],
            [
                (new Image())->setName(''),
                ''
            ]
        ];
    }
}
