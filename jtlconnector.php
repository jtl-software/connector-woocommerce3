<?php
/**
 * Plugin Name: JTL-Connector
 * Description: Verbinden Sie Ihren Shop mit JTL-Wawi, der kostenlosen Multichannel-Warenwirtschaft für den Versandhandel.
 * Version: 1.5.8-dev
 * WC tested up to: 3.4.5
 *
 * Author: JTL-Software GmbH
 * Author URI: http://www.jtl-software.de
 * License: GPL3
 * License URI: http://www.gnu.org/licenses/lgpl-3.0.html
 *
 * Requires at least WooCommerce: 3.4.0
 *
 * Text Domain: jtlconnector
 * Domain Path: languages/
 *
 * @author Jan Weskamp <jan.weskamp@jtl-software.com>
 */

const TEXT_DOMAIN = 'jtlconnector';
const WOOCOMMERCE_PLUGIN_FILE = 'woocommerce/woocommerce.php';

if (!defined('ABSPATH')) {
    exit;
}

require_once ABSPATH . '/wp-admin/includes/plugin.php';

add_action('init', 'load_internationalization');

add_action('plugins_loaded', 'validate_plugins');

if (rewriting_disabled()) {
    deactivate_plugin();
    add_action('admin_notices', 'rewriting_not_activated');
} else {
    require_once INCLUDES_DIR . 'JtlConnector.php';
    require_once INCLUDES_DIR . 'JtlConnectorAdmin.php';
    
    define('CONNECTOR_DIR', __DIR__);
    define('CONNECTOR_VERSION',  trim(\Symfony\Component\Yaml\Yaml::parseFile(__DIR__ . '/build-config.yaml')['version']));
    define('DS', DIRECTORY_SEPARATOR);
    define('INCLUDES_DIR', plugin_dir_path(__FILE__) . 'includes' . DS);

    require_once INCLUDES_DIR . 'JtlConnector.php';
    require_once INCLUDES_DIR . 'JtlConnectorAdmin.php';

    register_activation_hook(__FILE__, ['JtlConnectorAdmin', 'plugin_activation']);
    register_deactivation_hook(__FILE__, ['JtlConnectorAdmin', 'plugin_deactivation']);

    add_action('parse_request', ['JtlConnector', 'capture_request'], 1);

    if (is_admin()) {
        add_action('init', ['JtlConnectorAdmin', 'init']);
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), ['JtlConnectorAdmin', 'settings_link']);
    }
}

/**
 * Register the languages folder thus the DE and CH German translations are available based on the WP setting.
 */
function load_internationalization()
{
    load_plugin_textdomain(TEXT_DOMAIN, false, basename(dirname(__FILE__)) . '/languages');
}

/**
 * Check the status of WC, connector and the WC version.
 */
function validate_plugins()
{
    if (woocommerce_deactivated() && connector_activated()) {
        add_action('admin_notices', 'woocommerce_not_activated');
    } elseif (version_compare(get_woocommerce_version(), '3.0', '<')) {
        deactivate_plugin();
        add_action('admin_notices', 'wrong_woocommerce_version');
    }
}

/**
 * Deactivate the connector.
 */
function deactivate_plugin()
{
    deactivate_plugins(__FILE__);
}

/**
 * Check if the required WC is deactivated.
 *
 * @return bool
 */
function woocommerce_deactivated()
{
    return !in_array(WOOCOMMERCE_PLUGIN_FILE, apply_filters('active_plugins', get_option('active_plugins')));
}

/**
 * Check if the connector is activated.
 *
 * @return bool
 */
function connector_activated()
{
    return in_array('jtlconnector/jtlconnector.php', apply_filters('active_plugins', get_option('active_plugins')));
}

/**
 * Read out the WC version from the plugin file.
 *
 * @return string The WC version.
 */
function get_woocommerce_version()
{
    $plugin = get_plugin_data(WP_PLUGIN_DIR . '/' . WOOCOMMERCE_PLUGIN_FILE);

    return isset($plugin['Version']) ? $plugin['Version'] : '0';
}

/**
 * Without rewriting a URL like jtlconnector cannot be used.
 */
function rewriting_disabled()
{
    $permalink_structure = \get_option('permalink_structure');

    return empty($permalink_structure);
}

function woocommerce_not_activated()
{
    show_wordpress_error(__('Activate WooCommerce in order to use the JTL-Connector.', TEXT_DOMAIN), true);
}

function wrong_woocommerce_version()
{
    show_wordpress_error(__('At least WooCommerce 3.0 has to be installed.', TEXT_DOMAIN));
}

function rewriting_not_activated()
{
    show_wordpress_error(__('Rewriting is disabled. Please select another permalink setting.', TEXT_DOMAIN));
}

function show_wordpress_error($message, $show_install_link = false)
{
    $link = $show_install_link ? '<a class="" href="' . admin_url("plugin-install.php?tab=search&s=" . urlencode("WooCommerce")) . '">WooCommerce</a>' : '';

    echo "<div class='error'><h3>JTL-Connector</h3><p>$message</p><p>$link</p></div>";
}