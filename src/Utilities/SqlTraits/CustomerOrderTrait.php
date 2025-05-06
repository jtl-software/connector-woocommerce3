<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Utilities\SqlTraits;

use JtlWooCommerceConnector\Utilities\Config;
use JtlWooCommerceConnector\Utilities\Util;

trait CustomerOrderTrait
{
    /**
     * @param int|null $limit
     * @return string
     */
    public static function customerOrderPull(?int $limit): string
    {
        global $wpdb;
        $jclo = $wpdb->prefix . 'jtl_connector_link_order';

        if (\is_null($limit)) {
            $select     = 'COUNT(DISTINCT(p.ID))';
            $limitQuery = '';
        } else {
            $select     = 'DISTINCT(p.ID)';
            $limitQuery = 'LIMIT ' . $limit;
        }

        $status = Util::getOrderStatusesToImport();

        /** @var string $since */
        $since = Config::get(Config::OPTIONS_PULL_ORDERS_SINCE);
        /** @var int $delay */
        $delay = Config::get(Config::OPTIONS_IGNORE_ORDERS_YOUNGER_THAN, 0);

        $hposEnabled = \get_option('woocommerce_custom_orders_table_enabled') === 'yes';

        $dateColumn   = $hposEnabled ? 'date_created_gmt' : 'post_date';
        $statusColumn = $hposEnabled ? 'status' : 'post_status';
        $typeColumn   = $hposEnabled ? 'type' : 'post_type';
        $from         = $hposEnabled ? $wpdb->prefix . 'wc_orders' : $wpdb->posts;

        $where = ((!empty($since) && \strtotime($since) !== false) ? "AND p.{$dateColumn} > '{$since}' " : '')
            . "AND p.{$dateColumn} < DATE_SUB(NOW(), INTERVAL {$delay} SECOND)";

        return \sprintf(
            "
            SELECT %s FROM %s p
            LEFT JOIN {$jclo} l
            ON p.ID = l.endpoint_id
            WHERE p.%s = 'shop_order'
            AND p.%s IN ('%s')
            AND l.host_id IS NULL %s
            ORDER BY p.%s DESC %s",
            $select,
            $from,
            $typeColumn,
            $statusColumn,
            \join("','", $status),
            $where,
            $dateColumn,
            $limitQuery
        );
    }
}
