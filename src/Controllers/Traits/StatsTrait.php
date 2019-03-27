<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Traits;

trait StatsTrait
{
    /**
     * Should return the number of main models which has to be pulled.
     *
     * @return mixed The number of main models.
     */
    abstract protected function getStats();
}