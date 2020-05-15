<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\Wpml;

use JtlWooCommerceConnector\Integrations\Plugins\AbstractPlugin;
use JtlWooCommerceConnector\Logger\WpmlLogger;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use woocommerce_wpml;
use SitePress;
use wpdb;

/**
 * Class Wpml
 * @package JtlWooCommerceConnector\Integrations\Plugins\Wpml
 */
class Wpml extends AbstractPlugin
{
    /**
     * @var Db
     */
    protected $database;

    /**
     * @return bool
     */
    public function isMultiCurrencyEnabled(): bool
    {
        if (wcml_is_multi_currency_on() === false) {
            WpmlLogger::getInstance()->writeLog("WPML multi-currency is not enabled.");
            return false;
        }
        return true;
    }

    /**
     * @return array
     */
    public function getActiveLanguages(): array
    {
        return $this->getSitepress()->get_active_languages();
    }

    /**
     * @return string
     */
    public function getDefaultLanguage(): string
    {
        return wpml_get_default_language();
    }

    /**
     * @return bool
     */
    public function isWpmlMediaEnabled(): bool
    {
        return SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WPML_MEDIA);
    }

    /**
     * @return bool
     */
    public function canBeUsed(): bool
    {
        $canUse = $this->isWpmlEnabled();
        if ($canUse === true) {

            $isSetupCompleted = $this->isSetupCompleted();
            if ($isSetupCompleted === false) {
                WpmlLogger::getInstance()->writeLog("WPML setup is not completed cannot use WPML.");
            }

            $canUse &= $isSetupCompleted;
        }

        return (bool)$canUse;
    }

    /**
     * @return wpdb
     */
    public function getWpDb(): wpdb
    {
        global $wpdb;
        return $wpdb;
    }

    /**
     * @return woocommerce_wpml
     */
    public function getWcml(): woocommerce_wpml
    {
        global $woocommerce_wpml;
        return $woocommerce_wpml;
    }

    /**
     * @return SitePress
     */
    public function getSitepress(): SitePress
    {
        global $sitepress;
        return $sitepress;
    }

    /**
     * @param int $termId
     * @param string $elementType
     * @return int
     */
    public function getElementTrid(int $termId, string $elementType): int
    {
        return (int) $this->getSitepress()->get_element_trid($termId, $elementType);
    }

    /**
     * @return bool
     */
    protected function isSetupCompleted(): bool
    {
        return (bool)wpml_get_setting_filter(false, 'setup_complete');
    }

    /**
     * @return bool
     */
    protected function isWpmlEnabled(): bool
    {
        $plugins = [
            SupportedPlugins::PLUGIN_WPML_MULTILINGUAL_CMS,
            SupportedPlugins::PLUGIN_WPML_STRING_TRANSLATION,
            SupportedPlugins::PLUGIN_WPML_TRANSLATION_MANAGEMENT,
            SupportedPlugins::PLUGIN_WOOCOMMERCE_MULTILUNGUAL
        ];

        return SupportedPlugins::areAllActive(...$plugins);
    }
}
