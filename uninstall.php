<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$tables = [
	'jtl_connector_link_category',
	'jtl_connector_link_crossselling',
	'jtl_connector_link_customer',
	'jtl_connector_link_image',
	'jtl_connector_link_order',
	'jtl_connector_link_payment',
	'jtl_connector_link_product',
	'jtl_connector_category_level',
	'jtl_connector_product_checksum',
	'jtl_connector_specific',
	'jtl_connector_specific_value',
];

foreach ( $tables as $table ) {
	$wpdb->query( sprintf( 'DROP TABLE IF EXISTS %s%s', $wpdb->prefix , $table ));
}


$wpdb->query( "DELETE FROM `{$wpdb->options}` WHERE `option_name` LIKE 'jtlconnector_%'" );