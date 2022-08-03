<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Checksum;

use Jtl\Connector\Core\Checksum\ChecksumLoaderInterface;
use Jtl\Connector\Core\Model\Checksum;
use JtlWooCommerceConnector\Utilities\Db;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ChecksumLoader implements ChecksumLoaderInterface, LoggerAwareInterface
{
    protected $logger;

    protected $db;

    public function __construct(Db $db)
    {
        $this->db = $db;
        $this->logger = new NullLogger();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function read($endpointId, $type)
    {
        if ($endpointId === null || $type !== Checksum::TYPE_VARIATION) {
            return '';
        }

        $checksum = $this->db->queryOne($this->getChecksumReadQuery($endpointId, $type));

        $this->logger->debug(sprintf('Read: endpointId (%s), type (%s) - checksum (%s)', $endpointId, $type, $checksum));

        return is_null($checksum) ? '' : $checksum;
    }

    public function write($endpointId, $type, $checksum)
    {
        if ($endpointId === null || $type !== Checksum::TYPE_VARIATION) {
            return false;
        }

        $result = $this->db->query($this->getChecksumWriteQuery($endpointId, $type, $checksum));

        $this->logger->debug(sprintf('Write: endpointId (%s), type (%s) and checksum (%s)', $endpointId, $type, $checksum));

        return $result;
    }

    public function delete($endpointId, $type)
    {
        if ($endpointId === null || $type !== Checksum::TYPE_VARIATION) {
            return false;
        }

        $rows = $this->db->query($this->getChecksumDeleteQuery($endpointId, $type));

        $this->logger->debug(sprintf('Delete with endpointId (%s), type (%s)', $endpointId, $type));

        return $rows;
    }

    public function getChecksumReadQuery($endpointId, $type)
    {
        global $wpdb;

        return sprintf('SELECT checksum
                FROM %s%s
                WHERE product_id = %s
                AND type = %s;',
            $wpdb->prefix,
            'jtl_connector_product_checksum',
            $endpointId,
            $type
        );
    }

    public function getChecksumWriteQuery($endpointId, $type, $checksum)
    {
        global $wpdb;

        return sprintf("INSERT IGNORE INTO %s%s VALUES(%s,%s,'%s')",
            $wpdb->prefix,
            'jtl_connector_product_checksum',
            $endpointId,
            $type,
            $checksum
        );
    }

    public function getChecksumDeleteQuery($endpointId, $type)
    {
        global $wpdb;
        $jcpc = $wpdb->prefix . 'jtl_connector_product_checksum';

        return sprintf("DELETE FROM %s
                WHERE `product_id` = %s
                AND `type` = %s",
            $jcpc,
            $endpointId,
            $type
        );
    }
}
