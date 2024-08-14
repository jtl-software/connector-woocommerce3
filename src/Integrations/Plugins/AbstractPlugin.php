<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Integrations\Plugins;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class AbstractPlugin
 *
 * @package JtlWooCommerceConnector\Integrations\Plugins
 */
abstract class AbstractPlugin implements PluginInterface, LoggerAwareInterface
{
    /** @var ComponentInterface[] */
    protected array $components = [];

    protected LoggerInterface $logger;

    protected PluginsManager $pluginsManager;

    /**
     * AbstractPlugin constructor.
     */
    public function __construct()
    {
        $this->logger = new NullLogger();
    }

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
        if ($this->hasComponent($name) === false) {
            throw new \Exception(\sprintf("Cannot find component %s in plugin %s", $name, $this->getName()));
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
     * @param LoggerInterface $logger
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param PluginsManager $pluginsManager
     * @return void
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
        return \get_class($this);
    }
}
