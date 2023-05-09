<?php

namespace jtl\ProductCustomOptions;

use jtl\Connector\Event\CustomerOrder\CustomerOrderAfterPullEvent;
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
        EXTRA_PRODUCT_OPTIONS     = 'Extra Product Options (Product Addons) for WooCommerce',
        EXTRA_PRODUCT_OPTIONS_NEW = 'Extra product options For WooCommerce | Custom Product Addons and Fields';

    /**
     * @param EventDispatcher $dispatcher
     * @return void
     */
    public function registerListener(EventDispatcher $dispatcher): void
    {
        if (
            SupportedPlugins::isActive(self::EXTRA_PRODUCT_OPTIONS)
            || SupportedPlugins::isActive(self::EXTRA_PRODUCT_OPTIONS_NEW)
        ) {
            $dispatcher->addListener(CustomerOrderAfterPullEvent::EVENT_NAME, [
                new CustomerOrderListener(),
                'onCustomerOrderAfterPull'
            ]);
        }
    }
}
