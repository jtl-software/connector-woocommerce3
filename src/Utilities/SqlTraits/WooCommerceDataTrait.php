<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Utilities\SqlTraits;

use JtlWooCommerceConnector\Utilities\Category as CategoryUtil;

trait WooCommerceDataTrait
{
    /**
     * @param int $productId
     * @param int $termTaxonomyId
     * @return string
     */
    public static function findTermTaxonomyRelation(int $productId, int $termTaxonomyId): string
    {
        global $wpdb;

        return "
            SELECT term_taxonomy_id
            FROM {$wpdb->term_relationships}
            WHERE object_id = {$productId} AND term_taxonomy_id = $termTaxonomyId";
    }

    /**
     * @param int    $productId
     * @param string $taxonomy
     * @return string
     */
    public static function findTermsForProduct(int $productId, string $taxonomy): string
    {
        global $wpdb;

        return "
            SELECT tt.term_taxonomy_id, tt.term_id
            FROM {$wpdb->term_taxonomy} tt LEFT JOIN {$wpdb->term_relationships} tr 
                ON tr.term_taxonomy_id = tt.term_taxonomy_id
            WHERE  tr.object_id = {$productId} AND tt.taxonomy = '{$wpdb->_escape($taxonomy)}'";
    }

    /**
     * @param int $offset
     * @param int $limit
     * @return string
     */
    public static function categoryProductsCount(int $offset, int $limit): string
    {
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

    /**
     * @param int $termTaxonomyId
     * @param int $count
     * @return string
     */
    public static function termTaxonomyCountUpdate(int $termTaxonomyId, int $count): string
    {
        global $wpdb;

        return "UPDATE {$wpdb->term_taxonomy} SET count = {$count} WHERE term_taxonomy_id = {$termTaxonomyId}";
    }

    /**
     * @param int $termId
     * @param int $count
     * @return string
     */
    public static function categoryMetaCountUpdate(int $termId, int $count): string
    {
        list( $table, $column ) = CategoryUtil::getTermMetaData();

        return "
            UPDATE {$table}
            SET meta_value = {$count} WHERE {$column} = {$termId}
            AND meta_key = 'product_count_product_cat'";
    }

    /**
     * @param int $offset
     * @param int $limit
     * @return string
     */
    public static function productTagsCount(int $offset, int $limit): string
    {
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
