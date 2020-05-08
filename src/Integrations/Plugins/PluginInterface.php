<?php

namespace JtlWooCommerceConnector\Integrations\Plugins;

/**
 * Interface PluginInterface
 * @package JtlWooCommerceConnector\Integrations\Plugins
 */
interface PluginInterface
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param ComponentInterface $component
     * @return mixed
     */
    public function addComponent(ComponentInterface $component);

    /**
     * @param string $name
     * @return ComponentInterface
     */
    public function getComponent(string $name): ComponentInterface;
}
