<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Checksum;

use jtl\Connector\Checksum\IChecksumLoader;
use jtl\Connector\Model\Checksum;
use jtl\Connector\WooCommerce\Logger\ChecksumLogger;
use jtl\Connector\WooCommerce\Utility\Db;
use jtl\Connector\WooCommerce\Utility\SQLs;

class ChecksumLoader implements IChecksumLoader
{
    public function read($endpointId, $type)
    {
        if ($endpointId === null || $type !== Checksum::TYPE_VARIATION) {
            return '';
        }

        $checksum = Db::getInstance()->queryOne(SQLs::checksumRead($endpointId, $type));

        ChecksumLogger::getInstance()->readAction($endpointId, $type, $checksum);

        return is_null($checksum) ? '' : $checksum;
    }

    public function write($endpointId, $type, $checksum)
    {
        if ($endpointId === null || $type !== Checksum::TYPE_VARIATION) {
            return false;
        }

        $statement = Db::getInstance()->query(SQLs::checksumWrite($endpointId, $type, $checksum));

        ChecksumLogger::getInstance()->writeAction($endpointId, $type, $checksum);

        return $statement;
    }

    public function delete($endpointId, $type)
    {
        if ($endpointId === null || $type !== Checksum::TYPE_VARIATION) {
            return false;
        }

        $rows = Db::getInstance()->query(SQLs::checksumDelete($endpointId, $type));

        ChecksumLogger::getInstance()->deleteAction($endpointId, $type);

        return $rows;
    }
}
