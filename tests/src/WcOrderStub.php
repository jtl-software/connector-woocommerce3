<?php

// phpcs:ignoreFile

declare(strict_types=1);

/**
 * Minimal WC_Order stand-in: WooCommerce is not loaded in unit tests, but the
 * mock builder needs the class and the methods it overrides.
 */

if (!class_exists('WC_Order')) {
    class WC_Order
    {
        public function get_id(): int
        {
            return 0;
        }

        public function get_meta(string $key = '', bool $single = true, string $context = 'view'): mixed
        {
            return '';
        }
    }
}
