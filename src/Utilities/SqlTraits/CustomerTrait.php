<?php
/**
 * Created by PhpStorm.
 * User: Jan Weskamp <jan.weskamp@jtl-software.com>
 * Date: 07.11.2018
 * Time: 09:41
 */

namespace JtlWooCommerceConnector\Utilities\SqlTraits;

use JtlWooCommerceConnector\Controllers\Connector;
use JtlWooCommerceConnector\Utilities\Config;
use JtlWooCommerceConnector\Utilities\Id;
use JtlWooCommerceConnector\Utilities\Util;

trait CustomerTrait
{
    public static function customerNotLinked($limit)
    {
        global $wpdb;
        $jclc = $wpdb->prefix . 'jtl_connector_link_customer';

        $status = Util::getOrderStatusesToImport();

        if(Config::get(Config::OPTIONS_LIMIT_CUSTOMER_QUERY) === 'no_filter') {
            if (is_null($limit)) {
                $select = 'COUNT(DISTINCT(um.user_id))';
                $limitQuery = '';
            } else {
                $select = 'DISTINCT(um.user_id)';
                $limitQuery = 'LIMIT ' . $limit;
            }
            return sprintf("
            SELECT %s
            FROM `%s` um
            LEFT JOIN %s l ON l.endpoint_id = um.user_id AND l.is_guest = 0
            WHERE l.host_id IS NULL
            AND um.meta_key = 'wp_capabilities'
            AND um.meta_value REGEXP '%s'
            %s", $select, $wpdb->usermeta, $jclc, join("|",Config::get(Config::OPTIONS_PULL_CUSTOMER_GROUPS, ['customer'])), $limitQuery);

        }else {
            if (is_null($limit)) {
                $select = 'COUNT(DISTINCT(pm.meta_value))';
                $limitQuery = '';
            } else {
                $select = 'DISTINCT(pm.meta_value)';
                $limitQuery = 'LIMIT ' . $limit;
            }


            return sprintf("
            SELECT %s
            FROM `%s` pm
            LEFT JOIN `%s` p ON p.ID = pm.post_id
            LEFT JOIN %s l ON l.endpoint_id = pm.meta_value * 1 AND l.is_guest = 0
            WHERE l.host_id IS NULL
            AND p.post_status IN ('%s')
            AND pm.meta_key = '_customer_user'
            AND pm.meta_value != 0 
            %s
            %s", $select, $wpdb->postmeta, $wpdb->posts, $jclc, join("','", $status), self::getWhere(), $limitQuery);

        }
    }

    public static function guestNotLinked($limit)
    {
        global $wpdb;
        $jclc = $wpdb->prefix . 'jtl_connector_link_customer';

        $guestPrefix = Id::GUEST_PREFIX . Id::SEPARATOR;

        if (is_null($limit)) {
            $select = 'COUNT(p.ID)';
            $limitQuery = '';
        } else {
            $select = "DISTINCT(CONCAT('{$guestPrefix}', p.ID)) as id";
            $limitQuery = 'LIMIT ' . $limit;
        }

        $status = Util::getOrderStatusesToImport();

        return sprintf("
            SELECT %s
            FROM %s p
            LEFT JOIN %s pm
            ON p.ID = pm.post_id
            LEFT JOIN %s l
            ON l.endpoint_id = CONCAT('%s', p.ID) AND l.is_guest = 1
            WHERE l.host_id IS NULL
            AND p.post_status IN ('%s')
            AND pm.meta_key = '_customer_user'
            AND pm.meta_value = 0 
            %s
            %s", $select, $wpdb->posts, $wpdb->postmeta, $jclc, $guestPrefix, join("','", $status), self::getWhere(), $limitQuery);
    }

    private static function getWhere()
    {
        global $wpdb;
        $jclo = $wpdb->prefix . 'jtl_connector_link_order';

        switch (Config::get(Config::OPTIONS_LIMIT_CUSTOMER_QUERY)){
            case 'last_imported_order':
                $whereQuery = sprintf('AND p.id > (SELECT max(endpoint_id) from %s)', $jclo);
                break;
            case 'fixed_date':
                $since = Config::get(Config::OPTIONS_PULL_ORDERS_SINCE);
                $whereQuery = (!empty($since) && strtotime($since) !== false) ? sprintf('AND p.post_modified > \'%s\'', $since) : '';
                break;
            case 'not_imported':
                $whereQuery = sprintf('AND p.id NOT IN (SELECT endpoint_id from %s)', $jclo);
                break;
            case 'no_filter':
            default:
                $whereQuery = '';
                break;
        }

        return $whereQuery;
    }
}