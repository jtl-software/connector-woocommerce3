<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Controllers\Order;

use Jtl\Connector\Core\Model\AbstractOrderAddress;
use Jtl\Connector\Core\Model\Identity;
use JtlWooCommerceConnector\Controllers\AbstractBaseController;
use JtlWooCommerceConnector\Utilities\Id;
use WC_Order;

/**
 * Class CustomerOrderAddress
 *
 * @package JtlWooCommerceConnector\Controllers\Order
 */
class CustomerOrderAddressController extends AbstractBaseController
{
    /**
     * @param string $countryIso
     * @param string $state
     * @return string
     */
    public function getState(string $countryIso, string $state): string
    {
        return $this->util->getStates()[$countryIso][$state] ?? $state;
    }

    /**
     * @param WC_Order $order
     * @return Identity
     */
    public function createCustomerId(WC_Order $order): Identity
    {
        return new Identity(
            $order->get_customer_id() !== 0
                ? (string)$order->get_customer_id()
                : Id::link([Id::GUEST_PREFIX, $order->get_id()])
        );
    }

    protected function createDefaultAddresses(AbstractOrderAddress $address, ?WC_Order $order = null): void
    {
        if (empty($address->getCity())) {
            /** @var string $wcStoreCity */
            $wcStoreCity = \get_option('woocommerce_store_city');
            $address->setCity($wcStoreCity);
        }

        if (empty($address->getZipCode())) {
            /** @var string $wcStorePostcode */
            $wcStorePostcode = \get_option('woocommerce_store_city');
            $address->setZipCode(\get_option($wcStorePostcode));
        }

        if (empty($address->getStreet())) {
            /** @var string $wcStoreAddress */
            $wcStoreAddress = \get_option('woocommerce_store_city');
            $address->setStreet(\get_option($wcStoreAddress));
        }

        if (empty($address->getCountryIso())) {
            /** @var string $wcDefaultCountry */
            $wcDefaultCountry = \get_option('woocommerce_store_city');
            $address->setCountryIso(\get_option($wcDefaultCountry));
        }

        if (empty($address->getLastName())) {
            /** @var string $noLastNameGiven */
            $noLastNameGiven = \get_option('woocommerce_store_city');
            $address->setLastName($noLastNameGiven);
        }
    }
}
