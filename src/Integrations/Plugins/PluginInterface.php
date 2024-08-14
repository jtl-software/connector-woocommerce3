<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Integrations\Plugins;

/**
 * Interface PluginInterface
 *
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
     * @return self
     */
    public function addComponent(ComponentInterface $component): self;

    /**
     * @param ComponentInterface ...$components
     * @return self
     */
    public function addComponents(ComponentInterface ...$components): self;

    /**
     * @param string $name
     * @return ComponentInterface
     */
    public function getComponent(string $name): ComponentInterface;

    /**
     * @param PluginsManager $pluginsManager
     *
     * @return void
     */
    public function setPluginsManager(PluginsManager $pluginsManager): void;

    /**
     * @return PluginsManager
     */
    public function getPluginsManager(): PluginsManager;
}
