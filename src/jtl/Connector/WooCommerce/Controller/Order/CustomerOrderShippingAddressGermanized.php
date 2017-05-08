<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller\Order;

use jtl\Connector\Model\CustomerOrderShippingAddress;
use jtl\Connector\WooCommerce\Controller\BaseController;
use jtl\Connector\WooCommerce\Mapper\Order\CustomerOrderShippingAddress as CustomerOrderShippingAddressMapper;
use jtl\Connector\WooCommerce\Utility\UtilGermanized;

class CustomerOrderShippingAddressGermanized extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->mapper = new CustomerOrderShippingAddressMapper();
    }

    public function pullData(\WC_Order $order, $model)
    {
        $address = $this->mapper->toHost($order);

        if ($address instanceof CustomerOrderShippingAddress) {
            $index = \get_post_meta($order->get_id(), '_shipping_title', true);
            $address->setSalutation(UtilGermanized::getInstance()->parseIndexToSalutation($index));

            return $address;
        }

        return null;
    }
}
