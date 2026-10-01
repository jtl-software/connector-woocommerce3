<?php

// phpcs:ignoreFile

declare(strict_types=1);

/**
 * Lightweight WordPress user/role stubs and function shims for isolated unit
 * tests of CustomerController's object-level authorization. Test state is passed
 * through the $GLOBALS keys __jtlwcc_test_users, __jtlwcc_test_roles,
 * __jtlwcc_test_posts, __jtlwcc_test_wc_customer_calls and
 * __jtlwcc_test_wp_update_user_calls.
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

if (!class_exists('WP_Post')) {
    class WP_Post
    {
        public int $ID = 0;

        public string $post_name = '';
    }
}

if (!class_exists('WC_Customer')) {
    class WC_Customer
    {
        private int $id = 0;

        public function __construct(int $id = 0)
        {
            $this->id = $id;
            $GLOBALS['__jtlwcc_test_wc_customer_calls']['construct'][] = $id;
        }

        /**
         * @param string $name
         * @param array<int, mixed> $arguments
         * @return $this|null
         */
        public function __call(string $name, array $arguments): ?self
        {
            if (\str_starts_with($name, 'set_')) {
                $GLOBALS['__jtlwcc_test_wc_customer_calls'][$name][] = $arguments;
                return $this;
            }

            return null;
        }

        /**
         * @param string $role
         * @return void
         */
        public function set_role(string $role): void
        {
            $GLOBALS['__jtlwcc_test_wc_customer_calls']['set_role'][] = $role;
        }

        /**
         * @return int
         */
        public function save(): int
        {
            $GLOBALS['__jtlwcc_test_wc_customer_calls']['save'][] = $this->id;
            return $this->id;
        }

        /**
         * @return int
         */
        public function get_id(): int
        {
            return $this->id;
        }
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

if (!function_exists('get_post')) {
    function get_post(int $postId): \WP_Post|null
    {
        /** @var array<int, \WP_Post> $posts */
        $posts = $GLOBALS['__jtlwcc_test_posts'] ?? [];

        return $posts[$postId] ?? null;
    }
}

if (!function_exists('wp_update_user')) {
    function wp_update_user(array $userdata): int
    {
        $GLOBALS['__jtlwcc_test_wp_update_user_calls'][] = $userdata;

        return (int)($userdata['ID'] ?? 0);
    }
}
