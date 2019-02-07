<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Utilities;

final class SupportedPlugins
{
    //Compatible
    const PLUGIN_WOOCOMMERCE = 'WooCommerce';
    const PLUGIN_WOOCOMMERCE_GERMANIZED = 'WooCommerce Germanized';
    const PLUGIN_WOOCOMMERCE_BLOCKS = 'WooCommerce Blocks';
    const PLUGIN_PERFECT_WOO_BRANDS = 'Perfect WooCommerce Brands';
    //Incompatible
    const PLUGIN_ANTISPAM_BEE = 'Antispam Bee';
    const PLUGIN_SMUSH = 'Smush Image Compression and Optimization';
    const PLUGIN_WORDFENCE = 'Wordfence Security – Firewall & Malware Scan';
    const PLUGIN_CERBER_SECURITY = 'Cerber Security, Antispam & Malware Scan';
    const PLUGIN_WP_FASTEST_CACHE = 'WP Fastest Cache';
    const PLUGIN_GERMAN_MARKET = 'German Market';
    //arrays
    const SUPPORTED_PLUGINS = [
        self::PLUGIN_WOOCOMMERCE,
        self::PLUGIN_WOOCOMMERCE_GERMANIZED,
        self::PLUGIN_WOOCOMMERCE_BLOCKS,
        self::PLUGIN_PERFECT_WOO_BRANDS,
    ];
    
    const INCOMPATIBLE_PLUGINS = [
        self::PLUGIN_ANTISPAM_BEE,
        self::PLUGIN_SMUSH,
        self::PLUGIN_WORDFENCE,
        self::PLUGIN_CERBER_SECURITY,
        self::PLUGIN_WP_FASTEST_CACHE,
        self::PLUGIN_GERMAN_MARKET,
    ];
    
    /**
     * Returns all active and validated plugins
     *
     * @return array
     */
    public static function getInstalledAndActivated()
    {
        $plugins = get_plugins();
        $plArr   = [];
        
        foreach (wp_get_active_and_valid_plugins() as $activePl) {
            $tmp   = explode('/', $activePl);
            $count = count($tmp) - 1;
            
            $string = '';
            
            if (strcmp('plugins', $tmp[$count - 1]) !== 0) {
                $string .= (string)$tmp[$count - 1];
                $string .= '/';
            }
            
            $string .= (string)$tmp[$count];
            
            if (array_key_exists($string, $plugins)) {
                $plArr[] = $plugins[$string];
            }
            
        }
        
        return $plArr;
    }
    
    /**
     * Returns all supported active and validated plugins
     *
     * @param bool $asString
     *
     * @return array|string
     */
    public static function getSupported($asString = false)
    {
        $plArray = self::getInstalledAndActivated();
        $plugins = [];
        $tmp     = [];
        foreach ($plArray as $plugin) {
            if (array_search($plugin['Name'], self::SUPPORTED_PLUGINS) !== false) {
                $plugins[] = $plugin;
                $tmp[]     = $plugin['Name'];
            }
        }
        
        if ($asString) {
            return implode(', ', $tmp);
        } else {
            return $plugins;
        }
    }
    
    /**
     * Returns all not supported active plugins or all not supported plugins
     *
     * @param bool $asString
     * @param bool $all
     *
     * @return array|string
     */
    public static function getNotSupportedButActive($asString = false, $all = false)
    {
        $plArray = self::getInstalledAndActivated();
        $plugins = [];
        $tmp     = [];
        foreach ($plArray as $plugin) {
            if (array_search($plugin['Name'], self::INCOMPATIBLE_PLUGINS) !== false) {
                $plugins[] = $plugin;
                $tmp[]     = $plugin['Name'];
            }
        }
        
        if ($asString) {
            if ($all) {
                return implode(', ', self::INCOMPATIBLE_PLUGINS);
            } else {
                return implode(', ', $tmp);
            }
        } else {
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
    public static function isActive($pluginName = 'WooCommerce')
    {
        $plArray = self::getInstalledAndActivated();
        $active  = false;
        
        foreach ($plArray as $plugin) {
            if (strcmp($pluginName, $plugin['Name']) === 0) {
                $active = true;
            }
        }
        
        return $active;
    }
}
