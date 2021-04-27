<?php

namespace JtlWooCommerceConnector\Controllers\Order;

use jtl\Connector\Model\Identity;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Utilities\Id;
use JtlWooCommerceConnector\Utilities\Util;

/**
 * Class CustomerOrderAddress
 * @package JtlWooCommerceConnector\Controllers\Order
 */
class CustomerOrderAddress extends BaseController
{
    /**
     * @param string $countryIso
     * @param string $state
     * @return string
     */
    public function getState(string $countryIso, string $state): string
    {
        return Util::getStates()[$countryIso][$state] ?? '';
    }

    /**
     * @param \WC_Order $order
     * @return Identity
     */
    public function getCustomerId(\WC_Order $order): Identity
    {
        return new Identity($order->get_customer_id() !== 0 ? $order->get_customer_id() : Id::link([Id::GUEST_PREFIX, $order->get_id()]));
    }
}
