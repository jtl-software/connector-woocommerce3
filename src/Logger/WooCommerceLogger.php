<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Logger;

use jtl\Connector\Core\Logger\Logger;
use jtl\Connector\Core\Utilities\Singleton;

abstract class WooCommerceLogger extends Singleton
{
    public function writeLog($message)
    {
        return Logger::write(trim(preg_replace('/\s+/', ' ', $message)), $this->getLevel(), $this->getFilename());
    }

    abstract protected function getLevel();

    abstract protected function getFilename();

    /**
     * @return WooCommerceLogger
     */
    public static function getInstance()
    {
        return parent::getInstance();
    }
}
