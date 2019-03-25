<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Traits;

use jtl\Connector\Model\DataModel;

trait DeleteTrait
{
    /**
     * Called on a delete on the main model controllers including their sub model controllers.
     *
     * @param DataModel $data Data coming from JTL-Wawi.
     *
     * @return DataModel The deleted model.
     */
    abstract protected function deleteData($data);
}