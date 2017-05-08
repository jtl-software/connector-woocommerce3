<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller\Traits;

trait StatsTrait
{
    /**
     * Should return the number of main models which has to be pulled.
     *
     * @return mixed The number of main models.
     */
    abstract protected function getStats();
}