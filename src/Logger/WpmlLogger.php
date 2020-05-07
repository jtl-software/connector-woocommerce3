<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Logger;

use jtl\Connector\Core\Logger\Logger;

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
