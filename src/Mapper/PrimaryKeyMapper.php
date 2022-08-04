<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Mapper;

use Jtl\Connector\Core\Definition\IdentityType;
use Jtl\Connector\Core\Mapper\PrimaryKeyMapperInterface;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\Id;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class PrimaryKeyMapper implements PrimaryKeyMapperInterface, LoggerAwareInterface
{
    /**
     * @var Db
     */
    protected $db;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var SqlHelper
     */
    protected $sqlHelper;

    /**
     * @param Db $db
     * @param SqlHelper $sqlHelper
     */
    public function __construct(Db $db, SqlHelper $sqlHelper)
    {
        $this->db = $db;
        $this->sqlHelper = $sqlHelper;
        $this->logger = new NullLogger();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function getSqlHelper(): SqlHelper
    {
        return $this->sqlHelper;
    }

    public function getHostId(int $type, string $endpointId): ?int
    {
        $tableName = self::getTableName($type);

        if (is_null($tableName)) {
            return null;
        }

        if (in_array($type, self::getImageIdentityTypes(), true)) {
            $hostId = $this->db->queryOne($this->getSqlHelper()->primaryKeyMappingHostImage($endpointId, $type), false);
        } elseif ($type === IdentityType::CUSTOMER) {
            list($endpointId, $isGuest) = Id::unlinkCustomer($endpointId);
            $hostId = $this->db->queryOne($this->getSqlHelper()->primaryKeyMappingHostCustomer($endpointId, $isGuest), false);
        } elseif ($type === IdentityType::CUSTOMER_GROUP) {
            $hostId = $this->db->queryOne($this->getSqlHelper()->primaryKeyMappingHostString($endpointId, $tableName), false);
        } else {
            $hostId = $this->db->queryOne($this->getSqlHelper()->primaryKeyMappingHostInteger($endpointId, $tableName), false);
        }

        $this->logger->debug(sprintf('Read: endpoint (%s), type (%s) - host (%s)', $endpointId, $type, $hostId));

        return $hostId !== false ? (int)$hostId : null;
    }

    public function getEndpointId(int $type, int $hostId): ?string
    {
        $clause = '';
        $tableName = self::getTableName($type);

        if (is_null($tableName)) {
            return null;
        }

//        Identity will be updated in linker table during connector update, new image identities were introduced in new core
//        if ($type === IdentityLinker::TYPE_IMAGE) {
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

        $endpointId = $this->db->queryOne($this->getSqlHelper()->primaryKeyMappingEndpoint($hostId, $tableName, $clause), false);

        $this->logger->debug(sprintf('Read: host (%s), type (%s) - endpoint (%s)', $hostId, $type, $endpointId));

        return $endpointId;
    }

    public function save(int $type, string $endpointId, int $hostId): bool
    {
        $tableName = self::getTableName($type);

        if (is_null($tableName)) {
            return false;
        }

        $this->logger->debug(sprintf('Write: endpoint (%s), host (%s) and type (%s)', $endpointId, $hostId, $type));

        if (in_array($type, self::getImageIdentityTypes(), true)) {
            list($endpointId, $imageType) = Id::unlinkImage($endpointId);
            $id = $this->db->query($this->getSqlHelper()->primaryKeyMappingSaveImage($endpointId, $hostId, $type), false);
        } elseif ($type === IdentityType::CUSTOMER) {
            list($endpointId, $isGuest) = Id::unlinkCustomer($endpointId);
            $id = $this->db->query($this->getSqlHelper()->primaryKeyMappingSaveCustomer($endpointId, $hostId, $isGuest), false);
        } elseif (in_array($type, [IdentityType::CUSTOMER_GROUP, IdentityType::TAX_CLASS], true)) {
            $id = $this->db->query($this->getSqlHelper()->primaryKeyMappingSaveString($endpointId, $hostId, $tableName), false);
        } else {
            $id = $this->db->query($this->getSqlHelper()->primaryKeyMappingSaveInteger($endpointId, $hostId, $tableName), false);
        }

        return !empty($id);
    }

    public function delete(int $type, string $endpointId = null, int $hostId = null): bool
    {
        $where = [];
        $tableName = self::getTableName($type);

        if (is_null($tableName)) {
            return false;
        }

        $this->logger->debug(sprintf('Delete: endpoint (%s), host (%s) and type (%s)', $endpointId, $hostId, $type));

        if ($endpointId !== "") {
            $where[] = sprintf("endpoint_id = '%s'", $endpointId);
        }
        if ($hostId !== 0) {
            $where[] = sprintf("host_id = %d", $hostId);
        }
        if ($type !== 0) {
            $where[] = sprintf('type = %d', $type);
        }

        $whereCondition = sprintf('WHERE %s', implode(' AND ', $where));

        $deleteMappingQuery = $this->getSqlHelper()->primaryKeyMappingDelete($whereCondition, $tableName);

        return $this->db->query($deleteMappingQuery) !== null;
    }

    public function clear(int $type = null): bool
    {
        $this->logger->debug('Clearing linking tables');

        foreach ($this->getSqlHelper()->primaryKeyMappingClear() as $query) {
            $this->db->query($query);
        }

        return true;
    }

    public function gc()
    {
        return true;
    }

    public static function getTableName($type): ?string
    {
        switch ($type) {
            case IdentityType::CATEGORY:
                return 'jtl_connector_link_category';
            case IdentityType::CROSS_SELLING:
                return 'jtl_connector_link_crossselling';
            case IdentityType::CROSS_SELLING_GROUP:
                return 'jtl_connector_link_crossselling_group';
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
            case IdentityType::MANUFACTURER:
                return 'jtl_connector_link_manufacturer';
            case IdentityType::CUSTOMER_ORDER:
                return 'jtl_connector_link_order';
            case IdentityType::PAYMENT:
                return 'jtl_connector_link_payment';
            case IdentityType::PRODUCT:
                return 'jtl_connector_link_product';
            case IdentityType::SHIPPING_CLASS:
                return 'jtl_connector_link_shipping_class';
            case IdentityType::SPECIFIC:
                return 'jtl_connector_link_specific';
            case IdentityType::SPECIFIC_VALUE:
                return 'jtl_connector_link_specific_value';
            case IdentityType::TAX_CLASS:
                return 'jtl_connector_link_tax_class';
        }

        return null;
    }

    public static function getImageIdentityTypes(): array
    {
        return [
            IdentityType::CONFIG_GROUP_IMAGE,
            IdentityType::PRODUCT_VARIATION_VALUE_IMAGE,
            IdentityType::SPECIFIC_IMAGE,
            IdentityType::SPECIFIC_VALUE_IMAGE,
            IdentityType::MANUFACTURER_IMAGE,
            IdentityType::CATEGORY_IMAGE,
            IdentityType::PRODUCT_IMAGE
        ];
    }
}
