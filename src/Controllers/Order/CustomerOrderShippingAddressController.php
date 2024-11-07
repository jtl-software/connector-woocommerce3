<?php

namespace JtlWooCommerceConnector\Controllers\Order;

use Jtl\Connector\Core\Model\CustomerOrderShippingAddress as CustomerOrderShippingAddressModel;
use Jtl\Connector\Core\Model\Identity;
use JtlWooCommerceConnector\Controllers\CustomerOrderController;
use JtlWooCommerceConnector\Utilities\Germanized;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;

class CustomerOrderShippingAddressController extends CustomerOrderAddressController
{
    /**
     * @param \WC_Order $order
     * @return CustomerOrderShippingAddressModel
     */
    public function pull(\WC_Order $order): CustomerOrderShippingAddressModel
    {
        $address = (new CustomerOrderShippingAddressModel())
            ->setId(new Identity(CustomerOrderController::SHIPPING_ID_PREFIX . $order->get_id()))
            ->setFirstName($order->get_shipping_first_name())
            ->setLastName($order->get_shipping_last_name())
            ->setStreet($order->get_shipping_address_1())
            ->setExtraAddressLine($order->get_shipping_address_2())
            ->setZipCode($order->get_shipping_postcode())
            ->setCity($order->get_shipping_city())
            ->setState($this->getState($order->get_shipping_country(), $order->get_shipping_state()))
            ->setCountryIso($order->get_shipping_country())
            ->setCompany($order->get_shipping_company())
            ->setCustomerId($this->createCustomerId($order));

        if ($this->emptyAddressCheck($address)) {
            $this->useBillingAddress($address, $order);
        }

        if ($this->emptyAddressCheck($address)) {
            $this->createDefaultAddresses($address, $order);
        }

        if (SupportedPlugins::comparePluginVersion(SupportedPlugins::PLUGIN_WOOCOMMERCE, '>=', '5.6.0')) {
            $address->setPhone($order->get_shipping_phone());
        }

        $dhlPostNumber = '';

        if (
            SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO)
        ) {
            $index = \get_post_meta($order->get_id(), '_shipping_title', true);
            $address->setSalutation((new Germanized())->parseIndexToSalutation($index));

            $dhlPostNumber = $order->get_meta('_shipping_parcelshop_post_number', true);
            if (empty($dhlPostNumber)) {
                $dhlPostNumber = $order->get_meta('_shipping_dhl_postnumber', true);
            }
        } elseif (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_DHL_FOR_WOOCOMMERCE)) {
            $dhlPostNumber = $order->get_meta('_shipping_dhl_postnum', true);
        }

        if (!empty($dhlPostNumber)) {
            $address->setExtraAddressLine($dhlPostNumber);
        }

        return $address;
    }

    private function useBillingAddress(CustomerOrderShippingAddressModel $address, \WC_Order $order): void
    {
        if (empty($address->getCity())) {
            $address->setCity($order->get_billing_city());
        }

        if (empty($address->getZipCode())) {
            $address->setZipCode($order->get_billing_postcode());
        }

        if (empty($address->getStreet())) {
            $address->setStreet($order->get_billing_address_1());
        }

        if (empty($address->getCountryIso())) {
            $address->setCountryIso($order->get_billing_country());
        }

        if (empty($address->getLastName())) {
            $address->setLastName($order->get_billing_last_name());
        }
    }

    private function emptyAddressCheck(CustomerOrderShippingAddressModel $address): bool
    {
        if (
            empty($address->getCity())
            || empty($address->getStreet())
            || empty($address->getCountryIso())
            || empty($address->getLastName())
        ) {
            return true;
        }

        return false;
    }
}
