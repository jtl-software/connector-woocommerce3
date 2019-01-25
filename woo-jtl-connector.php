<?php
/**
 * Plugin Name: WooCommerce JTL-Connector
 * Description: Connect your woocommerce-shop with JTL-Wawi, the free multichannel-erp for mail order business.
 * Version: 1.6.4
 * WC tested up to: 3.5.2
 * Author: JTL-Software GmbH
 * Author URI: http://www.jtl-software.de
 * License: GPL3
 * License URI: http://www.gnu.org/licenses/lgpl-3.0.html
 * Requires at least WooCommerce: 3.4.0
 * Text Domain: woo-jtl-connector
 * Domain Path: languages/
 *
 * @author Jan Weskamp <jan.weskamp@jtl-software.com>
 */
define( 'JTLWCC_TEXT_DOMAIN', 'woo-jtl-connector' );
define( 'JTLWCC_WOOCOMMERCE_PLUGIN_FILE', 'woocommerce/woocommerce.php' );
define( 'JTLWCC_DS', DIRECTORY_SEPARATOR );
define( 'JTLWCC_CONNECTOR_DIR', __DIR__ );
define( 'CONNECTOR_DIR', __DIR__ ); // NEED CONNECTOR CORE CHANGES
define( 'JTLWCC_INCLUDES_DIR', plugin_dir_path( __FILE__ ) . 'includes' . JTLWCC_DS );


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once ABSPATH . '/wp-admin/includes/plugin.php';

try {
	if ( file_exists( JTLWCC_CONNECTOR_DIR . '/connector.phar' ) ) {
		if ( is_writable( sys_get_temp_dir() ) ) {
			$loader = require( 'phar://' . JTLWCC_CONNECTOR_DIR . '/connector.phar/vendor/autoload.php' );
			$loader->add( '', JTLWCC_CONNECTOR_DIR . '/plugins' );
		}
	} else {
		$loader = require( JTLWCC_CONNECTOR_DIR . '/vendor/autoload.php' );
		$loader->add( '', JTLWCC_CONNECTOR_DIR . '/plugins' );
	}
} catch ( \Exception $e ) {

}

add_action( 'init', 'jtlwcc_load_internationalization' );
add_action( 'plugins_loaded', 'jtlwcc_validate_plugins' );

if ( jtlwcc_rewriting_disabled() ) {
	jtlwcc_deactivate_plugin();
	add_action( 'admin_notices', 'jtlwcc_rewriting_not_activated' );
} else {
	require_once JTLWCC_INCLUDES_DIR . 'JtlConnector.php';
	require_once JTLWCC_INCLUDES_DIR . 'JtlConnectorAdmin.php';
	
	register_activation_hook( __FILE__, [ 'JtlConnectorAdmin', 'plugin_activation' ] );
	register_deactivation_hook( __FILE__, [ 'JtlConnectorAdmin', 'plugin_deactivation' ] );
	
	add_action( 'parse_request', 'JtlConnector::capture_request', 1 );
	
	if ( is_admin() ) {
		add_action( 'init', [ 'JtlConnectorAdmin', 'init' ] );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ 'JtlConnectorAdmin', 'settings_link' ] );
	}
}

/**
 * Register the languages folder thus the DE and CH German translations are available based on the WP setting.
 */
function jtlwcc_load_internationalization() {
	load_plugin_textdomain( JTLWCC_TEXT_DOMAIN, false, basename( dirname( __FILE__ ) ) . '/languages' );
}

/**
 * Check the status of WC, connector and the WC version.
 */
function jtlwcc_validate_plugins() {
	if ( jtlwcc_woocommerce_deactivated() && jtlwcc_connector_activated() ) {
		add_action( 'admin_notices', 'jtlwcc_woocommerce_not_activated' );
	} elseif ( version_compare( jtlwcc_get_woocommerce_version(), '3.0', '<' ) ) {
		jtlwcc_deactivate_plugin();
		add_action( 'admin_notices', 'jtlwcc_wrong_woocommerce_version' );
	}
}

/**
 * Deactivate the connector.
 */
function jtlwcc_deactivate_plugin() {
	deactivate_plugins( __FILE__ );
}

/**
 * Check if the required WC is deactivated.
 *
 * @return bool
 */
function jtlwcc_woocommerce_deactivated() {
	return ! in_array( JTLWCC_WOOCOMMERCE_PLUGIN_FILE,
		apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
}

/**
 * Check if the connector is activated.
 *
 * @return bool
 */
function jtlwcc_connector_activated() {
	return in_array( 'woo-jtl-connector/woo-jtl-connector.php',
		apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
}

/**
 * Read out the WC version from the plugin file.
 *
 * @return string The WC version.
 */
function jtlwcc_get_woocommerce_version() {
	$plugin = get_plugin_data( WP_PLUGIN_DIR . '/' . JTLWCC_WOOCOMMERCE_PLUGIN_FILE );
	
	return isset( $plugin['Version'] ) ? $plugin['Version'] : '0';
}

/**
 * Without rewriting a URL like jtlconnector cannot be used.
 */
function jtlwcc_rewriting_disabled() {
	$permalink_structure = \get_option( 'permalink_structure' );
	
	return empty( $permalink_structure );
}

function jtlwcc_woocommerce_not_activated() {
	jtlwcc_show_wordpress_error( __( 'Activate WooCommerce in order to use the JTL-Connector.', JTLWCC_TEXT_DOMAIN ),
		true );
}

function jtlwcc_wrong_woocommerce_version() {
	jtlwcc_show_wordpress_error( __( 'At least WooCommerce 3.0 has to be installed.', JTLWCC_TEXT_DOMAIN ) );
}

function jtlwcc_rewriting_not_activated() {
	jtlwcc_show_wordpress_error( __( 'Rewriting is disabled. Please select another permalink setting.',
		JTLWCC_TEXT_DOMAIN ) );
}

function jtlwcc_show_wordpress_error( $message, $show_install_link = false ) {
	$link = $show_install_link ? '<a class="" href="' . admin_url( "plugin-install.php?tab=search&s=" . urlencode( "WooCommerce" ) ) . '">WooCommerce</a>' : '';
	
	echo "<div class='error'><h3>JTL-Connector</h3><p>$message</p><p>$link</p></div>";
}