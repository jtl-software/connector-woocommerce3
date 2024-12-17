<?php

declare(strict_types=1);

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
     * @param array<int, int|string> $endpointIds
     * @return string
     */
    public static function link(array $endpointIds): string
    {
        return \implode(self::SEPARATOR, $endpointIds);
    }

    /**
     * @param string $endpointId
     * @return string[]
     */
    public static function unlink(string $endpointId): array
    {
        return \explode(self::SEPARATOR, $endpointId);
    }

    /**
     * @param int        $imageId
     * @param int|string $productId
     * @return string
     */
    public static function linkProductImage(int $imageId, int|string $productId): string
    {
        return self::link([self::PRODUCT_PREFIX, $imageId, $productId]);
    }

    /**
     * @param string $endpointId
     * @return array<int, int|string>|null
     */
    public static function unlinkImage(string $endpointId): ?array
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
     * @param int $attachmentId
     * @return string
     */
    public static function linkCategoryImage(int $attachmentId): string
    {
        return self::link([self::CATEGORY_PREFIX, $attachmentId]);
    }

    /**
     * @param string $endpoint
     * @return string
     */
    public static function unlinkCategoryImage(string $endpoint): string
    {
        if (\strstr($endpoint, self::CATEGORY_PREFIX . self::SEPARATOR)) {
            return self::unlink($endpoint)[1];
        }

        return '';
    }

    /**
     * @param int $attachmentId
     * @return string
     */
    public static function linkManufacturerImage(int $attachmentId): string
    {
        return self::link([self::MANUFACTURER_PREFIX, $attachmentId]);
    }

    /**
     * @param string $endpoint
     * @return string
     */
    public static function unlinkManufacturerImage(string $endpoint): string
    {
        if (\strstr($endpoint, self::MANUFACTURER_PREFIX . self::SEPARATOR)) {
            return self::unlink($endpoint)[1];
        }

        return '';
    }

    /**
     * @param string $endpointId
     * @return array<int, int|string>
     */
    public static function unlinkCustomer(string $endpointId): array
    {
        return [$endpointId, (int)(\str_contains($endpointId, self::SEPARATOR))];
    }
}
