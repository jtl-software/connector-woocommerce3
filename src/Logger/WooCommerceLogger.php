<?php

/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Logger;

use InvalidArgumentException;
use jtl\Connector\Core\Logger\Logger;
use jtl\Connector\Core\Utilities\Singleton;

abstract class WooCommerceLogger extends Singleton
{
    /**
     * @param $message
     * @return bool
     * @throws InvalidArgumentException
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function writeLog($message): bool
    {
        return Logger::write(
            \trim(\preg_replace('/\s+/', ' ', $message)),
            $this->getLevel(),
            $this->getFilename()
        );
    }

    abstract protected function getLevel();

    abstract protected function getFilename();

    /**
     * @return Singleton
     */
    public static function getInstance(): Singleton
    {
        return parent::getInstance();
    }
}
