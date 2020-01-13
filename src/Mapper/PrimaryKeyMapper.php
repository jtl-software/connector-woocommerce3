<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Mapper;

use jtl\Connector\Drawing\ImageRelationType;
use jtl\Connector\Linker\IdentityLinker;
use jtl\Connector\Mapper\IPrimaryKeyMapper;
use JtlWooCommerceConnector\Logger\PrimaryKeyMappingLogger;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\Id;
use JtlWooCommerceConnector\Utilities\SqlHelper;

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
            $hostId = Db::getInstance()->queryOne(SqlHelper::primaryKeyMappingHostImage($endpointId, $imageType),
                false);
        } elseif ($type === IdentityLinker::TYPE_CUSTOMER) {
            list($endpointId, $isGuest) = Id::unlinkCustomer($endpointId);
            $hostId = Db::getInstance()->queryOne(SqlHelper::primaryKeyMappingHostCustomer($endpointId, $isGuest),
                false);
        } elseif ($type === IdentityLinker::TYPE_CUSTOMER_GROUP) {
            $hostId = Db::getInstance()->queryOne(SqlHelper::primaryKeyMappingHostString($endpointId, $tableName), false);
        }else {
            $hostId = Db::getInstance()->queryOne(SqlHelper::primaryKeyMappingHostInteger($endpointId, $tableName),
                false);
        }
        
        PrimaryKeyMappingLogger::getInstance()->getHostId($endpointId, $type, $hostId);
        
        return $hostId !== false ? (int)$hostId : null;
    }
    
    public function getEndpointId($hostId, $type, $relationType = null)
    {
        $clause    = '';
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
                case ImageRelationType::TYPE_MANUFACTURER:
                    $relationType = IdentityLinker::TYPE_MANUFACTURER;
                    break;
            }
            
            $clause = "AND type = {$relationType}";
        }
        
        $endpointId = Db::getInstance()->queryOne(SqlHelper::primaryKeyMappingEndpoint($hostId, $tableName, $clause),
            false);
        
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
            $id = Db::getInstance()->query(SqlHelper::primaryKeyMappingSaveImage($endpointId, $hostId, $imageType),
                false);
        } elseif ($type === IdentityLinker::TYPE_CUSTOMER) {
            list($endpointId, $isGuest) = Id::unlinkCustomer($endpointId);
            $id = Db::getInstance()->query(SqlHelper::primaryKeyMappingSaveCustomer($endpointId, $hostId, $isGuest),
                false);
        }elseif ($type === IdentityLinker::TYPE_CUSTOMER_GROUP) {
            $id = Db::getInstance()->query(SqlHelper::primaryKeyMappingSaveString($endpointId, $hostId, $tableName),
                false);
        } else {
            $id = Db::getInstance()->query(SqlHelper::primaryKeyMappingSaveInteger($endpointId, $hostId, $tableName),
                false);
        }
        
        return $id !== false;
    }
    
    public function delete($endpointId = null, $hostId = null, $type)
    {
        $where     = '';
        $tableName = $this->getTableName($type);
        
        if (is_null($tableName)) {
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
    
    public function clear()
    {
        PrimaryKeyMappingLogger::getInstance()->writeLog('Clearing linking tables');
        
        foreach (SqlHelper::primaryKeyMappingClear() as $query) {
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
        global $wpdb;
        
        switch ($type) {
            case IdentityLinker::TYPE_CATEGORY:
                return 'jtl_connector_link_category';
            case IdentityLinker::TYPE_CROSSSELLING:
                return 'jtl_connector_link_crossselling';
            case IdentityLinker::TYPE_CROSSSELLING_GROUP:
                return 'jtl_connector_link_crossselling_group';
           /* case IdentityLinker::TYPE_CURRENCY:
                return 'jtl_connector_link_currency';*/
            case IdentityLinker::TYPE_CUSTOMER:
                return 'jtl_connector_link_customer';
            case IdentityLinker::TYPE_CUSTOMER_GROUP:
                return 'jtl_connector_link_customer_group';
            case IdentityLinker::TYPE_IMAGE:
                return 'jtl_connector_link_image';
            /*case IdentityLinker::TYPE_LANGUAGE:
                return 'jtl_connector_link_language';*/
            case IdentityLinker::TYPE_MANUFACTURER:
                return 'jtl_connector_link_manufacturer';
        /*    case IdentityLinker::TYPE_MEASUREMENT_UNIT:
                return 'jtl_connector_link_measurement_unit';*/
            case IdentityLinker::TYPE_CUSTOMER_ORDER:
                return 'jtl_connector_link_order';
            case IdentityLinker::TYPE_PAYMENT:
                return 'jtl_connector_link_payment';
            case IdentityLinker::TYPE_PRODUCT:
                return 'jtl_connector_link_product';
            case IdentityLinker::TYPE_SHIPPING_CLASS:
                return 'jtl_connector_link_shipping_class';
        /*    case IdentityLinker::TYPE_SHIPPING_METHOD:
                return 'jtl_connector_link_shipping_method';*/
            case IdentityLinker::TYPE_SPECIFIC:
                return 'jtl_connector_link_specific';
            case IdentityLinker::TYPE_SPECIFIC_VALUE:
                return 'jtl_connector_link_specific_value';
        }
        
        return null;
    }
}
