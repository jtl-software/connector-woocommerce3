<?php

namespace JtlWooCommerceConnector\Utilities;

use Jtl\Connector\Core\Definition\IdentityType;

class Id
{
    public const SEPARATOR           = '_';
    public const PRODUCT_PREFIX      = 'p';
    public const CATEGORY_PREFIX     = 'c';
    public const GUEST_PREFIX        = 'g';
    public const MANUFACTURER_PREFIX = 'm';

    /**
     * @param array $endpointIds
     * @return string
     */
    public static function link(array $endpointIds): string
    {
        return \implode(self::SEPARATOR, $endpointIds);
    }

    /**
     * @param $endpointId
     * @return array
     */
    public static function unlink($endpointId): array
    {
        return \explode(self::SEPARATOR, $endpointId);
    }

    /**
     * @param $imageId
     * @param $productId
     * @return string
     */
    public static function linkProductImage($imageId, $productId): string
    {
        return self::link([self::PRODUCT_PREFIX, $imageId, $productId]);
    }

    /**
     * @param $endpointId
     * @return array|null
     */
    public static function unlinkImage($endpointId): ?array
    {
        list($typePrefix, $parts) = \explode(self::SEPARATOR, $endpointId, 2);

        if ($typePrefix === self::CATEGORY_PREFIX) {
            return [$parts, IdentityType::CATEGORY_IMAGE];
        }

        if ($typePrefix === self::PRODUCT_PREFIX) {
            return [$parts, IdentityType::PRODUCT_IMAGE];
        }

        if ($typePrefix === self::MANUFACTURER_PREFIX) {
            return [$parts, IdentityType::MANUFACTURER_IMAGE];
        }

        return null;
    }

    /**
     * @param $attachmentId
     * @return string
     */
    public static function linkCategoryImage($attachmentId): string
    {
        return self::link([self::CATEGORY_PREFIX, $attachmentId]);
    }

    /**
     * @param $endpoint
     * @return mixed|string
     */
    public static function unlinkCategoryImage($endpoint): mixed
    {
        if (\strstr($endpoint, self::CATEGORY_PREFIX . self::SEPARATOR)) {
            return self::unlink($endpoint)[1];
        }

        return '';
    }

    /**
     * @param $attachmentId
     * @return string
     */
    public static function linkManufacturerImage($attachmentId): string
    {
        return self::link([self::MANUFACTURER_PREFIX, $attachmentId]);
    }

    /**
     * @param $endpoint
     * @return mixed|string
     */
    public static function unlinkManufacturerImage($endpoint): mixed
    {
        if (\strstr($endpoint, self::MANUFACTURER_PREFIX . self::SEPARATOR)) {
            return self::unlink($endpoint)[1];
        }

        return '';
    }

    /**
     * @param $endpointId
     * @return array
     */
    public static function unlinkCustomer($endpointId): array
    {
        return [$endpointId, (int)(\str_contains($endpointId, self::SEPARATOR))];
    }
}
