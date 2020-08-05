<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\Wpml;

use JtlWooCommerceConnector\Integrations\Plugins\AbstractComponent;

/**
 * Class WpmlStringTranslation
 * @package JtlWooCommerceConnector\Integrations\Plugins\Wpml
 */
class WpmlStringTranslation extends AbstractComponent
{
    /**
     * @param string $sourceName
     * @param string $targetName
     * @param string $wawiIsoLanguage
     */
    public function translate(string $sourceName, string $targetName, string $wawiIsoLanguage)
    {
        $context = \WPML_ST_Taxonomy_Strings::LEGACY_STRING_DOMAIN;
        $languageCode = $this->getCurrentPlugin()->convertLanguageToWpml($wawiIsoLanguage);

        $stringId = icl_get_string_id($sourceName, $context);
        if ($stringId !== 0) {
            icl_add_string_translation($stringId, $languageCode, html_entity_decode($targetName), 10);
        }
    }

    /**
     * @param $taxonomy
     * @param $name
     * @param $wawiIsoLanguage
     */
    public function registerString(string $taxonomy, string $name, string $wawiIsoLanguage)
    {
        $languageCode = $this->getCurrentPlugin()->convertLanguageToWpml($wawiIsoLanguage);
        $context = \WPML_ST_Taxonomy_Strings::LEGACY_STRING_DOMAIN;

        icl_register_string($context, sprintf("URL %s tax slug", $taxonomy), $taxonomy, false,
            $languageCode);
        $nameSingular = $name;
        icl_register_string($context,
            \WPML_ST_Taxonomy_Strings::LEGACY_NAME_PREFIX_SINGULAR . $nameSingular, $nameSingular,
            false, $languageCode);
        $nameGeneral = 'Produkt ' . $name;
        icl_register_string($context,
            \WPML_ST_Taxonomy_Strings::LEGACY_NAME_PREFIX_GENERAL . $nameGeneral, $nameGeneral, false,
            $languageCode);
    }
}