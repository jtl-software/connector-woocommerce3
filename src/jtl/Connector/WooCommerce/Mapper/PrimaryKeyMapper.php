<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Mapper;

use jtl\Connector\Drawing\ImageRelationType;
use jtl\Connector\Linker\IdentityLinker;
use jtl\Connector\Mapper\IPrimaryKeyMapper;
use jtl\Connector\WooCommerce\Logger\PrimaryKeyMappingLogger;
use jtl\Connector\WooCommerce\Utility\Db;
use jtl\Connector\WooCommerce\Utility\Id;
use jtl\Connector\WooCommerce\Utility\SQL;

class PrimaryKeyMapper implements IPrimaryKeyMapper
{
    public function getHostId($endpointId, $type)
    {
        $tableName = $this->getTableName($type);
        
        if (is_null($tableName)) {
            return null;
        }
        
        if ($type === IdentityLinker::TYPE_IMAGE) {
            list($endpointId, $imageType) = Id::unlinkImage($endpointId);
            $hostId = Db::getInstance()->queryOne(SQL::primaryKeyMappingHostImage($endpointId, $imageType), false);
        } elseif ($type === IdentityLinker::TYPE_CUSTOMER) {
            list($endpointId, $isGuest) = Id::unlinkCustomer($endpointId);
            $hostId = Db::getInstance()->queryOne(SQL::primaryKeyMappingHostCustomer($endpointId, $isGuest), false);
        } else {
            $hostId = Db::getInstance()->queryOne(SQL::primaryKeyMappingHostInteger($endpointId, $tableName), false);
        }
        
        PrimaryKeyMappingLogger::getInstance()->getHostId($endpointId, $type, $hostId);
        
        return $hostId !== false ? (int)$hostId : null;
    }
    
    public function getEndpointId($hostId, $type, $relationType = null)
    {
        $clause = '';
        $tableName = $this->getTableName($type);
        
        if (is_null($tableName)) {
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
            }
            
            $clause = "AND type = {$relationType}";
        }
        
        $endpointId = Db::getInstance()->queryOne(SQL::primaryKeyMappingEndpoint($hostId, $tableName, $clause), false);
        
        PrimaryKeyMappingLogger::getInstance()->getEndpointId($hostId, $type, $endpointId);
        
        return $endpointId;
    }
    
    public function save($endpointId, $hostId, $type)
    {
        $tableName = $this->getTableName($type);
        
        if (is_null($tableName)) {
            return null;
        }
        
        PrimaryKeyMappingLogger::getInstance()->save($endpointId, $hostId, $type);
        
        if ($type === IdentityLinker::TYPE_IMAGE) {
            list($endpointId, $imageType) = Id::unlinkImage($endpointId);
            $id = Db::getInstance()->query(SQL::primaryKeyMappingSaveImage($endpointId, $hostId, $imageType), false);
        } elseif ($type === IdentityLinker::TYPE_CUSTOMER) {
            list($endpointId, $isGuest) = Id::unlinkCustomer($endpointId);
            $id = Db::getInstance()->query(SQL::primaryKeyMappingSaveCustomer($endpointId, $hostId, $isGuest), false);
        } else {
            $id = Db::getInstance()->query(SQL::primaryKeyMappingSaveInteger($endpointId, $hostId, $tableName), false);
        }
        
        return $id !== false;
    }
    
    public function delete($endpointId = null, $hostId = null, $type)
    {
        $where = '';
        $tableName = $this->getTableName($type);
        
        if (is_null($tableName)) {
            return null;
        }
        
        PrimaryKeyMappingLogger::getInstance()->delete($endpointId, $hostId, $type);
        
        if ($type === IdentityLinker::TYPE_IMAGE || $type === IdentityLinker::TYPE_CUSTOMER) {
            $endpoint = "'{$endpointId}'";
        } else {
            $endpoint = "{$endpointId}";
        }
        
        if ($endpointId !== null && $hostId !== null) {
            $where = "WHERE endpoint_id = {$endpoint} AND host_id = {$hostId}";
        } elseif ($endpointId !== null) {
            $where = "WHERE endpoint_id = {$endpoint}";
        } elseif ($hostId !== null) {
            $where = "WHERE host_id = {$hostId}";
        }
        
        return Db::getInstance()->query(SQL::primaryKeyMappingDelete($where, $tableName), false);
    }
    
    public function clear()
    {
        PrimaryKeyMappingLogger::getInstance()->writeLog('Clearing linking tables');
        
        foreach (SQL::primaryKeyMappingClear() as $query) {
            Db::getInstance()->query($query);
        }
        
        return true;
    }
    
    public function gc()
    {
        return true;
    }
    
    public static function getTableName($type)
    {
        switch ($type) {
            case IdentityLinker::TYPE_CATEGORY:
                return 'jtl_connector_link_category';
            case IdentityLinker::TYPE_CUSTOMER:
                return 'jtl_connector_link_customer';
            case IdentityLinker::TYPE_PRODUCT:
                return 'jtl_connector_link_product';
            case IdentityLinker::TYPE_IMAGE:
                return 'jtl_connector_link_image';
            case IdentityLinker::TYPE_CUSTOMER_ORDER:
                return 'jtl_connector_link_order';
            case IdentityLinker::TYPE_PAYMENT:
                return 'jtl_connector_link_payment';
            case IdentityLinker::TYPE_CROSSSELLING:
                return 'jtl_connector_link_crossselling';
            case IdentityLinker::TYPE_SPECIFIC:
                return 'jtl_connector_link_specific';
            case IdentityLinker::TYPE_SPECIFIC_VALUE:
                return 'jtl_connector_link_specific_value';
        }
        
        return null;
    }
}
