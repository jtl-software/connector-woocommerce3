<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Logger;

use jtl\Connector\Core\Logger\Logger;

/**
 * Class WpErrorLogger has to be used by checksum reading, writing or deleting methods.
 * Predefined are the file which is checksum.log and the level which is debug.
 * @package JtlWooCommerceConnector\Logger
 */
class WpErrorLogger extends WooCommerceLogger
{
    public function logError(\WP_Error $error)
    {
        $this->writeLog(sprintf('%s: %s', get_called_class(), $error->get_error_message()));
    }

    protected function getLevel()
    {
        return Logger::DEBUG;
    }

    protected function getFilename()
    {
        return 'wp_error';
    }

    /**
     * @return WpErrorLogger
     */
    public static function getInstance()
    {
        return parent::getInstance();
    }
}
