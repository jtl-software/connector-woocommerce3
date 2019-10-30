<?php
/**
 * Created by PhpStorm.
 * User: Jan Weskamp <jan.weskamp@jtl-software.com>
 * Date: 07.11.2018
 * Time: 09:41
 */

namespace JtlWooCommerceConnector\Utilities\SqlTraits;

trait CustomerGroupTrait
{
    public static function customerGroupPull()
    {
        global $wpdb;
        $jclcg = $wpdb->prefix . 'jtl_connector_link_customer_group';
        
        return "
            SELECT p.ID, p.post_title, p.post_name
             FROM `{$wpdb->posts}` p
            LEFT JOIN {$jclcg} l
            ON l.endpoint_id = 'p.ID'
            WHERE l.host_id IS NULL
            AND p.post_type = 'customer_groups'
        ";
    }
}