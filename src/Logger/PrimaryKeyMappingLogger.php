<?php

/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Logger;

use jtl\Connector\Core\Logger\Logger;
use Monolog\Logger as LoggerAlias;

/**
 * Class PrimaryKeyMappingLogger has to be used by the primary key mapper.
 * Predefined are the file which is linker.log and the level which is debug.
 * @package JtlWooCommerceConnector\Logger
 */
class PrimaryKeyMappingLogger extends WooCommerceLogger
{
    /**
     * @param $endpointId
     * @param $type
     * @param $hostId
     * @return void
     * @throws \InvalidArgumentException
     */
    public function getHostId($endpointId, $type, $hostId): void
    {
        $this->writeLog(\sprintf('Read: endpoint (%s), type (%s) - host (%s)', $endpointId, $type, $hostId));
    }

    /**
     * @param $hostId
     * @param $type
     * @param $endpointId
     * @return void
     * @throws \InvalidArgumentException
     */
    public function getEndpointId($hostId, $type, $endpointId): void
    {
        $this->writeLog(\sprintf('Read: host (%s), type (%s) - endpoint (%s)', $hostId, $type, $endpointId));
    }

    /**
     * @param $endpointId
     * @param $hostId
     * @param $type
     * @return void
     * @throws \InvalidArgumentException
     */
    public function save($endpointId, $hostId, $type): void
    {
        $this->writeLog(\sprintf('Write: endpoint (%s), host (%s) and type (%s)', $endpointId, $hostId, $type));
    }

    /**
     * @param $endpointId
     * @param $hostId
     * @param $type
     * @return void
     * @throws \InvalidArgumentException
     */
    public function delete($endpointId, $hostId, $type): void
    {
        $this->writeLog(\sprintf('Delete: endpoint (%s), host (%s) and type (%s)', $endpointId, $hostId, $type));
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
        return 'linker';
    }

    /**
     * @return WooCommerceLogger
     */
    public static function getInstance(): WooCommerceLogger
    {
        return parent::getInstance();
    }
}
