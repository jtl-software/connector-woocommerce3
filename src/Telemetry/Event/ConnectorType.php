<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Telemetry\Event;

/**
 * Closed value set for the `connector_type` and `source_system` fields of the telemetry data model.
 *
 * @package JtlWooCommerceConnector\Telemetry\Event
 */
enum ConnectorType: string
{
    case Shopify     = 'shopify';
    case Shopware6   = 'shopware6';
    case WooCommerce = 'woocommerce';
    case PrestaShop  = 'prestashop';
}
