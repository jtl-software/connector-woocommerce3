<?php
/**
 * Plugin Name: WooCommerce JTL-Connector
 * Description: Connect your woocommerce-shop with JTL-Wawi, the free multichannel-erp for mail order business.
 * Version: 2.4.1-dev
 * Requires PHP: 7.1.3
 * WC tested up to: 5.0
 * Author: JTL-Software GmbH
 * Author URI: http://www.jtl-software.de
 * License: GPL3
 * License URI: http://www.gnu.org/licenses/lgpl-3.0.html
 * Requires at least WooCommerce: 3.4.7
 * Text Domain: woo-jtl-connector
 *
 * @author Jan Weskamp <jan.weskamp@jtl-software.com>
 */

define('JTLWCC_TEXT_DOMAIN', 'woo-jtl-connector');
define('JTLWCC_WOOCOMMERCE_PLUGIN_FILE', 'woocommerce/woocommerce.php');
define('JTLWCC_DS', DIRECTORY_SEPARATOR);
define('JTLWCC_CONNECTOR_DIR', __DIR__);
define('JTLWCC_EXT_CONNECTOR_PLUGIN_DIR', dirname(__DIR__) . '/' . JTLWCC_TEXT_DOMAIN . '-custom-plugins');
define('JTLWCC_CONNECTOR_DIR_URL', WP_PLUGIN_URL . JTLWCC_DS . JTLWCC_TEXT_DOMAIN);
define('CONNECTOR_DIR', __DIR__); // NEED CONNECTOR CORE CHANGES
define('JTLWCC_INCLUDES_DIR', plugin_dir_path(__FILE__) . 'includes' . JTLWCC_DS);

if (!defined('ABSPATH')) {
    exit;
}

require_once ABSPATH . '/wp-admin/includes/plugin.php';

try {
    if (file_exists(JTLWCC_CONNECTOR_DIR . '/connector.phar')) {
        if (is_writable(sys_get_temp_dir())) {
            $loader = require('phar://' . JTLWCC_CONNECTOR_DIR . '/connector.phar/vendor/autoload.php');
            $loader->add('', JTLWCC_CONNECTOR_DIR . '/plugins');
            if (is_dir(JTLWCC_EXT_CONNECTOR_PLUGIN_DIR)) {
                $loader->add('', JTLWCC_EXT_CONNECTOR_PLUGIN_DIR);
            }
        }
    } else {
        $loader = require(JTLWCC_CONNECTOR_DIR . '/vendor/autoload.php');
        $loader->add('', JTLWCC_CONNECTOR_DIR . '/plugins');
        if (is_dir(JTLWCC_EXT_CONNECTOR_PLUGIN_DIR)) {
            $loader->add('', JTLWCC_EXT_CONNECTOR_PLUGIN_DIR);
        }
    }
} catch (\Exception $e) {

}

add_action('init', 'jtlwcc_load_internationalization');
add_action('plugins_loaded', 'jtlwcc_validate_plugins');

if (jtlwcc_rewriting_disabled()) {
    jtlwcc_deactivate_plugin();
    add_action('admin_notices', 'jtlwcc_rewriting_not_activated');
} else {
    require_once JTLWCC_INCLUDES_DIR . 'JtlConnector.php';
    require_once JTLWCC_INCLUDES_DIR . 'JtlConnectorAdmin.php';
    
    register_activation_hook(__FILE__, [
        'JtlConnectorAdmin',
        'plugin_activation',
    ]);
    register_deactivation_hook(__FILE__, [
        'JtlConnectorAdmin',
        'plugin_deactivation',
    ]);
    
    add_action('parse_request', 'JtlConnector::capture_request', 1);
    
    if (is_admin()) {
        add_action('init', [
            'JtlConnectorAdmin',
            'init',
        ]);
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [
            'JtlConnectorAdmin',
            'settings_link',
        ]);
        add_action('admin_footer', 'woo_jtl_connector_settings_javascript', PHP_INT_MAX);
        add_action('wp_ajax_downloadJTLLogs', 'downloadJTLLogs', PHP_INT_MAX);
        add_action('wp_ajax_clearJTLLogs', 'clearJTLLogs', PHP_INT_MAX);
    }
}

function woo_jtl_connector_settings_javascript()
{
    ?>
    <script type="text/javascript">
        jQuery(document).ready(($) => {
            //console.log('Script Loaded ');
            $("#downloadLogBtn").click(
                () => {
                    let data = {
                        'action': 'downloadJTLLogs',
                    };

                    jQuery.ajax(
                        {
                            url: ajaxurl,
                            type: 'POST',
                            data: data,
                            success: (response) => {
                                console.log(response);
                                window.location.href = response;
                            },
                            error: (response) => {
                                response = JSON.parse(response.responseText);
                                alert(response.message);
                            }
                        }
                    );
                }
            );

            $("#clearLogBtn").click(
                () => {
                    let result = confirm("Are you sure you want a reset?");
                    if (result) {

                        let data = {
                            'action': 'clearJTLLogs',
                        };

                        jQuery.ajax(
                            {
                                url: ajaxurl,
                                type: 'POST',
                                data: data,
                                success: (response) => {
                                    //console.log(response);
                                },
                            }
                        );
                    }
                }
            );
        });
    </script>
    <?php
}

