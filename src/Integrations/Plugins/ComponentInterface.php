<?php

namespace JtlWooCommerceConnector\Integrations\Plugins;

/**
 * Interface ComponentInterface
 * @package JtlWooCommerceConnector\Integrations\Plugins
 */
interface ComponentInterface
{
    /**
     * @return PluginInterface
     */
    public function getCurrentPlugin(): PluginInterface;

    /**
     * @param PluginInterface $plugin
     * @return mixed
     */
    public function setPlugin(PluginInterface $plugin);

    /**
     * @return string
     */
    public function getName(): string;
}
