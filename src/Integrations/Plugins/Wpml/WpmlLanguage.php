<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\Wpml;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Language;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractComponent;
use JtlWooCommerceConnector\Utilities\Util;

/**
 * Class WpmlLanguage
 * @package JtlWooCommerceConnector\Integrations\Plugins\Wpml
 */
class WpmlLanguage extends AbstractComponent
{
    /**
     * @return Language[]
     */
    public function getLanguages(): array
    {
        $jtlLanguages = [];

        $defaultLanguage = $this->plugin->getDefaultLanguage();
        $activeLanguages = $this->plugin->getActiveLanguages();

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
