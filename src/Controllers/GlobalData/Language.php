<?php

/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\GlobalData;

use InvalidArgumentException;
use Jtl\Connector\Core\Model\Identity;
use JtlWooCommerceConnector\Utilities\Util;
use WhiteCube\Lingua\Service;

class Language
{
    /**
     * @return \Jtl\Connector\Core\Model\Language
     * @throws InvalidArgumentException
     */
    public function pull(): \jtl\Connector\Core\Model\Language
    {
        $locale = \get_locale();

        return (new \Jtl\Connector\Core\Model\Language())
            ->setId(new Identity(Util::mapLanguageIso($locale)))
            ->setNameGerman($this->nameGerman($locale))
            ->setNameEnglish($this->nameEnglish($locale))
            ->setLanguageISO(Util::mapLanguageIso($locale))
            ->setIsDefault(true);
    }

    /**
     * @param $locale
     * @return false|mixed|string
     */
    protected function nameGerman($locale)
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
     */
    protected function nameEnglish($locale)
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
     * @return mixed
     */
    protected function localeToIso($locale)
    {
        return Service::create($locale)->toISO_639_2b();
    }

}
