<?php

namespace jtl\ProductCustomOptions;

use DI\Container;
use Jtl\Connector\Core\Definition\Action;
use Jtl\Connector\Core\Definition\Controller;
use Jtl\Connector\Core\Definition\Event;
use Jtl\Connector\Core\Plugin\PluginInterface;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use Noodlehaus\ConfigInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class Bootstrap
 * @package jtl\ProductCustomOptions
 */
class Bootstrap implements PluginInterface
{
    public const
        EXTRA_PRODUCT_OPTIONS     = 'Extra Product Options (Product Addons) for WooCommerce',
        EXTRA_PRODUCT_OPTIONS_NEW = 'Extra product options For WooCommerce | Custom Product Addons and Fields';

    /**
     * @param EventDispatcher $dispatcher
     * @return void
     */
    public function registerListener(ConfigInterface $config, Container $container, EventDispatcher $dispatcher): void
    {
        if (
            SupportedPlugins::isActive(self::EXTRA_PRODUCT_OPTIONS)
        ) {
            $dispatcher->addListener(Event::createEventName(Controller::CUSTOMER_ORDER, Action::PUSH, Event::AFTER), [
                new CustomerOrderListener($container->get(Db::class)),
                'onCustomerOrderAfterPull'
            ]);
        }
    }
}
