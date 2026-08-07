<?php

namespace JtlWooCommerceConnector;

use DI\Container;
use Jtl\Connector\Core\Application\Application as CoreApplication;
use Jtl\Connector\Core\Config\CoreConfigInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Application extends CoreApplication
{
    protected function loadPlugins(CoreConfigInterface $config, Container $container, EventDispatcher $eventDispatcher, string $pluginsDir): void
    {
        parent::loadPlugins($config, $container, $eventDispatcher, $pluginsDir);

        if (!is_dir(JTLWCC_EXT_CONNECTOR_PLUGIN_DIR)) {
            return;
        }

        parent::loadPlugins($config, $container, $eventDispatcher, JTLWCC_EXT_CONNECTOR_PLUGIN_DIR);
    }
}