function downloadJTLLogs()
{
    $logDir = CONNECTOR_DIR . '/logs';
    $zip_file = CONNECTOR_DIR . '/tmp/connector_logs.zip';
    $url = get_site_url() . '/wp-content/plugins/woo-jtl-connector/tmp/connector_logs.zip';
    
    // Get real path for our folder
    $rootPath = $logDir;
    
    // Initialize archive object
    $zip = new ZipArchive();
    $zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    
    // Create recursive directory iterator
    /** @var SplFileInfo[] $files */
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootPath),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    $filesCounter = 0;
    foreach ($files as $name => $file) {
        
        if ($file->getFilename() === '.gitkeep') {
            continue;
        }
        
        // Skip directories (they would be added automatically)
        if (!$file->isDir()) {
            // Get real and relative path for current file
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($rootPath) + 1);
            
            // Add current file to archive
            $zip->addFile($filePath, $relativePath);
            $filesCounter++;
        }
    }
    
    // Zip archive will be created only after closing object
    $zip->close();
    
    header('Content-Type: application/json; charset=UTF-8');
    
    if ($filesCounter > 0) {
        print json_encode($url);
    } else {
        header('HTTP/1.1 451 Internal Server Booboo');
        die(json_encode([
            'message' => 'Keine Logs Vorhanden!',
            'code'    => 451,
        ]));
    }
    
    wp_die();
    //self::display_page();
}

function clearJTLLogs()
{
    $logDir = CONNECTOR_DIR . '/logs';
    $zip_file = CONNECTOR_DIR . '/tmp/connector_logs.zip';
    
    if (file_exists($zip_file)) {
        unlink($zip_file);
    }
    
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($logDir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($files as $name => $file) {
        
        if ($file->getFilename() === '.gitkeep') {
            continue;
        }
        
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }
    
    echo 'success';
    
    wp_die();
}

/**
 * Register the languages folder thus the DE and CH German translations are available based on the WP setting.
 */
function jtlwcc_load_internationalization()
{
    load_plugin_textdomain(JTLWCC_TEXT_DOMAIN, false, basename(dirname(__FILE__)) . '/languages');
}

/**
 * Check the status of WC, connector and the WC version.
 */
function jtlwcc_validate_plugins()
{
    if (jtlwcc_woocommerce_deactivated() && jtlwcc_connector_activated()) {
        add_action('admin_notices', 'jtlwcc_woocommerce_not_activated');
    } elseif (version_compare(jtlwcc_get_woocommerce_version(), '3.0', '<')) {
        jtlwcc_deactivate_plugin();
        add_action('admin_notices', 'jtlwcc_wrong_woocommerce_version');
    }
}

/**
 * Deactivate the connector.
 */
function jtlwcc_deactivate_plugin()
{
    deactivate_plugins(__FILE__);
}

/**
 * Check if the required WC is deactivated.
 *
 * @return bool
 */
function jtlwcc_woocommerce_deactivated()
{
    return !in_array(JTLWCC_WOOCOMMERCE_PLUGIN_FILE,
        apply_filters('active_plugins', get_option('active_plugins')));
}

/**
 * Redirect action
 */
function woo_jtl_connector_menu_link()
{
    $link = 'admin.php?page=wc-settings&tab=woo-jtl-connector';
    wp_redirect($link, 301);
    exit;
}

/**
 * Check if the connector is activated.
 *
 * @return bool
 */
function jtlwcc_connector_activated()
{
    return in_array('woo-jtl-connector/woo-jtl-connector.php',
        apply_filters('active_plugins', get_option('active_plugins')));
}

/**
 * Read out the WC version from the plugin file.
 *
 * @return string The WC version.
 */
function jtlwcc_get_woocommerce_version()
{
    $plugin = get_plugin_data(WP_PLUGIN_DIR . '/' . JTLWCC_WOOCOMMERCE_PLUGIN_FILE);
    
    return isset($plugin['Version']) ? $plugin['Version'] : '0';
}

/**
 * Without rewriting a URL like jtlconnector cannot be used.
 */
function jtlwcc_rewriting_disabled()
{
    $permalink_structure = \get_option('permalink_structure');
    
    return empty($permalink_structure);
}

function jtlwcc_woocommerce_not_activated()
{
    jtlwcc_show_wordpress_error(__('Activate WooCommerce in order to use the JTL-Connector.', JTLWCC_TEXT_DOMAIN),
        true);
}

function jtlwcc_wrong_woocommerce_version()
{
    jtlwcc_show_wordpress_error(__('At least WooCommerce 3.0 has to be installed.', JTLWCC_TEXT_DOMAIN));
}

function jtlwcc_rewriting_not_activated()
{
    jtlwcc_show_wordpress_error(__('Rewriting is disabled. Please select another permalink setting.',
        JTLWCC_TEXT_DOMAIN));
}

function jtlwcc_show_wordpress_error($message, $show_install_link = false)
{
    $link = $show_install_link ? '<a class="" href="' . admin_url("plugin-install.php?tab=search&s=" . urlencode("WooCommerce")) . '">WooCommerce</a>' : '';
    
    echo "<div class='error'><h3>JTL-Connector</h3><p>$message</p><p>$link</p></div>";
}