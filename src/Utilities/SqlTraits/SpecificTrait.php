<?php
/**
 * Created by PhpStorm.
 * User: Jan Weskamp <jan.weskamp@jtl-software.com>
 * Date: 07.11.2018
 * Time: 10:55
 */

namespace JtlWooCommerceConnector\Utilities\SqlTraits;


trait SpecificTrait
{
    public static function specificPull($limit)
    {
        global $wpdb;
        $wat  = $wpdb->prefix . 'woocommerce_attribute_taxonomies';
        $jcls = $wpdb->prefix . 'jtl_connector_link_specific';
        
        return "SELECT wat.attribute_id, wat.attribute_name, wat.attribute_label, wat.attribute_type
                FROM {$wat} wat
                LEFT JOIN {$jcls} l ON wat.attribute_id = l.endpoint_id
                WHERE l.host_id IS NULL
                LIMIT {$limit};";
    }
    
    public static function specificValuePull($specificName)
    {
        global $wpdb;
        $jclsv = $wpdb->prefix . 'jtl_connector_link_specific_value';
        
        return "SELECT t.term_id, t.name, tt.term_taxonomy_id, tt.taxonomy, t.slug, tt.description
                FROM {$wpdb->terms} t
                  LEFT JOIN {$wpdb->term_taxonomy} tt
                  ON t.term_id = tt.term_id
                  LEFT JOIN {$jclsv} lsv
                  ON t.term_id = lsv.endpoint_id
                WHERE lsv.host_id IS NULL
                AND tt.taxonomy LIKE '{$specificName}'
                ORDER BY tt.parent ASC;";
    }
    
    public static function forceSpecificValuePull($specificName)
    {
        global $wpdb;
        $jclsv = $wpdb->prefix . 'jtl_connector_link_specific_value';
        
        return "SELECT t.term_id, t.name, tt.term_taxonomy_id, tt.taxonomy, t.slug
                FROM {$wpdb->terms} t
                  LEFT JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                  LEFT JOIN {$jclsv} lsv ON t.term_id = lsv.endpoint_id
                WHERE tt.taxonomy LIKE '{$specificName}'
                ORDER BY tt.parent ASC;";
    }
    
    public static function specificStats()
    {
        global $wpdb;
        $wat  = $wpdb->prefix . 'woocommerce_attribute_taxonomies';
        $jcls = $wpdb->prefix . 'jtl_connector_link_specific';
        
        return sprintf("
            SELECT COUNT(at.attribute_id)
            FROM {$wat} at
            LEFT JOIN {$jcls} l ON at.attribute_id = l.endpoint_id
            WHERE l.host_id IS NULL;"
        );
    }
    
    public static function getSpecificValueId($specificName, $specificValueName)
    {
        global $wpdb;
        $jclsv = $wpdb->prefix . 'jtl_connector_link_specific_value';
        
        return "SELECT  lsv.host_id , lsv.endpoint_id, t.term_id, t.name, tt.term_taxonomy_id, tt.taxonomy, t.slug
                FROM {$wpdb->terms} t
                  LEFT JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                  LEFT JOIN {$jclsv} lsv ON tt.term_taxonomy_id = lsv.endpoint_id
                WHERE tt.taxonomy LIKE '{$specificName}' AND t.name = '{$specificValueName}';
        ";
    }
    
    public static function getSpecificValueIdBySlug($specificName, $specificValueName)
    {
        global $wpdb;
        $jclsv = $wpdb->prefix . 'jtl_connector_link_specific_value';
        
        return "SELECT  lsv.host_id , lsv.endpoint_id, t.term_id, t.name, tt.term_taxonomy_id, tt.taxonomy, t.slug
                FROM {$wpdb->terms} t
                  LEFT JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                  LEFT JOIN {$jclsv} lsv ON tt.term_taxonomy_id = lsv.endpoint_id
                WHERE tt.taxonomy LIKE '{$specificName}' AND t.slug = '{$specificValueName}';
        ";
    }
    
    public static function getSpecificId($specificName)
    {
        global $wpdb;
        
        $wat  = $wpdb->prefix . 'woocommerce_attribute_taxonomies';
        $jcls = $wpdb->prefix . 'jtl_connector_link_specific';
        
        return "SELECT wat.attribute_id
                  FROM {$wat} wat
                  LEFT JOIN {$jcls} l ON wat.attribute_id = l.endpoint_id
                WHERE wat.attribute_name LIKE '{$specificName}';
        ";
    }
    
    public static function removeSpecificLinking($id)
    {
        global $wpdb;
        
        $jcls = $wpdb->prefix . 'jtl_connector_link_specific';
        
        return "DELETE FROM {$jcls} WHERE endpoint_id = '{$id}';";
    }
    
    public static function removeSpecificValueLinking($id)
    {
        global $wpdb;
        
        $jcls = $wpdb->prefix . 'jtl_connector_link_specific_value';
        
        return "DELETE FROM {$jcls} WHERE endpoint_id = '{$id}';";
    }
    /*SELECT t.term_id, t.name, tt.taxonomy, t.slug
    FROM wp_terms t
    LEFT JOIN wp_term_taxonomy tt ON t.term_id = tt.term_id
    LEFT JOIN jtl_connector_link_specific l ON t.term_id = l.endpoint_id
    WHERE l.host_id IS NULL AND tt.taxonomy LIKE 'pa_groesse'
    ORDER BY tt.parent ASC;*/
}