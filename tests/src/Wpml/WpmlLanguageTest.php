<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Wpml;

use JtlWooCommerceConnector\Integrations\Plugins\Wpml\Wpml;
use JtlWooCommerceConnector\Tests\TestCase;
use JtlWooCommerceConnector\Utilities\Util;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlLanguage;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlUtils;

/**
 * Class WpmlLanguageTest
 *
 * @package JtlWooCommerceConnector\Tests\Wpml
 */
class WpmlLanguageTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @covers WpmlLanguage::getLanguages
     * @return void
     * @throws \Exception
     */
    public function testGetLanguages(): void
    {
        $util = \Mockery::mock("alias:" . Util::class);
        $util->shouldReceive('mapLanguageIso')->andReturn(
            'eng',
            'ger'
        );

        $wpmlPluginMock = \Mockery::mock(Wpml::class);
        $wpmlPluginMock->shouldReceive('getDefaultLanguage')->andReturn('en');
        $wpmlPluginMock->shouldReceive('getActiveLanguages')->andReturn([
            'en' => [
                'default_locale' => 'en_GB',
                'display_name' => 'English',
                'english_name' => 'English',
                'code' => 'en',
            ],
            'de' => [
                'default_locale' => 'de_DE',
                'display_name' => 'German',
                'english_name' => 'German',
                'code' => 'de',
            ]
        ]);

        $language = new WpmlLanguage();
        $language->setPlugin($wpmlPluginMock);
        $languages = $language->getLanguages();

        $this->assertCount(2, $languages);
        $this->assertSame('ger', $languages[1]->getLanguageISO());
    }
}
