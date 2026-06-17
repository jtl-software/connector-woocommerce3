<?php //phpcs:ignore PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Plugin Name: JTL-Connector for WooCommerce
 * Description: Connect your woocommerce-shop with JTL-Wawi, the free multichannel-erp for mail order business.
 * Version: 2.4.2
 * Requires PHP: 8.3
 * Requires at least: 6.4
 * Tested up to: 7.0
 * WC tested up to: 10.8.1
 * Author: JTL-Software GmbH
 * Author URI: http://www.jtl-software.de
 * License: GPL3
 * License URI: http://www.gnu.org/licenses/lgpl-3.0.html
 * Requires at least WooCommerce: 3.4.7
 * Text Domain: woo-jtl-connector
 *
 * @author JTL-Software-GmbH <info@jtl-software.com>
 */

if (!defined('ABSPATH')) {
    exit;
}

define('JTLWCC_TEXT_DOMAIN', 'woo-jtl-connector');
define('JTLWCC_WOOCOMMERCE_PLUGIN_FILE', 'woocommerce/woocommerce.php');
define('JTLWCC_DS', DIRECTORY_SEPARATOR);
define('JTLWCC_CONNECTOR_DIR', __DIR__);
define('JTLWCC_EXT_CONNECTOR_PLUGIN_DIR', dirname(__DIR__) . '/' . JTLWCC_TEXT_DOMAIN . '-custom-plugins');
/** @phpstan-ignore constant.notFound */
define('JTLWCC_CONNECTOR_DIR_URL', WP_PLUGIN_URL . JTLWCC_DS . JTLWCC_TEXT_DOMAIN);
define('CONNECTOR_DIR', __DIR__); // NEED CONNECTOR CORE CHANGES
define('JTLWCC_INCLUDES_DIR', plugin_dir_path(__FILE__) . 'includes' . JTLWCC_DS);

require_once ABSPATH . '/wp-admin/includes/plugin.php';

try {
    if (file_exists(JTLWCC_CONNECTOR_DIR . '/connector.phar')) {
        if (wp_is_writable(sys_get_temp_dir())) {
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
    //loader failed
}


add_action('before_woocommerce_init', function (): void {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});
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
        add_action('admin_footer', 'jtlwcc_settings_javascript', PHP_INT_MAX);
        add_action('wp_ajax_jtlwcc_download_logs', 'jtlwcc_download_logs', PHP_INT_MAX);
        add_action('wp_ajax_jtlwcc_clear_logs', 'jtlwcc_clear_logs', PHP_INT_MAX);
    }
}

/**
 * @return void
 */
