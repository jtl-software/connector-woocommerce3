<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Mapper\Order;

class CustomerOrderGermanized extends CustomerOrder
{
    public function __construct()
    {
        parent::__construct('CustomerOrder');

        $this->pull['items'] = 'Order\CustomerOrderItemGermanized';
        $this->pull['billingAddress'] = 'Order\CustomerOrderBillingAddressGermanized';
        $this->pull['shippingAddress'] = 'Order\CustomerOrderShippingAddressGermanized';
    }
}
