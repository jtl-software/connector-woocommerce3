<?php

namespace JtlWooCommerceConnector\Integrations\Plugins;

/**
 * Class AbstractComponent
 * @package JtlWooCommerceConnector\Integrations\Plugins
 */
abstract class AbstractComponent implements ComponentInterface
{
    /**
     * @var PluginInterface
     */
    protected $plugin;

    /**
     * @param PluginInterface $plugin
     * @return $this
     */
    public function setPlugin(PluginInterface $plugin)
    {
        $this->plugin = $plugin;
        return $this;
    }

    /**
     * @return PluginInterface
     */
    public function getCurrentPlugin(): PluginInterface
    {
        return $this->plugin;
    }

    /**
     * @return PluginsManager
     */
    public function getPluginsManager(): PluginsManager
    {
        return $this->getCurrentPlugin()->getPluginsManager();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return get_class($this);
    }
}
