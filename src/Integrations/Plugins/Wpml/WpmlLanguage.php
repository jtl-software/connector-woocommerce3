<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\Wpml;

use jtl\Connector\Model\Currency;
use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Language;
use JtlWooCommerceConnector\Utilities\Util;

/**
 * Class WpmlLanguage
 * @package JtlWooCommerceConnector\Integrations\Plugins\Wpml
 */
class WpmlLanguage
{
    /**
     * @return Language[]
     */
    public function getLanguages(): array
    {
        $jtlLanguages = [];

        $defaultLanguage = WpmlUtils::getDefaultLanguage();
        $activeLanguages = WpmlUtils::getActiveLanguages();

        foreach ($activeLanguages as $activeLanguage) {
            $jtlLanguages[] = (new Language())
                ->setId(new Identity(Util::getInstance()->mapLanguageIso($activeLanguage['default_locale'])))
                ->setNameGerman($activeLanguage['display_name'])
                ->setNameEnglish($activeLanguage['english_name'])
                ->setLanguageISO(Util::getInstance()->mapLanguageIso($activeLanguage['default_locale']))
                ->setIsDefault($defaultLanguage === $activeLanguage['code']);
        }

        return $jtlLanguages;
    }
}