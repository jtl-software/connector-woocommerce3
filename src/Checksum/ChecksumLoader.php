<?php

namespace JtlWooCommerceConnector\Checksum;

use Jtl\Connector\Core\Checksum\ChecksumLoaderInterface;
use Jtl\Connector\Core\Model\Checksum;
use JtlWooCommerceConnector\Utilities\Db;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ChecksumLoader implements ChecksumLoaderInterface
{
    /**
     * @var NullLogger
     */
    protected $logger;

    /**
     * @var Db
     */
    protected Db $db;

    public function __construct(Db $db)
    {
        $this->db     = $db;
        $this->logger = new NullLogger();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param $endpointId
     * @param $type
     * @return string
     * @throws InvalidArgumentException
     */
    public function read($endpointId, $type): string
    {
        if ($endpointId === null || $type !== Checksum::TYPE_VARIATION) {
            return '';
        }

        $checksum = $this->db->queryOne($this->getChecksumRead($endpointId, $type));

        $this->logger->debug(
            \sprintf('Read: endpointId (%s), type (%s) - checksum (%s)', $endpointId, $type, $checksum)
        );

        return \is_null($checksum) ? '' : $checksum;
    }

    /**
     * @param $endpointId
     * @param $type
     * @param $checksum
     * @return bool
     * @throws InvalidArgumentException
     */
    public function write($endpointId, $type, $checksum): bool
    {
        if ($endpointId === null || $type !== Checksum::TYPE_VARIATION) {
            return false;
        }

        $statement = $this->db->query($this->getChecksumWrite($endpointId, $type, $checksum));

        $this->logger->debug(
            \sprintf('Write: endpointId (%s), type (%s) - checksum (%s)', $endpointId, $type, $checksum)
        );

        return (bool)$statement;
    }

    /**
     * @param $endpointId
     * @param $type
     * @return bool
     * @throws InvalidArgumentException
     */
    public function delete($endpointId, $type): bool
    {
        if ($endpointId === null || $type !== Checksum::TYPE_VARIATION) {
            return false;
        }

        $rows = $this->db->query($this->getChecksumDelete($endpointId, $type));

        $this->logger->debug(
            \sprintf('Delete with endpointId (%s), type (%s)', $endpointId, $type)
        );

        return (bool)$rows;
    }

    public function getChecksumRead($endpointId, $type): string
    {
        global $wpdb;

        return \sprintf(
            'SELECT checksum
                FROM %s%s
                WHERE product_id = %s
                AND type = %s;',
            $wpdb->prefix,
            'jtl_connector_product_checksum',
            $endpointId,
            $type
        );
    }

    public function getChecksumWrite($endpointId, $type, $checksum): string
    {
        global $wpdb;

        return \sprintf(
            "INSERT IGNORE INTO %s%s VALUES(%s,%s,'%s')",
            $wpdb->prefix,
            'jtl_connector_product_checksum',
            $endpointId,
            $type,
            $checksum
        );
    }

    public function getChecksumDelete($endpointId, $type): string
    {
        global $wpdb;
        $jcpc = $wpdb->prefix . 'jtl_connector_product_checksum';

        return \sprintf(
            "DELETE FROM %s
                WHERE `product_id` = %s
                AND `type` = %s",
            $jcpc,
            $endpointId,
            $type
        );
    }
}