function jtlwcc_settings_javascript(): void
{
    $nonce = wp_create_nonce('jtl_logs_nonce');
    ?>
    <script type="text/javascript">
        jQuery(document).ready(($) => {
            $("#downloadLogBtn").click(
                () => {
                    let data = {
                        'action': 'jtlwcc_download_logs',
                        '_ajax_nonce': '<?php echo esc_js($nonce); ?>',
                    };

                    jQuery.ajax(
                        {
                            url: ajaxurl,
                            type: 'POST',
                            data: data,
                            xhrFields: { responseType: 'blob' },
                            success: (blob) => {
                                const url = window.URL.createObjectURL(blob);
                                const a = document.createElement('a');
                                a.href = url;
                                a.download = 'connector_logs.zip';
                                document.body.appendChild(a);
                                a.click();
                                a.remove();
                                window.URL.revokeObjectURL(url);
                            },
                            error: (response) => {
                                let msg = 'An error occurred.';
                                try {
                                    const parsed = JSON.parse(response.responseText);
                                    msg = parsed.message;
                                } catch (e) {}
                                alert(msg);
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
                            'action': 'jtlwcc_clear_logs',
                            '_ajax_nonce': '<?php echo esc_js($nonce); ?>',
                        };

                        jQuery.ajax(
                            {
                                url: ajaxurl,
                                type: 'POST',
                                data: data,
                                success: (response) => {
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

/**
 * @return void
 * @throws UnexpectedValueException
 */
function jtlwcc_download_logs(): void
{
    if (!current_user_can('manage_woocommerce')) {
        wp_die('', '', ['response' => 403]);
    }

    check_ajax_referer('jtl_logs_nonce');

    $logDir   = CONNECTOR_DIR . '/var/log';
    $tmp_file = wp_tempnam('connector_logs');

    if ($tmp_file === '' || $tmp_file === false) {
        wp_send_json_error(['message' => 'Failed to create temporary file.'], 500);
    }

    $zip_file = $tmp_file . '.zip';
    $rootPath = $logDir;

    $zip    = new ZipArchive();
    $result = $zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    if ($result !== true) {
        wp_delete_file($tmp_file);
        wp_send_json_error(['message' => 'Failed to create ZIP archive.'], 500);
    }

    /** @var SplFileInfo[] $files */
    $files        = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootPath),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    $filesCounter = 0;
    foreach ($files as $name => $file) {
        if ($file->getFilename() === '.gitkeep') {
            continue;
        }

        if (!$file->isDir()) {
            $filePath     = $file->getRealPath();
            $relativePath = substr($filePath, strlen($rootPath) + 1);

            $zip->addFile($filePath, $relativePath);
            $filesCounter++;
        }
    }

    $zip->close();

    if ($filesCounter > 0) {
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="connector_logs.zip"');
        header('Content-Length: ' . filesize($zip_file));
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- Streaming binary ZIP
        readfile($zip_file);
        wp_delete_file($zip_file);
        wp_delete_file($tmp_file);
        exit;
    }

    wp_delete_file($zip_file);
    wp_delete_file($tmp_file);
    wp_send_json_error(['message' => 'No log files found.'], 404);
}

/**
 * @return void
 * @throws UnexpectedValueException
 */
function jtlwcc_clear_logs(): void
{
    if (!current_user_can('manage_woocommerce')) {
        wp_die('', '', ['response' => 403]);
    }

    check_ajax_referer('jtl_logs_nonce');

    $logDir   = CONNECTOR_DIR . '/var/log';
    $zip_file = CONNECTOR_DIR . '/tmp/connector_logs.zip';

    if (file_exists($zip_file)) {
        wp_delete_file($zip_file);
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($logDir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    /** @var SplFileInfo[] $files */
    foreach ($files as $name => $file) {
        if ($file->getFilename() === '.gitkeep') {
            continue;
        }

        if (!$file->isDir()) {
            $filePath = $file->getRealPath();

            if (file_exists($filePath)) {
                wp_delete_file($filePath);
            }
        }
    }

    echo 'success';

    wp_die();
}


/**
 * @param bool $exit
 * @return void
 * @throws UnexpectedValueException
 */
function jtlwcc_clear_connector_cache(bool $exit = true): void
{
    $cacheDir = CONNECTOR_DIR . '/var/cache';

    if (is_dir($cacheDir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($cacheDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        /** @var SplFileInfo[] $files */
        foreach ($files as $name => $file) {
            if ($file->getFilename() === '.gitkeep') {
                continue;
            }

            if (!$file->isDir()) {
                $filePath = $file->getRealPath();

                if (file_exists($filePath)) {
                    wp_delete_file($filePath);
                }
            }
        }

        echo 'success';

        if ($exit) {
            wp_die();
        }
    }
}
/**
 * Register the languages folder thus the DE and CH German translations are available based on the WP setting.
 *
 * @return void
 */
function jtlwcc_load_internationalization(): void
{
    load_plugin_textdomain('woo-jtl-connector', false, basename(dirname(__FILE__)) . '/languages');
}

/**
 * Check the status of WC, connector and the WC version.
 *
 * @return void
 */
function jtlwcc_validate_plugins(): void
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
 *
 * @return void
 */
function jtlwcc_deactivate_plugin(): void
{
    deactivate_plugins(__FILE__);
}

/**
 * Check if the required WC is deactivated.
 *
 * @return bool
 */
function jtlwcc_woocommerce_deactivated(): bool
{
    return !in_array(
        JTLWCC_WOOCOMMERCE_PLUGIN_FILE,
        apply_filters('active_plugins', get_option('active_plugins'))
    );
}

/**
 * Redirect action
 *
 * @return void
 */
function jtlwcc_menu_link(): void
{
    $link = 'admin.php?page=wc-settings&tab=woo-jtl-connector';
    wp_safe_redirect($link, 301);
    exit;
}

/**
 * Check if the connector is activated.
 *
 * @return bool
 */
function jtlwcc_connector_activated(): bool
{
    return in_array(
        'woo-jtl-connector/woo-jtl-connector.php',
        apply_filters('active_plugins', get_option('active_plugins'))
    );
}

/**
 * Read out the WC version from the plugin file.
 *
 * @return string The WC version.
 */
function jtlwcc_get_woocommerce_version(): string
{
    $plugin = get_plugin_data(WP_PLUGIN_DIR . '/' . JTLWCC_WOOCOMMERCE_PLUGIN_FILE);

    return $plugin['Version'];
}

/**
 * Without rewriting a URL like jtlconnector cannot be used.
 *
 * @return bool
 */
function jtlwcc_rewriting_disabled(): bool
{
    $permalink_structure = \get_option('permalink_structure');

    return empty($permalink_structure);
}

/**
 * @return void
 */
function jtlwcc_woocommerce_not_activated(): void
{
    jtlwcc_show_wordpress_error(
        __('Activate WooCommerce in order to use the JTL-Connector.', 'woo-jtl-connector'),
        true
    );
}

/**
 * @return void
 */
function jtlwcc_wrong_woocommerce_version(): void
{
    jtlwcc_show_wordpress_error(__('At least WooCommerce 3.0 has to be installed.', 'woo-jtl-connector'));
}

/**
 * @return void
 */
function jtlwcc_rewriting_not_activated(): void
{
    jtlwcc_show_wordpress_error(__(
        'Rewriting is disabled. Please select another permalink setting.',
        'woo-jtl-connector'
    ));
}

/**
 * @param string $message
 * @param bool   $show_install_link
 * @return void
 */
function jtlwcc_show_wordpress_error(string $message, bool $show_install_link = false): void
{
    $link = $show_install_link
        ? '<a class="" href="' .
          esc_url(admin_url("plugin-install.php?tab=search&s=" .
                    urlencode("WooCommerce"))) . '">WooCommerce</a>'
        : '';

    echo "<div class='error'><h3>JTL-Connector</h3><p>"
        . esc_html($message) . "</p><p>" . wp_kses_post($link) . "</p></div>";
}