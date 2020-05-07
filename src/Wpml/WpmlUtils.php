<?php

namespace JtlWooCommerceConnector\Wpml;

use JtlWooCommerceConnector\Logger\WpmlLogger;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use \woocommerce_wpml;

/**
 * Class WpmlUtils
 * @package JtlWooCommerceConnector\Wpml
 */
class WpmlUtils
{
    /**
     * @return bool
     */
    public static function isMultiCurrencyEnabled(): bool
    {
        if (wcml_is_multi_currency_on() === false) {
            WpmlLogger::getInstance()->writeLog("WPML multi-currency is not enabled.");
            return false;
        }
        return true;
    }

    /**
     * @return bool
     */
    public static function isWpmlMediaEnabled(): bool
    {
        return SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WPML_MEDIA);
    }

    /**
     * @return bool
     */
    public static function canUseWcml(): bool
    {
        $canUse = self::isWpmlEnabled();
        if ($canUse === true) {

            $isSetupCompleted = self::isSetupCompleted();
            if ($isSetupCompleted === false) {
                WpmlLogger::getInstance()->writeLog("WPML setup is not completed cannot use WPML.");
            }

            $canUse &= $isSetupCompleted;
        }

        return (bool)$canUse;
    }

    /**
     * @return woocommerce_wpml
     */
    public static function getWcml(): woocommerce_wpml
    {
        global $woocommerce_wpml;
        return $woocommerce_wpml;
    }

    /**
     * @return bool
     */
    protected static function isSetupCompleted(): bool
    {
        return (bool)wpml_get_setting_filter(false, 'setup_complete');
    }

    /**
     * @return bool
     */
    protected static function isWpmlEnabled(): bool
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
