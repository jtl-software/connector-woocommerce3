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
use WhiteCube\Lingua\Service;

class CustomerController extends AbstractBaseController implements PullInterface, PushInterface, StatisticInterface
{
    /**
     * Capabilities that identify a privileged (non-customer) account. A push
     * targeting such an account is rejected, and a customer group must never
     * resolve to a role granting any of these capabilities.
     *
     * This intentionally covers not only administrator/shop-manager level
     * capabilities but every content-administration capability that lifts an
     * account above a plain shop customer (contributor, author and editor).
     * A default WooCommerce customer only holds "read", so no legitimate
     * customer role is affected, while privileged built-in roles such as
     * "editor" (which holds none of the classic admin capabilities) can no
     * longer slip through.
     */
    private const array PROTECTED_CAPABILITIES = [
        // Site, options and core administration
        'manage_options',
        'manage_woocommerce',
        'edit_dashboard',
        'update_core',
        'export',
        'import',
        'customize',
        'edit_theme_options',
        // Plugin and theme administration
        'activate_plugins',
        'edit_plugins',
        'install_plugins',
        'update_plugins',
        'delete_plugins',
        'switch_themes',
        'edit_themes',
        'install_themes',
        'update_themes',
        'delete_themes',
        // User administration
        'edit_users',
        'delete_users',
        'create_users',
        'list_users',
        'promote_users',
        'remove_users',
        'add_users',
        // Multisite / network administration
        'manage_network',
        'manage_sites',
        'manage_network_users',
        'manage_network_plugins',
        'manage_network_themes',
        'manage_network_options',
        // Content administration (contributor, author, editor)
        'edit_posts',
        'edit_others_posts',
        'edit_published_posts',
        'edit_private_posts',
        'publish_posts',
        'delete_posts',
        'delete_others_posts',
        'delete_published_posts',
        'delete_private_posts',
        'read_private_posts',
        'edit_pages',
        'edit_others_pages',
        'edit_published_pages',
        'edit_private_pages',
        'publish_pages',
        'delete_pages',
        'delete_others_pages',
        'delete_published_pages',
        'delete_private_pages',
        'read_private_pages',
        'manage_categories',
        'manage_links',
        'moderate_comments',
        'unfiltered_html',
    ];

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

            /** @var string|false $customerLanguage */
            $customerLanguage = \get_user_meta($wcCustomer->get_id(), 'locale', true);
            if ($customerLanguage !== '' && $customerLanguage !== false) {
                $customer->setLanguageIso(Service::create($customerLanguage)->toISO_639_2b());
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
                $endpointId = (int)$model->getId()->getEndpoint();

                if ($this->isProtectedUser($endpointId)) {
                    $this->logger->warning(
                        'Rejected customer push targeting a protected (privileged) user account with id ({id})',
                        ['id' => $endpointId]
                    );
                    $returnModels[] = $model;
                    continue;
                }

                $wcCustomer = new \WC_Customer($endpointId);
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

                if ($this->isAssignableCustomerRole($customerGroup->post_name)) {
                    $wcCustomer->set_role($customerGroup->post_name);
                }

                $wcCustomer->save();

                if (
                    ($wpCustomerRole = $this->getWpCustomerRole($model->getCustomerGroupId()->getEndpoint())) !== null
                    && $this->isAssignableCustomerRole($wpCustomerRole->name)
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
     * Determines whether the given user id belongs to a privileged account that
     * must not be modified through a customer push (e.g. an administrator).
     *
     * @param int $userId
     * @return bool
     */
    protected function isProtectedUser(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $user = \get_user_by('id', $userId);

        if (!$user instanceof \WP_User) {
            return false;
        }

        if (\in_array('administrator', (array)$user->roles, true)) {
            return true;
        }

        foreach (self::PROTECTED_CAPABILITIES as $capability) {
            if (\user_can($user, $capability)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determines whether the given role slug may be assigned to a customer. Only
     * existing, non-privileged roles are allowed; this prevents deriving a
     * privileged role (e.g. "administrator") from an attacker-controlled post
     * slug via the customer group.
     *
     * @param string $roleSlug
     * @return bool
     */
    protected function isAssignableCustomerRole(string $roleSlug): bool
    {
        if ($roleSlug === '' || $roleSlug === 'administrator') {
            return false;
        }

        $role = \get_role($roleSlug);

        if (!$role instanceof \WP_Role) {
            return false;
        }

        foreach (self::PROTECTED_CAPABILITIES as $capability) {
            if (!empty($role->capabilities[$capability])) {
                return false;
            }
        }

        return true;
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
