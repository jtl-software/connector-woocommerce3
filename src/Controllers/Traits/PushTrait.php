<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Traits;

use jtl\Connector\Model\DataModel;

trait PushTrait
{
    /**
     * Called on a push on the main model controllers including their sub model controllers.
     *
     * @param DataModel $data Data coming from JTL-Wawi
     *
     * @return array The saved models.
     */
    abstract protected function pushData($data);
}