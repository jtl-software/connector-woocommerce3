<?php

declare(strict_types=1);

if (! defined('WP_UNINSTALL_PLUGIN')) {
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

foreach ($tables as $table) {
    $table_name = esc_sql($wpdb->prefix . $table);
    $wpdb->query("DROP TABLE IF EXISTS `{$table_name}`"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names cannot be used with $wpdb->prepare() placeholders
}

$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
    $wpdb->prepare(
        "DELETE FROM `{$wpdb->options}` WHERE `option_name` LIKE %s",
        'jtlconnector_%'
    )
);
