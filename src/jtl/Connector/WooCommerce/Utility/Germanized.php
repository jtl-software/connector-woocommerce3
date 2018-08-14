<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Utility;

use jtl\Connector\Core\Utilities\Singleton;

/**
 * UtilGermanized is a singleton that can be used by controllers or mappers that are meant for the Germanized plugin.
 * @package jtl\Connector\WooCommerce\Utility
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
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');

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
}
