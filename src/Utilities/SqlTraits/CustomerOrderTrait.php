<?php
/**
 * Created by PhpStorm.
 * User: Jan Weskamp <jan.weskamp@jtl-software.com>
 * Date: 07.11.2018
 * Time: 09:44
 */

namespace JtlWooCommerceConnector\Utilities\SqlTraits;


trait CustomerOrderTrait {
	public static function customerOrderPull( $limit ) {
		global $wpdb;
		$jclo = $wpdb->prefix . 'jtl_connector_link_order';
		
		if ( is_null( $limit ) ) {
			$select     = 'COUNT(DISTINCT(p.ID))';
			$limitQuery = '';
		} else {
			$select     = 'DISTINCT(p.ID)';
			$limitQuery = 'LIMIT ' . $limit;
		}
		
		$status = "'wc-pending', 'wc-processing', 'wc-on-hold'";
  
		if (   \get_option( \JtlConnectorAdmin::OPTIONS_COMPLETED_ORDERS, '1' ) === '1' ) {
			$status .= ", 'wc-completed'";
		}
		
		$since = \get_option( \JtlConnectorAdmin::OPTIONS_PULL_ORDERS_SINCE );
		$where = ( ! empty( $since ) && strtotime( $since ) !== false ) ? "AND p.post_date > '{$since}'" : '';
		
		return "
            SELECT {$select}
            FROM {$wpdb->posts} p
            LEFT JOIN {$jclo} l
            ON p.ID = l.endpoint_id
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ({$status})
            AND l.host_id IS NULL {$where}
            ORDER BY p.post_date DESC
            {$limitQuery}";
	}
}