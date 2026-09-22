<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Security;

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

        $GLOBALS['__jtlwcc_test_users'] = [];
        $GLOBALS['__jtlwcc_test_roles'] = [];
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        unset($GLOBALS['__jtlwcc_test_users'], $GLOBALS['__jtlwcc_test_roles']);

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
     * @return CustomerController
     * @throws \Exception
     */
    private function createController(): CustomerController
    {
        return new CustomerController($this->createDbMock(), $this->createUtilMock());
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
}
