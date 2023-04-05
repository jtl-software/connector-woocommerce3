<?php

/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Logger;

use jtl\Connector\Core\Logger\Logger;
use Monolog\Logger as LoggerAlias;

/**
 * Class ControllerLogger has to be used by controllers.
 * Predefined are the file which is controller.log and the level which is debug.
 * @package JtlWooCommerceConnector\Logger
 */
class ControllerLogger extends WooCommerceLogger
{
    /**
     * @return int
     */
    protected function getLevel(): int
    {
        return LoggerAlias::WARNING;
    }

    /**
     * @return string
     */
    protected function getFilename(): string
    {
        return 'controller';
    }
}
