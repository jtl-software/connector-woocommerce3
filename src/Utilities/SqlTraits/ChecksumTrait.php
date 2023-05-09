<?php

/**
 * Created by PhpStorm.
 * User: Jan Weskamp <jan.weskamp@jtl-software.com>
 * Date: 07.11.2018
 * Time: 09:17
 */

namespace JtlWooCommerceConnector\Utilities\SqlTraits;

trait ChecksumTrait
{
    /**
     * @param $endpointId
     * @param $type
     * @return string
     */
    public static function checksumRead($endpointId, $type): string
    {
        global $wpdb;

        return \sprintf(
            'SELECT checksum
                FROM %s%s
                WHERE product_id = %s
                AND type = %s;',
            $wpdb->prefix,
            'jtl_connector_product_checksum',
            $endpointId,
            $type
        );
    }

    /**
     * @param $endpointId
     * @param $type
     * @param $checksum
     * @return string
     */
    public static function checksumWrite($endpointId, $type, $checksum): string
    {
        global $wpdb;

        return \sprintf(
            "INSERT IGNORE INTO %s%s VALUES(%s,%s,'%s')",
            $wpdb->prefix,
            'jtl_connector_product_checksum',
            $endpointId,
            $type,
            $checksum
        );
    }

    /**
     * @param $endpointId
     * @param $type
     * @return string
     */
    public static function checksumDelete($endpointId, $type): string
    {
        global $wpdb;
        $jcpc = $wpdb->prefix . 'jtl_connector_product_checksum';

        return \sprintf(
            "DELETE FROM %s
                WHERE `product_id` = %s
                AND `type` = %s",
            $jcpc,
            $endpointId,
            $type
        );
    }
}
