<?php
/**
 * Created by PhpStorm.
 * User: Jan Weskamp <jan.weskamp@jtl-software.com>
 * Date: 07.11.2018
 * Time: 10:55
 */

namespace JtlWooCommerceConnector\Utilities\SqlTraits;


trait ProductTrait {
	public static function productPull($limit = null)
	{
		global $wpdb;
		$jclp = $wpdb->prefix . 'jtl_connector_link_product';
		
		$limitQuery = is_null($limit) ? '' : 'LIMIT ' . $limit;
		
		return "
            SELECT p.ID
            FROM {$wpdb->posts} p
            LEFT JOIN {$jclp} l ON p.ID = l.endpoint_id
            LEFT JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            LEFT JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
            LEFT JOIN {$wpdb->terms} t ON t.term_id = tt.term_id AND t.slug IN ('simple', 'variable', 'variation')
            WHERE l.host_id IS NULL
            AND
            (
                p.post_type = 'product' OR
                (
                    p.post_type = 'product_variation' AND p.post_parent IN
                    (
                        SELECT p2.ID FROM {$wpdb->posts} p2
                        WHERE p2.post_type = 'product' AND p2.post_status IN ('future', 'publish', 'inherit', 'private')
                    )
                )
            )
            AND p.post_status IN ('future', 'publish', 'inherit', 'private')
            AND (tt.taxonomy IS NULL OR tt.taxonomy = 'product_type')
            ORDER BY p.post_type
            {$limitQuery}";
	}
	
	public static function productVariationObsoletes($id, $updatedAttributeKeys)
	{
		global $wpdb;
		
		return sprintf("
            SELECT meta_key
            FROM {$wpdb->postmeta}
            WHERE meta_key LIKE 'attribute_%%' AND meta_key NOT IN ('%s') AND post_id = {$id}",
			implode("','", $updatedAttributeKeys)
		);
	}
}