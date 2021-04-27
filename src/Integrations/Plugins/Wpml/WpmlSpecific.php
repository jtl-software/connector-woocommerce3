<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\Wpml;

use jtl\Connector\Model\Specific;
use jtl\Connector\Model\SpecificI18n as SpecificI18nModel;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractComponent;
use WPML\Auryn\InjectionException;

/**
 * Class WpmlSpecific
 * @package JtlWooCommerceConnector\Integrations\Plugins\Wpml
 */
class WpmlSpecific extends AbstractComponent
{
    /**
     * @return int
     */
    public function getStats(): int
    {
        $wpdb = $this->getCurrentPlugin()->getWpDb();
        $wat = $wpdb->prefix . 'woocommerce_attribute_taxonomies';
        $jcls = $wpdb->prefix . 'jtl_connector_link_specific';

        $sql = sprintf("
            SELECT COUNT(at.attribute_id)
            FROM {$wat} at
            LEFT JOIN {$jcls} l ON at.attribute_id = l.endpoint_id
            WHERE l.host_id IS NULL;"
        );

        return (int)$this->getCurrentPlugin()->getPluginsManager()->getDatabase()->queryOne($sql);
    }


    /**
     * @param Specific $specific
     * @param string $name
     * @throws \jtl\Connector\Core\Exception\LanguageException
     */
    public function getTranslations(Specific $specific, string $name)
    {
        $languages = $this->getCurrentPlugin()->getActiveLanguages();

        foreach ($languages as $languageCode => $language) {
            $translatedName = apply_filters('wpml_translate_single_string', $name, 'WordPress', $name, $languageCode);
            if ($translatedName !== $name) {
                $specific->addI18n(
                    (new SpecificI18nModel)
                        ->setSpecificId($specific->getId())
                        ->setLanguageISO($this->getCurrentPlugin()->convertLanguageToWawi($languageCode))
                        ->setName($translatedName)
                );
            }
        }
    }

    /**
     * @param Specific $specific
     * @param SpecificI18nModel $defaultTranslation
     * @throws InjectionException
     */
    public function setTranslations(Specific $specific, SpecificI18nModel $defaultTranslation)
    {
        $originalSlug = wc_sanitize_taxonomy_name(substr(trim($defaultTranslation->getName()), 0, 27));

        /** @var WpmlStringTranslation $stringTranslationComponent */
        $stringTranslationComponent = $this->getCurrentPlugin()->getComponent(WpmlStringTranslation::class);
        $stringTranslationComponent->registerString($originalSlug, $defaultTranslation->getName(), $defaultTranslation->getLanguageISO());

        $translations = [];

        foreach ($specific->getI18ns() as $specificI18n){
            $languageCode = $this->getCurrentPlugin()->convertLanguageToWpml($specificI18n->getLanguageISO());
            if ($this->getCurrentPlugin()->getDefaultLanguage() === $languageCode) {
                continue;
            }
            $slug = wc_sanitize_taxonomy_name(substr(trim($specificI18n->getName()), 0, 27));

            $translations[$languageCode] = $slug;

            $originalGeneralName = sprintf('Produkt %s',$defaultTranslation->getName());

            $stringTranslationComponent->translate($originalSlug, $slug, $specificI18n->getLanguageISO());
            $stringTranslationComponent->translate($originalGeneralName, $specificI18n->getName(), $specificI18n->getLanguageISO());
            $stringTranslationComponent->translate($defaultTranslation->getName(), $specificI18n->getName(), $specificI18n->getLanguageISO());
        }

        return $translations;
    }

    /**
     * @param string $specificName
     * @return array|null
     */
    public function getValues(string $specificName)
    {
        $wpdb = $this->getCurrentPlugin()->getWpDb();
        $jclsv = $wpdb->prefix . 'jtl_connector_link_specific_value';
        $iclt = $wpdb->prefix . 'icl_translations';
        $languageCode = $this->getCurrentPlugin()->getDefaultLanguage();
        $elementType = 'tax_' . $specificName;

        return $this->getPluginsManager()->getDatabase()->query(
            "SELECT t.term_id, t.name, tt.term_taxonomy_id, tt.taxonomy, t.slug, tt.description
                FROM {$wpdb->terms} t
                  LEFT JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                  LEFT JOIN {$jclsv} lsv ON t.term_id = lsv.endpoint_id
                  LEFT JOIN {$iclt} wpmlt ON t.term_id = wpmlt.element_id
                WHERE lsv.host_id IS NULL
                AND tt.taxonomy LIKE '{$specificName}'
                AND wpmlt.element_type = '{$elementType}'
                AND wpmlt.source_language_code IS NULL
                AND wpmlt.language_code = '{$languageCode}'
                ORDER BY tt.parent ASC;");
    }

    /**
     * @param string $specificName
     * @return bool
     */
    public function isTranslatable(string $specificName): bool
    {
        $attributes = $this->getCurrentPlugin()->getWcml()->get_setting('attributes_settings');
        return (isset($attributes[$specificName]) && (int)$attributes[$specificName] === 1) ? true : false;
    }
}
