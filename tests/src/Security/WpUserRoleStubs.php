<?php

// phpcs:ignoreFile

declare(strict_types=1);

/**
 * Lightweight WordPress user/role stubs and function shims for isolated unit
 * tests of CustomerController's object-level authorization. Test state is passed
 * through the $GLOBALS keys __jtlwcc_test_users and __jtlwcc_test_roles.
 */

if (!class_exists('WP_User')) {
    class WP_User
    {
        public int $ID = 0;

        /** @var array<int, string> */
        public array $roles = [];

        /** @var array<string, bool> */
        public array $allcaps = [];
    }
}

if (!class_exists('WP_Role')) {
    class WP_Role
    {
        public string $name = '';

        /** @var array<string, bool> */
        public array $capabilities = [];
    }
}

if (!function_exists('get_user_by')) {
    function get_user_by(string $field, int|string $value): \WP_User|false
    {
        /** @var array<int, \WP_User> $users */
        $users = $GLOBALS['__jtlwcc_test_users'] ?? [];

        return $users[(int)$value] ?? false;
    }
}

if (!function_exists('user_can')) {
    function user_can(\WP_User|int $user, string $capability): bool
    {
        if ($user instanceof \WP_User) {
            return !empty($user->allcaps[$capability]);
        }

        return false;
    }
}

if (!function_exists('get_role')) {
    function get_role(string $role): ?\WP_Role
    {
        /** @var array<string, \WP_Role> $roles */
        $roles = $GLOBALS['__jtlwcc_test_roles'] ?? [];

        return $roles[$role] ?? null;
    }
}
