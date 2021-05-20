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

        $status = Util::getOrderStatusesToImport();

        return sprintf("
            SELECT %s
            FROM `%s` pm
            LEFT JOIN `%s` p ON p.ID = pm.post_id
            LEFT JOIN %s l ON l.endpoint_id = pm.meta_value * 1 AND l.is_guest = 0
            WHERE l.host_id IS NULL
            AND p.post_status IN ('%s')
            AND pm.meta_key = '_customer_user'
            AND pm.meta_value != 0 
            %s", $select, $wpdb->postmeta, $wpdb->postmeta, $jclc, join("','", $status), $limitQuery);
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

        $status = Util::getOrderStatusesToImport();
		
		return sprintf("
            SELECT %s
            FROM %s p
            LEFT JOIN %s pm
            ON p.ID = pm.post_id
            LEFT JOIN %s l
            ON l.endpoint_id = CONCAT('%s', p.ID) AND l.is_guest = 1
            WHERE l.host_id IS NULL
            AND p.post_status IN ('%s')
            AND pm.meta_key = '_customer_user'
            AND pm.meta_value = 0 
            %s",$select, $wpdb->posts, $wpdb->postmeta, $jclc, $guestPrefix, join("','",$status,$limitQuery));
	}
}