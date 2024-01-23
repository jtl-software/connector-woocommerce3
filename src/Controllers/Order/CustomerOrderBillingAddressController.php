<?php

namespace JtlWooCommerceConnector\Controllers\Order;

use Jtl\Connector\Core\Model\CustomerOrderBillingAddress as CustomerOrderBillingAddressModel;
use Jtl\Connector\Core\Model\Identity;
use JtlWooCommerceConnector\Controllers\CustomerOrderController;
use JtlWooCommerceConnector\Utilities\Germanized;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;
use WC_Order;

class CustomerOrderBillingAddressController extends CustomerOrderAddressController
{
    /**
     * @param WC_Order $order
     * @return CustomerOrderBillingAddressModel
     */
    public function pull(WC_Order $order): CustomerOrderBillingAddressModel
    {
        $address = (new CustomerOrderBillingAddressModel())
            ->setId(new Identity(CustomerOrderController::BILLING_ID_PREFIX . $order->get_id()))
            ->setFirstName($order->get_billing_first_name())
            ->setLastName($order->get_billing_last_name())
            ->setStreet($order->get_billing_address_1())
            ->setExtraAddressLine($order->get_billing_address_2())
            ->setZipCode($order->get_billing_postcode())
            ->setCity($order->get_billing_city())
            ->setState($this->getState($order->get_billing_country(), $order->get_billing_state()))
            ->setCountryIso($order->get_billing_country())
            ->setEMail($order->get_billing_email())
            ->setCompany($order->get_billing_company())
            ->setPhone($order->get_billing_phone())
            ->setVatNumber(Util::getVatIdFromOrder($order->get_id()));

        $this->createDefaultAddresses($address);

        if (
            SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO)
        ) {
            $index = \get_post_meta($order->get_id(), '_billing_title', true);
            $address->setSalutation((new Germanized())->parseIndexToSalutation($index));
        }

        return $address;
    }
}
