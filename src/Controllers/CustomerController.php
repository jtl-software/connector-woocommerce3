<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Controllers;

use Jtl\Connector\Core\Controller\PullInterface;
use Jtl\Connector\Core\Controller\PushInterface;
use Jtl\Connector\Core\Controller\StatisticInterface;
use Jtl\Connector\Core\Model\AbstractModel;
use Jtl\Connector\Core\Model\Customer;
use Jtl\Connector\Core\Model\QueryFilter;
use Jtl\Connector\Core\Model\Customer as CustomerModel;
use Jtl\Connector\Core\Model\Identity;
use JtlWooCommerceConnector\Controllers\GlobalData\CustomerGroupController;
use JtlWooCommerceConnector\Utilities\Config;
use JtlWooCommerceConnector\Utilities\Germanized;
use JtlWooCommerceConnector\Utilities\Id;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;
use Psr\Log\InvalidArgumentException;

class CustomerController extends AbstractBaseController implements PullInterface, PushInterface, StatisticInterface
{
    /**
     * @param QueryFilter $query
     * @return array|AbstractModel[]
     * @throws \InvalidArgumentException
     */
    public function pull(QueryFilter $query): array
    {
        $customers = $this->pullCustomers($query->getLimit());
        $guests    = $this->pullGuests($query->getLimit() - \count($customers));

        return \array_merge($customers, $guests);
    }

    /**
     * @param int $limit
     * @return array<int, Customer>
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public function pullCustomers(int $limit): array
    {
        $customers = [];

        $customerIds = $this->db->queryList(SqlHelper::customerNotLinked($limit, $this->logger));

        foreach ($customerIds as $customerId) {
            $wcCustomer = new \WC_Customer((int)$customerId);

            /** @var bool|int|string $userMetaDescription */
            $userMetaDescription = \get_user_meta($wcCustomer->get_id(), 'description', true);

            $customer = (new CustomerModel())
                ->setId(new Identity((string)$customerId))
                ->setCustomerNumber((string)$customerId)
                ->setCompany($wcCustomer->get_billing_company())
                ->setStreet($wcCustomer->get_billing_address_1())
                ->setExtraAddressLine($wcCustomer->get_billing_address_2())
                ->setZipCode($wcCustomer->get_billing_postcode())
                ->setCity($wcCustomer->get_billing_city())
                ->setState($wcCustomer->get_billing_state())
                ->setCountryIso($wcCustomer->get_billing_country())
                ->setPhone($wcCustomer->get_billing_phone())
                ->setNote((string)$userMetaDescription)
                ->setCreationDate($wcCustomer->get_date_created())
                ->setCustomerGroupId($this->getCustomerGroupId($wcCustomer))
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

