<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller\Order;

use jtl\Connector\Model\CustomerOrderBillingAddress;
use jtl\Connector\WooCommerce\Controller\BaseController;
use jtl\Connector\WooCommerce\Mapper\Order\CustomerOrderBillingAddress as CustomerOrderBillingAddressMapper;
use jtl\Connector\WooCommerce\Utility\UtilGermanized;

class CustomerOrderBillingAddressGermanized extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->mapper = new CustomerOrderBillingAddressMapper();
    }

    public function pullData(\WC_Order $order, $model)
    {
        $address = $this->mapper->toHost($order);

        if ($address instanceof CustomerOrderBillingAddress) {
            $index = \get_post_meta($order->get_id(), '_billing_title', true);
            $address->setSalutation(UtilGermanized::getInstance()->parseIndexToSalutation($index));
        }

        return $address;
    }
}
