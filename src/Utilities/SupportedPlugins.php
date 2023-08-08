<?php

/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Utilities;

final class SupportedPlugins
{
    //THEMESPECIALS

    public const
        //Compatible
        PLUGIN_B2B_MARKET                                          = 'B2B Market',
        PLUGIN_GERMAN_MARKET                                       = 'German Market',
        PLUGIN_PERFECT_WOO_BRANDS                                  = 'Perfect WooCommerce Brands',
        PLUGIN_PERFECT_BRANDS_FOR_WOOCOMMERCE                      = 'Perfect Brands for WooCommerce',
        PLUGIN_PERFECT_BRANDS_WOOCOMMERCE                          = 'Perfect Brands WooCommerce',
        PLUGIN_FB_FOR_WOO                                          = 'Facebook for WooCommerce',
        PLUGIN_WOOCOMMERCE                                         = 'WooCommerce',
        PLUGIN_WOOCOMMERCE_GERMANIZED                              = 'WooCommerce Germanized',
        PLUGIN_WOOCOMMERCE_GERMANIZED2                             = 'Germanized for WooCommerce',
        PLUGIN_WOOCOMMERCE_GERMANIZEDPRO                           = 'Germanized for WooCommerce Pro', //TODO:CHECK THAT
        PLUGIN_WOOCOMMERCE_BLOCKS                                  = 'WooCommerce Blocks',
        PLUGIN_ATOMION_WOOCOMMERCE_BLOCKS                          = 'Atomion WooCommerce Blocks',
        PLUGIN_WOOF_WC_PRODUCT_FILTER                              = 'WOOF - WooCommerce Products Filter',
        PLUGIN_YOAST_SEO                                           = 'Yoast SEO',
        PLUGIN_YOAST_SEO_PREMIUM                                   = 'Yoast SEO Premium',
        PLUGIN_ADVANCED_SHIPMENT_TRACKING_FOR_WOOCOMMERCE          = 'Advanced Shipment Tracking for WooCommerce',
        PLUGIN_ADVANCED_SHIPMENT_TRACKING_PRO                      = 'Advanced Shipment Tracking Pro',
        PLUGIN_DHL_FOR_WOOCOMMERCE                                 = 'DHL for WooCommerce',
        PLUGIN_BACKUPBUDDY                                         = 'BackupBuddy',
        PLUGIN_UPDRAFTPLUS_BACKUP_RESTORE                          = 'UpdraftPlus - Backup/Restore',
        PLUGIN_VR_PAY_ECOMMERCE_WOOCOMMERCE                        = 'VR pay eCommerce - WooCommerce',
        PLUGIN_WPC_PRODUCT_QUANTITY_FOR_WOOCOMMERCE                = 'WPC Product Quantity for WooCommerce',
        PLUGIN_WPC_PRODUCT_QUANTITY_FOR_WOOCOMMERCE_PREMIUM        = 'WPC Product Quantity for WooCommerce (Premium)',
        PLUGIN_ADDITIONAL_VARIATION_IMAGES_GALLERY_FOR_WOOCOMMERCE = 'Additional Variation Images Gallery for WooCommerce',
        PLUGIN_RANK_MATH_SEO                                       = 'Rank Math SEO',
        PLUGIN_CHECKOUT_FIELD_EDITOR_FOR_WOOCOMMERCE               = 'Checkout Field Editor for WooCommerce',

        //Incompatible
        PLUGIN_ANTISPAM_BEE              = 'Antispam Bee',
        PLUGIN_CERBER_SECURITY           = 'Cerber Security, Antispam & Malware Scan',
        PLUGIN_WORDFENCE                 = 'Wordfence Security – Firewall & Malware Scan',
        PLUGIN_THEME_WOODMART_CORE       = 'Woodmart Core',
        PLUGIN_WP_FASTEST_CACHE          = 'WP Fastest Cache',
        PLUGIN_WP_MULTILANG              = 'WP Multilang',
        PLUGIN_WOODY_AD_SNIPPET          = 'Woody ad snippets (PHP snippets | Insert PHP)',
        PLUGIN_SCHEMA_ALL_IN_ONE_SNIPPET = 'Schema - All In One Schema Rich Snippets',
        PLUGIN_BACKWPUP                  = 'BackWPup';

    //arrays
    public const SUPPORTED_PLUGINS = [
        self::PLUGIN_PERFECT_WOO_BRANDS,
        self::PLUGIN_PERFECT_BRANDS_FOR_WOOCOMMERCE,
        self::PLUGIN_PERFECT_BRANDS_WOOCOMMERCE,
        self::PLUGIN_B2B_MARKET,
        self::PLUGIN_GERMAN_MARKET,
        self::PLUGIN_FB_FOR_WOO,
        self::PLUGIN_WOOCOMMERCE,
        self::PLUGIN_WOOCOMMERCE_GERMANIZED,
        self::PLUGIN_WOOCOMMERCE_GERMANIZED2,
        self::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO,
        self::PLUGIN_WOOCOMMERCE_BLOCKS,
        self::PLUGIN_ATOMION_WOOCOMMERCE_BLOCKS,
        self::PLUGIN_WOOF_WC_PRODUCT_FILTER,
        self::PLUGIN_YOAST_SEO,
        self::PLUGIN_YOAST_SEO_PREMIUM,
        self::PLUGIN_ADVANCED_SHIPMENT_TRACKING_FOR_WOOCOMMERCE,
        self::PLUGIN_ADVANCED_SHIPMENT_TRACKING_PRO,
        self::PLUGIN_DHL_FOR_WOOCOMMERCE,
        self::PLUGIN_UPDRAFTPLUS_BACKUP_RESTORE,
        self::PLUGIN_BACKUPBUDDY,
        self::PLUGIN_VR_PAY_ECOMMERCE_WOOCOMMERCE,
        self::PLUGIN_WPC_PRODUCT_QUANTITY_FOR_WOOCOMMERCE,
        self::PLUGIN_WPC_PRODUCT_QUANTITY_FOR_WOOCOMMERCE_PREMIUM,
        self::PLUGIN_ADDITIONAL_VARIATION_IMAGES_GALLERY_FOR_WOOCOMMERCE,
        self::PLUGIN_RANK_MATH_SEO,
        self::PLUGIN_CHECKOUT_FIELD_EDITOR_FOR_WOOCOMMERCE,
    ];

