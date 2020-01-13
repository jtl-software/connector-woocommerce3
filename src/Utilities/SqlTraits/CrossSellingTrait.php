<?php
/**
 * Created by PhpStorm.
 * User: Jan Weskamp <jan.weskamp@jtl-software.com>
 * Date: 07.11.2018
 * Time: 09:41
 */

namespace JtlWooCommerceConnector\Utilities\SqlTraits;


trait CrossSellingTrait {
	public static function crossSellingPull( $limit = null ) {
		global $wpdb;
		$jclc       = $wpdb->prefix . 'jtl_connector_link_crossselling';
		$limitQuery = is_null( $limit ) ? '' : 'LIMIT ' . $limit;
		
		return "
            SELECT pm.post_id, pm.meta_value, pm.meta_key
            FROM `{$wpdb->posts}` p
            LEFT JOIN `{$wpdb->postmeta}` pm ON p.ID = pm.post_id
            LEFT JOIN {$jclc} l ON p.ID = l.endpoint_id
            WHERE p.post_type = 'product' 
            AND (pm.meta_key = '_crosssell_ids' OR pm.meta_key = '_upsell_ids') 
            AND l.host_id IS NULL
            {$limitQuery}";
	}
}