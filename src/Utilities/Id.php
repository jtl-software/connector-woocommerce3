<?php
/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package   jtl\Connector\Shopware\Utilities
 */

namespace JtlWooCommerceConnector\Utilities;

use jtl\Connector\Linker\IdentityLinker;

final class Id
{
    const SEPARATOR = '_';
    const PRODUCT_PREFIX = 'p';
    const CATEGORY_PREFIX = 'c';
    const GUEST_PREFIX = 'g';
    const MANUFACTURER_PREFIX = 'm';
    
    public static function link(array $endpointIds)
    {
        return implode(self::SEPARATOR, $endpointIds);
    }
    
    public static function unlink($endpointId)
    {
        return explode(self::SEPARATOR, $endpointId);
    }
    
    public static function linkProductImage($imageId, $productId)
    {
        return self::link([self::PRODUCT_PREFIX, $imageId, $productId]);
    }
    
    public static function unlinkProductImage($endpoint)
    {
        if (strstr($endpoint, self::PRODUCT_PREFIX . self::SEPARATOR)) {
            $parts = self::unlink($endpoint);
            if (count($parts) === 3) {
                return array_splice($parts, 1);
            }
            
            return '';
        }
        
        return '';
    }
    
    public static function linkCategoryImage($attachmentId)
    {
        return self::link([self::CATEGORY_PREFIX, $attachmentId]);
    }
    
    public static function unlinkCategoryImage($endpoint)
    {
        if (strstr($endpoint, self::CATEGORY_PREFIX . self::SEPARATOR)) {
            return self::unlink($endpoint)[1];
        }
        
        return '';
    }
    
    public static function linkManufacturerImage($attachmentId)
    {
        return self::link([self::MANUFACTURER_PREFIX, $attachmentId]);
    }
    
    public static function unlinkManufacturerImage($endpoint)
    {
        if (strstr($endpoint, self::MANUFACTURER_PREFIX . self::SEPARATOR)) {
            return self::unlink($endpoint)[1];
        }
        
        return '';
    }
    
    public static function unlinkImage($endpointId)
    {
        list($typePrefix, $parts) = explode(self::SEPARATOR, $endpointId, 2);
        
        if ($typePrefix === self::CATEGORY_PREFIX) {
            return [$parts, IdentityLinker::TYPE_CATEGORY];
        } elseif ($typePrefix === self::PRODUCT_PREFIX) {
            return [$parts, IdentityLinker::TYPE_PRODUCT];
        } elseif ($typePrefix === self::MANUFACTURER_PREFIX) {
            return [$parts, IdentityLinker::TYPE_MANUFACTURER];
        }
        
        return null;
    }
    
    public static function unlinkCustomer($endpointId)
    {
        return [$endpointId, (int)(strpos($endpointId, self::SEPARATOR) !== false)];
    }
}
