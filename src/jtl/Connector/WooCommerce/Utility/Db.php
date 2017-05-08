<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Utility;

use jtl\Connector\WooCommerce\Logger\DatabaseLogger;

class Db extends WooCommerceDatabase
{
    public function query($query, $shouldLog = true)
    {
        global $wpdb;

        if ($shouldLog) {
            DatabaseLogger::getInstance()->writeLog($query);
        }

        $result = $wpdb->get_results($query, ARRAY_A);

        return $result;
    }

    public function queryOne($query, $shouldLog = true)
    {
        global $wpdb;

        if ($shouldLog) {
            DatabaseLogger::getInstance()->writeLog($query);
        }

        return $wpdb->get_var($query);
    }

    public function queryList($query, $shouldLog = true)
    {
        global $wpdb;

        $return = [];

        if ($shouldLog) {
            DatabaseLogger::getInstance()->writeLog($query);
        }

        $result = $wpdb->get_results($query, ARRAY_N);

        if (!empty($result)) {
            foreach ($result as $row) {
                $return[] = $row[0];
            }
        } else {
            return [];
        }

        return $return;
    }
}
