<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Utilities\SqlTraits;

trait CustomerGroupTrait
{
    /**
     * @return string
     */
    public static function customerGroupPull(): string
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

    /**
     * @return string
     */
    public static function customerGroupPullRole(): string
    {
        global $wpdb;
        $jclcg = $wpdb->prefix . 'jtl_connector_link_customer_group';

        return "
            SELECT p.ID, p.post_title, p.post_name
             FROM `{$wpdb->posts}` p
            AND p.post_type = 'customer_groups'
        ";
    }
}
