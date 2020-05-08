<?php

namespace JtlWooCommerceConnector\Integrations;

use JtlWooCommerceConnector\Integrations\Plugins\PluginsManager;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\Wpml;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlCurrency;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlLanguage;

/**
 * Class IntegrationsManager
 * @package JtlWooCommerceConnector\Integrations
 */
class IntegrationsManager
{
    /**
     * @var
     */
    protected $pluginsManager;

    /**
     * IntegrationsManager constructor.
     */
    public function __construct()
    {
        $this->pluginsManager = new PluginsManager();

        $this->addWpmlPlugin();
    }

    /**
     * @return mixed
     */
    public function getPluginsManager()
    {
        return $this->pluginsManager;
    }

    /**
     *
     */
    protected function addWpmlPlugin()
    {
        $wpml = new Wpml();

        $wpml->addComponent(new WpmlCurrency($wpml));
        $wpml->addComponent(new WpmlLanguage($wpml));

        $this->pluginsManager->addPlugin($wpml);
    }
}