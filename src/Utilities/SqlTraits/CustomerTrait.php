<?php
/**
 * Created by PhpStorm.
 * User: Jan Weskamp <jan.weskamp@jtl-software.com>
 * Date: 07.11.2018
 * Time: 09:41
 */

namespace JtlWooCommerceConnector\Utilities\SqlTraits;

use JtlWooCommerceConnector\Utilities\Id;
use JtlWooCommerceConnector\Utilities\Util;

trait CustomerTrait {
	public static function customerNotLinked( $limit ) {
		global $wpdb;
		$jclc = $wpdb->prefix . 'jtl_connector_link_customer';
		
		if ( is_null( $limit ) ) {
			$select     = 'COUNT(DISTINCT(pm.meta_value))';
			$limitQuery = '';
		} else {
			$select     = 'DISTINCT(pm.meta_value)';
			$limitQuery = 'LIMIT ' . $limit;
		}
		
		$status = "'wc-pending', 'wc-processing', 'wc-on-hold'";
		
		if ( Util::includeCompletedOrders() ) {
			$status .= ", 'wc-completed'";
		}
		
		return "
            SELECT {$select}
            FROM `{$wpdb->postmeta}` pm
            LEFT JOIN `{$wpdb->posts}` p
            ON p.ID = pm.post_id
            LEFT JOIN {$jclc} l
            ON l.endpoint_id = pm.meta_value * 1 AND l.is_guest = 0
            WHERE l.host_id IS NULL
            AND p.post_status IN ({$status})
            AND pm.meta_key = '_customer_user'
            AND pm.meta_value != 0
            {$limitQuery}";
	}
	
	public static function guestNotLinked( $limit ) {
		global $wpdb;
		$jclc = $wpdb->prefix . 'jtl_connector_link_customer';
		
		$guestPrefix = Id::GUEST_PREFIX . Id::SEPARATOR;
		
		if ( is_null( $limit ) ) {
			$select     = 'COUNT(p.ID)';
			$limitQuery = '';
		} else {
			$select     = "DISTINCT(CONCAT('{$guestPrefix}', p.ID)) as id";
			$limitQuery = 'LIMIT ' . $limit;
		}
		
		$status = "'wc-pending', 'wc-processing', 'wc-on-hold'";
		
		if ( Util::includeCompletedOrders() ) {
			$status .= ", 'wc-completed'";
		}
		
		return "
            SELECT {$select}
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm
            ON p.ID = pm.post_id
            LEFT JOIN {$jclc} l
            ON l.endpoint_id = CONCAT('{$guestPrefix}', p.ID) AND l.is_guest = 1
            WHERE l.host_id IS NULL
            AND p.post_status IN ({$status})
            AND pm.meta_key = '_customer_user'
            AND pm.meta_value = 0
            {$limitQuery}";
	}
}