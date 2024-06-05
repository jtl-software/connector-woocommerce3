<?php

namespace JtlWooCommerceConnector\Integrations\Plugins;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * Class AbstractComponent
 * @package JtlWooCommerceConnector\Integrations\Plugins
 */
abstract class AbstractComponent implements ComponentInterface, LoggerAwareInterface
{
    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @var PluginInterface
     */
    protected $plugin;

    /**
     * @param LoggerInterface $logger
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

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
        return \get_class($this);
    }
}
