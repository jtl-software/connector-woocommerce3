<?php
/**
 * Created by PhpStorm.
 * User: Jan Weskamp <jan.weskamp@jtl-software.com>
 * Date: 07.11.2018
 * Time: 09:46
 */

namespace JtlWooCommerceConnector\Utilities\SqlTraits;


use JtlWooCommerceConnector\Utilities\Config;

trait PaymentTrait
{
    public static function paymentCompletedPull($includeCompletedOrders, $limit = null)
    {
        global $wpdb;
        $jclp = $wpdb->prefix . 'jtl_connector_link_payment';
        $jclo = $wpdb->prefix . 'jtl_connector_link_order';

        if (is_null($limit)) {
            $select = 'COUNT(DISTINCT(p.ID))';
            $limitQuery = '';
            $onlyLined = '';
        } else {
            $select = 'DISTINCT(p.ID)';
            $limitQuery = 'LIMIT ' . $limit;
            $onlyLined = 'AND o.endpoint_id IS NOT NULL';
        }

        // Usually processing means paid but exception for Cash on delivery
        $status = "p.post_status = 'wc-processing' AND p.ID NOT IN (SELECT pm.post_id FROM {$wpdb->postmeta} pm WHERE pm.meta_value = 'cod' OR pm.meta_value = 'german_market_purchase_on_account')";

        if ($includeCompletedOrders) {
            $status = "(p.post_status = 'wc-completed' OR {$status})";
        }

        $since = Config::get(Config::OPTIONS_PULL_ORDERS_SINCE);
        $where = (!empty($since) && strtotime($since) !== false) ? "AND p.post_date > '{$since}'" : '';

        return "
            SELECT {$select}
            FROM {$wpdb->posts} p
            LEFT JOIN {$jclp} l ON l.endpoint_id = p.ID
            LEFT JOIN {$jclo} o ON o.endpoint_id = p.ID
            WHERE p.post_type = 'shop_order' AND l.host_id IS NULL AND {$status} {$where} {$onlyLined}
            {$limitQuery}";
    }
}