    public const INCOMPATIBLE_PLUGINS = [
        self::PLUGIN_ANTISPAM_BEE,
        self::PLUGIN_CERBER_SECURITY,
        self::PLUGIN_WORDFENCE,
        self::PLUGIN_WP_FASTEST_CACHE,
        self::PLUGIN_WP_MULTILANG,
        self::PLUGIN_THEME_WOODMART_CORE,
        self::PLUGIN_WOODY_AD_SNIPPET,
        self::PLUGIN_SCHEMA_ALL_IN_ONE_SNIPPET,
        self::PLUGIN_BACKWPUP,
    ];

    /**
     * Returns all active and validated plugins
     *
     * @return array
     */
    public static function getInstalledAndActivated(): array
    {
        $plugins       = get_plugins(); //phpcs:ignore SlevomatCodingStandard.Namespaces.FullyQualifiedGlobalFunctions
        $activePlugins = [];

        foreach ($plugins as $key => $plugin) {
            if (is_plugin_active($key)) { //phpcs:ignore SlevomatCodingStandard.Namespaces.FullyQualifiedGlobalFunctions
                $activePlugins[] = $plugins[$key];
            }
        }

        return $activePlugins;
    }

    /**
     * Returns all supported active and validated plugins
     *
     * @param bool $asString
     *
     * @return array|string
     */
    public static function getSupported(bool $asString = false)
    {
        $plArray = self::getInstalledAndActivated();
        $plugins = [];
        $tmp     = [];
        foreach ($plArray as $plugin) {
            if (\in_array($plugin['Name'], self::SUPPORTED_PLUGINS)) {
                $plugins[] = $plugin;
                $tmp[]     = $plugin['Name'];
            }
        }

        if ($asString) {
            return \implode(', ', $tmp);
        } else {
            return $plugins;
        }
    }

    /**
     * Returns all not supported active plugins or all not supported plugins
     *
     * @param bool $asString
     * @param bool $all
     * @param bool $asArray
     * @return array|string
     */
    public static function getNotSupportedButActive(
        bool $asString = false,
        bool $all = false,
        bool $asArray = false
    ) {
        $plArray = self::getInstalledAndActivated();
        $plugins = [];
        $tmp     = [];
        foreach ($plArray as $plugin) {
            if (\in_array($plugin['Name'], self::INCOMPATIBLE_PLUGINS)) {
                $plugins[] = $plugin;
                $tmp[]     = $plugin['Name'];
            }
        }

        if ($asString) {
            if ($all) {
                return \implode(', ', self::INCOMPATIBLE_PLUGINS);
            } else {
                return \implode(', ', $tmp);
            }
        } else {
            if ($all && $asArray) {
                return self::INCOMPATIBLE_PLUGINS;
            }

            return $plugins;
        }
    }

    /**
     * Check if Special PLugin is Installed
     *
     * @param string $pluginName
     *
     * @return bool
     */
    public static function isActive(string $pluginName = 'WooCommerce'): bool
    {
        $plArray = self::getInstalledAndActivated();
        $active  = false;

        foreach ($plArray as $plugin) {
            if (\strcmp($pluginName, $plugin['Name']) === 0) {
                $active = true;
            }
        }

        return $active;
    }

    /**
     * @param string $pluginName
     * @param string $operator
     * @param string $version
     * @return bool
     */
    public static function comparePluginVersion(string $pluginName, string $operator, string $version): bool
    {
        return self::isActive($pluginName) && \version_compare(self::getVersionOf($pluginName), $version, $operator);
    }

    /**
     * @return bool
     */
    public static function isPerfectWooCommerceBrandsActive(): bool
    {
        return (
            self::isActive(self::PLUGIN_PERFECT_WOO_BRANDS) ||
            self::isActive(self::PLUGIN_PERFECT_BRANDS_FOR_WOOCOMMERCE) ||
            self::isActive(self::PLUGIN_PERFECT_BRANDS_WOOCOMMERCE)
        );
    }

    /**
     * @param string $pluginName
     * @return string|null
     */
    public static function getVersionOf(string $pluginName = 'WooCommerce'): ?string
    {
        $plArray = self::getInstalledAndActivated();

        $version = null;
        foreach ($plArray as $plugin) {
            if (\strcmp($pluginName, $plugin['Name']) === 0) {
                $version = $plugin['Version'];
                break;
            }
        }

        return $version;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public static function themeIsInstalled(string $name = ''): bool
    {
        $installed = false;
        $themes    = \wp_get_themes();

        if (\is_array($themes)) {
            $installed = \array_key_exists((string)$name, $themes);
        }

        return $installed;
    }
}
