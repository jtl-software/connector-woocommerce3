<?php

/**
 * @copyright 2010-2013 JTL-Software GmbH
 * @package   jtl\Connector\Shopware\Utilities
 */

namespace JtlWooCommerceConnector\Utilities;

use jtl\Connector\Linker\IdentityLinker;

final class Id
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
     * @param $endpoint
     * @return array|string
     */
    public static function unlinkProductImage($endpoint): array|string
    {
        if (\strstr($endpoint, self::PRODUCT_PREFIX . self::SEPARATOR)) {
            $parts = self::unlink($endpoint);
            if (\count($parts) === 3) {
                return \array_splice($parts, 1);
            }

            return '';
        }

        return '';
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
     * @return array|null
     */
    public static function unlinkImage($endpointId): ?array
    {
        list($typePrefix, $parts) = \explode(self::SEPARATOR, $endpointId, 2);

        if ($typePrefix === self::CATEGORY_PREFIX) {
            return [$parts, IdentityLinker::TYPE_CATEGORY];
        } elseif ($typePrefix === self::PRODUCT_PREFIX) {
            return [$parts, IdentityLinker::TYPE_PRODUCT];
        } elseif ($typePrefix === self::MANUFACTURER_PREFIX) {
            return [$parts, IdentityLinker::TYPE_MANUFACTURER];
        }

        return null;
    }

    /**
     * @param $endpointId
     * @return array
     */
    public static function unlinkCustomer($endpointId): array
    {
        return [$endpointId, (int)(\strpos($endpointId, self::SEPARATOR) !== false)];
    }
}
