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
        EXTRA_PRODUCT_OPTIONS = 'Extra Product Options (Product Addons) for WooCommerce';

    /**
     * @param EventDispatcher $dispatcher
     */
    public function registerListener(EventDispatcher $dispatcher)
    {
        if (SupportedPlugins::isActive(self::EXTRA_PRODUCT_OPTIONS)) {
            $dispatcher->addListener(CustomerOrderAfterPullEvent::EVENT_NAME, [
                new CustomerOrderListener(),
                'onCustomerOrderAfterPull'
            ]);
        }
    }
}
