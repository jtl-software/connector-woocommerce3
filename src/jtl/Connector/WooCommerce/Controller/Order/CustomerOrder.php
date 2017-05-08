<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller\Order;

use jtl\Connector\Model\CustomerOrder as CustomerOrderModel;

class CustomerOrder extends AbstractCustomerOrder
{
    protected function onCustomerOrderMapped(CustomerOrderModel &$customerOrder)
    {
        $date = \get_post_meta($customerOrder->getId()->getEndpoint(), '_paid_date', true);

        if ($date != false) {
            $customerOrder->setPaymentDate(new \DateTime($date));
        }
    }
}
