<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Mapper;

use InvalidArgumentException;
use Jtl\Connector\Core\Definition\IdentityType;
use Jtl\Connector\Core\Mapper\PrimaryKeyMapperInterface;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\Id;
use JtlWooCommerceConnector\Utilities\LinkTableNames;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class PrimaryKeyMapper implements PrimaryKeyMapperInterface
{
    protected Db $db;

    /** @var LoggerInterface */
    protected LoggerInterface|NullLogger $logger;

    protected SqlHelper $sqlHelper;

    /**
     * @param Db        $db
     * @param SqlHelper $sqlHelper
     */
    public function __construct(Db $db, SqlHelper $sqlHelper)
    {
        $this->db        = $db;
        $this->sqlHelper = $sqlHelper;
        $this->logger    = new NullLogger();
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
     * @return SqlHelper
     */
    public function getSqlHelper(): SqlHelper
    {
        return $this->sqlHelper;
    }

    /**
     * @param int    $type
     * @param string $endpointId
     * @return int|null
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function getHostId(int $type, string $endpointId): ?int
    {
        $tableName = self::getTableName($type);

        if (\is_null($tableName)) {
            return null;
        }

        if (\in_array($type, self::getImageIdentityTypes(), true)) {
            $hostId = $this->db->queryOne(
                SqlHelper::primaryKeyMappingHostImage($endpointId, $type),
                false
            );
        } elseif ($type === IdentityType::CUSTOMER) {
            list($endpointId, $isGuest) = Id::unlinkCustomer($endpointId);
            $hostId                     = $this->db->queryOne(
                SqlHelper::primaryKeyMappingHostCustomer((string)$endpointId, (int)$isGuest),
                false
            );
        } elseif ($type === IdentityType::CUSTOMER_GROUP) {
            $hostId = $this->db->queryOne(
                SqlHelper::primaryKeyMappingHostString($endpointId, $tableName),
                false
            );
        } else {
            $hostId = $this->db->queryOne(
                SqlHelper::primaryKeyMappingHostInteger($endpointId, $tableName),
                false
            );
        }

        $this->logger->debug(\sprintf('Read: endpoint (%s), type (%s) - host (%s)', $endpointId, $type, $hostId));

        return (int)$hostId;
    }

    /**
     * @param int $type
     * @param int $hostId
     * @return string|null
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function getEndpointId(int $type, int $hostId): ?string
    {
        $clause    = '';
        $tableName = self::getTableName($type);

        if (\is_null($tableName)) {
            return null;
        }

// if ($type === IdentityType::TYPE_IMAGE) {
// switch ($relationType) {
// case ImageRelationType::TYPE_PRODUCT:
// $relationType = IdentityLinker::TYPE_PRODUCT;
// break;
// case ImageRelationType::TYPE_CATEGORY:
// $relationType = IdentityLinker::TYPE_CATEGORY;
// break;
// case ImageRelationType::TYPE_MANUFACTURER:
// $relationType = IdentityLinker::TYPE_MANUFACTURER;
// break;
// }
//
// $clause = "AND type = {$relationType}";
// }

        $endpointId = $this->db->queryOne(
            $this->getSqlHelper()->primaryKeyMappingEndpoint($hostId, $tableName, $clause),
            false
        );

        $this->logger->debug(\sprintf('Read: host (%s), type (%s) - endpoint (%s)', $hostId, $type, $endpointId));

        return $endpointId;
    }

    /**
     * @param int    $type
     * @param string $endpointId
     * @param int    $hostId
     * @return bool
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function save(int $type, string $endpointId, int $hostId): bool
    {
        $tableName = self::getTableName($type);

        if (\is_null($tableName)) {
            return false;
        }

        $this->logger->debug(
            \sprintf('Write: endpoint (%s), host (%s) and type (%s)', $endpointId, $hostId, $type)
        );

        if (\in_array($type, self::getImageIdentityTypes(), true)) {
            list($endpointId, $imageType) = Id::unlinkImage($endpointId) ?? ['', '0'];
            $id                           = $this->db->query(
                SqlHelper::primaryKeyMappingSaveImage((string)$endpointId, $hostId, (int)$imageType),
                false
            );
        } elseif ($type === IdentityType::CUSTOMER) {
            list($endpointId, $isGuest) = Id::unlinkCustomer($endpointId);
            $id                         = $this->db->query(
                SqlHelper::primaryKeyMappingSaveCustomer((string)$endpointId, $hostId, (int)$isGuest),
                false
            );
        } elseif (\in_array($type, [IdentityType::CUSTOMER_GROUP, IdentityType::TAX_CLASS])) {
            $id = $this->db->query(
                $this->getSqlHelper()->primaryKeyMappingSaveString($endpointId, $hostId, $tableName),
                false
            );
        } else {
            $id = $this->db->query(
                SqlHelper::primaryKeyMappingSaveInteger($endpointId, $hostId, $tableName),
                false
            );
        }

        return $id !== false;
    }

    /**
     * @param int         $type
     * @param string|null $endpointId
     * @param int|null    $hostId
     * @return bool
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function delete(int $type, ?string $endpointId = null, ?int $hostId = null): bool
    {
        $where     = '';
        $tableName = self::getTableName($type);

        if (\is_null($tableName)) {
            return false;
        }

        $this->logger->debug(
            \sprintf('Delete: endpoint (%s), host (%s) and type (%s)', $endpointId, $hostId, $type)
        );

        $endpoint = "'{$endpointId}'";

        if ($endpointId !== null && $hostId !== null) {
            $where = "WHERE endpoint_id = {$endpoint} AND host_id = {$hostId}";
        } elseif ($endpointId !== null) {
            $where = "WHERE endpoint_id = {$endpoint}";
        } elseif ($hostId !== null) {
            $where = "WHERE host_id = {$hostId}";
        }

        $deleteMappingQuery = $this->getSqlHelper()->primaryKeyMappingDelete($where, $tableName);

        return $this->db->query($deleteMappingQuery) !== null;
    }

    /**
     * @param int|null $type
     * @return bool
     * @throws InvalidArgumentException
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function clear(?int $type = null): bool
    {
        $this->logger->debug('Clearing linking tables');

        foreach ($this->getSqlHelper()->primaryKeyMappingClear() as $query) {
            $this->db->query($query);
        }

        return true;
    }

    /**
     * @return bool
     */
    public function gc(): bool
    {
        return true;
    }

    /**
     * @param int $type
     * @return string|null
     */
    public static function getTableName(int $type): ?string
    {
        global $wpdb;

        switch ($type) {
            case IdentityType::CATEGORY:
                return LinkTableNames::CATEGORY;
            case IdentityType::CROSS_SELLING:
                return LinkTableNames::CROSSSELLING;
            case IdentityType::CROSS_SELLING_GROUP:
                return LinkTableNames::CROSSSELLING_GROUP;

            /*
             * case IdentityLinker::TYPE_CURRENCY:
             * return LinkTableNames::CURRENCY;
             */

            case IdentityType::CUSTOMER:
                return LinkTableNames::CUSTOMER;
            case IdentityType::CUSTOMER_GROUP:
                return LinkTableNames::CUSTOMER_GROUP;
            case IdentityType::CONFIG_GROUP_IMAGE:
            case IdentityType::PRODUCT_VARIATION_VALUE_IMAGE:
            case IdentityType::SPECIFIC_IMAGE:
            case IdentityType::SPECIFIC_VALUE_IMAGE:
            case IdentityType::MANUFACTURER_IMAGE:
            case IdentityType::CATEGORY_IMAGE:
            case IdentityType::PRODUCT_IMAGE:
                return LinkTableNames::IMAGE;

            /*
             * case IdentityLinker::TYPE_LANGUAGE:
             * return LinkTableNames::LANGUAGE;
             */

            case IdentityType::MANUFACTURER:
                return LinkTableNames::MANUFACTURER;

            /*
             * case IdentityLinker::TYPE_MEASUREMENT_UNIT:
             * return LinkTableNames::MEASUREMENT_UNIT;
             */

            case IdentityType::CUSTOMER_ORDER:
                return LinkTableNames::ORDER;
            case IdentityType::PAYMENT:
                return LinkTableNames::PAYMENT;
            case IdentityType::PRODUCT:
                return LinkTableNames::PRODUCT;
            case IdentityType::SHIPPING_CLASS:
                return LinkTableNames::SHIPPING_CLASS;

            /*
             * case IdentityLinker::TYPE_SHIPPING_METHOD:
             * return LinkTableNames::SHIPPING_METHOD;
             */

            case IdentityType::SPECIFIC:
                return LinkTableNames::SPECIFIC;
            case IdentityType::SPECIFIC_VALUE:
                return LinkTableNames::SPECIFIC_VALUE;
            case IdentityType::TAX_CLASS:
                return LinkTableNames::TAX_CLASS;
        }

        return null;
    }

    /**
     * @return int[]
     */
    public function getImageIdentityTypes(): array
    {
        return [
            IdentityType::PRODUCT_IMAGE,
            IdentityType::CATEGORY_IMAGE,
            IdentityType::MANUFACTURER_IMAGE,
            IdentityType::PRODUCT_VARIATION_VALUE_IMAGE,
            IdentityType::SPECIFIC_IMAGE,
            IdentityType::SPECIFIC_VALUE_IMAGE,
            IdentityType::CONFIG_GROUP_IMAGE
        ];
    }
}
