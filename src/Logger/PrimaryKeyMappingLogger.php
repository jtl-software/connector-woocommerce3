<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Logger;

use jtl\Connector\Core\Logger\Logger;

/**
 * Class PrimaryKeyMappingLogger has to be used by the primary key mapper.
 * Predefined are the file which is linker.log and the level which is debug.
 * @package JtlWooCommerceConnector\Logger
 */
class PrimaryKeyMappingLogger extends WooCommerceLogger
{
    public function getHostId($endpointId, $type, $hostId)
    {
        $this->writeLog(sprintf('Read: endpoint (%s), type (%s) - host (%s)', $endpointId, $type, $hostId));
    }

    public function getEndpointId($hostId, $type, $endpointId)
    {
        $this->writeLog(sprintf('Read: host (%s), type (%s) - endpoint (%s)', $hostId, $type, $endpointId));
    }

    public function save($endpointId, $hostId, $type)
    {
        $this->writeLog(sprintf('Write: endpoint (%s), host (%s) and type (%s)', $endpointId, $hostId, $type));
    }

    public function delete($endpointId, $hostId, $type)
    {
        $this->writeLog(sprintf('Delete: endpoint (%s), host (%s) and type (%s)', $endpointId, $hostId, $type));
    }

    protected function getLevel()
    {
        return Logger::DEBUG;
    }

    protected function getFilename()
    {
        return 'linker';
    }

    /**
     * @return PrimaryKeyMappingLogger
     */
    public static function getInstance()
    {
        return parent::getInstance();
    }
}
