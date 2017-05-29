<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller\Order;

use jtl\Connector\Model\CustomerOrderShippingAddress as CustomerOrderShippingAddressModel;
use jtl\Connector\Model\Identity;
use jtl\Connector\WooCommerce\Controller\BaseController;
use jtl\Connector\WooCommerce\Utility\IdConcatenation;
use jtl\Connector\WooCommerce\Utility\UtilGermanized;

class CustomerOrderShippingAddress extends BaseController
{
    public function pullData(\WC_Order $order, $model)
    {
        $address = (new CustomerOrderShippingAddressModel())
            ->setId(new Identity(CustomerOrder::SHIPPING_ID_PREFIX . $order->get_id()))
            ->setFirstName($order->get_shipping_first_name())
            ->setLastName($order->get_shipping_last_name())
            ->setStreet($order->get_shipping_address_1())
            ->setExtraAddressLine($order->get_shipping_address_2())
            ->setZipCode($order->get_shipping_postcode())
            ->setCity($order->get_shipping_city())
            ->setState($order->get_shipping_state())
            ->setCountryIso($order->get_shipping_country())
            ->setCompany($order->get_shipping_company())
            ->setCustomerId(new Identity($order->get_customer_id() !== 0
                ? $order->get_customer_id()
                : IdConcatenation::link([IdConcatenation::GUEST_PREFIX, $order->get_id()])));

        if (UtilGermanized::getInstance()->isActive()) {
            $index = \get_post_meta($order->get_id(), '_shipping_title', true);
            $address->setSalutation(UtilGermanized::getInstance()->parseIndexToSalutation($index));
        }

        return $address;
    }
}
