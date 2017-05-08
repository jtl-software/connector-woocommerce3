<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Mapper\Order;

use jtl\Connector\Model\Identity;
use jtl\Connector\WooCommerce\Mapper\BaseObjectMapper;

class CustomerOrderShippingAddress extends BaseObjectMapper
{
    protected $pull = [
        'id'               => null,
        'customerId'       => 'customer_user',
        'firstName'        => 'shipping_first_name',
        'lastName'         => 'shipping_last_name',
        'company'          => 'shipping_company',
        'street'           => 'shipping_address_1',
        'extraAddressLine' => 'shipping_address_2',
        'zipCode'          => 'shipping_postcode',
        'city'             => 'shipping_city',
        'state'            => 'shipping_state',
        'countryIso'       => null,
    ];

    protected function id(\WC_Order $order)
    {
        return new Identity(CustomerOrder::SHIPPING_ID_PREFIX . $order->get_id());
    }

    protected function countryIso(\WC_Order $order)
    {
        return $order->get_shipping_country();
    }
}
