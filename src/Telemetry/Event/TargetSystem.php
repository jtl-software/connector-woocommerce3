<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Telemetry\Event;

/**
 * Closed value set for the `target_system` field of an ApiRequest.
 *
 * Superset of {@see ConnectorType} that additionally allows the ERP/WaWi write targets.
 *
 * @package JtlWooCommerceConnector\Telemetry\Event
 */
enum TargetSystem: string
{
    case Shopify     = 'shopify';
    case Shopware6   = 'shopware6';
    case WooCommerce = 'woocommerce';
    case PrestaShop  = 'prestashop';
    case Erp         = 'erp';
    case Wawi        = 'wawi';
}
