<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Order;

use jtl\Connector\Model\CustomerOrderBillingAddress as CustomerOrderBillingAddressModel;
use jtl\Connector\Model\Identity;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Utilities\Germanized;
use JtlWooCommerceConnector\Utilities\Id;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;

class CustomerOrderBillingAddress extends BaseController
{
    public function pullData(\WC_Order $order)
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
                : Id::link([
                    Id::GUEST_PREFIX,
                    $order->get_id(),
                ])))
            ->setVatNumber(Util::getVatIdFromOrder($order->get_id()));
        
        if (strcmp($address->getCity(), '') === 0) {
            $address->setCity(get_option('woocommerce_store_city'));
        }
        
        if (strcmp($address->getZipCode(), '') === 0) {
            $address->setZipCode(get_option('woocommerce_store_postcode'));
        }
        
        if (strcmp($address->getStreet(), '') === 0 && strcmp($address->getExtraAddressLine(), '') === 0) {
            $address->setExtraAddressLine(get_option('woocommerce_store_postcode'));
        }
        
        if (strcmp($address->getStreet(), '') === 0) {
            $address->setStreet(get_option('woocommerce_store_address'));
        }
        
        if (strcmp($address->getCountryIso(), '') === 0) {
            $address->setCountryIso(get_option('woocommerce_default_country'));
        }
        
        if ($this->getPluginsManager()->get(\JtlWooCommerceConnector\Integrations\Plugins\Germanized\Germanized::class)->canBeUsed()) {
            $index = \get_post_meta($order->get_id(), '_billing_title', true);
            $address->setSalutation(Germanized::getInstance()->parseIndexToSalutation($index));
        }

        return $address;
    }
}
