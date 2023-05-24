<?php

/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Order;

use jtl\Connector\Core\Model\CustomerOrderShippingAddress as CustomerOrderShippingAddressModel;
use jtl\Connector\Core\Model\Identity;
use JtlWooCommerceConnector\Utilities\Germanized;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;

class CustomerOrderShippingAddress extends CustomerOrderAddress
{
    /**
     * @param \WC_Order $order
     * @return CustomerOrderShippingAddressModel
     */
    public function pull(\WC_Order $order): CustomerOrderShippingAddressModel
    {
        $address = (new CustomerOrderShippingAddressModel())
            ->setId(new Identity(CustomerOrder::SHIPPING_ID_PREFIX . $order->get_id()))
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

        if (
            SupportedPlugins::comparePluginVersion(
                SupportedPlugins::PLUGIN_WOOCOMMERCE,
                '>=',
                '5.6.0'
            )
        ) {
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
}