            if (
                SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED)
                || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)
                || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO)
            ) {
                /** @var bool|int|string $index */
                $index = \get_user_meta((int)$customerId, 'billing_title', true);
                $customer->setSalutation((new Germanized())->parseIndexToSalutation((string)$index));
            }

            $customer->setVatNumber(Util::getVatIdFromCustomer((int)$customerId));

            $customers[] = $customer;
        }

        return $customers;
    }

    /**
     * @param int $limit
     * @return array<int, Customer>
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    private function pullGuests(int $limit): array
    {
        $customers = [];

        $guests = $this->db->queryList(SqlHelper::guestNotLinked($limit, $this->logger));

        foreach ($guests as $guest) {
            $order = new \WC_Order((int)(Id::unlink((string)$guest)[1]));

            $customer = (new CustomerModel())
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
                ->setCustomerGroupId($this->getDefaultCustomerGroup())
                ->setIsActive(false)
                ->setHasCustomerAccount(false)
                ->setVatNumber(Util::getVatIdFromOrder($order->get_id()));

            if (
                SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED)
                || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)
                || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO)
            ) {
                /** @var bool|int|string $index */
                $index = \get_post_meta($order->get_id(), '_billing_title', true);
                $customer->setSalutation((new Germanized())->parseIndexToSalutation((string)$index));
            }

            $customers[] = $customer;
        }

        return $customers;
    }

    /**
     * @param AbstractModel ...$models
     * @return AbstractModel[]
     * @throws \InvalidArgumentException
     */
    public function push(AbstractModel ...$models): array
    {
        $returnModels = [];

        foreach ($models as $model) {
            // Only registered customers data can be updated
            /** @var Customer $model */
            if (!$model->getHasCustomerAccount()) {
                $returnModels[] = $model;
                return $returnModels;
            }

            try {
                $wcCustomer = new \WC_Customer((int)$model->getId()->getEndpoint());
                $wcCustomer->set_first_name($model->getFirstName());
                $wcCustomer->set_billing_first_name($model->getFirstName());
                $wcCustomer->set_last_name($model->getLastName());
                $wcCustomer->set_billing_last_name($model->getLastName());
                $wcCustomer->set_billing_company($model->getCompany());
                $wcCustomer->set_billing_address_1($model->getStreet());
                $wcCustomer->set_billing_address_2($model->getExtraAddressLine());
                $wcCustomer->set_billing_postcode($model->getZipCode());
                $wcCustomer->set_billing_city($model->getCity());
                $wcCustomer->set_state($model->getState());
                $wcCustomer->set_billing_country($model->getCountryIso());
                $wcCustomer->set_email($model->getEMail());
                $wcCustomer->set_billing_email($model->getEMail());
                $wcCustomer->set_billing_phone($model->getPhone());

                $customerGroup = \get_post((int)$model->getCustomerGroupId()->getEndpoint());

                if (!$customerGroup instanceof \WP_Post) {
                    throw new \InvalidArgumentException("Customer group not found");
                }

                $wcCustomer->set_role($customerGroup->post_name);

                $wcCustomer->save();

                if (
                    ($wpCustomerRole = $this->getWpCustomerRole($model->getCustomerGroupId()->getEndpoint())) !== null
                ) {
                    \wp_update_user(['ID' => $wcCustomer->get_id(), 'role' => $wpCustomerRole->name]);
                }
            } catch (\Exception $exception) {
                $this->logger->error($exception->getTraceAsString());
            }

            $returnModels[] = $model;
        }
        return $returnModels;
    }

    /**
     * @param string $customerGroupId
     * @return \WP_Role|null
     */
    protected function getWpCustomerRole(string $customerGroupId): ?\WP_Role
    {
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)) {
            $customerGroups = \get_posts(['post_type' => 'customer_groups', 'numberposts' => -1]);
            foreach ($customerGroups as $customerGroup) {
                $role = \get_role($customerGroup->post_name);
                if ($role instanceof \WP_Role && (int)$customerGroupId === $customerGroup->ID) {
                    return $role;
                }
            }
        }

        return null;
    }

    /**
     * @param QueryFilter $query
     * @return int
     * @throws InvalidArgumentException
     */
    public function statistic(QueryFilter $query): int
    {
        $customers  = (int)$this->db->queryOne(SqlHelper::customerNotLinked(null, $this->logger));
        $customers += (int)$this->db->queryOne(SqlHelper::guestNotLinked(null, $this->logger));

        return $customers;
    }

    /**
     * @param \WC_Customer $wcCustomer
     * @return Identity
     */
    protected function getCustomerGroupId(\WC_Customer $wcCustomer): Identity
    {
        $customerGroupIdentity = new Identity(CustomerGroupController::DEFAULT_GROUP);
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)) {
            $customerGroupIdentity = new Identity();

            $customerGroupName = $wcCustomer->get_role();
            if (!empty($customerGroupName) && \is_string($customerGroupName)) {
                $groups = $this->getB2BMarketCustomerGroups();
                foreach ($groups as $id => $groupName) {
                    if ($customerGroupName === $groupName) {
                        $customerGroupIdentity->setEndpoint((string)$id);
                        break;
                    }
                }
            }
        }

        return $customerGroupIdentity;
    }

    /**
     * @return Identity
     */
    protected function getDefaultCustomerGroup(): Identity
    {
        $customerGroupIdentity = new Identity(CustomerGroupController::DEFAULT_GROUP);
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)) {
            $customerGroupIdentity = new Identity();

            /** @var bool|int|string|null $defaultCustomerGroupId */
            $defaultCustomerGroupId = Config::get(Config::OPTIONS_DEFAULT_CUSTOMER_GROUP);
            $groups                 = $this->getB2BMarketCustomerGroups();
            foreach ($groups as $id => $name) {
                if ((int)$defaultCustomerGroupId === $id) {
                    $customerGroupIdentity->setEndpoint((string)$id);
                    break;
                }
            }
        }

        return $customerGroupIdentity;
    }

    /**
     * @return array<int, string>
     */
    protected function getB2BMarketCustomerGroups(): array
    {
        $customerGroups = [];
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)) {
            $bmUser = new \BM_User();
            $groups = $bmUser->get_all_customer_groups();
            foreach ($groups as $group) {
                $id   = \end($group);
                $name = \key($group);

                $customerGroups[$id] = $name;
            }
        }

        return $customerGroups;
    }
}
