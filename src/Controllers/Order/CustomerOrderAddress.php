<?php

namespace JtlWooCommerceConnector\Controllers\Order;

use Jtl\Connector\Core\Model\Identity;
use JtlWooCommerceConnector\Controllers\AbstractBaseController;
use JtlWooCommerceConnector\Utilities\Id;

/**
 * Class CustomerOrderAddress
 * @package JtlWooCommerceConnector\Controllers\Order
 */
class CustomerOrderAddress extends AbstractBaseController
{
    /**
     * @param string $countryIso
     * @param string $state
     * @return string
     */
    public function getState(string $countryIso, string $state): string
    {
        return $this->util->getStates()[$countryIso][$state] ?? $state;
    }

    /**
     * @param \WC_Order $order
     * @return Identity
     */
    public function createCustomerId(\WC_Order $order): Identity
    {
        return new Identity(
            $order->get_customer_id() !== 0
                ? $order->get_customer_id()
                : Id::link([Id::GUEST_PREFIX, $order->get_id()])
        );
    }
}
