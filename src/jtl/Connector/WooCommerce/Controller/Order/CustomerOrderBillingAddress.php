<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller\Order;

use jtl\Connector\WooCommerce\Controller\BaseController;

class CustomerOrderBillingAddress extends BaseController
{
    public function pullData(\WC_Order $order, $model)
    {
        return $this->mapper->toHost($order);
    }
}
