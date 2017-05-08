<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Utility;

use jtl\Connector\Core\Utilities\Singleton;

/**
 * Implementations of the abstract WooCommerceDatabase class can be used by the BaseController.
 * Besides one implementation used by the Connector an implementation for e.g. tests can be implemented.
 * @package jtl\Connector\WooCommerce\Utility
 */
abstract class WooCommerceDatabase extends Singleton
{
    /**
     * Run a plain SQL query on the database.
     *
     * @param string $query   SQL query.
     * @param bool $shouldLog Query should be written to log files.
     *
     * @return array|null Database query results
     */
    abstract public function query($query, $shouldLog = true);

    /**
     * Run a SQL query which should only return one value.
     *
     * @param string $query   SQL query.
     * @param bool $shouldLog Query should be written to log files.
     *
     * @return null|string Found value or null.
     */
    abstract public function queryOne($query, $shouldLog = true);

    /**
     * Run a SQL query which should return a list of single values.
     *
     * @param string $query   SQL query.
     * @param bool $shouldLog Query should be written to log files.
     *
     * @return array The array of values
     */
    abstract public function queryList($query, $shouldLog = true);

    /**
     * @return WooCommerceDatabase
     */
    public static function getInstance()
    {
        return parent::getInstance();
    }
}
