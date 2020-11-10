<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Order;

use jtl\Connector\Model\CustomerOrderShippingAddress as CustomerOrderShippingAddressModel;
use jtl\Connector\Model\Identity;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Utilities\Germanized;
use JtlWooCommerceConnector\Utilities\Id;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;

class CustomerOrderShippingAddress extends BaseController
{
    public function pullData(\WC_Order $order)
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
                : Id::link([Id::GUEST_PREFIX, $order->get_id()])));

        if ($this->getPluginsManager()->get(\JtlWooCommerceConnector\Integrations\Plugins\Germanized\Germanized::class)->canBeUsed()) {
            $index = \get_post_meta($order->get_id(), '_shipping_title', true);
            $address->setSalutation(Germanized::getInstance()->parseIndexToSalutation($index));

            $postNumber = \get_post_meta($order->get_id(), '_shipping_parcelshop_post_number', true);
            if (empty($postNumber)) {
                $postNumber = $order->get_meta('_shipping_dhl_postnumber', true);
            }

            if (!empty($postNumber)) {
                $address->setExtraAddressLine((string) $postNumber);
            }
        }

        return $address;
    }
}
