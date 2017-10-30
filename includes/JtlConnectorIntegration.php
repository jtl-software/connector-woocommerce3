<?php

if (!class_exists('JtlConnectorIntegration')) :

    class JtlConnectorIntegration extends WC_Integration
    {

        /**
         * Init and hook in the integration.
         */
        public function __construct()
        {
            $this->id = 'jtlconnector';
            $this->method_title = 'JTL-Connector';
            $this->method_description = __('An integration demo to show you how easy it is to extend WooCommerce.', 'woocommerce-integration-demo');

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables.
            $this->api_key = $this->get_option('api_key');
            $this->debug = $this->get_option('debug');

            // Actions.
            add_action('woocommerce_update_options_integration_' . $this->id, [$this, 'process_admin_options']);
        }

        /**
         * Initialize integration settings form fields.
         */
        public function init_form_fields()
        {
            $this->form_fields = [
                'api_key' => [
                    'title'       => __('API Key', 'woocommerce-integration-demo'),
                    'type'        => 'text',
                    'description' => __('Enter with your API Key. You can find this in "User Profile" drop-down (top right corner) > API Keys.', 'woocommerce-integration-demo'),
                    'desc_tip'    => true,
                    'default'     => '',
                ],
                'debug'   => [
                    'title'       => __('Debug Log', 'woocommerce-integration-demo'),
                    'type'        => 'checkbox',
                    'label'       => __('Enable logging', 'woocommerce-integration-demo'),
                    'default'     => 'no',
                    'description' => __('Log events such as API requests', 'woocommerce-integration-demo'),
                ],
            ];
        }
    }

endif;