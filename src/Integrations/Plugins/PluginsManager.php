<?php

namespace JtlWooCommerceConnector\Integrations\Plugins;

use JtlWooCommerceConnector\Integrations\Plugins\PerfectWooCommerceBrands\PerfectWooCommerceBrands;
use JtlWooCommerceConnector\Integrations\Plugins\WooCommerce\WooCommerce;
use JtlWooCommerceConnector\Integrations\Plugins\WooCommerce\WooCommerceCategory;
use JtlWooCommerceConnector\Integrations\Plugins\WooCommerce\WooCommerceSpecific;
use JtlWooCommerceConnector\Integrations\Plugins\WooCommerce\WooCommerceSpecificValue;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\Wpml;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlCategory;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlCurrency;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlLanguage;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlPerfectWooCommerceBrands;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlSpecific;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlSpecificValue;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlTermTranslation;
use JtlWooCommerceConnector\Integrations\Plugins\YoastSeo\YoastSeo;
use JtlWooCommerceConnector\Utilities\Db;

/**
 * Class PluginsManager
 * @package JtlWooCommerceConnector\Integrations\Plugins
 */
class PluginsManager
{
    /**
     * @var array
     */
    protected $pluginsList = [];

    /**
     * @var Db
     */
    protected $database;

    /**
     * PluginsManager constructor.
     * @param Db $database
     */
    public function __construct(Db $database)
    {
        $this->database = $database;

        $this->addPlugin(
            (new Wpml())->addComponents(
                new WpmlCurrency(),
                new WpmlLanguage(),
                new WpmlCategory(),
                new WpmlTermTranslation(),
                new WpmlPerfectWooCommerceBrands(),
                new WpmlSpecific(),
                new WpmlSpecificValue()
            )
        )
            ->addPlugin(new YoastSeo())
            ->addPlugin(new PerfectWooCommerceBrands())
            ->addPlugin(
                (new WooCommerce())->addComponents(
                    new WooCommerceCategory(),
                    new WooCommerceSpecific(),
                    new WooCommerceSpecificValue()
                )
            );
    }

    /**
     * @return Db
     */
    public function getDatabase(): Db
    {
        return $this->database;
    }

    /**
     * @param PluginInterface $plugin
     * @return $this
     */
    public function addPlugin(PluginInterface $plugin): self
    {
        $plugin->setPluginsManager($this);
        $this->pluginsList[$plugin->getName()] = $plugin;

        return $this;
    }

    /**
     * @param string $name
     * @return PluginInterface
     * @throws \Exception
     */
    public function get(string $name): PluginInterface
    {
        if (!isset($this->pluginsList[$name])) {
            throw new \Exception(sprintf("Plugin %s not found in PluginsManager", $name));
        }

        return $this->pluginsList[$name];
    }
}