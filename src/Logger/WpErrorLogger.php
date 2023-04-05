<?php

/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Logger;

use jtl\Connector\Core\Logger\Logger;

/**
 * Class WpErrorLogger has to be used by checksum reading, writing or deleting methods.
 * Predefined are the file which is checksum.log and the level which is debug.
 * @package JtlWooCommerceConnector\Logger
 */
class WpErrorLogger extends WooCommerceLogger
{
    /**
     * @param \WP_Error $error
     * @return void
     * @throws \InvalidArgumentException
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function logError(\WP_Error $error): void
    {
        $this->writeLog(\sprintf('%s: %s', \get_called_class(), $error->get_error_message()));
    }

    /**
     * @return int
     */
    protected function getLevel(): int
    {
        return Logger::DEBUG;
    }

    /**
     * @return string
     */
    protected function getFilename(): string
    {
        return 'wp_error';
    }

    /**
     * @return \jtl\Connector\Core\Utilities\Singleton
     */
    public static function getInstance(): \jtl\Connector\Core\Utilities\Singleton
    {
        return parent::getInstance();
    }
}
