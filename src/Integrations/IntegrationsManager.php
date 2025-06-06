<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Integrations;

use JtlWooCommerceConnector\Integrations\Plugins\PluginsManager;
use JtlWooCommerceConnector\Utilities\Db;

/**
 * Class IntegrationsManager
 *
 * @package JtlWooCommerceConnector\Integrations
 */
class IntegrationsManager
{
    protected PluginsManager $pluginsManager;

    /**
     * IntegrationsManager constructor.
     *
     * @param Db $database
     */
    public function __construct(Db $database)
    {
        $this->pluginsManager = new PluginsManager($database);
    }

    /**
     * @return PluginsManager
     */
    public function getPluginsManager(): PluginsManager
    {
        return $this->pluginsManager;
    }
}
