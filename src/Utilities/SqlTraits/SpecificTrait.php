<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Utilities\SqlTraits;

trait SpecificTrait
{
    /**
     * @param int $limit
     * @return string
     */
    public static function specificPull(int $limit): string
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

    /**
     * @param string $specificName
     * @return string
     */
    public static function specificValuePull(string $specificName): string
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
                AND tt.taxonomy LIKE '{$wpdb->_escape($specificName)}'
                ORDER BY tt.parent ASC;";
    }

    /**
     * @param string $specificName
     * @return string
     */
    public static function forceSpecificValuePull(string $specificName): string
    {
        global $wpdb;
        $jclsv = $wpdb->prefix . 'jtl_connector_link_specific_value';

        return "SELECT t.term_id, t.name, tt.term_taxonomy_id, tt.taxonomy, t.slug
                FROM {$wpdb->terms} t
                  LEFT JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                  LEFT JOIN {$jclsv} lsv ON t.term_id = lsv.endpoint_id
                WHERE tt.taxonomy LIKE '{$wpdb->_escape($specificName)}'
                ORDER BY tt.parent ASC;";
    }

    /**
     * @return string
     */
    public static function specificStats(): string
    {
        global $wpdb;
        $wat  = $wpdb->prefix . 'woocommerce_attribute_taxonomies';
        $jcls = $wpdb->prefix . 'jtl_connector_link_specific';

        return \sprintf("
            SELECT COUNT(at.attribute_id)
            FROM {$wat} at
            LEFT JOIN {$jcls} l ON at.attribute_id = l.endpoint_id
            WHERE l.host_id IS NULL;");
    }

    /**
     * @param string $specificName
     * @param string $specificValueName
     * @return string
     */
    public static function getSpecificValueId(string $specificName, string $specificValueName): string
    {
        global $wpdb;
        $jclsv = $wpdb->prefix . 'jtl_connector_link_specific_value';

        return "SELECT  lsv.host_id , lsv.endpoint_id, t.term_id, t.name, tt.term_taxonomy_id, tt.taxonomy, t.slug
                FROM {$wpdb->terms} t
                  LEFT JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                  LEFT JOIN {$jclsv} lsv ON tt.term_taxonomy_id = lsv.endpoint_id
                WHERE tt.taxonomy LIKE '{$wpdb->_escape($specificName)}'
                  AND t.name = '{$wpdb->_escape($specificValueName)}';
        ";
    }

    /**
     * @param string $specificName
     * @param string $specificValueName
     * @return string
     */
    public static function getSpecificValueIdBySlug(string $specificName, string $specificValueName): string
    {
        global $wpdb;
        $jclsv = $wpdb->prefix . 'jtl_connector_link_specific_value';

        return "SELECT  lsv.host_id , lsv.endpoint_id, t.term_id, t.name, tt.term_taxonomy_id, tt.taxonomy, t.slug
                FROM {$wpdb->terms} t
                  LEFT JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                  LEFT JOIN {$jclsv} lsv ON tt.term_taxonomy_id = lsv.endpoint_id
                WHERE tt.taxonomy LIKE '{$wpdb->_escape($specificName)}'
                  AND t.slug = '{$wpdb->_escape($specificValueName)}';
        ";
    }

    /**
     * @param string $specificValue
     * @return string
     */
    public static function getWpmlRegisteredStringId(string $specificValue): string
    {
        global $wpdb;

        $wis = $wpdb->prefix . 'icl_strings';

        return "SELECT wis.id
                FROM {$wis} wis
                WHERE wis.value = '{$wpdb->_escape($specificValue)}'
        ";
    }

    /**
     * @param string $stringId
     * @return string
     */
    public static function getWpmlTranslatedSpecificValue(string $stringId): string
    {
        global $wpdb;

        $wist = $wpdb->prefix . 'icl_string_translations';

        return "SELECT wist.value
                FROM {$wist} wist
                WHERE wist.string_id = '{$wpdb->_escape($stringId)}'
        ";
    }

    /**
     * @param string $specificName
     * @return string
     */
    public static function getSpecificId(string $specificName): string
    {
        global $wpdb;

        $wat  = $wpdb->prefix . 'woocommerce_attribute_taxonomies';
        $jcls = $wpdb->prefix . 'jtl_connector_link_specific';

        return "SELECT wat.attribute_id
                  FROM {$wat} wat
                  LEFT JOIN {$jcls} l ON wat.attribute_id = l.endpoint_id
                WHERE wat.attribute_name LIKE '{$wpdb->_escape($specificName)}';
        ";
    }

    /**
     * @param int $id
     * @return string
     */
    public static function removeSpecificLinking(int $id): string
    {
        global $wpdb;

        $jcls = $wpdb->prefix . 'jtl_connector_link_specific';

        return "DELETE FROM {$jcls} WHERE endpoint_id = '{$id}';";
    }

    /**
     * @param int $id
     * @return string
     */
    public static function removeSpecificValueLinking(int $id): string
    {
        global $wpdb;

        $jcls = $wpdb->prefix . 'jtl_connector_link_specific_value';

        return "DELETE FROM {$jcls} WHERE endpoint_id = '{$id}';";
    }
}

/*
    SELECT t.term_id, t.name, tt.taxonomy, t.slug
    FROM wp_terms t
    LEFT JOIN wp_term_taxonomy tt ON t.term_id = tt.term_id
    LEFT JOIN jtl_connector_link_specific l ON t.term_id = l.endpoint_id
    WHERE l.host_id IS NULL AND tt.taxonomy LIKE 'pa_groesse'
    ORDER BY tt.parent ASC;
*/
