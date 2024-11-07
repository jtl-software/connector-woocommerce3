<?php

/**
 * Created by PhpStorm.
 * User: Jan Weskamp <jan.weskamp@jtl-software.com>
 * Date: 07.11.2018
 * Time: 09:41
 */

namespace JtlWooCommerceConnector\Utilities\SqlTraits;

use JtlWooCommerceConnector\Utilities\Config;
use JtlWooCommerceConnector\Utilities\Id;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;

trait CustomerTrait
{
    /**
     * @param $limit
     * @param $logger
     * @return string
     */
    public static function customerNotLinked($limit, $logger): string
    {
        if (
            Config::get(
                Config::OPTIONS_LIMIT_CUSTOMER_QUERY_TYPE,
                Config::JTLWCC_CONFIG_DEFAULTS[Config::OPTIONS_LIMIT_CUSTOMER_QUERY_TYPE]
            ) === 'no_filter'
        ) {
            return self::customerNotLinkedNoCondition($limit);
        } else {
            return self::customerNotLinkedCondition($limit, $logger);
        }
    }

    /**
     * @param $limit
     * @return string
     */
    private static function customerNotLinkedNoCondition($limit): string
    {
        global $wpdb;
        $jclc = $wpdb->prefix . 'jtl_connector_link_customer';

        if (\is_null($limit)) {
            $select     = 'COUNT(DISTINCT(um.user_id))';
            $limitQuery = '';
        } else {
            $select     = 'DISTINCT(um.user_id)';
            $limitQuery = 'LIMIT ' . $limit;
        }

        $pullGroups = ['customer'];
        if (
            SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)
            && \version_compare(
                (string)SupportedPlugins::getVersionOf(SupportedPlugins::PLUGIN_B2B_MARKET),
                '1.0.3',
                '>'
            )
        ) {
            $pullGroups = Config::get(Config::OPTIONS_PULL_CUSTOMER_GROUPS, []);
        }

        return \sprintf("
            SELECT %s
            FROM `%s` um
            LEFT JOIN %s l ON l.endpoint_id = um.user_id AND l.is_guest = 0
            WHERE l.host_id IS NULL
            AND um.meta_key = '{$wpdb->prefix}capabilities'
            AND um.meta_value REGEXP '%s'
            %s", $select, $wpdb->usermeta, $jclc, \join("|", $pullGroups), $limitQuery);
    }

    /**
     * @param $limit
     * @return string
     */
    private static function customerNotLinkedCondition($limit, $logger): string
    {
        global $wpdb;
        $jclc = $wpdb->prefix . 'jtl_connector_link_customer';

        $status = Util::getOrderStatusesToImport();

        if (\is_null($limit)) {
            $select     = 'COUNT(DISTINCT(pm.meta_value))';
            $limitQuery = '';
        } else {
            $select     = 'DISTINCT(pm.meta_value)';
            $limitQuery = 'LIMIT ' . $limit;
        }


        return \sprintf(
            "
            SELECT %s
            FROM `%s` pm
            LEFT JOIN `%s` p ON p.ID = pm.post_id
            LEFT JOIN %s l ON l.endpoint_id = pm.meta_value * 1 AND l.is_guest = 0
            WHERE l.host_id IS NULL
            AND p.post_status IN ('%s')
            AND pm.meta_key = '_customer_user'
            AND pm.meta_value != 0 
            %s
            %s",
            $select,
            $wpdb->postmeta,
            $wpdb->posts,
            $jclc,
            \join("','", $status),
            self::getCustomerPullCondition($logger),
            $limitQuery
        );
    }

    /**
     * @param $limit
     * @param $logger
     * @return string
     */
    public static function guestNotLinked($limit, $logger): string
    {
        global $wpdb;
        $jclc = $wpdb->prefix . 'jtl_connector_link_customer';

        $guestPrefix = Id::GUEST_PREFIX . Id::SEPARATOR;

        if (\is_null($limit)) {
            $select     = 'COUNT(p.ID)';
            $limitQuery = '';
        } else {
            $select     = "DISTINCT(CONCAT('{$guestPrefix}', p.ID)) as id";
            $limitQuery = 'LIMIT ' . $limit;
        }

        $status = Util::getOrderStatusesToImport();

        return \sprintf(
            "
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
            %s",
            $select,
            $wpdb->posts,
            $wpdb->postmeta,
            $jclc,
            $guestPrefix,
            \join("','", $status),
            self::getCustomerPullCondition($logger),
            $limitQuery
        );
    }

    /**
     * @param $logger
     * @return string
     */
    private static function getCustomerPullCondition($logger): string
    {
        global $wpdb;
        $jclo = $wpdb->prefix . 'jtl_connector_link_order';

        $logger->debug('Customer Pull Condition: ' . Config::get(Config::OPTIONS_LIMIT_CUSTOMER_QUERY_TYPE));

        switch (Config::get(Config::OPTIONS_LIMIT_CUSTOMER_QUERY_TYPE)) {
            case 'last_imported_order':
                $whereQuery = \sprintf('AND p.id > (SELECT IF(max(endpoint_id),max(endpoint_id),0) from %s)', $jclo);
                break;
            case 'fixed_date':
                $since      = Config::get(Config::OPTIONS_PULL_ORDERS_SINCE);
                $whereQuery = (!empty($since) && \strtotime($since) !== false)
                    ? \sprintf('AND p.post_modified > \'%s\'', $since)
                    : '';
                break;
            case 'not_imported':
                $whereQuery = \sprintf('AND p.id NOT IN (SELECT endpoint_id from %s)', $jclo);
                break;
            case 'no_filter':
            default:
                $whereQuery = '';
                break;
        }

        return $whereQuery;
    }
}
