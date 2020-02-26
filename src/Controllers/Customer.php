<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers;

use jtl\Connector\Model\Customer as CustomerModel;
use jtl\Connector\Model\Identity;
use JtlWooCommerceConnector\Controllers\GlobalData\CustomerGroup;
use JtlWooCommerceConnector\Controllers\Traits\PullTrait;
use JtlWooCommerceConnector\Controllers\Traits\PushTrait;
use JtlWooCommerceConnector\Controllers\Traits\StatsTrait;
use JtlWooCommerceConnector\Logger\WooCommerceLogger;
use JtlWooCommerceConnector\Utilities\Germanized;
use JtlWooCommerceConnector\Utilities\Id;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;

class Customer extends BaseController
{
    use PullTrait, PushTrait, StatsTrait;
    
    public function pullData($limit)
    {
        $customers = $this->pullCustomers($limit);
        $guests = $this->pullGuests($limit - count($customers));
        
        return array_merge($customers, $guests);
    }
    
    protected function pullCustomers($limit)
    {
        $customers = [];
        
        $customerIds = $this->database->queryList(SqlHelper::customerNotLinked($limit));
        
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
                ->setNote((string)\get_user_meta($wcCustomer->get_id(), 'description', true))
                ->setCreationDate($wcCustomer->get_date_created())
                ->setCustomerGroupId(new Identity(CustomerGroup::DEFAULT_GROUP))
                ->setIsActive(true)
                ->setHasCustomerAccount(true);
            
            $firstName = $wcCustomer->get_first_name();
            if (!empty($firstName)) {
                $customer->setFirstName($wcCustomer->get_first_name());
            } else {
                $customer->setFirstName($wcCustomer->get_billing_first_name());
            }
            
            $lastName = $wcCustomer->get_last_name();
            if (!empty($lastName)) {
                $customer->setLastName($wcCustomer->get_last_name());
            } else {
                $customer->setLastName($wcCustomer->get_billing_last_name());
            }
            
            $email = $wcCustomer->get_email();
            if (!empty($email)) {
                $customer->setEMail($wcCustomer->get_email());
            } else {
                $customer->setEMail($wcCustomer->get_billing_email());
            }
            
            if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED)
                || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)
                || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO)) {
                $index = \get_user_meta($customerId, 'billing_title', true);
                $customer->setSalutation(Germanized::getInstance()->parseIndexToSalutation($index));
            }

            $customer->setVatNumber((string) $this->getVatId($customerId));

            $customers[] = $customer;
        }

        return $customers;
    }

    /**
     * @param $customerId
     * @return mixed|string
     */
    protected function getVatId($customerId)
    {
        $vatIdPlugins = [
            'b2b_uid' => SupportedPlugins::PLUGIN_B2B_MARKET,
            'billing_vat' => SupportedPlugins::PLUGIN_GERMAN_MARKET,
            'billing_vat_id' => SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO,
            'shipping_vat_id' => SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO,
        ];

        return Util::findVatId($customerId, $vatIdPlugins, function ($id, $metaKey) {
            return \get_user_meta($id, $metaKey, true);
        });
    }
    
    private function pullGuests($limit)
    {
        $customers = [];
        
        $guests = $this->database->queryList(SqlHelper::guestNotLinked($limit));
        
        foreach ($guests as $guest) {
            $order = new \WC_Order((Id::unlink($guest)[1]));
            
            $customer = (new CustomerModel)
                ->setId(new Identity(Id::link([
                    Id::GUEST_PREFIX,
                    $order->get_id(),
                ])))
                ->setCustomerNumber(Id::link([
                    Id::GUEST_PREFIX,
                    $order->get_id(),
                ]))
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
            
            if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED)
                || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)
                || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO)) {
                $index = \get_post_meta($order->get_id(), '_billing_title', true);
                $customer->setSalutation(Germanized::getInstance()->parseIndexToSalutation($index));
            }
            
            $customers[] = $customer;
        }
        
        return $customers;
    }
    
    public function pushData(CustomerModel $customer)
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
            WooCommerceLogger::getInstance()->writeLog($exception->getTraceAsString());
        }
        
        return $customer;
    }
    
    public function getStats()
    {
        $customers = (int)$this->database->queryOne(SqlHelper::customerNotLinked(null));
        $customers += (int)$this->database->queryOne(SqlHelper::guestNotLinked(null));

        return $customers;
    }
}
