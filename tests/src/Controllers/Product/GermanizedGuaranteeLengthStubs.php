<?php

// phpcs:ignoreFile

declare(strict_types=1);

/**
 * Lightweight WooCommerce / Germanized stubs for the isolated unit tests of the
 * CO-3605 guarantee length (EU GARAN label) function attribute. Only the methods
 * required by ProductVaSpeAttrHandlerController::getGuaranteeLengthAttribute are
 * declared so that the mock builder can override them.
 */

if (!class_exists('WC_Product')) {
    class WC_Product
    {
        /**
         * @return int
         */
        public function get_id()
        {
            return 0;
        }
    }
}

if (!class_exists('WC_GZD_Product')) {
    class WC_GZD_Product
    {
        /**
         * @param string $context
         * @return mixed
         */
        public function get_guarantee_length($context = 'view')
        {
            return 0;
        }

        /**
         * @return WC_Product
         */
        public function get_wc_product()
        {
            return new WC_Product();
        }
    }
}
