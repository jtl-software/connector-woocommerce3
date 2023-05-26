<?php

/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Order;

use Jtl\Connector\Core\Model\CustomerOrderBillingAddress as CustomerOrderBillingAddressModel;
use Jtl\Connector\Core\Model\Identity;
use JtlWooCommerceConnector\Utilities\Germanized;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;

class CustomerOrderBillingAddressController extends CustomerOrderAddressController
{
    /**
     * @param \WC_Order $order
     * @return CustomerOrderBillingAddressModel
     * @throws \InvalidArgumentException
     */
    public function pull(\WC_Order $order): CustomerOrderBillingAddressModel
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
            ->setCustomerId($this->createCustomerId($order))
            ->setVatNumber(Util::getVatIdFromOrder($order->get_id()));

        if (\strcmp($address->getCity(), '') === 0) {
            $address->setCity(\get_option('woocommerce_store_city'));
        }

        if (\strcmp($address->getZipCode(), '') === 0) {
            $address->setZipCode(\get_option('woocommerce_store_postcode'));
        }

        if (
            \strcmp($address->getStreet(), '') === 0
            && \strcmp($address->getExtraAddressLine(), '') === 0
        ) {
            $address->setExtraAddressLine(\get_option('woocommerce_store_postcode'));
        }

        if (\strcmp($address->getStreet(), '') === 0) {
            $address->setStreet(\get_option('woocommerce_store_address'));
        }

        if (\strcmp($address->getCountryIso(), '') === 0) {
            $address->setCountryIso(\get_option('woocommerce_default_country'));
        }

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
