<?php

namespace JtlWooCommerceConnector\Logger;

use jtl\Connector\Core\Logger\Logger; //TODO:checken
use WooCommerce\WooCommerce\Logging\Logger\WooCommerceLogger;

/**
 * Class WpmlLogger
 * @package JtlWooCommerceConnector\Logger
 */
class WpmlLogger extends WooCommerceLogger
{
    protected function getLevel()
    {
        return Logger::WARNING;
    }

    protected function getFilename()
    {
        return 'wpml';
    }
}
