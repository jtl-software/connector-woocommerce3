<?php

/**
 * Created by PhpStorm.
 * User: Jan Weskamp <jan.weskamp@jtl-software.com>
 * Date: 07.11.2018
 * Time: 09:44
 */

namespace JtlWooCommerceConnector\Utilities\SqlTraits;

use JtlWooCommerceConnector\Utilities\Config;
use JtlWooCommerceConnector\Utilities\Util;

trait CustomerOrderTrait
{
    /**
     * @param $limit
     * @return string
     */
    public static function customerOrderPull($limit): string
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

        $since = Config::get(Config::OPTIONS_PULL_ORDERS_SINCE);
        $delay = Config::get(Config::OPTIONS_IGNORE_ORDERS_YOUNGER_THAN, 0);
        $where = ((!empty($since) && \strtotime($since) !== false) ? "AND p.post_date > '{$since}' " : '')
            . "AND p.post_date < DATE_SUB(NOW(), INTERVAL {$delay} SECOND)";

        return \sprintf("
            SELECT %s FROM %s p
            LEFT JOIN {$jclo} l
            ON p.ID = l.endpoint_id
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('%s')
            AND l.host_id IS NULL %s
            ORDER BY p.post_date DESC %s", $select, $wpdb->posts, \join("','", $status), $where, $limitQuery);
    }
}
