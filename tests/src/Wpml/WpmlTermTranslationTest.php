<?php

namespace JtlWooCommerceConnector\Tests\Wpml;

use JtlWooCommerceConnector\Integrations\Plugins\Wpml\Wpml;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlTermTranslation;
use JtlWooCommerceConnector\Tests\TestCase;

/**
 * Class WpmlTermTranslationTest
 * @package JtlWooCommerceConnector\Tests\Wpml
 */
class WpmlTermTranslationTest extends TestCase
{
    /**
     * @return array
     */
    public function existingTranslationsDataProvider(): array
    {
        return [
            [['en' => [], 'de' => []], false, 2],
            [['en' => [], 'de' => []], 'en', 1]
        ];
    }

    /**
     * @dataProvider existingTranslationsDataProvider
     *
     * @param array $elementTranslations
     * @param $defaultLanguage
     * @param int $expectedTranslationsReturned
     */
    public function testGetAllExistingTranslations(
        array $elementTranslations,
        $defaultLanguage,
        int $expectedTranslationsReturned
    ) {
        $wpmlPluginMock = \Mockery::mock(Wpml::class);
        $wpmlPluginMock->shouldReceive('getSitepress->get_element_translations')->andReturn($elementTranslations);
        $wpmlPluginMock->shouldReceive('getDefaultLanguage')->andReturn($defaultLanguage);

        $wpmlTermTranslationComponent = new WpmlTermTranslation();
        $wpmlTermTranslationComponent->setPlugin($wpmlPluginMock);
        $translations = $wpmlTermTranslationComponent->getTranslations(1, 'foo', $defaultLanguage);

        $this->assertCount($expectedTranslationsReturned, $translations);
    }

    /**
     * @return array
     */
    public function getTranslatedTermDataProvider()
    {
        return [
            [[], []],
            [false, []],
        ];
    }

    /**
     * @dataProvider getTranslatedTermDataProvider
     *
     * @param $getTermByIdReturnValue
     * @param $expectedReturnValue
     */
    public function testGetTranslatedTerm($getTermByIdReturnValue, $expectedReturnValue)
    {
        $wpmlPluginMock = \Mockery::mock(Wpml::class);

        $wpmlTermTranslationComponent = \Mockery::mock(WpmlTermTranslation::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $wpmlTermTranslationComponent->shouldReceive('disableGetTermAdjustId')->andReturn(true);
        $wpmlTermTranslationComponent->shouldReceive('enableGetTermAdjustId')->andReturn(true);
        $wpmlTermTranslationComponent->shouldReceive('getTermById')->andReturn($getTermByIdReturnValue);
        $wpmlTermTranslationComponent->setPlugin($wpmlPluginMock);
        $translatedTerm = $wpmlTermTranslationComponent->getTranslatedTerm(1, 'foo');

        $this->assertEquals($expectedReturnValue, $translatedTerm);
    }
}