<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Utilities;

use Symfony\Component\Yaml\Yaml;

/**
 * Class Config
 * @package JtlWooCommerceConnector\Utilities
 */
class Config
{
    public const
        OPTIONS_AUTO_B2B_MARKET_OPTIONS = 'jtlconnector_auto_b2b_market',
        OPTIONS_PULL_ORDERS_SINCE = 'jtlconnector_pull_orders_since',
        OPTIONS_SHOW_VARIATION_SPECIFICS_ON_PRODUCT_PAGE = 'jtlconnector_show_variation_specifics_on_product_page',
        OPTIONS_TOKEN = 'connector_password',
        OPTIONS_RECALCULATE_COUPONS_ON_PULL = 'jtlconnector_recalculate_coupons_on_pull',
        OPTIONS_SEND_CUSTOM_PROPERTIES = 'jtlconnector_send_custom_properties',
        OPTIONS_INSTALLED_VERSION = 'jtlconnector_installed_version',
        OPTIONS_SUFFIX_DELIVERYTIME = 'jtlconnector_suffix_deliverytime',
        OPTIONS_VARIATION_NAME_FORMAT = 'jtlconnector_variation_name_format',
        OPTIONS_DEFAULT_CUSTOMER_GROUP = 'jtlconnector_default_customer_group',
        OPTIONS_USE_GTIN_FOR_EAN = 'jtlconnector_use_gtin_for_ean',
        OPTIONS_ALLOW_HTML_IN_PRODUCT_ATTRIBUTES = 'jtlconnector_allow_html_in_product_attributes',
        OPTIONS_COMPLETED_ORDERS = 'jtlconnector_completed_orders',
        OPTIONS_DISABLED_ZERO_DELIVERY_TIME = 'jtlconnector_disabled_zero_delivery_time',
        OPTIONS_UPDATE_FAILED = 'jtlconnector_update_failed',
        OPTIONS_USE_DELIVERYTIME_CALC = 'jtlconnector_use_deliverytime_calc',
        OPTIONS_PRAEFIX_DELIVERYTIME = 'jtlconnector_praefix_deliverytime',
        OPTIONS_DEVELOPER_LOGGING = 'developer_logging',
        OPTIONS_AUTO_WOOCOMMERCE_OPTIONS = 'jtlconnector_auto_woocommerce',
        OPTIONS_AUTO_GERMAN_MARKET_OPTIONS = 'jtlconnector_auto_german_market';

    public const JTLWCC_CONFIG_DEFAULTS = [
        //FIRSTPAGE
        Config::OPTIONS_SHOW_VARIATION_SPECIFICS_ON_PRODUCT_PAGE => true,
        Config::OPTIONS_SEND_CUSTOM_PROPERTIES => true,
        Config::OPTIONS_VARIATION_NAME_FORMAT => '',
        Config::OPTIONS_USE_GTIN_FOR_EAN => true,
        Config::OPTIONS_ALLOW_HTML_IN_PRODUCT_ATTRIBUTES => false,
        Config::OPTIONS_DEFAULT_CUSTOMER_GROUP => 'customer',
        //PAGE
        Config::OPTIONS_USE_DELIVERYTIME_CALC => true,
        Config::OPTIONS_DISABLED_ZERO_DELIVERY_TIME => true,
        Config::OPTIONS_PRAEFIX_DELIVERYTIME => 'ca. ',
        Config::OPTIONS_SUFFIX_DELIVERYTIME => ' Werktage',
        //PAGE
        Config::OPTIONS_COMPLETED_ORDERS => true,
        Config::OPTIONS_PULL_ORDERS_SINCE => '',
        Config::OPTIONS_RECALCULATE_COUPONS_ON_PULL => false,
        //Page
        Config::OPTIONS_DEVELOPER_LOGGING => false,
        Config::OPTIONS_AUTO_WOOCOMMERCE_OPTIONS => true,
        Config::OPTIONS_AUTO_GERMAN_MARKET_OPTIONS => true,
        Config::OPTIONS_AUTO_B2B_MARKET_OPTIONS => true,
    ];

    public const JTLWCC_CONFIG = [
        //FIRSTPAGE
        Config::OPTIONS_SHOW_VARIATION_SPECIFICS_ON_PRODUCT_PAGE => 'bool',
        Config::OPTIONS_SEND_CUSTOM_PROPERTIES => 'bool',
        Config::OPTIONS_VARIATION_NAME_FORMAT => 'string',
        Config::OPTIONS_USE_GTIN_FOR_EAN => 'bool',
        Config::OPTIONS_ALLOW_HTML_IN_PRODUCT_ATTRIBUTES => 'bool',
        Config::OPTIONS_DEFAULT_CUSTOMER_GROUP => 'string',
        //PAGE
        Config::OPTIONS_USE_DELIVERYTIME_CALC => 'bool',
        Config::OPTIONS_DISABLED_ZERO_DELIVERY_TIME => 'bool',
        Config::OPTIONS_PRAEFIX_DELIVERYTIME => 'string',
        Config::OPTIONS_SUFFIX_DELIVERYTIME => 'string',
        //PAGE
        Config::OPTIONS_COMPLETED_ORDERS => 'bool',
        Config::OPTIONS_PULL_ORDERS_SINCE => 'date',
        Config::OPTIONS_RECALCULATE_COUPONS_ON_PULL => 'bool',
        //Page
        Config::OPTIONS_DEVELOPER_LOGGING => 'bool',
        Config::OPTIONS_AUTO_WOOCOMMERCE_OPTIONS => 'bool',
        Config::OPTIONS_AUTO_GERMAN_MARKET_OPTIONS => 'bool',
        Config::OPTIONS_AUTO_B2B_MARKET_OPTIONS => 'bool',
    ];

    /**
     * @param string $name
     * @param $value
     * @return bool
     */
    public static function set(string $name, $value): bool
    {
        $allowedKeys = self::JTLWCC_CONFIG;
        $allowedKeys[Config::OPTIONS_INSTALLED_VERSION] = 'string';
        $allowedKeys[Config::OPTIONS_TOKEN] = 'string';

        $result = false;
        if (in_array($name, array_keys($allowedKeys))) {
            $oldValue = self::get($name);
            if (is_null($oldValue)) {
                $result = add_option($name, $value);
            } else {
                $result = update_option($name, $value, true);
            }
        }

        return $result;
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function has(string $name): bool
    {
        return self::get($name, false) ? true : false;
    }

    /**
     * @param string $name
     * @param null $defaultValue
     * @return bool|mixed|void
     */
    public static function get(string $name, $defaultValue = null)
    {
        return get_option($name, $defaultValue);
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function remove(string $name): bool
    {
        return delete_option($name);
    }

    /**
     * @return string
     */
    public static function getBuildVersion(): string
    {
        return (string)trim(Yaml::parseFile(JTLWCC_CONNECTOR_DIR . '/build-config.yaml')['version']);
    }

    /**
     * @param bool $value
     * @return bool
     */
    public static function updateDeveloperLoggingSettings(bool $value): bool
    {
        $file = CONNECTOR_DIR . '/config/config.json';

        $config = new \stdClass();
        if (!file_exists($file)) {
            file_put_contents($file, json_encode($config));
        } else {
            $config = json_decode(file_get_contents($file));
            if (!$config instanceof \stdClass) {
                $config = new \stdClass();
            }
        }

        $config->{self::OPTIONS_DEVELOPER_LOGGING} = $value;

        return (bool)file_put_contents($file, json_encode($config));
    }
}
