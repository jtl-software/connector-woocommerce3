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

    /**
     * @param AbstractOrderAddress $address
     * @param WC_Order|null        $order
     * @return void
     */
    protected function createDefaultAddresses(AbstractOrderAddress $address, ?WC_Order $order = null): void
    {
        if (empty($address->getCity())) {
            $wcStoreCity = $this->safeGetOption('woocommerce_store_city');
            $address->setCity($wcStoreCity);
        }

        if (empty($address->getZipCode())) {
            $wcStorePostcode = $this->safeGetOption('woocommerce_store_postcode');
            $wcStoreZipCode  = $this->safeGetOption($wcStorePostcode);
            $address->setZipCode($wcStoreZipCode);
        }

        if (empty($address->getStreet())) {
            $wcStoreAddress = $this->safeGetOption('woocommerce_store_address');
            $wcStoreStreet  = $this->safeGetOption($wcStoreAddress);
            $address->setStreet($wcStoreStreet);
        }

        if (empty($address->getCountryIso())) {
            $wcDefaultCountry = $this->safeGetOption('woocommerce_default_country');
            $wcCountryIso     = $this->safeGetOption($wcDefaultCountry);
            $address->setCountryIso($wcCountryIso);
        }

        if (empty($address->getLastName())) {
            $noLastNameGiven = $this->safeGetOption('NoLastNameGiven');
            $address->setLastName($noLastNameGiven);
        }
    }

    /**
     * @param string $optionName
     *
     * @return string
     */
    private function safeGetOption(string $optionName): string
    {
        $value = \get_option($optionName);

        if (\is_string($value)) {
            return $value;
        }

        return '';
    }
}
