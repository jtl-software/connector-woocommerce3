<?php
/**
 * Created by PhpStorm.
 * User: Jan Weskamp <jan.weskamp@jtl-software.com>
 * Date: 07.11.2018
 * Time: 09:46
 */

namespace JtlWooCommerceConnector\Utilities\SqlTraits;


trait PaymentTrait {
	public static function paymentCompletedPull( $limit = null, $includeCompletedOrders ) {
		global $wpdb;
		$jclp = $wpdb->prefix . 'jtl_connector_link_payment';
		
		if ( is_null( $limit ) ) {
			$select     = 'COUNT(DISTINCT(p.ID))';
			$limitQuery = '';
		} else {
			$select     = 'DISTINCT(p.ID)';
			$limitQuery = 'LIMIT ' . $limit;
		}
		
		// Usually processing means paid but exception for Cash on delivery
		$status = "p.post_status = 'wc-processing' AND p.ID NOT IN (SELECT pm.post_id FROM {$wpdb->postmeta} pm WHERE pm.meta_value = 'cod')";
		
		if ( $includeCompletedOrders ) {
			$status = "(p.post_status = 'wc-completed' OR {$status})";
		}
		
		$since = \get_option( \JtlConnectorAdmin::OPTIONS_PULL_ORDERS_SINCE );
		$where = ( ! empty( $since ) && strtotime( $since ) !== false ) ? "AND p.post_date > '{$since}'" : '';
		
		return "
            SELECT {$select}
            FROM {$wpdb->posts} p
            LEFT JOIN {$jclp} l ON l.endpoint_id = p.ID
            WHERE p.post_type = 'shop_order' AND l.host_id IS NULL AND {$status} {$where}
            {$limitQuery}";
	}
}