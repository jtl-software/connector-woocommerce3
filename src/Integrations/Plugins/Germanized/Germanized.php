<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\Germanized;

use JtlWooCommerceConnector\Integrations\Plugins\AbstractPlugin;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;

class Germanized extends AbstractPlugin
{
    /**
     * @return bool
     */
    public function canBeUsed(): bool
    {
        return SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO);
    }

    /**
     * @param \WC_Product $wcProduct
     * @return bool
     */
    public function hasUnitProduct(\WC_Product $wcProduct): bool
    {
        return (bool)\JtlWooCommerceConnector\Utilities\Germanized::getInstance()->hasUnitProduct($wcProduct);
    }

    /**
     * @param \WC_Product $wcProduct
     * @return bool|mixed|void
     */
    public function getUnit(\WC_Product $wcProduct)
    {
        return \JtlWooCommerceConnector\Utilities\Germanized::getInstance()->getUnit($wcProduct);
    }
}