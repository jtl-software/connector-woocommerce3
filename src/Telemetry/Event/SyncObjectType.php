<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Telemetry\Event;

/**
 * Value set for the `object_type` field of a SyncRun.
 *
 * The data model lists `order`, `product`, `customer`, `inventory` explicitly and marks the set as
 * open-ended ("...", extended by adding new enum values). The values below cover the entity types
 * the WooCommerce connector actually synchronises; add further cases here as new object types are
 * instrumented.
 *
 * @package JtlWooCommerceConnector\Telemetry\Event
 */
enum SyncObjectType: string
{
    case Order        = 'order';
    case Product      = 'product';
    case Customer     = 'customer';
    case Inventory    = 'inventory';
    case Category     = 'category';
    case Manufacturer = 'manufacturer';
    case Payment      = 'payment';
}
