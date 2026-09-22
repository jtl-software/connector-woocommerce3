<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Security;

use Jtl\Connector\Core\Model\Customer;
use Jtl\Connector\Core\Model\Identity;
use JtlWooCommerceConnector\Controllers\CustomerController;
use JtlWooCommerceConnector\Tests\AbstractTestCase;

require_once __DIR__ . '/WpUserRoleStubs.php';

/**
 * Verifies the object-level authorization added to CustomerController::push()
 * that prevents an attacker from hijacking privileged (administrator) accounts
 * or escalating a customer to a privileged role.
 *
 * @covers \JtlWooCommerceConnector\Controllers\CustomerController
 */
class CustomerControllerAuthorizationTest extends AbstractTestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['__jtlwcc_test_users']                = [];
        $GLOBALS['__jtlwcc_test_roles']                = [];
        $GLOBALS['__jtlwcc_test_posts']                = [];
        $GLOBALS['__jtlwcc_test_wc_customer_calls']    = [
            'construct' => [],
            'save'      => [],
            'set_role'  => [],
        ];
        $GLOBALS['__jtlwcc_test_wp_update_user_calls'] = [];
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        unset(
            $GLOBALS['__jtlwcc_test_users'],
            $GLOBALS['__jtlwcc_test_roles'],
            $GLOBALS['__jtlwcc_test_posts'],
            $GLOBALS['__jtlwcc_test_wc_customer_calls'],
            $GLOBALS['__jtlwcc_test_wp_update_user_calls']
        );

        parent::tearDown();
    }

    /**
     * @param int                 $id
     * @param array<int, string>  $roles
     * @param array<string, bool> $capabilities
     * @return void
     */
    private function registerUser(int $id, array $roles = [], array $capabilities = []): void
    {
        $user          = new \WP_User();
        $user->ID      = $id;
        $user->roles   = $roles;
        $user->allcaps = $capabilities;

        $GLOBALS['__jtlwcc_test_users'][$id] = $user;
    }

    /**
     * @param string              $slug
     * @param array<string, bool> $capabilities
     * @return void
     */
    private function registerRole(string $slug, array $capabilities = []): void
    {
        $role               = new \WP_Role();
        $role->name         = $slug;
        $role->capabilities = $capabilities;

        $GLOBALS['__jtlwcc_test_roles'][$slug] = $role;
    }

    /**
     * @param int    $id
     * @param string $slug
     * @return void
     */
    private function registerCustomerGroupPost(int $id, string $slug): void
    {
        $post            = new \WP_Post();
        $post->ID        = $id;
        $post->post_name = $slug;

        $GLOBALS['__jtlwcc_test_posts'][$id] = $post;
    }

    /**
     * @param int    $endpointId
     * @param string $customerGroupId
     * @return Customer
     */
    private function createCustomerModel(int $endpointId, string $customerGroupId = '100'): Customer
    {
        return (new Customer())
            ->setId(new Identity((string)$endpointId))
            ->setHasCustomerAccount(true)
            ->setCustomerGroupId(new Identity($customerGroupId))
            ->setFirstName('Jane')
            ->setLastName('Doe')
            ->setCompany('Example Inc.')
            ->setStreet('Main Street 1')
            ->setExtraAddressLine('')
            ->setZipCode('12345')
            ->setCity('Sample City')
            ->setState('NW')
            ->setCountryIso('DE')
            ->setEMail('jane.doe@example.invalid')
            ->setPhone('123456789');
    }

    /**
     * @param \WP_Role|null $wpCustomerRole
     * @throws \Exception
     * @return CustomerController
     */
    private function createController(?\WP_Role $wpCustomerRole = null): CustomerController
    {
        if ($wpCustomerRole === null) {
            return new CustomerController($this->createDbMock(), $this->createUtilMock());
        }

        return new class ($this->createDbMock(), $this->createUtilMock(), $wpCustomerRole) extends CustomerController {
            private ?\WP_Role $wpCustomerRole;

            /**
             * @param \JtlWooCommerceConnector\Utilities\Db   $db
             * @param \JtlWooCommerceConnector\Utilities\Util $util
             * @param \WP_Role|null                           $wpCustomerRole
             * @throws \Exception
             */
            public function __construct(
                \JtlWooCommerceConnector\Utilities\Db $db,
                \JtlWooCommerceConnector\Utilities\Util $util,
                ?\WP_Role $wpCustomerRole
            ) {
                $this->wpCustomerRole = $wpCustomerRole;
                parent::__construct($db, $util);
            }

            /**
             * @param string $customerGroupId
             * @return \WP_Role|null
             */
            protected function getWpCustomerRole(string $customerGroupId): ?\WP_Role
            {
                return $this->wpCustomerRole;
            }
        };
    }

    /**
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     * @covers \JtlWooCommerceConnector\Controllers\CustomerController::isProtectedUser
     */
    public function testIsProtectedUserRejectsAdministratorRole(): void
    {
        $this->registerUser(1, ['administrator']);

        $this->assertTrue(
            (bool)$this->invokeMethodFromObject($this->createController(), 'isProtectedUser', 1)
        );
    }

    /**
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     * @covers \JtlWooCommerceConnector\Controllers\CustomerController::isProtectedUser
     */
    public function testIsProtectedUserRejectsPrivilegedCapability(): void
    {
        $this->registerUser(5, ['customer'], ['manage_options' => true]);

        $this->assertTrue(
            (bool)$this->invokeMethodFromObject($this->createController(), 'isProtectedUser', 5)
        );
    }

    /**
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     * @covers \JtlWooCommerceConnector\Controllers\CustomerController::isProtectedUser
     */
    public function testIsProtectedUserRejectsShopManagerCapability(): void
    {
        $this->registerUser(6, ['shop_manager'], ['manage_woocommerce' => true]);

        $this->assertTrue(
            (bool)$this->invokeMethodFromObject($this->createController(), 'isProtectedUser', 6)
        );
    }

    /**
     * An editor holds content-administration capabilities but none of the
     * classic administrator/shop-manager capabilities; it must still be
     * treated as privileged.
     *
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     * @covers \JtlWooCommerceConnector\Controllers\CustomerController::isProtectedUser
     */
    public function testIsProtectedUserRejectsEditorRole(): void
    {
        $this->registerUser(8, ['editor'], [
            'read'              => true,
            'edit_posts'        => true,
            'edit_others_posts' => true,
            'publish_posts'     => true,
            'manage_categories' => true,
            'moderate_comments' => true,
            'unfiltered_html'   => true,
        ]);

        $this->assertTrue(
            (bool)$this->invokeMethodFromObject($this->createController(), 'isProtectedUser', 8)
        );
    }

    /**
     * A contributor only holds edit_posts/delete_posts but must still be
     * classified as privileged.
     *
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     * @covers \JtlWooCommerceConnector\Controllers\CustomerController::isProtectedUser
     */
    public function testIsProtectedUserRejectsContributorRole(): void
    {
        $this->registerUser(9, ['contributor'], [
            'read'         => true,
            'edit_posts'   => true,
            'delete_posts' => true,
        ]);

        $this->assertTrue(
            (bool)$this->invokeMethodFromObject($this->createController(), 'isProtectedUser', 9)
        );
    }

    /**
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     * @covers \JtlWooCommerceConnector\Controllers\CustomerController::isProtectedUser
     */
    public function testIsProtectedUserAllowsRegularCustomer(): void
    {
        $this->registerUser(7, ['customer'], ['read' => true]);

        $this->assertFalse(
            (bool)$this->invokeMethodFromObject($this->createController(), 'isProtectedUser', 7)
        );
    }

    /**
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     * @covers \JtlWooCommerceConnector\Controllers\CustomerController::isProtectedUser
     */
    public function testIsProtectedUserAllowsNewCustomer(): void
    {
        $this->assertFalse(
            (bool)$this->invokeMethodFromObject($this->createController(), 'isProtectedUser', 0)
        );
    }

    /**
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     * @covers \JtlWooCommerceConnector\Controllers\CustomerController::isProtectedUser
     */
    public function testIsProtectedUserAllowsUnknownUser(): void
    {
        $this->assertFalse(
            (bool)$this->invokeMethodFromObject($this->createController(), 'isProtectedUser', 999)
        );
    }

    /**
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     * @covers \JtlWooCommerceConnector\Controllers\CustomerController::isAssignableCustomerRole
     */
    public function testIsAssignableCustomerRoleRejectsAdministrator(): void
    {
        $this->registerRole('administrator', ['manage_options' => true]);

        $this->assertFalse(
            (bool)$this->invokeMethodFromObject(
                $this->createController(),
                'isAssignableCustomerRole',
                'administrator'
            )
        );
    }

    /**
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     * @covers \JtlWooCommerceConnector\Controllers\CustomerController::isAssignableCustomerRole
     */
    public function testIsAssignableCustomerRoleRejectsPrivilegedRole(): void
    {
        $this->registerRole('sneaky', ['edit_users' => true]);

        $this->assertFalse(
            (bool)$this->invokeMethodFromObject($this->createController(), 'isAssignableCustomerRole', 'sneaky')
        );
    }

    /**
     * A role carrying editor-level content-administration capabilities must not
     * be assignable to a customer.
     *
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     * @covers \JtlWooCommerceConnector\Controllers\CustomerController::isAssignableCustomerRole
     */
    public function testIsAssignableCustomerRoleRejectsEditorLikeRole(): void
    {
        $this->registerRole('shop_editor', [
            'read'              => true,
            'edit_others_posts' => true,
            'publish_pages'     => true,
        ]);

        $this->assertFalse(
            (bool)$this->invokeMethodFromObject($this->createController(), 'isAssignableCustomerRole', 'shop_editor')
        );
    }

    /**
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     * @covers \JtlWooCommerceConnector\Controllers\CustomerController::isAssignableCustomerRole
     */
    public function testIsAssignableCustomerRoleRejectsUnknownRole(): void
    {
        $this->assertFalse(
            (bool)$this->invokeMethodFromObject($this->createController(), 'isAssignableCustomerRole', 'ghost')
        );
    }

    /**
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     * @covers \JtlWooCommerceConnector\Controllers\CustomerController::isAssignableCustomerRole
     */
    public function testIsAssignableCustomerRoleRejectsEmptySlug(): void
    {
        $this->assertFalse(
            (bool)$this->invokeMethodFromObject($this->createController(), 'isAssignableCustomerRole', '')
        );
    }

    /**
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     * @covers \JtlWooCommerceConnector\Controllers\CustomerController::isAssignableCustomerRole
     */
    public function testIsAssignableCustomerRoleAllowsPlainCustomerRole(): void
    {
        $this->registerRole('customer', ['read' => true]);

        $this->assertTrue(
            (bool)$this->invokeMethodFromObject($this->createController(), 'isAssignableCustomerRole', 'customer')
        );
    }

    /**
     * @return void
     * @throws \Exception
     * @covers \JtlWooCommerceConnector\Controllers\CustomerController::push
     */
    public function testPushDoesNotConstructOrSaveProtectedUser(): void
    {
        $this->registerUser(11, ['administrator']);

        $controller = $this->createController();
        $model      = $this->createCustomerModel(11);

        $this->assertSame([$model], $controller->push($model));
        $this->assertSame([], $GLOBALS['__jtlwcc_test_wc_customer_calls']['construct']);
        $this->assertSame([], $GLOBALS['__jtlwcc_test_wc_customer_calls']['save']);
        $this->assertSame([], $GLOBALS['__jtlwcc_test_wc_customer_calls']['set_role']);
        $this->assertSame([], $GLOBALS['__jtlwcc_test_wp_update_user_calls']);
    }

    /**
     * @return void
     * @throws \Exception
     * @covers \JtlWooCommerceConnector\Controllers\CustomerController::push
     */
    public function testPushDoesNotPassPrivilegedRoleToRoleAssignmentApis(): void
    {
        $this->registerUser(12, ['customer'], ['read' => true]);
        $this->registerCustomerGroupPost(100, 'administrator');
        $this->registerRole('administrator', ['manage_options' => true]);

        $role       = new \WP_Role();
        $role->name = 'administrator';

        $controller = $this->createController($role);
        $model      = $this->createCustomerModel(12);

        $this->assertSame([$model], $controller->push($model));
        $this->assertSame([12], $GLOBALS['__jtlwcc_test_wc_customer_calls']['construct']);
        $this->assertSame([], $GLOBALS['__jtlwcc_test_wc_customer_calls']['set_role']);
        $this->assertSame([12], $GLOBALS['__jtlwcc_test_wc_customer_calls']['save']);
        $this->assertSame([], $GLOBALS['__jtlwcc_test_wp_update_user_calls']);
    }
}
