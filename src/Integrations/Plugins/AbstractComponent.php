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
     * AbstractComponent constructor.
     * @param PluginInterface $plugin
     */
    public function __construct(PluginInterface $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @return PluginInterface
     */
    public function getPlugin(): PluginInterface
    {
        return $this->plugin;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return get_class($this);
    }
}
