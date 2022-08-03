<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\GlobalData;

use Jtl\Connector\Core\Model\Identity;
use JtlWooCommerceConnector\Controllers\AbstractController;
use JtlWooCommerceConnector\Utilities\Util;
use WhiteCube\Lingua\Service;

class Language
{
    public function pullData()
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
     * @throws \Exception
     */
    protected function nameGerman($locale)
    {
        if (function_exists('locale_get_display_language')) {
            return \locale_get_display_language($locale, 'de');
        }

        $isoCode = $this->localeToIso($locale);
        $countries = WC()->countries->get_countries();

        return isset($countries[$isoCode]) ? $countries[$isoCode] : '';
    }

    protected function nameEnglish($locale)
    {
        if (function_exists('locale_get_display_language')) {
            return \locale_get_display_language($locale, 'en');
        }

        $isoCode = $this->localeToIso($locale);
        $countries = WC()->countries->get_countries();

        return isset($countries[$isoCode]) ? $countries[$isoCode] : '';
    }

    protected function localeToIso($locale)
    {
        return Service::create($locale)->toISO_639_2b();
    }
}
