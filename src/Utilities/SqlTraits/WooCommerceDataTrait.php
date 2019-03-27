<?php
/**
 * Created by PhpStorm.
 * User: Jan Weskamp <jan.weskamp@jtl-software.com>
 * Date: 07.11.2018
 * Time: 10:55
 */

namespace JtlWooCommerceConnector\Utilities\SqlTraits;

use JtlWooCommerceConnector\Utilities\Category as CategoryUtil;

trait WooCommerceDataTrait {
	public static function findTermTaxonomyRelation( $productId, $termTaxonomyId ) {
		global $wpdb;
		
		return "
            SELECT term_taxonomy_id
            FROM {$wpdb->term_relationships}
            WHERE object_id = {$productId} AND term_taxonomy_id = $termTaxonomyId";
	}
	
	public static function findTermsForProduct( $productId, $taxonomy ) {
		global $wpdb;
		
		return "
            SELECT tt.term_taxonomy_id, tt.term_id
            FROM {$wpdb->term_taxonomy} tt LEFT JOIN {$wpdb->term_relationships} tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
            WHERE  tr.object_id = {$productId} AND tt.taxonomy = '{$taxonomy}'";
	}
	
	public static function categoryProductsCount( $offset, $limit ) {
		global $wpdb;
		
		return "
            SELECT tt.term_taxonomy_id, tt.term_id, COUNT(tr.object_id) as count
            FROM {$wpdb->term_relationships} tr
            LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            WHERE tt.taxonomy = 'product_cat'
            GROUP BY tt.term_taxonomy_id
            OFFSET {$offset}
            LIMIT {$limit}";
	}
	
	public static function termTaxonomyCountUpdate( $termTaxonomyId, $count ) {
		global $wpdb;
		
		return "UPDATE {$wpdb->term_taxonomy} SET count = {$count} WHERE term_taxonomy_id = {$termTaxonomyId}";
	}
	
	public static function categoryMetaCountUpdate( $termId, $count ) {
		list( $table, $column ) = CategoryUtil::getTermMetaData();
		
		return "
            UPDATE {$table}
            SET meta_value = {$count} WHERE {$column} = {$termId}
            AND meta_key = 'product_count_product_cat'";
	}
	
	public static function productTagsCount( $offset, $limit ) {
		global $wpdb;
		
		return "
            SELECT tt.term_taxonomy_id, COUNT(tr.object_id) as count
            FROM {$wpdb->term_relationships} tr
            LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            WHERE tt.taxonomy = 'product_tag'
            GROUP BY tt.term_taxonomy_id
            OFFSET {$offset}
            LIMIT {$limit}";
	}
}