<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Utility;

use jtl\Connector\Core\Utilities\Singleton;

/**
 * UtilGermanized is a singleton that can be used by controllers or mappers that are meant for the Germanized plugin.
 * @package jtl\Connector\WooCommerce\Utility
 */
final class UtilGermanized extends Singleton
{
    /**
     * @var array Index used in database mapped to translated salutation.
     */
    private $salutations;
    /**
     * @var array Controllers where a Germanized implementation is provided for.
     */
    private static $germanizedController = [
        'GlobalData',
        'Payment'
    ];

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

    public function getController($controller, $namespaceController)
    {
        if ($this->isActive()) {
            if (in_array($controller, self::$germanizedController)) {
                $namespaceController .= 'Germanized';

                return $namespaceController;
            }

            return $namespaceController;
        }

        return $namespaceController;
    }

    public function parseUnit($code)
    {
        return in_array($code, array_keys(self::$units)) ? self::$units[$code] : $code;
    }

    /**
     * @return UtilGermanized
     */
    public static function getInstance()
    {
        return parent::getInstance();
    }
}
