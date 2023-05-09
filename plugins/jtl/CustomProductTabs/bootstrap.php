<?php

namespace jtl\CustomProductTabs;

use jtl\Connector\Event\Product\ProductAfterPushEvent;
use jtl\Connector\Plugin\IPlugin;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class Bootstrap
 * @package jtl\ProductCustomOptions
 */
class Bootstrap implements IPlugin
{
    public const
        CUSTOM_PRODUCT_TABS_FOR_WOO_COMMERCE_PLUGIN = 'Custom Product Tabs for WooCommerce';

    /**
     * @param EventDispatcher $dispatcher
     * @return void
     */
    public function registerListener(EventDispatcher $dispatcher): void
    {
        if (SupportedPlugins::isActive(self::CUSTOM_PRODUCT_TABS_FOR_WOO_COMMERCE_PLUGIN)) {
            $dispatcher->addListener(ProductAfterPushEvent::EVENT_NAME, [
                new ProductListener(),
                'onProductAfterPush'
            ]);
        }
    }
}
