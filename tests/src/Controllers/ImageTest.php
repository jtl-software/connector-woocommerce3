<?php
namespace JtlWooCommerceConnector\Tests\Utilities;

use Jtl\Connector\Core\Model\ImageI18n;
use Jtl\Connector\Core\Model\ProductImage;
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
        $imageController = new \JtlWooCommerceConnector\Controllers\ImageController();

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
