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
    public static function checksumRead($endpointId, $type)
    {
        global $wpdb;
        
        return sprintf('SELECT checksum
                FROM %s%s
                WHERE product_id = %s
                AND type = %s;',
            $wpdb->prefix,
            'jtl_connector_product_checksum',
            $endpointId,
            $type
        );
    }
    
    public static function checksumWrite($endpointId, $type, $checksum)
    {
        global $wpdb;
        
        return sprintf("INSERT IGNORE INTO %s%s VALUES(%s,%s,'%s')",
            $wpdb->prefix,
            'jtl_connector_product_checksum',
            $endpointId,
            $type,
            $checksum
        );
    }
    
    public static function checksumDelete($endpointId, $type)
    {
        global $wpdb;
        $jcpc = $wpdb->prefix . 'jtl_connector_product_checksum';
        
        return sprintf("DELETE FROM %s
                WHERE `product_id` = %s
                AND `type` = %s",
            $jcpc,
            $endpointId,
            $type
        );
    }
}