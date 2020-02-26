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
                ])));
        
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
        
        if (
            SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO)
        ) {
            $index = \get_post_meta($order->get_id(), '_billing_title', true);
            $address->setSalutation(Germanized::getInstance()->parseIndexToSalutation($index));
        }

        $address->setVatNumber((string)$this->getVatId($order->get_id()));

        return $address;
    }

    /**
     * @param $orderId
     * @return mixed|string
     */
    protected function getVatId($orderId)
    {
        $vatIdPlugins = [
            'billing_vat' => SupportedPlugins::PLUGIN_GERMAN_MARKET,
            'billing_vat_id' => SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO,
        ];

        return Util::findVatId($orderId, $vatIdPlugins, function ($id, $metaKey) {
            return \get_post_meta($id, $metaKey, true);
        });
    }
}
