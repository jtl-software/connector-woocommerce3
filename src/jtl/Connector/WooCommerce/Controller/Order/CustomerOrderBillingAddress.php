<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller\Order;

use jtl\Connector\Model\CustomerOrderBillingAddress as CustomerOrderBillingAddressModel;
use jtl\Connector\Model\Identity;
use jtl\Connector\WooCommerce\Controller\BaseController;
use jtl\Connector\WooCommerce\Utility\Id;
use jtl\Connector\WooCommerce\Utility\Germanized;

class CustomerOrderBillingAddress extends BaseController
{
    public function pullData(\WC_Order $order, $model)
    {
        $address = (new CustomerOrderBillingAddressModel())
            ->setId(new Identity(CustomerOrder::BILLING_ID_PREFIX . $order->get_id()))
            ->setFirstName($order->get_billing_first_name())
            ->setLastName($order->get_billing_last_name())
            ->setStreet($order->get_billing_address_1())
            ->setExtraAddressLine($order->get_billing_address_2())
            ->setZipCode($order->get_billing_postcode())
            ->setCity($order->get_billing_city())
            ->setState($order->get_billing_state())
            ->setCountryIso($order->get_billing_country())
            ->setEMail($order->get_billing_email())
            ->setCompany($order->get_billing_company())
            ->setPhone($order->get_billing_phone())
            ->setCustomerId(new Identity($order->get_customer_id() !== 0
                ? $order->get_customer_id()
                : Id::link([Id::GUEST_PREFIX, $order->get_id()])));

        if (Germanized::getInstance()->isActive()) {
            $index = \get_post_meta($order->get_id(), '_billing_title', true);
            $address->setSalutation(Germanized::getInstance()->parseIndexToSalutation($index));
        }

        return $address;
    }
}
