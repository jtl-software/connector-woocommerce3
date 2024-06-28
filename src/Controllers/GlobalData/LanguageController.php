<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Controllers\GlobalData;

use Exception;
use InvalidArgumentException;
use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\Language as LanguageModel;
use JtlWooCommerceConnector\Controllers\AbstractBaseController;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\Wpml;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlLanguage;
use JtlWooCommerceConnector\Utilities\Util;
use WhiteCube\Lingua\Service;

class LanguageController extends AbstractBaseController
{
    /**
     * @return LanguageModel[]
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function pull(): array
    {
        $wpml = $this->getPluginsManager()->get(Wpml::class);
        if ($wpml->canBeUsed()) {
            /** @var WpmlLanguage $wpmlLanguage */
            $wpmlLanguage = $wpml->getComponent(WpmlLanguage::class);
            return $wpmlLanguage->getLanguages();
        } else {
            $locale = \get_locale();
            return [
                (new LanguageModel())
            ->setId(new Identity(Util::mapLanguageIso($locale)))
            ->setNameGerman($this->nameGerman($locale))
            ->setNameEnglish($this->nameEnglish($locale))
            ->setLanguageISO(Util::mapLanguageIso($locale))
            ->setIsDefault(true)
            ];
        }
    }

    /**
     * @param string $locale
     * @return string
     * @throws Exception
     */
    protected function nameGerman(string $locale): string
    {
        if (\function_exists('locale_get_display_language')) {
            $language = \locale_get_display_language($locale, 'de');
            return !$language ? '' : $language;
        }

        $isoCode = $this->localeToIso($locale);

        /** @var array<string, string> $countries */
        $countries = \WC()->countries->get_countries();

        return $countries[$isoCode] ?? '';
    }

    /**
     * @param string $locale
     * @return string
     * @throws Exception
     */
    protected function nameEnglish(string $locale): string
    {
        if (\function_exists('locale_get_display_language')) {
            $language = \locale_get_display_language($locale, 'en');
            return !$language ? '' : $language;
        }

        $isoCode = $this->localeToIso($locale);

        /** @var array<string, string> $countries */
        $countries = \WC()->countries->get_countries();

        return $countries[$isoCode] ?? '';
    }

    /**
     * @param string $locale
     * @return string
     * @throws Exception
     */
    protected function localeToIso(string $locale): string
    {
        return Service::create($locale)->toISO_639_2b();
    }
}
