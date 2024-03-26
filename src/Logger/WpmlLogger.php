<?php

namespace JtlWooCommerceConnector\Logger;

use Psr\Log\LogLevel;
use WooCommerce\WooCommerce\Logging\Logger\WooCommerceLogger;

/**
 * Class WpmlLogger
 * @package JtlWooCommerceConnector\Logger
 */
class WpmlLogger extends WooCommerceLogger
{
    protected function getLevel()
    {
        return LogLevel::WARNING;
    }

    protected function getFilename()
    {
        return 'wpml';
    }
}
