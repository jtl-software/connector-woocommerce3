<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Utilities\SqlTraits;

trait PrimaryKeyMappingTrait
{
    /**
     * @param string $endpointId
     * @param int    $type
     * @return string
     */
    public static function primaryKeyMappingHostImage(string $endpointId, int $type): string
    {
        global $wpdb;
        $jcli = $wpdb->prefix . 'jtl_connector_link_image';

        return "SELECT host_id
                FROM {$jcli}
                WHERE endpoint_id = '{$wpdb->_escape($endpointId)}' AND `type` = {$type}";
    }

    /**
     * @param string $endpointId
     * @param int    $isGuest
     * @return string
     */
    public static function primaryKeyMappingHostCustomer(string $endpointId, int $isGuest): string
    {
        global $wpdb;
        $jclc = $wpdb->prefix . 'jtl_connector_link_customer';

        return "SELECT `host_id`
                FROM {$jclc}
                WHERE `endpoint_id` = '{$wpdb->_escape($endpointId)}' AND `is_guest` = {$isGuest}";
    }

    /**
     * @param string      $endpointId
     * @param string|null $tableName
     * @return string
     */
    public static function primaryKeyMappingHostString(string $endpointId, ?string $tableName): string
    {
        global $wpdb;
        $jcl = $wpdb->prefix . $wpdb->_escape($tableName);

        return "SELECT host_id
                FROM {$jcl}
                WHERE endpoint_id = '{$wpdb->_escape($endpointId)}'";
    }

    /**
     * @param string      $endpointId
     * @param string|null $tableName
     * @return string
     */
    public static function primaryKeyMappingHostInteger(string $endpointId, ?string $tableName): string
    {
        global $wpdb;
        $jcl = $wpdb->prefix . $wpdb->_escape($tableName);

        return "SELECT host_id
                FROM {$jcl}
                WHERE endpoint_id = {$wpdb->_escape($endpointId)}";
    }

    /**
     * @param int    $hostId
     * @param string $tableName
     * @param string $clause
     * @return string
     */
    public static function primaryKeyMappingEndpoint(int $hostId, string $tableName, string $clause): string
    {
        global $wpdb;
        $jcl = $wpdb->prefix . $wpdb->_escape($tableName);

        return "SELECT endpoint_id
                FROM {$jcl}
                WHERE host_id = {$hostId} {$wpdb->_escape($clause)}";
    }

    /**
     * @param string $endpointId
     * @param int    $hostId
     * @param int    $type
     * @return string
     */
    public static function primaryKeyMappingSaveImage(string $endpointId, int $hostId, int $type): string
    {
        global $wpdb;
        $jcli = $wpdb->prefix . 'jtl_connector_link_image';

        return "INSERT INTO {$jcli} (endpoint_id, host_id, `type`)
                VALUES ('{$wpdb->_escape($endpointId)}', {$hostId}, {$type})";
    }

    /**
     * @param string $endpointId
     * @param int    $hostId
     * @param int    $isGuest
     * @return string
     */
    public static function primaryKeyMappingSaveCustomer(string $endpointId, int $hostId, int $isGuest): string
    {
        global $wpdb;
        $jclc = $wpdb->prefix . 'jtl_connector_link_customer';

        return "INSERT INTO {$jclc} (endpoint_id, host_id, is_guest)
                VALUES ('{$wpdb->_escape($endpointId)}', {$hostId}, {$isGuest})";
    }

    /**
     * @param string $endpointId
     * @param int    $hostId
     * @param string $tableName
     * @return string
     */
    public static function primaryKeyMappingSaveInteger(string $endpointId, int $hostId, string $tableName): string
    {
        global $wpdb;
        $jcl = $wpdb->prefix . $wpdb->_escape($tableName);

        return "INSERT INTO {$jcl} (endpoint_id, host_id)
                VALUES ({$wpdb->_escape($endpointId)}, {$hostId})";
    }

    /**
     * @param string $endpointId
     * @param int    $hostId
     * @param string $tableName
     * @return string
     */
    public static function primaryKeyMappingSaveString(string $endpointId, int $hostId, string $tableName): string
    {
        global $wpdb;
        $jcl = $wpdb->prefix . $wpdb->_escape($tableName);

        return "INSERT INTO {$jcl} (endpoint_id, host_id)
                VALUES ('{$wpdb->_escape($endpointId)}', {$hostId})";
    }

    /**
     * @param string $where
     * @param string $tableName
     * @return string
     */
    public function primaryKeyMappingDelete(string $where, string $tableName): string
    {
        global $wpdb;
        $jcl = $wpdb->prefix . $wpdb->_escape($tableName);

        return "DELETE FROM {$jcl} {$where}";
    }

    /**
     * @return string[]
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
