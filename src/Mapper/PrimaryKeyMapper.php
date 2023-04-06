<?php

/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Mapper;

use InvalidArgumentException;
use jtl\Connector\Drawing\ImageRelationType;
use jtl\Connector\Linker\IdentityLinker;
use jtl\Connector\Mapper\IPrimaryKeyMapper;
use JtlWooCommerceConnector\Logger\PrimaryKeyMappingLogger;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\Id;
use JtlWooCommerceConnector\Utilities\SqlHelper;

class PrimaryKeyMapper implements IPrimaryKeyMapper
{
    /**
     * @param $endpointId
     * @param $type
     * @return int|null
     */
    public function getHostId($endpointId, $type): ?int
    {
        $tableName = self::getTableName($type);

        if (\is_null($tableName)) {
            return null;
        }

        if ($type === IdentityLinker::TYPE_IMAGE) {
            list($endpointId, $imageType) = Id::unlinkImage($endpointId);
            $hostId                       = Db::getInstance()->queryOne(
                SqlHelper::primaryKeyMappingHostImage($endpointId, $imageType),
                false
            );
        } elseif ($type === IdentityLinker::TYPE_CUSTOMER) {
            list($endpointId, $isGuest) = Id::unlinkCustomer($endpointId);
            $hostId                     = Db::getInstance()->queryOne(
                SqlHelper::primaryKeyMappingHostCustomer($endpointId, $isGuest),
                false
            );
        } elseif ($type === IdentityLinker::TYPE_CUSTOMER_GROUP) {
            $hostId = Db::getInstance()->queryOne(
                SqlHelper::primaryKeyMappingHostString($endpointId, $tableName),
                false
            );
        } else {
            $hostId = Db::getInstance()->queryOne(
                SqlHelper::primaryKeyMappingHostInteger($endpointId, $tableName),
                false
            );
        }

        PrimaryKeyMappingLogger::getInstance()->getHostId($endpointId, $type, $hostId);

        return $hostId !== false ? (int)$hostId : null;
    }

    /**
     * @param $hostId
     * @param $type
     * @param $relationType
     * @return string|null
     */
    public function getEndpointId($hostId, $type, $relationType = null): ?string
    {
        $clause    = '';
        $tableName = self::getTableName($type);

        if (\is_null($tableName)) {
            return null;
        }

        if ($type === IdentityLinker::TYPE_IMAGE) {
            switch ($relationType) {
                case ImageRelationType::TYPE_PRODUCT:
                    $relationType = IdentityLinker::TYPE_PRODUCT;
                    break;
                case ImageRelationType::TYPE_CATEGORY:
                    $relationType = IdentityLinker::TYPE_CATEGORY;
                    break;
                case ImageRelationType::TYPE_MANUFACTURER:
                    $relationType = IdentityLinker::TYPE_MANUFACTURER;
                    break;
            }

            $clause = "AND type = {$relationType}";
        }

        $endpointId = Db::getInstance()->queryOne(
            SqlHelper::primaryKeyMappingEndpoint($hostId, $tableName, $clause),
            false
        );

        PrimaryKeyMappingLogger::getInstance()->getEndpointId($hostId, $type, $endpointId);

        return $endpointId;
    }

    /**
     * @param $endpointId
     * @param $hostId
     * @param $type
     * @return bool|null
     */
    public function save($endpointId, $hostId, $type): ?bool
    {
        $tableName = self::getTableName($type);

        if (\is_null($tableName)) {
            return null;
        }

        PrimaryKeyMappingLogger::getInstance()->save($endpointId, $hostId, $type);

        if ($type === IdentityLinker::TYPE_IMAGE) {
            list($endpointId, $imageType) = Id::unlinkImage($endpointId);
            $id                           = Db::getInstance()->query(
                SqlHelper::primaryKeyMappingSaveImage($endpointId, $hostId, $imageType),
                false
            );
        } elseif ($type === IdentityLinker::TYPE_CUSTOMER) {
            list($endpointId, $isGuest) = Id::unlinkCustomer($endpointId);
            $id                         = Db::getInstance()->query(
                SqlHelper::primaryKeyMappingSaveCustomer($endpointId, $hostId, $isGuest),
                false
            );
        } elseif (\in_array($type, [IdentityLinker::TYPE_CUSTOMER_GROUP, IdentityLinker::TYPE_TAX_CLASS])) {
            $id = Db::getInstance()->query(
                SqlHelper::primaryKeyMappingSaveString($endpointId, $hostId, $tableName),
                false
            );
        } else {
            $id = Db::getInstance()->query(
                SqlHelper::primaryKeyMappingSaveInteger($endpointId, $hostId, $tableName),
                false
            );
        }

        return $id !== false;
    }

    /**
     * @param $endpointId
     * @param $hostId
     * @param $type
     * @return bool|array|null
     */
    public function delete($endpointId, $hostId, $type): bool|array|null
    {
        $where     = '';
        $tableName = self::getTableName($type);

        if (\is_null($tableName)) {
            return null;
        }

        PrimaryKeyMappingLogger::getInstance()->delete($endpointId, $hostId, $type);

        $endpoint = "'{$endpointId}'";

        if ($endpointId !== null && $hostId !== null) {
            $where = "WHERE endpoint_id = {$endpoint} AND host_id = {$hostId}";
        } elseif ($endpointId !== null) {
            $where = "WHERE endpoint_id = {$endpoint}";
        } elseif ($hostId !== null) {
            $where = "WHERE host_id = {$hostId}";
        }

        return Db::getInstance()->query(SqlHelper::primaryKeyMappingDelete($where, $tableName), false);
    }

    /**
     * @return bool
     * @throws InvalidArgumentException
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function clear(): bool
    {
        PrimaryKeyMappingLogger::getInstance()->writeLog('Clearing linking tables');

        foreach (SqlHelper::primaryKeyMappingClear() as $query) {
            Db::getInstance()->query($query);
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

        return match ($type) {
            IdentityLinker::TYPE_CATEGORY => 'jtl_connector_link_category',
            IdentityLinker::TYPE_CROSSSELLING => 'jtl_connector_link_crossselling',
            IdentityLinker::TYPE_CROSSSELLING_GROUP => 'jtl_connector_link_crossselling_group',
            IdentityLinker::TYPE_CUSTOMER => 'jtl_connector_link_customer',
            IdentityLinker::TYPE_CUSTOMER_GROUP => 'jtl_connector_link_customer_group',
            IdentityLinker::TYPE_IMAGE => 'jtl_connector_link_image',
            IdentityLinker::TYPE_MANUFACTURER => 'jtl_connector_link_manufacturer',
            IdentityLinker::TYPE_CUSTOMER_ORDER => 'jtl_connector_link_order',
            IdentityLinker::TYPE_PAYMENT => 'jtl_connector_link_payment',
            IdentityLinker::TYPE_PRODUCT => 'jtl_connector_link_product',
            IdentityLinker::TYPE_SHIPPING_CLASS => 'jtl_connector_link_shipping_class',
            IdentityLinker::TYPE_SPECIFIC => 'jtl_connector_link_specific',
            IdentityLinker::TYPE_SPECIFIC_VALUE => 'jtl_connector_link_specific_value',
            IdentityLinker::TYPE_TAX_CLASS => 'jtl_connector_link_tax_class',
            default => null,
        };
    }
}
