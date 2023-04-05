<?php

/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Logger;

use jtl\Connector\Core\Logger\Logger;
use Monolog\Logger as LoggerAlias;

/**
 * Class ChecksumLogger has to be used by checksum reading, writing or deleting methods.
 * Predefined are the file which is checksum.log and the level which is debug.
 * @package JtlWooCommerceConnector\Logger
 */
class ChecksumLogger extends WooCommerceLogger
{
    /**
     * @param $endpointId
     * @param $type
     * @param $checksum
     * @return void
     * @throws \InvalidArgumentException
     */
    public function readAction($endpointId, $type, $checksum): void
    {
        $this->writeLog(
            \sprintf('Read: endpointId (%s), type (%s) - checksum (%s)', $endpointId, $type, $checksum)
        );
    }

    /**
     * @param $endpointId
     * @param $type
     * @param $checksum
     * @return void
     * @throws \InvalidArgumentException
     */
    public function writeAction($endpointId, $type, $checksum): void
    {
        $this->writeLog(
            \sprintf('Write: endpointId (%s), type (%s) and checksum (%s)', $endpointId, $type, $checksum)
        );
    }

    /**
     * @param $endpointId
     * @param $type
     * @return void
     * @throws \InvalidArgumentException
     */
    public function deleteAction($endpointId, $type): void
    {
        $this->writeLog(\sprintf('Delete with endpointId (%s), type (%s)', $endpointId, $type));
    }

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
        return 'checksum';
    }

    /**
     * @return WooCommerceLogger
     */
    public static function getInstance(): WooCommerceLogger
    {
        return parent::getInstance();
    }
}
