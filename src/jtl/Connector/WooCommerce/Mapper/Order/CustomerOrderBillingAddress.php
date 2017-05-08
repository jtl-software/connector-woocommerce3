<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Mapper\Order;

use jtl\Connector\Model\Identity;
use jtl\Connector\WooCommerce\Mapper\BaseObjectMapper;

class CustomerOrderBillingAddress extends BaseObjectMapper
{
    protected $pull = [
        'id'               => null,
        'customerId'       => 'customer_user',
        'firstName'        => 'billing_first_name',
        'lastName'         => 'billing_last_name',
        'company'          => 'billing_company',
        'eMail'            => 'billing_email',
        'phone'            => 'billing_phone',
        'street'           => 'billing_address_1',
        'extraAddressLine' => 'billing_address_2',
        'zipCode'          => 'billing_postcode',
        'city'             => 'billing_city',
        'state'            => 'billing_state',
        'countryIso'       => null,
    ];

    protected function id(\WC_Order $order)
    {
        return new Identity(CustomerOrder::BILLING_ID_PREFIX . $order->get_id());
    }

    protected function countryIso(\WC_Order $order)
    {
        return $order->get_billing_country();
    }
}
