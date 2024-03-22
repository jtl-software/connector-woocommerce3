<?php

/**
 * Created by PhpStorm.
 * User: Jan Weskamp <jan.weskamp@jtl-software.com>
 * Date: 07.11.2018
 * Time: 09:46
 */

namespace JtlWooCommerceConnector\Utilities\SqlTraits;

use JtlWooCommerceConnector\Utilities\Config;
use JtlWooCommerceConnector\Utilities\Util;

use function Symfony\Component\String\s;

trait PaymentTrait
{
    /**
     * @param @deprecated $includeCompletedOrders
     * @param null $limit
     * @return string
     */
    public static function paymentCompletedPull($includeCompletedOrders, $limit = null): string
    {
        global $wpdb;
        $jclp        = $wpdb->prefix . 'jtl_connector_link_payment';
        $jclo        = $wpdb->prefix . 'jtl_connector_link_order';
        $since       = Config::get(Config::OPTIONS_PULL_ORDERS_SINCE);
        $hposEnabled = \get_option('woocommerce_custom_orders_table_enabled') === 'yes';

        if (\is_null($limit)) {
            $select     = $hposEnabled ? 'COUNT(DISTINCT(p.id))' : 'COUNT(DISTINCT(p.ID))';
            $limitQuery = '';
            $onlyLined  = '';
        } else {
            $select     = $hposEnabled ? 'DISTINCT(p.id)' : 'DISTINCT(p.ID)';
            $limitQuery = 'LIMIT ' . $limit;
            $onlyLined  = 'AND o.endpoint_id IS NOT NULL';
        }

        $manualPaymentMethods = Util::getManualPaymentTypes();

        if ($hposEnabled) {
            return \sprintf(
                "
            SELECT %s
            FROM %s p
            LEFT JOIN %s l ON l.endpoint_id = p.id
            LEFT JOIN %s o ON o.endpoint_id = p.id
            WHERE l.host_id IS NULL AND
                (%s p.status = 'wc-processing' AND p.payment_method NOT IN ('%s') OR
                (p.status = 'wc-completed' AND p.payment_method IN ('%s')))
            %s
            %s
            %s",
                $select,
                $wpdb->prefix . 'wc_orders',
                $jclp,
                $jclo,
                $includeCompletedOrders ? "p.status = 'wc-completed' OR " : '',
                \implode("','", $manualPaymentMethods),
                \implode("','", $manualPaymentMethods),
                (!empty($since) && \strtotime($since) !== false) ? "AND p.date_created_gmt > '{$since}'" : '',
                $onlyLined,
                $limitQuery
            );
        }

        // Usually processing means paid but exception for Cash on delivery
        $status = \sprintf(
            "(p.post_status = 'wc-processing' AND p.ID NOT IN 
            (SELECT pm.post_id FROM %s pm WHERE pm.meta_key = '_payment_method' AND pm.meta_value IN ('%s'))",
            $wpdb->postmeta,
            \implode("','", $manualPaymentMethods)
        );
        // Import manual payment methods what are in $manualPaymentMethods only when order status is completed
        $status .= \sprintf(
            " OR p.ID IN (SELECT pm.post_id FROM %s pm WHERE pm.meta_key 
            = '_payment_method' AND pm.meta_value IN ('%s')) 
            AND p.post_status = 'wc-completed')",
            $wpdb->postmeta,
            \implode("','", $manualPaymentMethods)
        );

        if ($includeCompletedOrders) {
            $status = "(p.post_status = 'wc-completed' OR {$status})";
        }

        $where = (!empty($since) && \strtotime($since) !== false) ? "AND p.post_date > '{$since}'" : '';

        $sql = "
            SELECT %s
            FROM %s p
            LEFT JOIN %s l ON l.endpoint_id = p.ID
            LEFT JOIN %s o ON o.endpoint_id = p.ID
            WHERE p.post_type = 'shop_order' AND l.host_id IS NULL AND %s %s %s
            %s";

        return \sprintf($sql, $select, $wpdb->posts, $jclp, $jclo, $status, $where, $onlyLined, $limitQuery);
    }
}
