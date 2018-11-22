<?php
/**
 * Created by PhpStorm.
 * User: Jan Weskamp <jan.weskamp@jtl-software.com>
 * Date: 07.11.2018
 * Time: 09:39
 */

namespace JtlWooCommerceConnector\Utilities\SqlTraits;

use JtlWooCommerceConnector\Utilities\Category as CategoryUtil;

trait CategoryTrait {
	public static function categoryTreeGet( $where ) {
		global $wpdb;
		
		list( $table, $column ) = CategoryUtil::getTermMetaData();
		
		return sprintf( "
            SELECT tt.term_id, tt.parent, IF(tm.meta_key IS NULL, 0, tm.meta_value) as sort
            FROM `{$wpdb->term_taxonomy}` tt
            LEFT JOIN `{$wpdb->terms}` t ON tt.term_id = t.term_id
            LEFT JOIN `{$table}` tm ON tm.{$column} = tt.term_id AND tm.meta_key = 'order'
            WHERE tt.taxonomy = '%s' {$where}
            ORDER BY tt.parent ASC, sort ASC, t.name ASC",
			CategoryUtil::TERM_TAXONOMY
		);
	}
	
	public static function categoryTreeAddIgnore( $categoryId, $level, $sort ) {
		global $wpdb;
		
		return sprintf(
			"INSERT IGNORE INTO `%s%s` VALUES ({$categoryId}, {$level}, {$sort})",
			$wpdb->prefix,
			CategoryUtil::LEVEL_TABLE
		);
	}
	
	public static function categoryTreeAdd( $categoryId, $level, $sort ) {
		global $wpdb;
		
		return sprintf(
			"INSERT INTO `%s%s` VALUES ({$categoryId}, {$level}, {$sort})",
			$wpdb->prefix,
			CategoryUtil::LEVEL_TABLE
		);
	}
	
	public static function categoryTreeUpdate( $categoryId, $level, $sort ) {
		global $wpdb;
		
		return sprintf(
			"UPDATE `%s%s` SET `level` = {$level}, `sort` = {$sort} WHERE `category_id` = {$categoryId}",
			$wpdb->prefix,
			CategoryUtil::LEVEL_TABLE
		);
	}
	
	public static function categoryTreePreOrderRoot() {
		global $wpdb;
		
		return sprintf( "
            SELECT ccl.category_id, ccl.level
            FROM `%s%s` ccl
            LEFT JOIN {$wpdb->terms} t ON t.term_id = ccl.category_id
            WHERE ccl.level = 0
            ORDER BY ccl.sort, t.slug",
			$wpdb->prefix,
			CategoryUtil::LEVEL_TABLE
		);
	}
	
	public static function categoryTreePreOrder( $categoryId, $level ) {
		global $wpdb;
		
		return sprintf( "
            SELECT ccl.category_id, ccl.level
            FROM `%s%s` ccl
            LEFT JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = ccl.category_id
            LEFT JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
            WHERE tt.parent = {$categoryId} AND ccl.level = {$level}
            ORDER BY ccl.sort, t.slug",
			$wpdb->prefix,
			CategoryUtil::LEVEL_TABLE
		);
	}
	
	public static function categoryPull( $limit ) {
		global $wpdb;
		$jclc = $wpdb->prefix . 'jtl_connector_link_category';
		
		return sprintf( "
            SELECT tt.parent, tt.description, cl.*, t.name, t.slug, tt.count
            FROM `{$wpdb->terms}` t
            LEFT JOIN `{$wpdb->term_taxonomy}` tt ON t.term_id = tt.term_id
            LEFT JOIN `%s` cl ON tt.term_id = cl.category_id
            LEFT JOIN `%s` l ON t.term_id = l.endpoint_id
            WHERE tt.taxonomy = '%s' AND l.host_id IS NULL
            ORDER BY cl.level ASC, tt.parent ASC, cl.sort ASC
            LIMIT {$limit}",
			$wpdb->prefix . CategoryUtil::LEVEL_TABLE,
			$jclc,
			CategoryUtil::TERM_TAXONOMY
		);
	}
	
	public static function categoryStats() {
		global $wpdb;
		$jclc = $wpdb->prefix . 'jtl_connector_link_category';
		
		return sprintf( "
            SELECT COUNT(tt.term_id)
            FROM `{$wpdb->term_taxonomy}` tt
            LEFT JOIN `%s` l ON tt.term_id = l.endpoint_id
            WHERE tt.taxonomy = '%s' AND l.host_id IS NULL",
			$jclc,
			CategoryUtil::TERM_TAXONOMY
		);
	}
}