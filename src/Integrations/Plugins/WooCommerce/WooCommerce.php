<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\WooCommerce;

use JtlWooCommerceConnector\Integrations\Plugins\AbstractPlugin;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;

/**
 * Class WooCommerce
 * @package JtlWooCommerceConnector\Integrations\Plugins\WooCommerce
 */
class WooCommerce extends AbstractPlugin
{
    /**
     * @return bool
     */
    public function canBeUsed(): bool
    {
        return SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE);
    }
}