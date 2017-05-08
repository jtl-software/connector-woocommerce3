<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$wpdb->query('DROP TABLE IF EXISTS jtl_connector_link');
$wpdb->query('DROP TABLE IF EXISTS jtl_connector_category_level');
$wpdb->query('DROP TABLE IF EXISTS jtl_connector_product_checksum');