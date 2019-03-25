<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Logger;

use jtl\Connector\Core\Logger\Logger;

/**
 * Class DatabaseLogger has to be used by database querying methods.
 * Predefined are the file which is database.log and the level which is debug.
 * @package JtlWooCommerceConnector\Logger
 */
class DatabaseLogger extends WooCommerceLogger
{
    protected function getLevel()
    {
        return Logger::DEBUG;
    }

    protected function getFilename()
    {
        return 'database';
    }
}
