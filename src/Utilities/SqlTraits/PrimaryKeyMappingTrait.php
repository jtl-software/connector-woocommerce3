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
    public function primaryKeyMappingHostImage($endpointId, $type)
    {
        $wpdb = $this->getDb()->getWpDb();
        $jcli = $wpdb->prefix . 'jtl_connector_link_image';

        return "SELECT host_id
                FROM {$jcli}
                WHERE endpoint_id = '{$endpointId}' AND `type` = {$type}";
    }

    public function primaryKeyMappingHostCustomer($endpointId, $isGuest)
    {
        $wpdb = $this->getDb()->getWpDb();
        $jclc = $wpdb->prefix . 'jtl_connector_link_customer';

        return "SELECT `host_id`
                FROM {$jclc}
                WHERE `endpoint_id` = '{$endpointId}' AND `is_guest` = {$isGuest}";
    }

    public function primaryKeyMappingHostString($endpointId, $tableName)
    {
        $wpdb = $this->getDb()->getWpDb();
        $jcl = $wpdb->prefix . $tableName;

        return "SELECT host_id
                FROM {$jcl}
                WHERE endpoint_id = '{$endpointId}'";
    }

    public function primaryKeyMappingHostInteger($endpointId, $tableName)
    {
        $wpdb = $this->getDb()->getWpDb();
        $jcl = $wpdb->prefix . $tableName;

        return "SELECT host_id
                FROM {$jcl}
                WHERE endpoint_id = {$endpointId}";
    }

    public function primaryKeyMappingEndpoint($hostId, $tableName, $clause)
    {
        $wpdb = $this->getDb()->getWpDb();
        $jcl = $wpdb->prefix . $tableName;

        return "SELECT endpoint_id
                FROM {$jcl}
                WHERE host_id = {$hostId} {$clause}";
    }

    public function primaryKeyMappingSaveImage($endpointId, $hostId, $type)
    {
        $wpdb = $this->getDb()->getWpDb();
        $jcli = $wpdb->prefix . 'jtl_connector_link_image';

        return "INSERT INTO {$jcli} (endpoint_id, host_id, `type`)
                VALUES ('{$endpointId}', {$hostId}, {$type})";
    }

    public function primaryKeyMappingSaveCustomer($endpointId, $hostId, $isGuest)
    {
        $wpdb = $this->getDb()->getWpDb();
        $jclc = $wpdb->prefix . 'jtl_connector_link_customer';

        return "INSERT INTO {$jclc} (endpoint_id, host_id, is_guest)
                VALUES ('{$endpointId}', {$hostId}, {$isGuest})";
    }

    public function primaryKeyMappingSaveInteger($endpointId, $hostId, $tableName)
    {
        $wpdb = $this->getDb()->getWpDb();
        $jcl = $wpdb->prefix . $tableName;

        return "INSERT INTO {$jcl} (endpoint_id, host_id)
                VALUES ({$endpointId}, {$hostId})";
    }

    public function primaryKeyMappingSaveString($endpointId, $hostId, $tableName)
    {
        $wpdb = $this->getDb()->getWpDb();
        $jcl = $wpdb->prefix . $tableName;

        return "INSERT INTO {$jcl} (endpoint_id, host_id)
                VALUES ('{$endpointId}', {$hostId})";
    }

    public function primaryKeyMappingDelete($where, $tableName)
    {
        $wpdb = $this->getDb()->getWpDb();
        $jcl = $wpdb->prefix . $tableName;

        return "DELETE FROM {$jcl} {$where}";
    }

    public function primaryKeyMappingClear()
    {
        $wpdb = $this->getDb()->getWpDb();
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
        $arr = [];

        foreach ($tables as $table) {
            $arr[] = sprintf('DELETE FROM %s', $wpdb->prefix . $table);
        }

        return $arr;
    }
}