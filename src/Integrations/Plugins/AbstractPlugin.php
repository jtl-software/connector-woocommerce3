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
     * @param ComponentInterface $component
     * @return mixed|void
     */
    public function addComponent(ComponentInterface $component)
    {
        $this->components[$component->getName()] = $component;
    }

    /**
     * @param string $name
     * @return ComponentInterface
     */
    public function getComponent(string $name): ComponentInterface
    {
        return $this->components[$name];
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return get_class($this);
    }
}
