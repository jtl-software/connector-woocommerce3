<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Wpml;

use JtlWooCommerceConnector\Integrations\Plugins\Wpml\Wpml;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlTermTranslation;
use JtlWooCommerceConnector\Tests\TestCase;
use Mockery\Exception\RuntimeException;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\ExpectationFailedException;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

/**
 * Class WpmlTermTranslationTest
 *
 * @package JtlWooCommerceConnector\Tests\Wpml
 */
class WpmlTermTranslationTest extends TestCase
{
    /**
     * @return array<int, array<int, array<string, array>|bool|int|string>>
     */
    public function existingTranslationsDataProvider(): array
    {
        return [
            [['en' => [], 'de' => []], 'en', false, 2],
            [['en' => [], 'de' => []], 'en', true, 1]
        ];
    }

    /**
     * @dataProvider existingTranslationsDataProvider
     *
     * @param array<int, mixed> $elementTranslations
     * @param string            $defaultLanguage
     * @param bool              $withoutDefaultTranslation
     * @param int               $expectedTranslationsReturned
     * @return void
     * @throws Exception
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws \ReflectionException
     */
    public function testGetAllExistingTranslations(
        array $elementTranslations,
        string $defaultLanguage,
        bool $withoutDefaultTranslation,
        int $expectedTranslationsReturned
    ): void {
        $wpmlPluginMock = \Mockery::mock(Wpml::class);
        $wpmlPluginMock->shouldReceive('getSitepress->get_element_translations')->andReturn($elementTranslations);
        $wpmlPluginMock->shouldReceive('getDefaultLanguage')->andReturn($defaultLanguage);

        $wpmlTermTranslationComponent = new WpmlTermTranslation();
        $wpmlTermTranslationComponent->setPlugin($wpmlPluginMock);
        $translations = $wpmlTermTranslationComponent->getTranslations(1, 'foo', $withoutDefaultTranslation);

        $this->assertCount($expectedTranslationsReturned, $translations);
    }

    /**
     * @return array<int, mixed>
     */
    public function getTranslatedTermDataProvider(): array
    {
        return [
            [ [], []],
            [false, []],
        ];
    }

    /**
     * @dataProvider getTranslatedTermDataProvider
     *
     * @param mixed $getTermByIdReturnValue
     * @param mixed $expectedReturnValue
     * @return void
     */
    public function testGetTranslatedTerm(mixed $getTermByIdReturnValue, mixed $expectedReturnValue): void
    {
        $wpmlPluginMock = \Mockery::mock(Wpml::class);

        $wpmlTermTranslationComponent = \Mockery::mock(WpmlTermTranslation::class)
            ->makePartial()->shouldAllowMockingProtectedMethods();
        $wpmlTermTranslationComponent->shouldReceive('disableGetTermAdjustId')->andReturn(true);
        $wpmlTermTranslationComponent->shouldReceive('enableGetTermAdjustId')->andReturn(true);
        $wpmlTermTranslationComponent->shouldReceive('getTermById')
            ->andReturn(empty($getTermByIdReturnValue) ? false : $getTermByIdReturnValue);
        $wpmlTermTranslationComponent->setPlugin($wpmlPluginMock);
        $translatedTerm = $wpmlTermTranslationComponent->getTranslatedTerm(1, 'foo');

        $this->assertEquals($expectedReturnValue, $translatedTerm);
    }
}
