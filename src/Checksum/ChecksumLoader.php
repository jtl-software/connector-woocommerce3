<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Checksum;

use Jtl\Connector\Core\Checksum\ChecksumLoaderInterface;
use Jtl\Connector\Core\Model\Checksum;
use JtlWooCommerceConnector\Utilities\Db;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ChecksumLoader implements ChecksumLoaderInterface
{
    protected LoggerInterface $logger;

    protected Db $db;

    /**
     * @param Db $db
     */
    public function __construct(Db $db)
    {
        $this->db     = $db;
        $this->logger = new NullLogger();
    }

    /**
     * @param LoggerInterface $logger
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param string $endpointId
     * @param int    $type
     * @return string
     * @throws InvalidArgumentException
     */
    public function read(string $endpointId, int $type): string
    {
        if ($endpointId === '' || $type !== Checksum::TYPE_VARIATION) {
            return '';
        }

        $checksum = $this->db->queryOne($this->getChecksumRead($endpointId, $type));

        $this->logger->debug(
            \sprintf('Read: endpointId (%s), type (%s) - checksum (%s)', $endpointId, $type, $checksum)
        );

        return \is_null($checksum) ? '' : $checksum;
    }

    /**
     * @param string $endpointId
     * @param int    $type
     * @param string $checksum
     * @return bool
     * @throws InvalidArgumentException
     */
    public function write(string $endpointId, int $type, string $checksum): bool
    {
        if ($endpointId === '' || $type !== Checksum::TYPE_VARIATION) {
            return false;
        }

        $statement = $this->db->query($this->getChecksumWrite($endpointId, $type, $checksum));

        $this->logger->debug(
            \sprintf('Write: endpointId (%s), type (%s) - checksum (%s)', $endpointId, $type, $checksum)
        );

        return (bool)$statement;
    }

    /**
     * @param string $endpointId
     * @param int    $type
     * @return bool
     * @throws InvalidArgumentException
     */
    public function delete(string $endpointId, int $type): bool
    {
        if ($endpointId === '' || $type !== Checksum::TYPE_VARIATION) {
            return false;
        }

        $rows = $this->db->query($this->getChecksumDelete($endpointId, $type));

        $this->logger->debug(
            \sprintf('Delete with endpointId (%s), type (%s)', $endpointId, $type)
        );

        return (bool)$rows;
    }

    /**
     * @param string $endpointId
     * @param int    $type
     * @return string
     */
    public function getChecksumRead(string $endpointId, int $type): string
    {
        global $wpdb;

        return \sprintf(
            'SELECT checksum
                FROM %s%s
                WHERE product_id = %s
                AND type = %s;',
            $wpdb->prefix,
            'jtl_connector_product_checksum',
            \esc_sql($endpointId),
            $type
        );
    }

    /**
     * @param string $endpointId
     * @param int    $type
     * @param string $checksum
     * @return string
     */
    public function getChecksumWrite(string $endpointId, int $type, string $checksum): string
    {
        global $wpdb;

        return \sprintf(
            "INSERT IGNORE INTO %s%s VALUES(%s,%s,'%s')",
            $wpdb->prefix,
            'jtl_connector_product_checksum',
            \esc_sql($endpointId),
            $type,
            \esc_sql($checksum)
        );
    }

    /**
     * @param string $endpointId
     * @param int    $type
     * @return string
     */
    public function getChecksumDelete(string $endpointId, int $type): string
    {
        global $wpdb;
        $jcpc = $wpdb->prefix . 'jtl_connector_product_checksum';

        return \sprintf(
            "DELETE FROM %s
                WHERE `product_id` = %s
                AND `type` = %s",
            $jcpc,
            \esc_sql($endpointId),
            $type
        );
    }
}
