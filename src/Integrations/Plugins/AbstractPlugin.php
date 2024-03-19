<?php

namespace JtlWooCommerceConnector\Integrations\Plugins;

/**
 * Class AbstractPlugin
 * @package JtlWooCommerceConnector\Integrations\Plugins
 */
abstract class AbstractPlugin implements PluginInterface
{
    /**
     * @var ComponentInterface[]
     */
    protected $components = [];

    /**
     * @var PluginsManager
     */
    protected $pluginsManager;

    /**
     * @param ComponentInterface $component
     * @return $this
     */
    public function addComponent(ComponentInterface $component): PluginInterface
    {
        $component->setPlugin($this);
        $this->components[$component->getName()] = $component;

        return $this;
    }

    /**
     * @param ComponentInterface ...$components
     * @return PluginInterface
     */
    public function addComponents(ComponentInterface ...$components): PluginInterface
    {
        foreach ($components as $component) {
            $this->addComponent($component);
        }

        return $this;
    }

    /**
     * @param string $name
     * @return ComponentInterface
     * @throws \Exception
     */
    public function getComponent(string $name): ComponentInterface
    {
        if($this->hasComponent($name) === false){
            throw new \Exception(sprintf("Cannot find component %s in plugin %s", $name, $this->getName()));
        }

        return $this->components[$name];
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasComponent(string $name): bool
    {
        return isset($this->components[$name]);
    }

    /**
     * @param PluginsManager $pluginsManager
     */
    public function setPluginsManager(PluginsManager $pluginsManager): void
    {
        $this->pluginsManager = $pluginsManager;
    }

    /**
     * @return PluginsManager
     */
    public function getPluginsManager(): PluginsManager
    {
        return $this->pluginsManager;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return get_class($this);
    }
}
