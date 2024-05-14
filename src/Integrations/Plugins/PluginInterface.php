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
     * @return bool
     */
    public function canBeUsed(): bool;

    /**
     * @param ComponentInterface $component
     * @return mixed
     */
    public function addComponent(ComponentInterface $component): self;

    /**
     * @param ComponentInterface ...$components
     * @return mixed
     */
    public function addComponents(ComponentInterface ...$components): self;

    /**
     * @param string $name
     * @return ComponentInterface
     */
    public function getComponent(string $name): ComponentInterface;

    /**
     * @param PluginsManager $pluginsManager
     */
    public function setPluginsManager(PluginsManager $pluginsManager): void;

    /**
     * @return PluginsManager
     */
    public function getPluginsManager(): PluginsManager;
}
