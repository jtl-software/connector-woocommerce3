<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller;

use jtl\Connector\Model\Customer as CustomerModel;
use jtl\Connector\Model\Identity;
use jtl\Connector\WooCommerce\Controller\GlobalData\CustomerGroup;
use jtl\Connector\WooCommerce\Controller\Traits\PullTrait;
use jtl\Connector\WooCommerce\Controller\Traits\PushTrait;
use jtl\Connector\WooCommerce\Controller\Traits\StatsTrait;
use jtl\Connector\WooCommerce\Utility\IdConcatenation;
use jtl\Connector\WooCommerce\Utility\SQLs;

class Customer extends BaseController
{
    use PullTrait, PushTrait, StatsTrait;

    public function pullData($limit)
    {
        $includeCompletedOrders = \get_option(\JtlConnectorAdmin::OPTIONS_COMPLETED_ORDERS, 'yes') === 'yes';

        $customers = $this->pullCustomers($limit, $includeCompletedOrders);
        $guests = $this->pullGuests($limit - count($customers), $includeCompletedOrders);

        return array_merge($customers, $guests);
    }

    protected function pullCustomers($limit, $includeCompletedOrders)
    {
        $customers = [];

        $customerIds = $this->database->queryList(SQLs::customerNotLinked($limit, $includeCompletedOrders));

        foreach ($customerIds as $customerId) {
            $wcCustomer = new \WC_Customer($customerId);

            $customer = (new CustomerModel)
                ->setId(new Identity($customerId))
                ->setCustomerNumber($customerId)
                ->setCompany($wcCustomer->get_billing_company())
                ->setStreet($wcCustomer->get_billing_address_1())
                ->setExtraAddressLine($wcCustomer->get_billing_address_2())
                ->setZipCode($wcCustomer->get_billing_postcode())
                ->setCity($wcCustomer->get_billing_city())
                ->setState($wcCustomer->get_billing_state())
                ->setCountryIso($wcCustomer->get_billing_country())
                ->setPhone($wcCustomer->get_billing_phone())
                ->setCreationDate($wcCustomer->get_date_created())
                ->setCustomerGroupId(new Identity(CustomerGroup::DEFAULT_GROUP))
                ->setIsActive(true)
                ->setHasCustomerAccount(true);

            if (!empty($wcCustomer->get_first_name())) {
                $customer->setFirstName($wcCustomer->get_first_name());
            } else {
                $customer->setFirstName($wcCustomer->get_billing_first_name());
            }

            if (!empty($wcCustomer->get_last_name())) {
                $customer->setLastName($wcCustomer->get_last_name());
            } else {
                $customer->setLastName($wcCustomer->get_billing_last_name());
            }

            if (!empty($wcCustomer->get_email())) {
                $customer->setEMail($wcCustomer->get_email());
            } else {
                $customer->setEMail($wcCustomer->get_billing_email());
            }

            $customers[] = $customer;
        }

        return $customers;
    }

    private function pullGuests($limit, $includeCompletedOrders)
    {
        $users = [];

        $guests = $this->database->queryList(SQLs::guestNotLinked($limit, $includeCompletedOrders));

        foreach ($guests as $guest) {
            $order = new \WC_Order((IdConcatenation::unlink($guest)[1]));

            $users[] = (new CustomerModel)
                ->setId(new Identity(IdConcatenation::link([IdConcatenation::GUEST_PREFIX, $order->get_id()])))
                ->setCustomerNumber(IdConcatenation::link([IdConcatenation::GUEST_PREFIX, $order->get_id()]))
                ->setFirstName($order->get_billing_first_name())
                ->setLastName($order->get_billing_last_name())
                ->setCompany($order->get_billing_company())
                ->setStreet($order->get_billing_address_1())
                ->setExtraAddressLine($order->get_billing_address_2())
                ->setZipCode($order->get_billing_postcode())
                ->setCity($order->get_billing_city())
                ->setState($order->get_billing_state())
                ->setCountryIso($order->get_billing_country())
                ->setEMail($order->get_billing_email())
                ->setPhone($order->get_billing_phone())
                ->setCreationDate($order->get_date_created())
                ->setCustomerGroupId(new Identity(CustomerGroup::DEFAULT_GROUP))
                ->setIsActive(false)
                ->setHasCustomerAccount(false);
        }

        return $users;
    }

    public function pushData(CustomerModel $customer, $model)
    {
        // Only registered customers data can be updated
        if (!$customer->getHasCustomerAccount()) {
            return $customer;
        }

        try {
            $wcCustomer = new \WC_Customer((int)$customer->getId()->getEndpoint());
            $wcCustomer->set_first_name($customer->getFirstName());
            $wcCustomer->set_billing_first_name($customer->getFirstName());
            $wcCustomer->set_last_name($customer->getLastName());
            $wcCustomer->set_billing_last_name($customer->getLastName());
            $wcCustomer->set_billing_company($customer->getCompany());
            $wcCustomer->set_billing_address_1($customer->getStreet());
            $wcCustomer->set_billing_address_2($customer->getExtraAddressLine());
            $wcCustomer->set_billing_postcode($customer->getZipCode());
            $wcCustomer->set_billing_city($customer->getCity());
            $wcCustomer->set_state($customer->getState());
            $wcCustomer->set_billing_country($customer->getCountryIso());
            $wcCustomer->set_email($customer->getEMail());
            $wcCustomer->set_billing_email($customer->getEMail());
            $wcCustomer->set_billing_phone($customer->getPhone());
            $wcCustomer->save();
        } catch (\Exception $exception) {

        }

        return $customer;
    }

    public function getStats()
    {
        $includeCompletedOrders = \get_option(\JtlConnectorAdmin::OPTIONS_COMPLETED_ORDERS, 'yes') === 'yes';

        $customers = (int)$this->database->queryOne(SQLs::customerNotLinked(null, $includeCompletedOrders));
        $customers += (int)$this->database->queryOne(SQLs::guestNotLinked(null, $includeCompletedOrders));

        return $customers;
    }
}
