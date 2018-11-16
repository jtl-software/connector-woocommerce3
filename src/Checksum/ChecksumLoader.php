<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Checksum;

use jtl\Connector\Checksum\IChecksumLoader;
use jtl\Connector\Model\Checksum;
use JtlWooCommerceConnector\Logger\ChecksumLogger;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\SqlHelper;

class ChecksumLoader implements IChecksumLoader
{
    public function read($endpointId, $type)
    {
        if ($endpointId === null || $type !== Checksum::TYPE_VARIATION) {
            return '';
        }

        $checksum = Db::getInstance()->queryOne(SqlHelper::checksumRead($endpointId, $type));

        ChecksumLogger::getInstance()->readAction($endpointId, $type, $checksum);

        return is_null($checksum) ? '' : $checksum;
    }

    public function write($endpointId, $type, $checksum)
    {
        if ($endpointId === null || $type !== Checksum::TYPE_VARIATION) {
            return false;
        }

        $statement = Db::getInstance()->query(SqlHelper::checksumWrite($endpointId, $type, $checksum));

        ChecksumLogger::getInstance()->writeAction($endpointId, $type, $checksum);

        return $statement;
    }

    public function delete($endpointId, $type)
    {
        if ($endpointId === null || $type !== Checksum::TYPE_VARIATION) {
            return false;
        }

        $rows = Db::getInstance()->query(SqlHelper::checksumDelete($endpointId, $type));

        ChecksumLogger::getInstance()->deleteAction($endpointId, $type);

        return $rows;
    }
}
