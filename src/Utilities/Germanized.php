<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Utilities;

use jtl\Connector\Core\Utilities\Singleton;

/**
 * UtilGermanized is a singleton that can be used by controllers or mappers that are meant for the Germanized plugin.
 * @package JtlWooCommerceConnector\Utilities
 */
final class Germanized extends Singleton
{
    /**
     * @var array Index used in database mapped to translated salutation.
     */
    private $salutations;

    private static $units = [
        'l'  => 'L',
        'ml' => 'mL',
        'dl' => 'dL',
        'cl' => 'cL',
    ];

    public function __construct()
    {
        $this->salutations = [
            1 => __('Mr.', 'woocommerce-germanized'),// m
            2 => __('Ms.', 'woocommerce-germanized') // f
        ];
    }

    public function isActive()
    {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        return \is_plugin_active('woocommerce-germanized/woocommerce-germanized.php');
    }

    public function parseIndexToSalutation($index)
    {
        return isset($this->salutations[(int)$index]) ? $this->salutations[$index] : '';
    }

    public function parseUnit($code)
    {
        return in_array($code, array_keys(self::$units)) ? self::$units[$code] : $code;
    }

    /**
     * @return $this
     */
    public static function getInstance()
    {
        return parent::getInstance();
    }

    /**
     * Backward compatibility method
     *
     * @param $wcProduct
     * @return bool
     */
    public function hasUnitProduct($wcProduct)
    {
        if($this->pluginVersionIsGreaterOrEqual('3.0.0')){
            return wc_gzd_get_gzd_product($wcProduct)->has_unit_product();
        }
        return $wcProduct->gzd_product->has_product_units();
    }

    /**
     * Backward compatibility method
     *
     * @param $wcProduct
     * @return bool|mixed|void
     */
    public function getUnit($wcProduct)
    {
        if($this->pluginVersionIsGreaterOrEqual('3.0.0')){
            return wc_gzd_get_gzd_product($wcProduct)->get_unit();
        }
        return $wcProduct->gzd_product->unit;
    }

    /**
     * Backward compatibility method
     *
     * @param $wcProduct
     * @return bool|mixed|void
     */
    public function getUnitProduct($wcProduct)
    {
        if($this->pluginVersionIsGreaterOrEqual('3.0.0')){
            return wc_gzd_get_gzd_product($wcProduct)->get_unit_product();
        }
        return $wcProduct->gzd_product->unit_product;
    }

    /**
     * Backward compatibility method
     *
     * @param $wcProduct
     * @return bool|mixed|void
     */
    public function getUnitBase($wcProduct)
    {
        if($this->pluginVersionIsGreaterOrEqual('3.0.0')){
            return wc_gzd_get_gzd_product($wcProduct)->get_unit_base();
        }
        return $wcProduct->gzd_product->unit_base;
    }

    /**
     * @param $versionToCompare
     * @return bool
     */
    public function pluginVersionIsGreaterOrEqual($versionToCompare)
    {
        $currentVersion = SupportedPlugins::getVersionOf(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED);
        if(is_null($currentVersion)){
            $currentVersion = SupportedPlugins::getVersionOf(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2);
        }

        if(version_compare($currentVersion,$versionToCompare,'>=')){
            return true;
        }
        return false;
    }

}
