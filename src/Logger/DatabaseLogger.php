<?php

/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Logger;

use jtl\Connector\Core\Logger\Logger;
use Monolog\Logger as LoggerAlias;

/**
 * Class DatabaseLogger has to be used by database querying methods.
 * Predefined are the file which is database.log and the level which is debug.
 * @package JtlWooCommerceConnector\Logger
 */
class DatabaseLogger extends WooCommerceLogger
{
    /**
     * @return int
     */
    protected function getLevel(): int
    {
        return LoggerAlias::DEBUG;
    }

    /**
     * @return string
     */
    protected function getFilename(): string
    {
        return 'database';
    }
}
