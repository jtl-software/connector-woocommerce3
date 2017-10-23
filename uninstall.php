<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$wpdb->query('DROP TABLE IF EXISTS `jtl_connector_link_category`');
$wpdb->query('DROP TABLE IF EXISTS `jtl_connector_link_crossselling`');
$wpdb->query('DROP TABLE IF EXISTS `jtl_connector_link_customer`');
$wpdb->query('DROP TABLE IF EXISTS `jtl_connector_link_image`');
$wpdb->query('DROP TABLE IF EXISTS `jtl_connector_link_order`');
$wpdb->query('DROP TABLE IF EXISTS `jtl_connector_link_payment`');
$wpdb->query('DROP TABLE IF EXISTS `jtl_connector_link_product`');
$wpdb->query('DROP TABLE IF EXISTS `jtl_connector_category_level`');
$wpdb->query('DROP TABLE IF EXISTS `jtl_connector_product_checksum`');

$wpdb->query("DELETE FROM `{$wpdb->options}` WHERE `option_name` LIKE 'jtlconnector_%'");