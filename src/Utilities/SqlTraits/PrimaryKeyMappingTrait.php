<?php

/**
 * Created by PhpStorm.
 * User: Jan Weskamp <jan.weskamp@jtl-software.com>
 * Date: 07.11.2018
 * Time: 10:54
 */

namespace JtlWooCommerceConnector\Utilities\SqlTraits;

trait PrimaryKeyMappingTrait
{
    /**
     * @param $endpointId
     * @param $type
     * @return string
     */
    public static function primaryKeyMappingHostImage($endpointId, $type): string
    {
        global $wpdb;
        $jcli = $wpdb->prefix . 'jtl_connector_link_image';

        return "SELECT host_id
                FROM {$jcli}
                WHERE endpoint_id = '{$wpdb->_escape($endpointId)}' AND `type` = {$type}";
    }

    /**
     * @param $endpointId
     * @param $isGuest
     * @return string
     */
    public static function primaryKeyMappingHostCustomer($endpointId, $isGuest): string
    {
        global $wpdb;
        $jclc = $wpdb->prefix . 'jtl_connector_link_customer';

        return "SELECT `host_id`
                FROM {$jclc}
                WHERE `endpoint_id` = '{$wpdb->_escape($endpointId)}' AND `is_guest` = {$isGuest}";
    }

    /**
     * @param $endpointId
     * @param $tableName
     * @return string
     */
    public static function primaryKeyMappingHostString($endpointId, $tableName): string
    {
        global $wpdb;
        $jcl = $wpdb->prefix . $wpdb->_escape($tableName);

        return "SELECT host_id
                FROM {$jcl}
                WHERE endpoint_id = '{$wpdb->_escape($endpointId)}'";
    }

    /**
     * @param $endpointId
     * @param $tableName
     * @return string
     */
    public static function primaryKeyMappingHostInteger($endpointId, $tableName): string
    {
        global $wpdb;
        $jcl = $wpdb->prefix . $wpdb->_escape($tableName);

        return "SELECT host_id
                FROM {$jcl}
                WHERE endpoint_id = {$wpdb->_escape($endpointId)}";
    }

    /**
     * @param $hostId
     * @param $tableName
     * @param $clause
     * @return string
     */
    public static function primaryKeyMappingEndpoint($hostId, $tableName, $clause): string
    {
        global $wpdb;
        $jcl = $wpdb->prefix . $wpdb->_escape($tableName);

        return "SELECT endpoint_id
                FROM {$jcl}
                WHERE host_id = {$hostId} {$wpdb->_escape($clause)}";
    }

    /**
     * @param $endpointId
     * @param $hostId
     * @param $type
     * @return string
     */
    public static function primaryKeyMappingSaveImage($endpointId, $hostId, $type): string
    {
        global $wpdb;
        $jcli = $wpdb->prefix . 'jtl_connector_link_image';

        return "INSERT INTO {$jcli} (endpoint_id, host_id, `type`)
                VALUES ('{$wpdb->_escape($endpointId)}', {$hostId}, {$type})";
    }

    /**
     * @param $endpointId
     * @param $hostId
     * @param $isGuest
     * @return string
     */
    public static function primaryKeyMappingSaveCustomer($endpointId, $hostId, $isGuest): string
    {
        global $wpdb;
        $jclc = $wpdb->prefix . 'jtl_connector_link_customer';

        return "INSERT INTO {$jclc} (endpoint_id, host_id, is_guest)
                VALUES ('{$wpdb->_escape($endpointId)}', {$hostId}, {$isGuest})";
    }

    /**
     * @param $endpointId
     * @param $hostId
     * @param $tableName
     * @return string
     */
    public static function primaryKeyMappingSaveInteger($endpointId, $hostId, $tableName): string
    {
        global $wpdb;
        $jcl = $wpdb->prefix . $wpdb->_escape($tableName);

        return "INSERT INTO {$jcl} (endpoint_id, host_id)
                VALUES ({$wpdb->_escape($endpointId)}, {$hostId})";
    }

    /**
     * @param $endpointId
     * @param $hostId
     * @param $tableName
     * @return string
     */
    public static function primaryKeyMappingSaveString($endpointId, $hostId, $tableName): string
    {
        global $wpdb;
        $jcl = $wpdb->prefix . $wpdb->_escape($tableName);

        return "INSERT INTO {$jcl} (endpoint_id, host_id)
                VALUES ('{$wpdb->_escape($endpointId)}', {$hostId})";
    }

    /**
     * @param $where
     * @param $tableName
     * @return string
     */
    public function primaryKeyMappingDelete($where, $tableName): string
    {
        global $wpdb;
        $jcl = $wpdb->prefix . $wpdb->_escape($tableName);

        return "DELETE FROM {$jcl} {$where}";
    }

    /**
     * @return array
     */
    public static function primaryKeyMappingClear(): array
    {
        global $wpdb;
        $tables = [
            "jtl_connector_link_category",
            "jtl_connector_link_crossselling",
            "jtl_connector_link_crossselling_group",
            "jtl_connector_link_currency",
            "jtl_connector_link_customer",
            "jtl_connector_link_customer_group",
            "jtl_connector_link_image",
            "jtl_connector_link_language",
            "jtl_connector_link_manufacturer",
            "jtl_connector_link_manufacturer_unit",
            "jtl_connector_link_order",
            "jtl_connector_link_payment",
            "jtl_connector_link_product",
            "jtl_connector_link_shipping_class",
            "jtl_connector_link_shipping_method",
            "jtl_connector_link_specific",
            "jtl_connector_link_specific_value",
        ];
        $arr    = [];

        foreach ($tables as $table) {
            $arr[] = \sprintf('DELETE FROM %s', $wpdb->prefix . $table);
        }

        return $arr;
    }
}
