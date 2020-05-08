<?php

namespace JtlWooCommerceConnector\Integrations\Plugins;

/**
 * Class PluginsManager
 * @package JtlWooCommerceConnector\Integrations\Plugins
 */
class PluginsManager
{
    /**
     * @var array
     */
    protected $pluginsList = [];

    /**
     * @param PluginInterface $plugin
     */
    public function addPlugin(PluginInterface $plugin)
    {
        $this->pluginsList[$plugin->getName()] = $plugin;
    }

    /**
     * @param string $name
     * @return PluginInterface
     * @throws \Exception
     */
    public function get(string $name): PluginInterface
    {
        if (!isset($this->pluginsList[$name])) {
            throw new \Exception(sprintf("Plugin %s not found in PluginsManager", $name));
        }

        return $this->pluginsList[$name];
    }
}