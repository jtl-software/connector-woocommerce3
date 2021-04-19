<?php
/**
 * Created by PhpStorm.
 * User: Jan Weskamp <jan.weskamp@jtl-software.com>
 * Date: 07.11.2018
 * Time: 10:55
 */

namespace JtlWooCommerceConnector\Utilities\SqlTraits;


trait ManufacturerTrait
{
    public static function manufacturerStats()
    {
        global $wpdb;
        $jclm = $wpdb->prefix . 'jtl_connector_link_manufacturer';
        
        return sprintf("
            SELECT COUNT(tt.term_id)
            FROM `{$wpdb->term_taxonomy}` tt
            LEFT JOIN `%s` l ON tt.term_id = l.endpoint_id
            WHERE tt.taxonomy = '%s' AND l.host_id IS NULL",
            $jclm,
            'pwb-brand'
        );
    }
    
    public static function manufacturerPull( $limit ) {
        global $wpdb;
        $jclm = $wpdb->prefix . 'jtl_connector_link_manufacturer';
        
        return sprintf( "
            SELECT t.term_id, tt.parent, tt.description, t.name, t.slug, tt.count
            FROM `{$wpdb->terms}` t
            LEFT JOIN `{$wpdb->term_taxonomy}` tt ON t.term_id = tt.term_id
            LEFT JOIN `%s` l ON t.term_id = l.endpoint_id
            WHERE tt.taxonomy = '%s' AND l.host_id IS NULL
            ORDER BY tt.parent ASC
            LIMIT {$limit}",
            $jclm,
            'pwb-brand'
        );
    }

    /**
     * @param int $termId
     * @return string
     */
    public static function pullRankMathSeoTermData(int $termId): string
    {
        global $wpdb;
        $table = sprintf('%stermmeta', $wpdb->prefix);
        $metaKeys = [
            'rank_math_title',
            'rank_math_description',
            'rank_math_focus_keyword'
        ];
        return sprintf('SELECT meta_key,meta_value FROM %s WHERE term_id = %s AND meta_key IN ("%s")', $table, $termId, join('","', $metaKeys));
    }

}