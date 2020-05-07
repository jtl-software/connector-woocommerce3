<?php

namespace JtlWooCommerceConnector\Wpml;

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
        if (self::getWcml()->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_DISABLED) {
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
            $canUse &= self::isSetupCompleted();
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
    public static function isSetupCompleted(): bool
    {
        return (bool)wpml_get_setting_filter(false, 'setup_complete');
    }

    /**
     * @return bool
     */
    public static function isWpmlEnabled(): bool
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
