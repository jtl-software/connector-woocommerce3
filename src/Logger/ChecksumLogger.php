<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Logger;

use jtl\Connector\Core\Logger\Logger;

/**
 * Class ChecksumLogger has to be used by checksum reading, writing or deleting methods.
 * Predefined are the file which is checksum.log and the level which is debug.
 * @package JtlWooCommerceConnector\Logger
 */
class ChecksumLogger extends WooCommerceLogger
{
    public function readAction($endpointId, $type, $checksum)
    {
        $this->writeLog(sprintf('Read: endpointId (%s), type (%s) - checksum (%s)', $endpointId, $type, $checksum));
    }

    public function writeAction($endpointId, $type, $checksum)
    {
        $this->writeLog(sprintf('Write: endpointId (%s), type (%s) and checksum (%s)', $endpointId, $type, $checksum));
    }

    public function deleteAction($endpointId, $type)
    {
        $this->writeLog(sprintf('Delete with endpointId (%s), type (%s)', $endpointId, $type));
    }

    protected function getLevel()
    {
        return Logger::DEBUG;
    }

    protected function getFilename()
    {
        return 'checksum';
    }

    /**
     * @return ChecksumLogger
     */
    public static function getInstance()
    {
        return parent::getInstance();
    }
}
