<?php

declare(strict_types=1);

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

use JtlWooCommerceConnector\Utilities\LinkTableNames;

require_once __DIR__ . '/vendor/autoload.php';

global $wpdb;

$tables = [
    LinkTableNames::CATEGORY,
    LinkTableNames::CROSSSELLING,
    LinkTableNames::CUSTOMER,
    LinkTableNames::IMAGE,
    LinkTableNames::ORDER,
    LinkTableNames::PAYMENT,
    LinkTableNames::PRODUCT,
    'jtl_connector_category_level',
    'jtl_connector_product_checksum',
    'jtl_connector_specific',
    'jtl_connector_specific_value',
];

foreach ($tables as $table) {
    $table_name = esc_sql($wpdb->prefix . $table);
    // phpcs:ignore WordPress.DB -- Table names cannot be used with $wpdb->prepare() placeholders
    $wpdb->query("DROP TABLE IF EXISTS `{$table_name}`");
}

$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
    $wpdb->prepare(
        "DELETE FROM `{$wpdb->options}` WHERE `option_name` LIKE %s",
        'jtlconnector_%'
    )
);
