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
    public function getPlugin(): PluginInterface;

    /**
     * @return string
     */
    public function getName(): string;
}
