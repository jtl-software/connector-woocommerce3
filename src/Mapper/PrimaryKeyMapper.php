<?php

namespace JtlWooCommerceConnector\Mapper;

use InvalidArgumentException;
use Jtl\Connector\Core\Definition\IdentityType;
use Jtl\Connector\Core\Mapper\PrimaryKeyMapperInterface;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\Id;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class PrimaryKeyMapper implements PrimaryKeyMapperInterface
{
    /**
     * @var Db
     */
    protected Db $db;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface|NullLogger $logger;

    /**
     * @var SqlHelper
     */
    protected SqlHelper $sqlHelper;

    /**
     * @param Db $db
     * @param SqlHelper $sqlHelper
     */
    public function __construct(Db $db, SqlHelper $sqlHelper)
    {
        $this->db        = $db;
        $this->sqlHelper = $sqlHelper;
        $this->logger    = new NullLogger();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function getSqlHelper(): SqlHelper
    {
        return $this->sqlHelper;
    }

    /**
     * @param $endpointId
     * @param $type
     * @return int|null
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function getHostId($type, $endpointId): ?int
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
                SqlHelper::primaryKeyMappingHostCustomer($endpointId, $isGuest),
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

        return $hostId !== false ? (int)$hostId : null;
    }

    /**
     * @param $hostId
     * @param $type
     * @param $relationType
     * @return string|null
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function getEndpointId($type, $hostId, $relationType = null): ?string
    {
        $clause    = '';
        $tableName = self::getTableName($type);

        if (\is_null($tableName)) {
            return null;
        }

//        if ($type === IdentityType::TYPE_IMAGE) {
//            switch ($relationType) {
//                case ImageRelationType::TYPE_PRODUCT:
//                    $relationType = IdentityLinker::TYPE_PRODUCT;
//                    break;
//                case ImageRelationType::TYPE_CATEGORY:
//                    $relationType = IdentityLinker::TYPE_CATEGORY;
//                    break;
//                case ImageRelationType::TYPE_MANUFACTURER:
//                    $relationType = IdentityLinker::TYPE_MANUFACTURER;
//                    break;
//            }
//
//            $clause = "AND type = {$relationType}";
//        }

        $endpointId = $this->db->queryOne(
            $this->getSqlHelper()->primaryKeyMappingEndpoint($hostId, $tableName, $clause),
            false
        );

        $this->logger->debug(\sprintf('Read: host (%s), type (%s) - endpoint (%s)', $hostId, $type, $endpointId));

        return $endpointId;
    }

    /**
     * @param int $type
     * @param string $endpointId
     * @param int $hostId
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
            list($endpointId, $imageType) = Id::unlinkImage($endpointId);
            $id                           = $this->db->query(
                SqlHelper::primaryKeyMappingSaveImage($endpointId, $hostId, $imageType),
                false
            );
        } elseif ($type === IdentityType::CUSTOMER) {
            list($endpointId, $isGuest) = Id::unlinkCustomer($endpointId);
            $id                         = $this->db->query(
                SqlHelper::primaryKeyMappingSaveCustomer($endpointId, $hostId, $isGuest),
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
     * @param int $type
     * @param string|null $endpointId
     * @param int|null $hostId
     * @return bool
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function delete(int $type, string $endpointId = null, int $hostId = null): bool
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
    public function clear(int $type = null): bool
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
     * @param $type
     * @return string|null
     */
    public static function getTableName($type): ?string
    {
        global $wpdb;

        switch ($type) {
            case IdentityType::CATEGORY:
                return 'jtl_connector_link_category';
            case IdentityType::CROSS_SELLING:
                return 'jtl_connector_link_crossselling';
            case IdentityType::CROSS_SELLING_GROUP:
                return 'jtl_connector_link_crossselling_group';
            /* case IdentityLinker::TYPE_CURRENCY:
                 return 'jtl_connector_link_currency';*/
            case IdentityType::CUSTOMER:
                return 'jtl_connector_link_customer';
            case IdentityType::CUSTOMER_GROUP:
                return 'jtl_connector_link_customer_group';
            case IdentityType::CONFIG_GROUP_IMAGE:
            case IdentityType::PRODUCT_VARIATION_VALUE_IMAGE:
            case IdentityType::SPECIFIC_IMAGE:
            case IdentityType::SPECIFIC_VALUE_IMAGE:
            case IdentityType::MANUFACTURER_IMAGE:
            case IdentityType::CATEGORY_IMAGE:
            case IdentityType::PRODUCT_IMAGE:
                return 'jtl_connector_link_image';
            /*case IdentityLinker::TYPE_LANGUAGE:
                return 'jtl_connector_link_language';*/
            case IdentityType::MANUFACTURER:
                return 'jtl_connector_link_manufacturer';
            /*    case IdentityLinker::TYPE_MEASUREMENT_UNIT:
                    return 'jtl_connector_link_measurement_unit';*/
            case IdentityType::CUSTOMER_ORDER:
                return 'jtl_connector_link_order';
            case IdentityType::PAYMENT:
                return 'jtl_connector_link_payment';
            case IdentityType::PRODUCT:
                return 'jtl_connector_link_product';
            case IdentityType::SHIPPING_CLASS:
                return 'jtl_connector_link_shipping_class';
            /*    case IdentityLinker::TYPE_SHIPPING_METHOD:
                    return 'jtl_connector_link_shipping_method';*/
            case IdentityType::SPECIFIC:
                return 'jtl_connector_link_specific';
            case IdentityType::SPECIFIC_VALUE:
                return 'jtl_connector_link_specific_value';
            case IdentityType::TAX_CLASS:
                return 'jtl_connector_link_tax_class';
        }

        return null;
    }

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
