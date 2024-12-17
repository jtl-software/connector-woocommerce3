<?php

/**
 * Created by PhpStorm.
 * User: Jan Weskamp <jan.weskamp@jtl-software.com>
 * Date: 07.11.2018
 * Time: 10:55
 */

declare(strict_types=1);

namespace JtlWooCommerceConnector\Utilities\SqlTraits;

trait ManufacturerTrait
{
/*
    Public static function specificPull($limit)
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

            return "SELECT t.term_id, t.name, tt.term_taxonomy_id, tt.taxonomy, t.slug
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
*/

    /**
     * @return string
     */
    public static function manufacturerStats(): string
    {
        global $wpdb;
        $jclm = $wpdb->prefix . 'jtl_connector_link_manufacturer';

        return \sprintf(
            "
            SELECT COUNT(tt.term_id)
            FROM `{$wpdb->term_taxonomy}` tt
            LEFT JOIN `%s` l ON tt.term_id = l.endpoint_id
            WHERE tt.taxonomy = '%s' AND l.host_id IS NULL",
            $jclm,
            'pwb-brand'
        );
    }

    /**
     * @param int $limit
     * @return string
     */
    public static function manufacturerPull(int $limit): string
    {
        global $wpdb;
        $jclm = $wpdb->prefix . 'jtl_connector_link_manufacturer';

        return \sprintf(
            "
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
        $table    = \sprintf('%stermmeta', $wpdb->prefix);
        $metaKeys = [
            'rank_math_title',
            'rank_math_description',
            'rank_math_focus_keyword'
        ];
        return \sprintf(
            'SELECT meta_key,meta_value FROM %s WHERE term_id = %s AND meta_key IN ("%s")',
            $table,
            $termId,
            \join('","', $metaKeys)
        );
    }
}

/*
    Public static function getSpecificValueId($specificName, $specificValueName)
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
*/
