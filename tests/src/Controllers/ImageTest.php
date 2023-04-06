<?php

namespace JtlWooCommerceConnector\Tests\Utilities;

use InvalidArgumentException;
use jtl\Connector\Model\Image;
use jtl\Connector\Model\ImageI18n;
use phpmock\MockBuilder;
use phpmock\MockEnabledException;
use PHPUnit\Framework\TestCase;

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
     *
     * @param Image $image
     * @param $expectedAltText
     * @throws \ReflectionException
     * @throws MockEnabledException
     */
    public function testGetImageAltText(Image $image, $expectedAltText)
    {
        $imageController = new \JtlWooCommerceConnector\Controllers\Image();

        $controller  = new \ReflectionClass($imageController);
        $getImageAlt = $controller->getMethod('getImageAlt');
        $getImageAlt->setAccessible(true);

        $result = $getImageAlt->invoke($imageController, $image);
        $this->assertSame($expectedAltText, $result);
    }

    /**
     * @return array[]
     * @throws InvalidArgumentException
     */
    public function imageAltTextDataProvider(): array
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
