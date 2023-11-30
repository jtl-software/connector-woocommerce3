<?php

namespace JtlWooCommerceConnector\Controllers\GlobalData;

use Exception;
use InvalidArgumentException;
use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\Language as LanguageModel;
use JtlWooCommerceConnector\Utilities\Util;
use WhiteCube\Lingua\Service;

class LanguageController
{
    /**
     * @return LanguageModel
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function pull(): LanguageModel
    {
        $locale = \get_locale();

        return (new LanguageModel())
            ->setId(new Identity(Util::mapLanguageIso($locale)))
            ->setNameGerman($this->nameGerman($locale))
            ->setNameEnglish($this->nameEnglish($locale))
            ->setLanguageISO(Util::mapLanguageIso($locale))
            ->setIsDefault(true);
    }

    /**
     * @param $locale
     * @return false|mixed|string
     * @throws Exception
     */
    protected function nameGerman($locale): mixed
    {
        if (\function_exists('locale_get_display_language')) {
            return \locale_get_display_language($locale, 'de');
        }

        $isoCode   = $this->localeToIso($locale);
        $countries = \WC()->countries->get_countries();

        return $countries[$isoCode] ?? '';
    }

    /**
     * @param $locale
     * @return false|mixed|string
     * @throws Exception
     */
    protected function nameEnglish($locale): mixed
    {
        if (\function_exists('locale_get_display_language')) {
            return \locale_get_display_language($locale, 'en');
        }

        $isoCode   = $this->localeToIso($locale);
        $countries = \WC()->countries->get_countries();

        return $countries[$isoCode] ?? '';
    }

    /**
     * @param $locale
     * @return string
     * @throws Exception
     */
    protected function localeToIso($locale): string
    {
        return Service::create($locale)->toISO_639_2b();
    }
}
