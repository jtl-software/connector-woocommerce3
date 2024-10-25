<?php

use Jtl\Connector\Core\Definition\IdentityType;
use Jtl\Connector\Core\Exception\MissingRequirementException;
use Jtl\Connector\Core\System\Check;
use Jtl\Connector\Core\Model\CustomerGroup as CustomerGroupModel;
use Jtl\Connector\Core\Model\CustomerGroupI18n as CustomerGroupI18nModel;
use JtlWooCommerceConnector\Controllers\GlobalData\CustomerGroupController;
use JtlWooCommerceConnector\Utilities\Config;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\Id;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final class JtlConnectorAdmin //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    private static bool $initiated = false;

    public function __construct()
    {
        if (! defined('ABSPATH')) {
            exit;
        }
    }
    // <editor-fold defaultstate="collapsed" desc="Activation">

    /**
     * @return void
     * @throws ParseException
     * @throws UnexpectedValueException
     */
    public static function plugin_activation(): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        global $woocommerce;

        $version      = $woocommerce->version;
        $buildVersion = Config::getBuildVersion();

        clearConnectorCache();

        if (jtlwcc_woocommerce_deactivated()) {
            jtlwcc_deactivate_plugin();
            add_action('admin_notices', 'jtlwcc_woocommerce_not_activated');
        } elseif (
            version_compare(
                $version,
                trim(Yaml::parseFile(JTLWCC_CONNECTOR_DIR . '/build-config.yaml')['min_wc_version']),
                '<'
            )
        ) {
            jtlwcc_deactivate_plugin();
            add_action('admin_notices', 'jtlwcc_wrong_woocommerce_version');
        }

        try {
            Check::run();
            self::activate_linking();
            self::initDefaultConfigValues($buildVersion);
        } catch (MissingRequirementException $exc) {
            if (is_admin() && ( ! defined('DOING_AJAX') || ! DOING_AJAX )) {
                jtlwcc_deactivate_plugin();
                wp_die($exc->getMessage());
            } else {
                return;
            }
        }
    }

    /**
     * @return void
     * @throws \Psr\Log\InvalidArgumentException
     */
    private static function activate_linking(): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        global $wpdb;

        $db = new Db($wpdb);

        $createQuery = '
            CREATE TABLE IF NOT EXISTS `%s` (
                `endpoint_id` BIGINT(20) unsigned NOT NULL,
                `host_id` INT(10) unsigned NOT NULL,
                PRIMARY KEY (`endpoint_id`, `host_id`),
                INDEX (`host_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci';

        $dropOldQuery = '
          DROP TABLE IF EXISTS `%s`;
        ';

        $oldPrefix = 'jtl_connector_link_';
        $prefix    = $wpdb->prefix . $oldPrefix;

        $existingTables = self::getOldDatabaseTables();

        $tables = [
            'category',
            'category_level',
            'crossselling',
            'crossselling_group',
            'currency',
            //not implemented yet
            'customer',
            'customer_group',
            //not implemented yet
            'image',
            'language',
            //not implemented yet
            'manufacturer',
            'measurement_unit',
            //not implemented yet
            'order',
            'payment',
            'product',
            'product_checksum',
            'shipping_class',
            'shipping_method',
            //not implemented yet
            'specific',
            'specific_value',
            'tax_class',
        ];
        //self::activate_category_tree();
        foreach ($tables as $key => $table) {
            $oldExists = in_array($oldPrefix . $table, $existingTables);
            $newExists = in_array($prefix . $table, $existingTables);

            if (strcmp('category_level', $table) === 0 || strcmp('product_checksum', $table) === 0) {
                //repair bug
                if (in_array($prefix . $table, $existingTables)) {
                    self::renameTable($prefix . $table, substr($prefix, 0, - 5) . $table);
                }

                $oldPrefix = substr($oldPrefix, 0, - 5);
                $prefix    = substr($prefix, 0, - 5);
                $oldExists = in_array($oldPrefix . $table, $existingTables);
                $newExists = in_array($prefix . $table, $existingTables);
            }

            if ($oldExists && $newExists) {
                $wpdb->query(sprintf($dropOldQuery, $oldPrefix . $table));
            } elseif (! $oldExists && ! $newExists) {
                if (strcmp($table, 'category_level') === 0) {
                    self::activate_category_tree($db);
                } elseif (strcmp($table, 'product_checksum') === 0) {
                    self::activate_checksum($prefix);
                } elseif (strcmp($table, 'customer') === 0) {
                    self::createCustomerLinkingTable();
                } elseif (strcmp($table, 'customer_group') === 0) {
                    self::createCustomerGroupLinkingTable();
                } elseif (strcmp($table, 'image') === 0) {
                    self::createImageLinkingTable();
                } elseif (strcmp($table, 'manufacturer') === 0) {
                    self::createManufacturerLinkingTable($db);
                } elseif (strcmp($table, 'tax_class') === 0) {
                    self::createTaxClassLinkingTable();
                } else {
                    $wpdb->query(sprintf($createQuery, $prefix . $table));
                }
            } elseif ($oldExists && ! $newExists) {
                if (strcmp($table, 'category_level') === 0 || strcmp($table, 'product_checksum') === 0) {
                    $tmp    = $prefix;
                    $prefix = substr($prefix, 0, - 5);
                    self::renameTable($oldPrefix . $table, $prefix . $table);
                    $prefix = $tmp;
                } else {
                    self::renameTable($oldPrefix . $table, $prefix . $table);
                }
            }
            //reset values
            $oldPrefix = 'jtl_connector_link_';
            $prefix    = $wpdb->prefix . $oldPrefix;
        }

        self::add_constraints_for_multi_linking_tables($prefix, $db);
    }

    /**
     * @return array
     */
    private static function getOldDatabaseTables(): array
    {
        global $wpdb;
        $existingTables = [];
        $tableDataSet   = $wpdb->get_results('SHOW TABLES');

        if (count($tableDataSet) !== 0) {
            foreach ($tableDataSet as $tableData) {
                foreach ($tableData as $table) {
                    $existingTables[] = $table;
                }
            }
        }

        return array_values($existingTables);
    }

    /**
     * @param $oldName
     * @param $newName
     *
     * @return void
     */
    private static function renameTable($oldName, $newName): void
    {
        global $wpdb;

        $query = 'RENAME TABLE %s TO %s;';

        $sql = sprintf($query, $oldName, $newName);
        $wpdb->query($sql);
    }

    /**
     * @return void
     * @throws \Psr\Log\InvalidArgumentException
     * @throws \Psr\Log\InvalidArgumentException
     */
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    private static function activate_category_tree(Db $db): void
    {
        $wpdb   = $db->getWpDb();
        $prefix = $wpdb->prefix . 'jtl_connector_';
        $engine = $wpdb->get_var(sprintf(
            "SELECT ENGINE
            FROM information_schema.TABLES
            WHERE TABLE_NAME = '{$wpdb->terms}' AND TABLE_SCHEMA = '%s'",
            DB_NAME
        ));

        $constraint = '';

        if ($engine === 'InnoDB') {
            if (!$db->checkIfFKExists($prefix . 'category_level', 'jtl_connector_category_level1')) {
                $constraint = ", CONSTRAINT `jtl_connector_category_level1` FOREIGN KEY (`category_id`) 
                               REFERENCES {$wpdb->terms} (`term_id`) ON DELETE CASCADE ON UPDATE NO ACTION";
            }
        }

        $wpdb->query("
            CREATE TABLE IF NOT EXISTS `{$prefix}category_level` (
                `category_id` BIGINT(20) unsigned NOT NULL,
                `level` int(10) unsigned NOT NULL,
                `sort` int(10) unsigned NOT NULL,
                PRIMARY KEY (`category_id`),
                INDEX (`level`) {$constraint}
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
    }

    /**
     * @param $prefix
     *
     * @return void
     */
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    private static function activate_checksum($prefix): void
    {
        global $wpdb;

        $engine = $wpdb->get_var(sprintf(
            "SELECT ENGINE
            FROM information_schema.TABLES
            WHERE TABLE_NAME = '{$wpdb->posts}' AND TABLE_SCHEMA = '%s'",
            DB_NAME
        ));

        if ($engine === 'InnoDB') {
            $constraint = ", CONSTRAINT `jtl_connector_product_checksum1` FOREIGN KEY (`product_id`) 
                           REFERENCES {$wpdb->posts} (`ID`) ON DELETE CASCADE ON UPDATE NO ACTION";
        } else {
            $constraint = '';
        }

        $wpdb->query("
            CREATE TABLE IF NOT EXISTS `{$prefix}product_checksum` (
                `product_id` BIGINT(20) unsigned NOT NULL,
                `type` tinyint unsigned NOT NULL,
                `checksum` varchar(255) NOT NULL,
                PRIMARY KEY (`product_id`) {$constraint}
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
    }

    /**
     * @return void
     */
    private static function createCustomerLinkingTable(): void
    {
        global $wpdb;
        $wpdb->query('
            CREATE TABLE IF NOT EXISTS `jtl_connector_link_customer` (
                `endpoint_id` VARCHAR(255) NOT NULL,
                `host_id` INT(10) unsigned NOT NULL,
                `is_guest` BIT,
                PRIMARY KEY (`endpoint_id`, `host_id`, `is_guest`),
                INDEX (`host_id`, `is_guest`),
                INDEX (`endpoint_id`, `is_guest`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci');
    }

    /**
     * @return void
     */
    private static function createCustomerGroupLinkingTable(): void
    {
        global $wpdb;
        $wpdb->query('
            CREATE TABLE IF NOT EXISTS `jtl_connector_link_customer_group` (
                `endpoint_id` VARCHAR(255) NOT NULL,
                `host_id` INT(10) unsigned NOT NULL,
                PRIMARY KEY (`endpoint_id`, `host_id`),
                INDEX (`host_id`),
                INDEX (`endpoint_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci');
    }

    /**
     * @return void
     */
    private static function createImageLinkingTable(): void
    {
        global $wpdb;
        $wpdb->query('
            CREATE TABLE IF NOT EXISTS `jtl_connector_link_image` (
                `endpoint_id` VARCHAR(255) NOT NULL,
                `host_id` INT(10) NOT NULL,
                `type` INT unsigned NOT NULL,
                PRIMARY KEY (`endpoint_id`, `host_id`, `type`),
                INDEX (`host_id`, `type`),
                INDEX (`endpoint_id`, `type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci');
    }

    /**
     * @return void
     */
    private static function createManufacturerLinkingTable(Db $db): void
    {
        global $wpdb;

        $query = '
            CREATE TABLE IF NOT EXISTS `%s` (
                `endpoint_id` BIGINT(20) unsigned NOT NULL,
                `host_id` INT(10) unsigned NOT NULL,
                PRIMARY KEY (`endpoint_id`, `host_id`),
                INDEX (`host_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci';

        $wpdb->query(sprintf($query, $wpdb->prefix . 'jtl_connector_link_manufacturer'));

        $engine = $wpdb->get_var(sprintf(
            "SELECT ENGINE
            FROM information_schema.TABLES
            WHERE TABLE_NAME = '{$wpdb->posts}' AND TABLE_SCHEMA = '%s'",
            DB_NAME
        ));

        if ($engine === 'InnoDB') {
            if (
                !$db->checkIfFKExists(
                    $wpdb->prefix . 'jtl_connector_link_manufacturer',
                    'jtl_connector_link_manufacturer_1'
                )
            ) {
                $wpdb->query("
              ALTER TABLE `{$wpdb->prefix}jtl_connector_link_manufacturer`
                ADD CONSTRAINT `jtl_connector_link_manufacturer_1` FOREIGN KEY (`endpoint_id`) 
                REFERENCES `{$wpdb->terms}` (`term_id`) ON DELETE CASCADE ON UPDATE NO ACTION");
            }
        }
    }

    /**
     * @return void
     */
    protected static function createTaxClassLinkingTable(): void
    {
        global $wpdb;

        $query = '
            CREATE TABLE IF NOT EXISTS `%s%s` (
                `endpoint_id` VARCHAR(200) NOT NULL,
                `host_id` INT(10) unsigned NOT NULL,
                PRIMARY KEY (`endpoint_id`),
                UNIQUE (`host_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci';

        $wpdb->query(sprintf($query, $wpdb->prefix, 'jtl_connector_link_tax_class'));
    }

    // </editor-fold>

    /**
     * @param $prefix
     * @param $db
     * @return void
     */
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    private static function add_constraints_for_multi_linking_tables($prefix, $db): void
    {
        global $wpdb;

        $engine = $wpdb->get_var(sprintf(
            "SELECT ENGINE
            FROM information_schema.TABLES
            WHERE TABLE_NAME = '{$wpdb->posts}' AND TABLE_SCHEMA = '%s'",
            DB_NAME
        ));

        if ($engine === 'InnoDB') {
            if (! $db->checkIfFKExists($prefix . 'product', 'jtl_connector_link_product_1')) {
                $wpdb->query(
                    "ALTER TABLE `{$prefix}product`
                ADD CONSTRAINT `jtl_connector_link_product_1` FOREIGN KEY  (`endpoint_id`) 
                REFERENCES `{$wpdb->posts}` (`ID`) ON DELETE CASCADE ON UPDATE NO ACTION"
                );
            }
            if (! $db->checkIfFKExists($prefix . 'order', 'jtl_connector_link_order_1')) {
                $wpdb->query(
                    "ALTER TABLE `{$prefix}order`
                            ADD CONSTRAINT `jtl_connector_link_order_1` FOREIGN KEY (`endpoint_id`) 
                            REFERENCES `{$wpdb->posts}` (`ID`) ON DELETE CASCADE ON UPDATE NO ACTION"
                );
            }
            if (! $db->checkIfFKExists($prefix . 'payment', 'jtl_connector_link_payment_1')) {
                $wpdb->query(
                    "ALTER TABLE `{$prefix}payment`
                            ADD CONSTRAINT `jtl_connector_link_payment_1` FOREIGN KEY (`endpoint_id`) 
                            REFERENCES `{$wpdb->posts}` (`ID`) ON DELETE CASCADE ON UPDATE NO ACTION"
                );
            }
            if (! $db->checkIfFKExists($prefix . 'crossselling', 'jtl_connector_link_crossselling_1')) {
                $wpdb->query(
                    "ALTER TABLE `{$prefix}crossselling`
                            ADD CONSTRAINT `jtl_connector_link_crossselling_1` FOREIGN KEY (`endpoint_id`) 
                                REFERENCES `{$wpdb->posts}` (`ID`) ON DELETE CASCADE ON UPDATE NO ACTION"
                );
            }
            if (! $db->checkIfFKExists($prefix . 'category', 'jtl_connector_link_category_1')) {
                $wpdb->query(
                    "ALTER TABLE `{$prefix}category`
                            ADD CONSTRAINT `jtl_connector_link_category_1` FOREIGN KEY  (`endpoint_id`) 
                            REFERENCES `{$wpdb->terms}` (`term_id`) ON DELETE CASCADE ON UPDATE NO ACTION"
                );
            }

            $table = $wpdb->prefix . 'woocommerce_attribute_taxonomies';
            if (! $db->checkIfFKExists($prefix . 'specific', 'jtl_connector_link_specific_1')) {
                $wpdb->query(
                    "ALTER TABLE `{$prefix}specific`
                            ADD CONSTRAINT `jtl_connector_link_specific_1` FOREIGN KEY (`endpoint_id`) 
                            REFERENCES `{$table}` (`attribute_id`) ON DELETE CASCADE ON UPDATE NO ACTION"
                );
            }
        }
    }

    /**
     * @param string $buildVersion
     *
     * @return void
     */
    private static function initDefaultConfigValues(string $buildVersion): void
    {
        Config::set(Config::OPTIONS_TOKEN, self::create_password());
        Config::set(Config::OPTIONS_INSTALLED_VERSION, $buildVersion);

        foreach (Config::JTLWCC_CONFIG as $name => $castItem) {
            $currentValue = Config::get($name);
            if ($currentValue === null) {
                $defaultValue = Config::JTLWCC_CONFIG_DEFAULTS[ $name ];
                Config::set($name, $defaultValue);
            }
        }
    }

    /**
     * @return string
     */
    private static function create_password(): string //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (function_exists('com_create_guid') === true) {
            return trim(com_create_guid(), '{}');
        }

        return sprintf(
            '%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(16384, 20479),
            mt_rand(32768, 49151),
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(0, 65535)
        );
    }

    /**
     * @return void
     * @throws Exception
     */
    public static function loadFeaturesJson(string $featuresJsonPath): void
    {
        $features = Config::get(Config::OPTIONS_FEATURES_JSON);
        if (! empty($features)) {
            $featuresJson = json_decode($features, true);
            if (is_array($featuresJson)) {
                $saveResult = file_put_contents($featuresJsonPath, json_encode($featuresJson, JSON_PRETTY_PRINT));
                if ($saveResult === false) {
                    throw new Exception(sprintf("Cannot save features in %s file.", $featuresJsonPath), 100);
                }
            }
        } else {
            $features = json_decode(file_get_contents($featuresJsonPath), true);
            Config::set(Config::OPTIONS_FEATURES_JSON, json_encode($features));
        }
    }

    /**
     * @return void
     */
    public static function plugin_deactivation(): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        delete_option(Config::OPTIONS_TOKEN);
    }

    /**
     * @return void
     * @throws ParseException
     * @throws \Psr\Log\InvalidArgumentException
     */
    public static function init(): void
    {
        if (! self::$initiated) {
            global $wpdb;
            $db = new Db($wpdb);
            self::init_hooks($db);
            self::checkIfDefaultCustomerGroupIsSet();
            self::checkIfPullCustomerGroupIsSet();
        }
    }

    // <editor-fold defaultstate="collapsed" desc="Settings">

    /**
     * @return void
     * @throws ParseException
     * @throws \Psr\Log\InvalidArgumentException
     * @throws \Psr\Log\InvalidArgumentException
     */
    public static function init_hooks(Db $db): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        self::$initiated = true;

        add_filter('plugin_row_meta', [
            'JtlConnectorAdmin',
            'jtlconnector_plugin_row_meta',
        ], 10, 2);

        add_action('admin_post_settings_save_woo-jtl-connector', [
            'JtlConnectorAdmin',
            'save',
        ]);

        //Register custom fields
        add_action(
            'woocommerce_admin_field_jtl_date_field',
            [
                'JtlConnectorAdmin',
                'jtl_date_field',
            ]
        );
        add_action(
            'woocommerce_admin_field_paragraph',
            [
                'JtlConnectorAdmin',
                'paragraph_field',
            ]
        );
        add_action(
            'woocommerce_admin_field_connector_url',
            [
                'JtlConnectorAdmin',
                'connector_url_field',
            ]
        );
        add_action(
            'woocommerce_admin_field_connector_password',
            [
                'JtlConnectorAdmin',
                'connector_password_field',
            ]
        );
        add_action(
            'woocommerce_admin_field_active_true_false_radio',
            [
                'JtlConnectorAdmin',
                'active_true_false_radio_btn',
            ]
        );
        add_action(
            'woocommerce_admin_field_jtl_connector_select',
            [
                'JtlConnectorAdmin',
                'jtl_connector_select',
            ]
        );
        add_action(
            'woocommerce_admin_field_jtl_connector_multiselect',
            [
                'JtlConnectorAdmin',
                'jtl_connector_multiselect',
            ]
        );
        add_action(
            'woocommerce_admin_field_dev_log_btn',
            [
                'JtlConnectorAdmin',
                'dev_log_btn',
            ]
        );
        add_action(
            'woocommerce_admin_field_jtl_text_input',
            [
                'JtlConnectorAdmin',
                'jtl_text_input',
            ]
        );
        add_action(
            'woocommerce_admin_field_jtl_number_input',
            [
                'JtlConnectorAdmin',
                'jtl_number_input',
            ]
        );
        add_action(
            'woocommerce_admin_field_jtl_checkbox',
            [
                'JtlConnectorAdmin',
                'jtl_checkbox',
            ]
        );
        add_action(
            'woocommerce_admin_field_not_compatible_plugins_field',
            [
                'JtlConnectorAdmin',
                'not_compatible_plugins_field',
            ]
        );
        add_action(
            'woocommerce_admin_field_jtlwcc_card',
            [
                'JtlConnectorAdmin',
                'jtlwcc_card',
            ]
        );
        add_action(
            'woocommerce_admin_field_compatible_plugins_field',
            [
                'JtlConnectorAdmin',
                'compatible_plugins_field',
            ]
        );

        //NEW PAGE
        add_action('admin_menu', 'woo_jtl_connector_add_admin_menu');
        add_action('admin_enqueue_scripts', 'woo_jtl_connector_loadCssAndJs');

        /**
         * @return void
         */
        function woo_jtl_connector_add_admin_menu(): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
        {
            add_menu_page(
                __('JTL-Connector', JTLWCC_TEXT_DOMAIN),
                __('JTL-Connector', JTLWCC_TEXT_DOMAIN),
                'manage_woocommerce',
                'woo-jtl-connector',
                null,
                null,
                '55.5'
            );

            add_submenu_page(
                'woo-jtl-connector',
                __('JTL-Connector:Information', JTLWCC_TEXT_DOMAIN),
                __('Information', JTLWCC_TEXT_DOMAIN),
                'manage_woocommerce',
                'woo-jtl-connector-information',
                'woo_jtl_connector_information_page'
            );

            add_submenu_page(
                'woo-jtl-connector',
                __('JTL-Connector:Advanced', JTLWCC_TEXT_DOMAIN),
                __('Advanced Settings', JTLWCC_TEXT_DOMAIN),
                'manage_woocommerce',
                'woo-jtl-connector-advanced',
                'woo_jtl_connector_advanced_page'
            );

            add_submenu_page(
                'woo-jtl-connector',
                __('JTL-Connector:Delivery times', JTLWCC_TEXT_DOMAIN),
                __('Delivery times', JTLWCC_TEXT_DOMAIN),
                'manage_woocommerce',
                'woo-jtl-connector-delivery-time',
                'woo_jtl_connector_delivery_time_page'
            );

            add_submenu_page(
                'woo-jtl-connector',
                __('JTL-Connector:Customer orders', JTLWCC_TEXT_DOMAIN),
                __('Customer orders', JTLWCC_TEXT_DOMAIN),
                'manage_woocommerce',
                'woo-jtl-connector-customer-order',
                'woo_jtl_connector_customer_order_page'
            );

            add_submenu_page(
                'woo-jtl-connector',
                __('JTL-Connector:Customers', JTLWCC_TEXT_DOMAIN),
                __('Customers', JTLWCC_TEXT_DOMAIN),
                'manage_woocommerce',
                'woo-jtl-connector-customers',
                'woo_jtl_connector_customers_page'
            );

            add_submenu_page(
                'woo-jtl-connector',
                __('JTL-Connector:Developer Settings', JTLWCC_TEXT_DOMAIN),
                __('Developer Settings', JTLWCC_TEXT_DOMAIN),
                'manage_woocommerce',
                'woo-jtl-connector-developer-settings',
                'woo_jtl_connector_developer_settings_page'
            );

            remove_submenu_page('woo-jtl-connector', 'woo-jtl-connector');
        }

        /**
         * @param $hook
         *
         * @return void
         */
        //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
        function woo_jtl_connector_loadCssAndJs($hook): void
        {
            // your-slug => The slug name to refer to this menu used in "add_submenu_page"
            // tools_page => refers to Tools top menu, so it's a Tools' sub-menu page
            if (! str_starts_with($hook, 'jtl-connector_page_woo-')) {
                return;
            }

            wp_enqueue_style(
                'bootstrap4',
                'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css'
            );
            wp_enqueue_style(
                'custom-css-jtl',
                JTLWCC_CONNECTOR_DIR_URL . '/includes/css/custom.css'
            );
            wp_enqueue_script(
                'boot1',
                'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.bundle.min.js',
                [ 'jquery' ],
                '',
                true
            );
        }

        /**
         * @return void
         */
        function woo_jtl_connector_information_page(): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
        {
            JtlConnectorAdmin::displayPageNew('information_page', __('Connector information', JTLWCC_TEXT_DOMAIN));
        }

        /**
         * @return void
         */
        //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
        function woo_jtl_connector_advanced_page(): void
        {
            JtlConnectorAdmin::displayPageNew('advanced_page', __('Advanced Settings', JTLWCC_TEXT_DOMAIN), true);
        }

        /**
         * @return void
         */
        //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
        function woo_jtl_connector_delivery_time_page(): void
        {
            JtlConnectorAdmin::displayPageNew('delivery_time_page', __('Delivery time', JTLWCC_TEXT_DOMAIN), true);
        }

        /**
         * @return void
         */
        //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
        function woo_jtl_connector_customer_order_page(): void
        {
            JtlConnectorAdmin::displayPageNew('customer_order_page', __('Customer order', JTLWCC_TEXT_DOMAIN), true);
        }

        /**
         * @return void
         */
        //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
        function woo_jtl_connector_customers_page(): void
        {
            JtlConnectorAdmin::displayPageNew('customers_page', __('Customers', JTLWCC_TEXT_DOMAIN), true);
        }

        /**
         * @return void
         */
        //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
        function woo_jtl_connector_developer_settings_page(): void
        {
            JtlConnectorAdmin::displayPageNew(
                'developer_settings_page',
                __('Developer Settings', JTLWCC_TEXT_DOMAIN),
                true
            );
        }

        self::update($db);
    }

    /**
     * @param        $page
     * @param string $title
     * @param bool   $submit
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public static function displayPageNew($page, string $title = 'Connector information', bool $submit = false): void
    {
        $options = null;
        if (is_null($page)) {
            return;
        }

        switch ($page) {
            case 'information_page':
                $settings = apply_filters('woocommerce_settings_jtlconnector', self::getInformationFields());
                break;
            case 'advanced_page':
                $settings = apply_filters('woocommerce_settings_jtlconnector', self::getAdvancedFields());
                break;
            case 'delivery_time_page':
                $settings = apply_filters('woocommerce_settings_jtlconnector', self::getDeliveryTimeFields());
                break;
            case 'customer_order_page':
                $settings = apply_filters('woocommerce_settings_jtlconnector', self::getCustomerOrderFields());
                break;
            case 'customers_page':
                $settings = apply_filters('woocommerce_settings_jtlconnector', self::getCustomersFields());
                break;
            case 'developer_settings_page':
                $settings = apply_filters('woocommerce_settings_jtlconnector', self::getDeveloperSettingsFields());
                break;
            default:
                $settings = null;
                break;
        }

        if (is_null($settings)) {
            return;
        }

        $options = apply_filters('woocommerce_get_settings_jtlconnector', $settings);

        ?>
        <div class="bootstrap-wrapper m-0 bg-light">
            <?php
            self::displayNanvigation($page);
            ?>
            <div class="container-fluid">
                <div class="row justify-content-center pb-4">
                    <form method="post"
                          id="mainform"
                          class="form-horizontal col-10 bg-light"
                          action="<?php echo esc_html(admin_url('admin-post.php'));
                            ?>?action=settings_save_woo-jtl-connector"
                          enctype="multipart/form-data">
                        <div class="form-group row">
                            <h2 class="col-12"><?php print $title ?></h2>
                        </div>

                        <?php
                        if ($submit) {
                            ?>
                            <div class="form-group row">
                                <button type="submit" name="submit" id="submit" class="btn btn-outline-primary ml-3">
                                    Änderungen speichern
                                </button>
                            </div>
                            <?php
                        }
                        print '' . woocommerce_admin_fields($options) . '';
                        if ($submit) {
                            ?>
                            <div class="form-group row">
                                <button type="submit" name="submit" id="submit" class="btn btn-outline-primary ml-3">
                                    Änderungen speichern
                                </button>
                            </div>
                            <?php
                        }
                        ?>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * @return array
     */
    private static function getInformationFields(): array
    {
        $fields = [];

        self::notCompatiblePluginsError();

        //Add Information field
        $fields[] = [
            'type' => 'title',
            'desc' => __(
                'With JTL-Connector for WooCommerce, you can connect your WooCommerce online shop 
                with the free JTL-Wawi ERP system by JTL-Software. The ERP system as well as the entire 
                JTL product family are perfectly suited to the requirements of e-commerce and mail order businesses. 
                They help you to process more orders in a shorter time and offer a range of exciting functionalities. 
                Basic information and credentials of the installed JTL-Connector. It is needed to configure the 
                JTL-Connector in the jtl customer center and JTL-Wawi.',
                JTLWCC_TEXT_DOMAIN
            ),
        ];

        //Add sectionend
        $fields[] = [
            'type' => 'sectionend',
        ];

        //Add connector url field
        $fields[] = [
            'title'     => 'Connector URL',
            'type'      => 'connector_url',
            'helpBlock' => __(
                'This URL should be placed in the JTL-Customer-Center and in your JTL-Wawi as "Onlineshop-URL".',
                JTLWCC_TEXT_DOMAIN
            ),
            'id'        => 'connector_url',
            'value'     => sprintf(
                '%s%s%s',
                $protocol = isset($_SERVER['HTTPS'])
                            && ( $_SERVER['HTTPS'] == 'on'
                                 || $_SERVER['HTTPS'] == 1 )
                            || isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
                               && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'
                    ? 'https://' : 'http://',
                str_replace(
                    'http://',
                    '',
                    str_replace(
                        'https://',
                        '',
                        get_bloginfo('url')
                    )
                ),
                '/index.php/jtlconnector/'
            ),
        ];

        //Add connector password field
        $fields[] = [
            'title'     => __('Connector Password', JTLWCC_TEXT_DOMAIN),
            'type'      => 'connector_password',
            'helpBlock' => __(
                'This secret password will be used for identifying that your JTL-Wawi ist allowed to pull/push data.',
                JTLWCC_TEXT_DOMAIN
            ),
            'id'        => 'connector_password',
            'value'     => Config::get(Config::OPTIONS_TOKEN),
        ];

        //Add connector version field
        $fields[] = [
            'title'     => 'Connector Version',
            'type'      => 'paragraph',
            'helpBlock' => __('This is your current installed connector version.', JTLWCC_TEXT_DOMAIN),
            'desc'      => Config::get(Config::OPTIONS_INSTALLED_VERSION),
        ];

        //Add sectionend
        $fields[] = [
            'type' => 'sectionend',
        ];

        //Add extend plugin informations
        if (count(SupportedPlugins::getSupported()) > 0) {
            $fields[] = [
                'title'   => __('These activated plugins extend the JTL-Connector:', JTLWCC_TEXT_DOMAIN),
                'type'    => 'compatible_plugins_field',
                'plugins' => SupportedPlugins::getSupported(),
            ];
        }

        //Add Incompatible plugin informations
        $fields[] = [
            'title'   => __('Incompatible with these plugins:', JTLWCC_TEXT_DOMAIN),
            'type'    => 'not_compatible_plugins_field',
            'plugins' => SupportedPlugins::getNotSupportedButActive(false, true, true),
        ];

        $fields[] = [
            'title'      => __('Important information', JTLWCC_TEXT_DOMAIN),
            'type'       => 'jtlwcc_card',
            'color'      => 'border-warning',
            'text-color' => 'text-warning',
            'center'     => true,
            'text'       => __(
                'Similar plugins, like the <b>not compatible plugins</b> which 
                    are listed here, might be incompatible too!',
                JTLWCC_TEXT_DOMAIN
            ),
        ];

        //Add sectionend
        $fields[] = [
            'type' => 'sectionend',
        ];

        return $fields;
    }

    /**
     * @return void
     */
    private static function notCompatiblePluginsError(): void
    {
        //Show error if unsupported plugins are in use
        if (count(SupportedPlugins::getNotSupportedButActive()) > 0) {
            self::jtlwcc_show_wordpress_error(
                sprintf(
                    __('The listed plugins can cause problems when using the connector: %s', JTLWCC_TEXT_DOMAIN),
                    SupportedPlugins::getNotSupportedButActive(true)
                )
            );
        }
    }

    /**
     * @param $message
     *
     * @return void
     */
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public static function jtlwcc_show_wordpress_error($message): void
    {
        echo '<div class="alert alert-danger" id="jtlwcc_plugin_error" role="alert">
                    <p><b>JTL-Connector:</b>&nbsp;' . $message . '</p>
                </div>';
    }

    /**
     * @return array
     */
    private static function getAdvancedFields(): array
    {
        $fields = [];

        self::notCompatiblePluginsError();

        //Add Settings information field
        $fields[] = [
            'type' => 'title',
            'desc' => __(
                'With JTL-Connector for WooCommerce, you can connect your WooCommerce online shop 
                with the free JTL-Wawi ERP system by JTL-Software. These are the advanced settings of the 
                installed JTL-Connector. Here you can configure how some data is handled while push/pull.',
                JTLWCC_TEXT_DOMAIN
            ),
        ];

        //Add sectionend
        $fields[] = [
            'type' => 'sectionend',
        ];

        //Add variation specific radio field
        $fields[] = [
            'title'     => __('Variation specifics', JTLWCC_TEXT_DOMAIN),
            'type'      => 'active_true_false_radio',
            'desc'      => __(
                'Enable if you want to show your customers the variation as specific (Default : Enabled).',
                JTLWCC_TEXT_DOMAIN
            ),
            'id'        => Config::OPTIONS_SHOW_VARIATION_SPECIFICS_ON_PRODUCT_PAGE,
            'value'     => Config::get(Config::OPTIONS_SHOW_VARIATION_SPECIFICS_ON_PRODUCT_PAGE),
            'trueText'  => __('Enabled', JTLWCC_TEXT_DOMAIN),
            'falseText' => __('Disabled', JTLWCC_TEXT_DOMAIN),
        ];

        $fields[] = [
            'title'     => __('Delete unknown attributes', JTLWCC_TEXT_DOMAIN),
            'type'      => 'active_true_false_radio',
            'desc'      => __(
                'Enable if you want to delete unknown attributes on push (Default : Disabled).',
                JTLWCC_TEXT_DOMAIN
            ),
            'id'        => Config::OPTIONS_DELETE_UNKNOWN_ATTRIBUTES,
            'value'     => Config::get(Config::OPTIONS_DELETE_UNKNOWN_ATTRIBUTES),
            'trueText'  => __('Enabled', JTLWCC_TEXT_DOMAIN),
            'falseText' => __('Disabled', JTLWCC_TEXT_DOMAIN),
        ];

        //Add custom properties radio field
        $fields[] = [
            'title'     => __('Custom properties', JTLWCC_TEXT_DOMAIN),
            'type'      => 'active_true_false_radio',
            'desc'      => __(
                'If you activate this option, custom fields from JTL-Wawi will be handled 
                as attributes in the shop. After changing this option, full-sync is required (Default : Enabled).',
                JTLWCC_TEXT_DOMAIN
            ),
            'id'        => Config::OPTIONS_SEND_CUSTOM_PROPERTIES,
            'value'     => Config::get(Config::OPTIONS_SEND_CUSTOM_PROPERTIES),
            'trueText'  => __('Enabled', JTLWCC_TEXT_DOMAIN),
            'falseText' => __('Disabled', JTLWCC_TEXT_DOMAIN),
        ];

        //Add gtin/ean radio field
        $fields[] = [
            'title'     => __('GTIN / EAN', JTLWCC_TEXT_DOMAIN),
            'type'      => 'active_true_false_radio',
            'desc'      => __(
                'Enable if you want to use the GTIN field for ean. 
                (Default : Enabled / Required plugin: WooCommerce Germanized).',
                JTLWCC_TEXT_DOMAIN
            ),
            'id'        => Config::OPTIONS_USE_GTIN_FOR_EAN,
            'value'     => Config::get(Config::OPTIONS_USE_GTIN_FOR_EAN),
            'trueText'  => __('Enabled', JTLWCC_TEXT_DOMAIN),
            'falseText' => __('Disabled', JTLWCC_TEXT_DOMAIN),
        ];

        //Allow html in attributes
        $fields[] = [
            'title'     => __('Allow HTML in product attributes', JTLWCC_TEXT_DOMAIN),
            'type'      => 'active_true_false_radio',
            'desc'      => __(
                'Enable if you want to allow saving HTML in product attributes (Default : Disabled)',
                JTLWCC_TEXT_DOMAIN
            ),
            'id'        => Config::OPTIONS_ALLOW_HTML_IN_PRODUCT_ATTRIBUTES,
            'value'     => Config::get(Config::OPTIONS_ALLOW_HTML_IN_PRODUCT_ATTRIBUTES),
            'trueText'  => __('Enabled', JTLWCC_TEXT_DOMAIN),
            'falseText' => __('Disabled', JTLWCC_TEXT_DOMAIN),
        ];

        //Add sectionend
        $fields[] = [
            'type' => 'sectionend',
        ];


        return $fields;
    }

    /**
     * @return array
     */
    private static function getDeliveryTimeFields(): array
    {
        $fields = [];

        self::notCompatiblePluginsError();

        //Add Settings information field
        $fields[] = [
            'type' => 'title',
            'desc' => __(
                'With JTL-Connector for WooCommerce, you can connect your WooCommerce online shop 
                with the free JTL-Wawi ERP system by JTL-Software. Delivery time related settings of the 
                installed JTL-Connector. Here you can set some options to modify the pull/psuh of delivery times.',
                JTLWCC_TEXT_DOMAIN
            ),
        ];

        //Add sectionend
        $fields[] = [
            'type' => 'sectionend',
        ];

        //Add delivery time calculation radio field
        $fields[] = [
            'title'     => __('DeliveryTime Calculation', JTLWCC_TEXT_DOMAIN),
            'type'      => 'jtl_connector_select',
            'id'        => Config::OPTIONS_USE_DELIVERYTIME_CALC,
            'value'     => Config::get(Config::OPTIONS_USE_DELIVERYTIME_CALC),
            'options'   => [
                'delivery_time_calc' => __('Lieferzeit Berechnung nutzen', JTLWCC_TEXT_DOMAIN),
                'delivery_status'    => __('Lieferstatus nutzen', JTLWCC_TEXT_DOMAIN),
                'deactivated'        => __('Deaktiviert', JTLWCC_TEXT_DOMAIN),
            ],
            'helpBlock' => __(
                "Enable if you want to use delivery time calculation. <br>
                        Delivery time calculation: Let JTL Wawi calculate the delivery time. <br>
                        Delivery status: Use the delivery status as delivery time. <br>
                        Deactivated: Don't use delivery time. <br>
                        (Default : Delivery time calculation / Required plugin: WooCommerce Germanized).",
                JTLWCC_TEXT_DOMAIN
            ),
        ];

        //Add dont use zero values radio field
        $fields[] = [
            'title'     => __('Dont use zero values for delivery time', JTLWCC_TEXT_DOMAIN),
            'type'      => 'active_true_false_radio',
            'desc'      => __(
                'Enable if you dont want to use zero values for delivery time. (Default : Enabled).',
                JTLWCC_TEXT_DOMAIN
            ),
            'id'        => Config::OPTIONS_DISABLED_ZERO_DELIVERY_TIME,
            'value'     => Config::get(Config::OPTIONS_DISABLED_ZERO_DELIVERY_TIME),
            'trueText'  => __('Enabled', JTLWCC_TEXT_DOMAIN),
            'falseText' => __('Disabled', JTLWCC_TEXT_DOMAIN),
        ];

        //Add prefix for delivery time textinput field
        $fields[] = [
            'title'     => __('Prefix for delivery time', JTLWCC_TEXT_DOMAIN),
            'type'      => 'jtl_text_input',
            'id'        => Config::OPTIONS_PRAEFIX_DELIVERYTIME,
            'value'     => Config::get(Config::OPTIONS_PRAEFIX_DELIVERYTIME),
            'helpBlock' => __("Define the prefix like" . PHP_EOL . "'ca. 4 Days'.", JTLWCC_TEXT_DOMAIN),
        ];

        //Add suffix for delivery time textinput field
        $fields[] = [
            'title'     => __('Suffix for delivery time', JTLWCC_TEXT_DOMAIN),
            'type'      => 'jtl_text_input',
            'id'        => Config::OPTIONS_SUFFIX_DELIVERYTIME,
            'value'     => Config::get(Config::OPTIONS_SUFFIX_DELIVERYTIME),
            'helpBlock' => __("Define the Suffix like" . PHP_EOL . "'ca. 4 work days'.", JTLWCC_TEXT_DOMAIN),
        ];

        //Use next available inflow date if needed
        $fields[] = [
            'title'     => __('Consider available inflow date for shipping', JTLWCC_TEXT_DOMAIN),
            'type'      => 'active_true_false_radio',
            'desc'      => __(
                'Enable if you want that connector calculate shipping time based on next a
                vailable inflow date from supplier when stock is 0',
                JTLWCC_TEXT_DOMAIN
            ),
            'id'        => Config::OPTIONS_CONSIDER_SUPPLIER_INFLOW_DATE,
            'value'     => Config::get(Config::OPTIONS_CONSIDER_SUPPLIER_INFLOW_DATE),
            'trueText'  => __('Enabled', JTLWCC_TEXT_DOMAIN),
            'falseText' => __('Disabled', JTLWCC_TEXT_DOMAIN),
        ];

        //Add sectionend
        $fields[] = [
            'type' => 'sectionend',
        ];

        return $fields;
    }

    /**
     * @return array
     */
    private static function getCustomerOrderFields(): array
    {
        $fields = [];

        self::notCompatiblePluginsError();

        //Add Settings information field
        $fields[] = [
            'type' => 'title',
            'desc' => __(
                'With JTL-Connector for WooCommerce, you can connect your WooCommerce online shop with the 
                free JTL-Wawi ERP system by JTL-Software. Customer order related settings of the installed 
                JTL-Connector. Here you can set some options to modify the import of customer orders.',
                JTLWCC_TEXT_DOMAIN
            ),
        ];

        //Add sectionend
        $fields[] = [
            'type' => 'sectionend',
        ];

        //Add variation specific radio field

        //Add pull order since date field
        $fields[] = [
            'title'     => __('Pull orders since', JTLWCC_TEXT_DOMAIN),
            'type'      => 'jtl_date_field',
            // 'default'  => '2019-03-22',
            'value'     => Config::get(Config::OPTIONS_PULL_ORDERS_SINCE),
            'helpBlock' => __('Define a start date for pulling of orders.', JTLWCC_TEXT_DOMAIN),
            'id'        => Config::OPTIONS_PULL_ORDERS_SINCE,
        ];
        $fields[] = [
            'title'     => __('Recalculate order when has coupons', JTLWCC_TEXT_DOMAIN),
            'type'      => 'active_true_false_radio',
            'desc'      => __(
                'When option is enabled, connector will recalculate order when coupons were applied to order.',
                JTLWCC_TEXT_DOMAIN
            ),
            'id'        => Config::OPTIONS_RECALCULATE_COUPONS_ON_PULL,
            'value'     => Config::get(Config::OPTIONS_RECALCULATE_COUPONS_ON_PULL),
            'trueText'  => __('Enabled', JTLWCC_TEXT_DOMAIN),
            'falseText' => __('Disabled', JTLWCC_TEXT_DOMAIN),
        ];
        $fields[] = [
            'title'     => __('Default order statuses to import', JTLWCC_TEXT_DOMAIN),
            'type'      => 'jtl_connector_multiselect',
            'options'   => wc_get_order_statuses(),
            'id'        => Config::OPTIONS_DEFAULT_ORDER_STATUSES_TO_IMPORT,
            'value'     => Config::get(
                Config::OPTIONS_DEFAULT_ORDER_STATUSES_TO_IMPORT,
                [ 'wc-pending', 'wc-processing', 'wc-on-hold' ]
            ),
            'helpBlock' => __(
                'Order statuses that should be imported. Default: pending, processing, on hold, completed',
                JTLWCC_TEXT_DOMAIN
            ),
        ];

        $paymentGateways = [];
        if (WC()->payment_gateways() instanceof WC_Payment_Gateways) {
            $paymentGateways = WC()->payment_gateways->payment_gateways();
            $paymentGateways = array_combine(array_keys($paymentGateways), array_column($paymentGateways, 'title'));
        }

        $fields[] = [
            'title'   => __(
                'Import payments with following payment types only when order 
                is completed (usually manual payment types)',
                JTLWCC_TEXT_DOMAIN
            ),
            'type'    => 'jtl_connector_multiselect',
            'options' => $paymentGateways,
            'id'      => Config::OPTIONS_DEFAULT_MANUAL_PAYMENT_TYPES,
            'value'   => Config::get(
                Config::OPTIONS_DEFAULT_MANUAL_PAYMENT_TYPES,
                Config::JTLWCC_CONFIG_DEFAULTS[ Config::OPTIONS_DEFAULT_MANUAL_PAYMENT_TYPES ]
            ),
        ];

        $fields[] = [
            'title'     => __('Delay time in seconds before order import'),
            'type'      => 'jtl_number_input',
            'value'     => Config::get(Config::OPTIONS_IGNORE_ORDERS_YOUNGER_THAN),
            'helpBlock' => __('Define the delay time in seconds before new orders get imported.', JTLWCC_TEXT_DOMAIN),
            'id'        => Config::OPTIONS_IGNORE_ORDERS_YOUNGER_THAN,
        ];

        //Add custom checkout fields input field
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_CHECKOUT_FIELD_EDITOR_FOR_WOOCOMMERCE)) {
            $fields[] = [
                'title'     => __('Custom Checkout Fields', JTLWCC_TEXT_DOMAIN),
                'type'      => 'jtl_text_input',
                'id'        => Config::OPTIONS_CUSTOM_CHECKOUT_FIELDS,
                'value'     => Config::get(Config::OPTIONS_CUSTOM_CHECKOUT_FIELDS),
                'helpBlock' => __(
                    "Define what custom fields should be imported to Wawi. Comma-separated.",
                    JTLWCC_TEXT_DOMAIN
                ),
            ];
        }

        //Add sectionend
        $fields[] = [
            'type' => 'sectionend',
        ];

        return $fields;
    }

    /**
     * @return array
     * @throws InvalidArgumentException
     */
    private static function getCustomersFields(): array
    {
        $fields = [];

        self::notCompatiblePluginsError();


        $fields[] = [
            'type' => 'title',
            'desc' => __(
                '',
                JTLWCC_TEXT_DOMAIN
            ),
        ];


        $fields[] = [
            'type' => 'sectionend',
        ];

        $fields[] = [
            'title'     => __('Limit Customer Pull', JTLWCC_TEXT_DOMAIN),
            'type'      => 'jtl_connector_select',
            'id'        => Config::OPTIONS_LIMIT_CUSTOMER_QUERY_TYPE,
            'value'     => Config::get(
                Config::OPTIONS_LIMIT_CUSTOMER_QUERY_TYPE,
                Config::JTLWCC_CONFIG_DEFAULTS[ Config::OPTIONS_LIMIT_CUSTOMER_QUERY_TYPE ]
            ),
            'options'   => [
                'no_filter'           => __('No Limit', JTLWCC_TEXT_DOMAIN),
                'last_imported_order' => __('Since last pulled Order ID', JTLWCC_TEXT_DOMAIN),
                'not_imported'        => __('Only from not pulled Order', JTLWCC_TEXT_DOMAIN),
                'fixed_date'          => __('Since fixed Date', JTLWCC_TEXT_DOMAIN),
            ],
            'helpBlock' => __(
                '"No Limit" will Pull all Users in the User Group "Customers" (with B2B Market, define Groups below), 
                                with and without Orders, slower the more Customers you have. <br>
                                "Only from not pulled Order" will only pull Customers with not pulled Order, 
                                slower the more Orders you have. <br>
                                "Since Last pulled Order ID" may skip Customers, if they have an Order which 
                                was not pulled in the past, generally fast. <br>
                                "Fixed Date" will only pull Customers with an Order since the defined date 
                                (defined in Orders Tab), slower the longer ago the Date is. <br>
                                JTL recommends to use "No Limit"<br>
                                Use "Since Last pulled Order ID" or "Since fixed Date" if you get 
                                Timeout Errors in JTL-WAWI. <br><br>
                                Speeds decreases linearly with the number of Customers and/or Orders 
                                except for "Only from not pulled Order" which decreases exponentially.',
                JTLWCC_TEXT_DOMAIN
            ),
        ];


        if (
            SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)
            && version_compare(
                (string) SupportedPlugins::getVersionOf(SupportedPlugins::PLUGIN_B2B_MARKET),
                '1.0.3',
                '>'
            )
        ) {
            global $wpdb;
            $db     = new Db($wpdb);
            $util   = new Util($db);
            $roles  = [];
            $sql    = SqlHelper::customerGroupPull();
            $result = $db->query($sql);
            $result = array_diff($result, [ 'guest' ]);
            foreach ($result as $role) {
                $roles[ $role['post_name'] ] = translate_user_role($role['post_title']);
            }

            $fields[] = [
                'title'     => __('Customer Groups to Pull (only with no Limit)', JTLWCC_TEXT_DOMAIN),
                'type'      => 'jtl_connector_multiselect',
                'options'   => $roles,
                'id'        => Config::OPTIONS_PULL_CUSTOMER_GROUPS,
                'value'     => Config::get(Config::OPTIONS_PULL_CUSTOMER_GROUPS, []),
                'helpBlock' => __(
                    'Pull Customers with this Customer Groups, only respected if no Limit is defined. 
                    <br> Guests are always pulled. ',
                    JTLWCC_TEXT_DOMAIN
                ),
            ];


            $customerGroups = ( new CustomerGroupController($db, $util) )->pull();
            $options        = [];

            /** @var CustomerGroupModel $customerGroup */
            foreach ($customerGroups as $key => $customerGroup) {
                if (count($customerGroup->getI18ns()) > 0) {
                    /** @var CustomerGroupI18nModel $i18n */
                    $i18n                                              = $customerGroup->getI18ns()[0];
                    $options[ $customerGroup->getId()->getEndpoint() ] = $i18n->getName();
                }
            }


            $fields[] = [
                'title'     => __('B2B-Market/WooCommerce default customer group', JTLWCC_TEXT_DOMAIN),
                'type'      => 'jtl_connector_select',
                'id'        => Config::OPTIONS_DEFAULT_CUSTOMER_GROUP,
                'value'     => Config::get(Config::OPTIONS_DEFAULT_CUSTOMER_GROUP),
                'options'   => $options,
                'helpBlock' => __('Define which customer group is default.', JTLWCC_TEXT_DOMAIN),
            ];
        }


        $fields[] = [
            'type' => 'sectionend',
        ];

        return $fields;
    }

    /**
     * @return array
     */
    private static function getDeveloperSettingsFields(): array
    {
        $fields = [];

        self::notCompatiblePluginsError();

        //Add Settings information field
        $fields[] = [
            'type' => 'title',
            'desc' => __(
                'With JTL-Connector for WooCommerce, you can connect your WooCommerce online shop 
                with the free JTL-Wawi ERP system by JTL-Software. Developer related settings of 
                the installed JTL-Connector. Here you can enable/disable/reset/download the 
                developer logs of the jtl connector.',
                JTLWCC_TEXT_DOMAIN
            ),
        ];

        //Add dev log radio field
        $fields[] = [
            'title'     => __('Dev-Logs', JTLWCC_TEXT_DOMAIN),
            'type'      => 'active_true_false_radio',
            'desc'      => __(
                'Enable JTL-Connector dev-logs for debugging (Default : Disabled).',
                JTLWCC_TEXT_DOMAIN
            ),
            'id'        => Config::OPTIONS_DEVELOPER_LOGGING,
            'value'     => Config::get(Config::OPTIONS_DEVELOPER_LOGGING),
            'trueText'  => __('Enabled', JTLWCC_TEXT_DOMAIN),
            'falseText' => __('Disabled', JTLWCC_TEXT_DOMAIN),
        ];

        //Add dev log buttons
        $fields[] = [
            'type'          => 'dev_log_btn',
            'downloadText'  => __('Download', JTLWCC_TEXT_DOMAIN),
            'clearLogsText' => __('Clear logs', JTLWCC_TEXT_DOMAIN),
        ];

        $fields[] = [
            'title'     => __('Recommend WooCommerce Settings', JTLWCC_TEXT_DOMAIN),
            'type'      => 'active_true_false_radio',
            'desc'      => __(
                'JTL-Wawi set automatically stable settings (Default : Enabled). 
                Disable this at your own risk!',
                JTLWCC_TEXT_DOMAIN
            ),
            'id'        => Config::OPTIONS_AUTO_WOOCOMMERCE_OPTIONS,
            'value'     => Config::get(Config::OPTIONS_AUTO_WOOCOMMERCE_OPTIONS),
            'trueText'  => __('Enabled', JTLWCC_TEXT_DOMAIN),
            'falseText' => __('Disabled', JTLWCC_TEXT_DOMAIN),
        ];
        //phpcs:disable
        $fields[] = [
            'title'      => __('Important information', JTLWCC_TEXT_DOMAIN),
            'type'       => 'jtlwcc_card',
            'color'      => 'border-info',
            'text-color' => 'text-danger',
            'center'     => false,
            'text'       => __(
                'The <b>JTL-Connector</b> set following settings for WooCommerce:</br></br>
                 <ul class="list-group bg-transparent border-info text-info">
                 <li class="list-group-item bg-transparent">Prices entered with tax: "No, I will enter prices exclusive of tax" (Dont change this!)</li>
                 <li class="list-group-item bg-transparent">Display prices in the shop: "Including tax" (Dont change this!)</li>
                 <li class="list-group-item bg-transparent">Display prices during cart and checkout: "Including tax" (Dont change this!)</li>
                 </ul>',
                JTLWCC_TEXT_DOMAIN
            ),
        ];

        //phpcs:enable
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_GERMAN_MARKET)) {
            $fields[] = [
                'title'     => __('Recommend German Market Settings', JTLWCC_TEXT_DOMAIN),
                'type'      => 'active_true_false_radio',
                'desc'      => __(
                    'JTL-Wawi set automatically stable settings (Default : Enabled). Disable this at your own risk!',
                    JTLWCC_TEXT_DOMAIN
                ),
                'id'        => Config::OPTIONS_AUTO_GERMAN_MARKET_OPTIONS,
                'value'     => Config::get(Config::OPTIONS_AUTO_GERMAN_MARKET_OPTIONS),
                'trueText'  => __('Enabled', JTLWCC_TEXT_DOMAIN),
                'falseText' => __('Disabled', JTLWCC_TEXT_DOMAIN),
            ];
            //phpcs:disable
            $fields[] = [
                'title'      => __('Important information', JTLWCC_TEXT_DOMAIN),
                'type'       => 'jtlwcc_card',
                'color'      => 'border-info',
                'text-color' => 'text-danger',
                'center'     => false,
                'text'       => __(
                    '<h6>The <b>JTL-Connector</b> set following settings for German Market:</br></br></h6>
                    <ul class="list-group bg-transparent border-info text-info">
                    <li class="list-group-item bg-transparent">Delivery Time > Default Delivery Time: "not specified" (Dont change this!)</li>
                    <li class="list-group-item bg-transparent">Delivery Time > Show Delivery Times on Product Pages: "On"</li>
                    <li class="list-group-item bg-transparent">Delivery Time > Show Delivery Times during Checkout: "On"</li>
                    <li class="list-group-item bg-transparent">Delivery Time > Show Delivery Times on Order Summary: "On"</li>
                                      
                    <li class="list-group-item bg-transparent">Sale Labels > Show Sale Labels in Shop: "Off" (Dont change this!)</li>
                    <li class="list-group-item bg-transparent">Sale Labels > Show Sale Labels on Product Pages: "Off" (Dont change this!)</li>
                                  
                    <li class="list-group-item bg-transparent">Products > Product Attributes in product name: "Off" (Dont change this!)</li>
                    <li class="list-group-item bg-transparent">Products > Show Single Price of Order Items in Orders: "On"</li>
                    <li class="list-group-item bg-transparent">Products > Show Product Attributes not used for Variations: "Off" (Dont change this!)</li>
                                      
                    <li class="list-group-item bg-transparent">Products > Product Images on Cart Page: "On"</li>
                    <li class="list-group-item bg-transparent">Products > Product Images for Order Summaries: "On"</li>
                                      
                    <li class="list-group-item bg-transparent">Products > Activate GTIN: "On"</li>
                    <li class="list-group-item bg-transparent">Products > Show GTIN on Product Pages: "On"</li>
                                      
                    <li class="list-group-item bg-transparent">Products > Show Price per Unit: "On" (Dont change this!)</li>
                    <li class="list-group-item bg-transparent">Products > Automatic Calculation: "On" (Dont change this!)</li>
                    <li class="list-group-item bg-transparent">Products > Automatic Calculation - Use WooCommerce Weight Unit and Product Weights: "Off" (Dont change this!)</li>
                    <li class="list-group-item bg-transparent">Products > Automatic Calculation - Use WooCommerce Weight Unit and Product Weights - Scale Unit: "kg" (Dont change this!)</li>
                    <li class="list-group-item bg-transparent">Products > Automatic Calculation - Use WooCommerce Weight Unit and Product Weights - Quantity to display: "1" (Dont change this!)</li>
                                      
                    <li class="list-group-item bg-transparent">Global Options > Prorated Tax Calculation For Fees & Shipping Cost: "On" (Dont change this!)</li>
                    <li class="list-group-item bg-transparent">Global Options > Gross Shipping Costs and Gross Fees: "Off"</li>
                    </ul>',
                    JTLWCC_TEXT_DOMAIN
                ),
            ];
            //phpcs:enable
        }

        //CURRENT DISBALED THIS
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)) {
            $fields[] = [
                'title'     => __('Recommend B2B Market Settings', JTLWCC_TEXT_DOMAIN),
                'type'      => 'active_true_false_radio',
                'desc'      => __(
                    'JTL-Wawi set automatically stable settings (Default : Enabled). Disable this at your own risk!',
                    JTLWCC_TEXT_DOMAIN
                ),
                'id'        => Config::OPTIONS_AUTO_B2B_MARKET_OPTIONS,
                'value'     => Config::get(Config::OPTIONS_AUTO_B2B_MARKET_OPTIONS),
                'trueText'  => __('Enabled', JTLWCC_TEXT_DOMAIN),
                'falseText' => __('Disabled', JTLWCC_TEXT_DOMAIN),
            ];
//            $fields[] = [
//                'title' => __('Important information', JTLWCC_TEXT_DOMAIN),
//                'type' => 'jtlwcc_card',
//                'color' => 'border-info',
//                'text-color' => 'text-info',
//                'center' => false,
//                'text' => __('Similar plugins, like the <b>not compatible
//                              plugins</b> which are listed here, might be incompatible too!',
//                    JTLWCC_TEXT_DOMAIN),
//            ];
        }

        //Add sectionend
        $fields[] = [
            'type' => 'sectionend',
        ];

        return $fields;
    }

    // <editor-fold defaultstate="collapsed" desc="CustomOutputFields">

    private static function displayNanvigation($page): void
    {
        //phpcs:disable
        ?>
        <div class="container-fluid mb-3 navbar navbar-dark bg-dark">
            <nav class="nav nav-pills nav-fill flex-column flex-sm-row " id="jtlNavbar">
                <a class="navbar-brand" href="https://guide.jtl-software.de/jtl-connector/woocommerce/" target="_blank">
                    <img src=" https://www.jtl-software.de/site/themes/jtlwebsite/assets/dist/images/logos/jtl-logo.svg"
                         width="120" height="30" class="d-inline-block align-top" alt="JTL-Software">
                    Connector
                </a>
                <a class="flex-sm-fill text-sm-center nav-link <?php if (strcmp($page, 'information_page') === 0) {
                    print 'active';
                                                               } ?>"
                   href="admin.php?page=woo-jtl-connector-information"><?php print __(
                       'Information',
                       JTLWCC_TEXT_DOMAIN
                   ); ?></a>
                <a class="flex-sm-fill text-sm-center nav-link <?php if (strcmp($page, 'advanced_page') === 0) {
                    print 'active';
                                                               } ?>"
                   href="admin.php?page=woo-jtl-connector-advanced"><?php print __(
                       'Advanced Settings',
                       JTLWCC_TEXT_DOMAIN
                   ); ?></a>
                <a class="flex-sm-fill text-sm-center nav-link <?php if (strcmp($page, 'delivery_time_page') === 0) {
                    print 'active';
                                                               } ?>"
                   href="admin.php?page=woo-jtl-connector-delivery-time"><?php print __(
                       'Delivery times',
                       JTLWCC_TEXT_DOMAIN
                   ); ?></a>
                <a class="flex-sm-fill text-sm-center nav-link <?php if (strcmp($page, 'customer_order_page') === 0) {
                    print 'active';
                                                               } ?>"
                   href="admin.php?page=woo-jtl-connector-customer-order"><?php print __(
                       'Customer orders',
                       JTLWCC_TEXT_DOMAIN
                   ); ?></a>
                <a class="flex-sm-fill text-sm-center nav-link <?php if (strcmp($page, 'customers_page') === 0) {
                    print 'active';
                                                               } ?>"
                   href="admin.php?page=woo-jtl-connector-customers"><?php print __(
                       'Customers',
                       JTLWCC_TEXT_DOMAIN
                   ); ?></a>
                <a class="flex-sm-fill text-sm-center nav-link <?php if (
                    strcmp(
                        $page,
                        'developer_settings_page'
                    ) === 0) {
                         print 'active';
                   } ?>"
                   href="admin.php?page=woo-jtl-connector-developer-settings"><?php print __(
                       'Developer Settings',
                       JTLWCC_TEXT_DOMAIN
                   ); ?></a>
                <a class="flex-sm-fill text-sm-center nav-link"
                   href="https://guide.jtl-software.de/jtl-connector/woocommerce/"
                   target="_blank"><?php print __(
                       'JTL-Guide',
                       JTLWCC_TEXT_DOMAIN
                   ); ?></a>


            </nav>
        </div>
        <?php
        //phpcs:enable
    }

    /**
     * @param Db $db
     * @return void
     * @throws ParseException
     * @throws \Psr\Log\InvalidArgumentException
     */
    private static function update(Db $db): void
    {
        $wpdb = $db->getWpDb();

        $installed_version = Config::get(Config::OPTIONS_INSTALLED_VERSION, '');
        $installed_version = version_compare($installed_version, '1.3.0', '<') ? '1.0' : $installed_version;

        switch ($installed_version) {
            case '1.0':
                self::update_to_multi_linking();
            // no break
            case '1.3.0':
            case '1.3.1':
                self::update_multi_linking_endpoint_types($db);
            // no break
            case '1.3.2':
            case '1.3.3':
            case '1.3.4':
            case '1.3.5':
            case '1.4.0':
            case '1.4.1':
            case '1.4.2':
            case '1.4.3':
            case '1.4.4':
            case '1.4.5':
            case '1.4.6':
            case '1.4.7':
            case '1.4.8':
            case '1.4.9':
            case '1.4.10':
            case '1.4.11':
            case '1.4.12':
            case '1.5.0':
                self::add_specifc_linking_tables($db);
            // no break
            case '1.5.1':
            case '1.5.2':
            case '1.5.3':
            case '1.5.4':
            case '1.5.5':
            case '1.5.6':
            case '1.5.7':
            case '1.6.0':
                self::set_linking_table_name_prefix_correctly();
            // no break
            case '1.6.1':
            case '1.6.2':
            case '1.6.3':
            case '1.6.4':
            case '1.7.0':
            case '1.7.1':
                self::createManufacturerLinkingTable($db);
            // no break
            case '1.8.0':
            case '1.8.0.1':
                //hotfix
            case '1.8.0.2':
                //hotfix
            case '1.8.0.3':
                //hotfix
            case '1.8.0.4':
                //hotfix
            case '1.8.0.5':
                //hotfix
            case '1.8.0.6':
                //hotfix
            case '1.8.0.7':
                //hotfix
            case '1.8.0.8':
                //hotfix
            case '1.8.0.9':
                //hotfix
            case '1.8.0.10':
                //hotfix
            case '1.8.0.11':
                //hotfix
            case '1.8.0.12':
                //hotfix
            case '1.8.0.13':
                //hotfix
            case '1.8.0.14':
                //hotfix
            case '1.8.0.15':
                //hotfix
            case '1.8.0.16':
                //hotfix
            case '1.8.0.17':
                //hotfix
            case '1.8.0.18':
                //hotfix
            case '1.8.0.19':
                //hotfix
            case '1.8.1':
                //hotfix
            case '1.8.1.1':
                //hotfix
            case '1.8.1.2':
                //hotfix
            case '1.8.1.3':
                //hotfix
            case '1.8.1.4':
                //hotfix
            case '1.8.1.5':
                //hotfix
            case '1.8.1.6':
                //hotfix
            case '1.8.1.7':
                //hotfix
            case '1.8.1.8':
                //hotfix
            case '1.8.1.9':
                //hotfix
            case '1.8.2':
            case '1.8.2.1':
                //hotfix
            case '1.8.2.2':
                //hotfix
            case '1.8.2.3':
                //hotfix
            case '1.8.2.4':
                //hotfix
                $dropOldQuery = 'DROP TABLE IF EXISTS `%s`;';
                $wpdb->query(sprintf($dropOldQuery, $wpdb->prefix . 'jtl_connector_link_customer_group'));
                self::createCustomerGroupLinkingTable();
            // no break
            case '1.8.2.5':
                //hotfix
            case '1.8.2.6':
                //hotfix
            case '1.8.2.7':
                //hotfix
            case '1.8.2.8':
                //hotfix
            case '1.8.2.9':
                //hotfix
            case '1.8.2.10':
                //hotfix
            case '1.8.3':
            case '1.8.3.1':
                //hotfix
            case '1.8.3.2':
                //hotfix
            case '1.8.4':
            case '1.8.4.1':
                //hotfix
            case '1.8.4.2':
                //hotfix
            case '1.8.4.3':
                //hotfix
            case '1.8.4.4':
                //hotfix
            case '1.8.4.5':
                //hotfix
            case '1.8.4.6':
                //hotfix
            case '1.8.5':
            case '1.9.0':
                //wc 4
            case '1.9.0.1':
                //hotfix
            case '1.9.0.2':
                //hotfix
            case '1.9.1':
            case '1.9.2':
            case '1.9.3':
            case '1.9.4':
            case '1.9.5':
            case '1.9.5.1':
                //hotfix
            case '1.9.5.2':
            case '1.10.0':
            case '1.11.0':
            case '1.11.1':
            case '1.12.0':
            case '1.13.0':
            case '1.13.1':
            case '1.14.0':
            case '1.14.1':
            case '1.14.2':
            case '1.15.0':
            case '1.15.1':
            case '1.15.2':
            case '1.16.0':
            case '1.16.1':
                if (empty(Config::get(Config::OPTIONS_TOKEN))) {
                    Config::set(Config::OPTIONS_TOKEN, self::create_password());
                }
            // no break
            case '1.17.0':
            case '1.18.0':
            case '1.19.0':
            case '1.20.0':
            case '1.21.0':
            case '1.21.1':
            case '1.22.0':
            case '1.23.0':
            case '1.23.1':
            case '1.23.2':
            case '1.24.0':
            case '1.24.1':
            case '1.25.0':
                self::createTaxClassLinkingTable();
            // no break
            case '1.26.0':
            case '1.26.1':
            case '1.26.2':
            case '1.27.0':
                self::setupDefaultOrderStatusesToImport();
            // no break
            case '1.27.1':
            case '1.28.0':
            case '1.28.1':
            case '1.29.0':
                self::setupDefaultManualPaymentTypes();
            // no break
            case '1.30.0':
            case '1.31.0':
            case '1.32.0':
            case '1.32.1':
            case '1.33.0':
            case '1.34.0':
            case '1.35.0':
            case '1.35.1':
            case '1.36.0':
            case '1.37.0':
            case '1.38.0':
            case '1.39.0':
            case '1.39.1':
            case '1.39.3':
            case '1.39.4':
            case '1.39.5':
            case '1.39.6':
            case '1.39.7':
            case '1.39.8':
            case '1.39.9':
            case '1.39.10':
            case '1.40.0':
            case '1.40.1':
            case '1.40.2':
            case '1.40.3':
            case '1.40.4':
            case '1.41.0':
            case '1.41.1':
            case '1.41.2':
            case '1.42.0':
            case '1.42.1':
            case '1.42.2':
            case '2.0.0':
            case '2.0.1':
            case '2.0.2':
            case '2.0.3':
            case '2.0.4':
                self::updateImageIdentities($db);
            // no break
            case '2.0.5':
            case '2.0.6':
            case '2.0.6.1':
            default:
                self::activate_linking();
        }

        Config::updateDeveloperLoggingSettings((bool) Config::get(
            Config::OPTIONS_DEVELOPER_LOGGING,
            false
        ));
        Config::set(Config::OPTIONS_INSTALLED_VERSION, Config::getBuildVersion());
        self::updateDeliveryTimeCalc();
    }

    protected static function updateImageIdentities(Db $db): void
    {
        $imageMapping = [
            IdentityType::PRODUCT => IdentityType::PRODUCT_IMAGE,
            IdentityType::CATEGORY => IdentityType::CATEGORY_IMAGE,
            IdentityType::MANUFACTURER => IdentityType::MANUFACTURER_IMAGE,
            IdentityType::SPECIFIC => IdentityType::SPECIFIC_IMAGE,
            IdentityType::SPECIFIC_VALUE => IdentityType::SPECIFIC_VALUE_IMAGE,
            IdentityType::PRODUCT_VARIATION_VALUE => IdentityType::PRODUCT_VARIATION_VALUE_IMAGE,
            IdentityType::CONFIG_GROUP => IdentityType::CONFIG_GROUP_IMAGE,
        ];

        foreach ($imageMapping as $relationType => $identityType) {
            $updateIdentityQuery = sprintf(
                'UPDATE `%sjtl_connector_link_image` SET `type` = %d WHERE `type` = %d',
                $db->getWpDb()->prefix,
                $relationType,
                $identityType
            );
            $db->query($updateIdentityQuery);
        }
    }


    /**
     * @return void
     */
    private static function update_to_multi_linking(): void //phpcs:ignore
    {
        global $wpdb;

        $query =
            'CREATE TABLE IF NOT EXISTS `%s` (
                `endpoint_id` varchar(255) NOT NULL,
                `host_id` INT(10) NOT NULL,
                PRIMARY KEY (`endpoint_id`, `host_id`),
                INDEX (`host_id`),
                INDEX (`endpoint_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci';

        $result = true;
        $wpdb->query('START TRANSACTION');

        $result = $result && $wpdb->query(sprintf($query, 'jtl_connector_link_category'));
        $result = $result && $wpdb->query(sprintf($query, 'jtl_connector_link_customer'));
        $result = $result && $wpdb->query(sprintf($query, 'jtl_connector_link_product'));
        $result = $result && $wpdb->query(sprintf($query, 'jtl_connector_link_image'));
        $result = $result && $wpdb->query(sprintf($query, 'jtl_connector_link_order'));
        $result = $result && $wpdb->query(sprintf($query, 'jtl_connector_link_payment'));
        $result = $result && $wpdb->query(sprintf($query, 'jtl_connector_link_crossselling'));

        $types = $wpdb->get_results('SELECT type FROM `jtl_connector_link` GROUP BY type');

        foreach ($types as $type) {
            $type      = (int) $type->type;
            $tableName = self::get_table_name($type);
            $result    = $result && $wpdb->query("
                INSERT INTO `{$tableName}` (`host_id`, `endpoint_id`)
                SELECT `host_id`, `endpoint_id` FROM `jtl_connector_link` WHERE `type` = {$type}
            ");
        }

        if ($result) {
            $wpdb->query('DROP TABLE IF EXISTS `jtl_connector_link`');
            $wpdb->query('COMMIT');
        } else {
            $wpdb->query('ROLLBACK');
            Config::set(Config::OPTIONS_UPDATE_FAILED, 'yes');
            add_action('admin_notices', 'update_failed');
        }
    }

    /**
     * @param $type
     *
     * @return string|null
     */
    private static function get_table_name( $type ): ?string//phpcs:ignore
    {
        switch ($type) {
            case IdentityType::CATEGORY:
                return 'jtl_connector_link_category';
            case IdentityType::CUSTOMER:
                return 'jtl_connector_link_customer';
            case IdentityType::PRODUCT:
                return 'jtl_connector_link_product';
            case 16:
                return 'jtl_connector_link_image';
            case IdentityType::CUSTOMER_ORDER:
                return 'jtl_connector_link_order';
            case IdentityType::PAYMENT:
                return 'jtl_connector_link_payment';
            case IdentityType::CROSS_SELLING:
                return 'jtl_connector_link_crossselling';
        }

        return null;
    }

    /**
     * @return void
     */
    private static function update_multi_linking_endpoint_types($db): void //phpcs:ignore
    {
        global $wpdb;

        // Modify varchar endpoint_id to integer
        $modifyEndpointType = 'ALTER TABLE `%s` MODIFY `endpoint_id` BIGINT(20) unsigned';
        $wpdb->query(sprintf($modifyEndpointType, 'jtl_connector_link_order'));
        $wpdb->query(sprintf($modifyEndpointType, 'jtl_connector_link_payment'));
        $wpdb->query(sprintf($modifyEndpointType, 'jtl_connector_link_product'));
        $wpdb->query(sprintf($modifyEndpointType, 'jtl_connector_link_crossselling'));
        $wpdb->query(sprintf($modifyEndpointType, 'jtl_connector_link_category'));

        // Add is_guest column for customers instead of using a prefix
        $wpdb->query('ALTER TABLE `jtl_connector_link_customer` ADD COLUMN `is_guest` BIT');
        $wpdb->query(sprintf(
            'UPDATE `jtl_connector_link_customer` 
            SET `is_guest` = 1
            WHERE `endpoint_id` LIKE "%s_%%"',
            Id::GUEST_PREFIX
        ));
        $wpdb->query(sprintf(
            'UPDATE `jtl_connector_link_customer` 
            SET `is_guest` = 0
            WHERE `endpoint_id` NOT LIKE "%s_%%"',
            Id::GUEST_PREFIX
        ));

        // Add type column for images instead of using a prefix
        $wpdb->query('ALTER TABLE `jtl_connector_link_image` ADD COLUMN `type` INT(4) unsigned');
        $updateImageLinkingTable = '
            UPDATE `jtl_connector_link_image` 
            SET `type` = %d, `endpoint_id` = SUBSTRING(`endpoint_id`, 3)
            WHERE `endpoint_id` LIKE "%s_%%"';
        $wpdb->query(sprintf(
            $updateImageLinkingTable,
            IdentityType::CATEGORY,
            Id::CATEGORY_PREFIX
        ));
        $wpdb->query(sprintf(
            $updateImageLinkingTable,
            IdentityType::PRODUCT,
            Id::PRODUCT_PREFIX
        ));

        self::add_constraints_for_multi_linking_tables('jtl_connector_link_', $db);
    }

    /**
     * @param Db $db
     * @return void
     * @throws \Psr\Log\InvalidArgumentException
     */
    private static function add_specifc_linking_tables(Db $db): void //phpcs:ignore
    {
        $wpdb = $db->getWpDb();

        $query = '
            CREATE TABLE IF NOT EXISTS `%s` (
                `endpoint_id` BIGINT(20) unsigned NOT NULL,
                `host_id` INT(10) unsigned NOT NULL,
                PRIMARY KEY (`endpoint_id`, `host_id`),
                INDEX (`host_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci';

        $wpdb->query(sprintf($query, 'jtl_connector_link_specific'));
        $wpdb->query(sprintf($query, 'jtl_connector_link_specific_value'));

        $engine = $wpdb->get_var(sprintf(
            "SELECT ENGINE
            FROM information_schema.TABLES
            WHERE TABLE_NAME = '{$wpdb->posts}' AND TABLE_SCHEMA = '%s'",
            DB_NAME
        ));

        if ($engine === 'InnoDB') {
            if (
                ! $db->checkIfFKExists(
                    'jtl_connector_link_category',
                    'jtl_connector_link_category_1'
                )
            ) {
                $wpdb->query(
                    "ALTER TABLE `jtl_connector_link_category`
                            ADD CONSTRAINT `jtl_connector_link_category_1` FOREIGN KEY (`endpoint_id`) 
                            REFERENCES `{$wpdb->terms}` (`term_id`) ON DELETE CASCADE ON UPDATE NO ACTION"
                );
            }

            $table = $wpdb->prefix . 'woocommerce_attribute_taxonomies';
            if (
                ! $db->checkIfFKExists(
                    'jtl_connector_link_specific',
                    'jtl_connector_link_specific_1'
                )
            ) {
                $wpdb->query("
                ALTER TABLE `jtl_connector_link_specific`
                ADD CONSTRAINT `jtl_connector_link_specific_1` FOREIGN KEY (`endpoint_id`) 
                REFERENCES `{$table}` (`attribute_id`) ON DELETE CASCADE ON UPDATE NO ACTION");
            }
        }
    }

    /**
     * @return void
     */
    private static function set_linking_table_name_prefix_correctly(): void //phpcs:ignore
    {
        global $wpdb;

        $query = 'RENAME TABLE %s TO %s%s;';

        $tables = [
            'jtl_connector_category_level',
            'jtl_connector_link_category',
            'jtl_connector_link_crossselling',
            'jtl_connector_link_customer',
            'jtl_connector_link_image',
            'jtl_connector_link_order',
            'jtl_connector_link_payment',
            'jtl_connector_link_product',
            'jtl_connector_link_shipping_class',
            'jtl_connector_link_specific',
            'jtl_connector_link_specific_value',
            'jtl_connector_product_checksum',
        ];
        foreach ($tables as $table) {
            $sql = sprintf($query, $table, $wpdb->prefix, $table);
            $wpdb->query($sql);
        }
    }

    /**
     * @return void
     */
    protected static function setupDefaultOrderStatusesToImport(): void
    {
        if (Config::get(Config::OPTIONS_DEFAULT_ORDER_STATUSES_TO_IMPORT) === null) {
            $statusList = Config::JTLWCC_CONFIG_DEFAULTS[ Config::OPTIONS_DEFAULT_ORDER_STATUSES_TO_IMPORT ];
            if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_VR_PAY_ECOMMERCE_WOOCOMMERCE)) {
                $statusList[] = 'wc-payment-accepted';
            }

            $includeCompletedOrdersOption = Config::get(Config::OPTIONS_COMPLETED_ORDERS, 'yes');
            if (in_array($includeCompletedOrdersOption, [ 'yes', '1' ], true)) {
                $statusList[] = 'wc-completed';
            }

            Config::set(Config::OPTIONS_DEFAULT_ORDER_STATUSES_TO_IMPORT, $statusList);
        }
    }

    /**
     * @return void
     */
    protected static function setupDefaultManualPaymentTypes(): void
    {
        if (Config::get(Config::OPTIONS_DEFAULT_MANUAL_PAYMENT_TYPES) === null) {
            $paymentTypes = Config::JTLWCC_CONFIG_DEFAULTS[ Config::OPTIONS_DEFAULT_MANUAL_PAYMENT_TYPES ];
            Config::set(Config::OPTIONS_DEFAULT_MANUAL_PAYMENT_TYPES, $paymentTypes);
        }
    }

    /**
     * @return void
     */
    protected static function updateDeliveryTimeCalc(): void
    {
        if (is_multisite()) {
            $sites = get_sites();

            foreach ($sites as $site) {
                switch_to_blog($site->blog_id);
                if (in_array(Config::get(Config::OPTIONS_USE_DELIVERYTIME_CALC), [ "1", "0" ], true)) {
                    update_blog_option(
                        $site->blog_id,
                        'jtlconnector_use_deliverytime_calc',
                        Config::get(Config::OPTIONS_USE_DELIVERYTIME_CALC)
                            ? 'delivery_time_calc'
                            : 'deactivated'
                    );
                }
                restore_current_blog();
            }
        }

        if (in_array(Config::get(Config::OPTIONS_USE_DELIVERYTIME_CALC), [ "1", "0" ], true)) {
            update_option(
                'jtlconnector_use_deliverytime_calc',
                Config::get(
                    Config::OPTIONS_USE_DELIVERYTIME_CALC
                )
                    ? 'delivery_time_calc'
                    : 'deactivated'
            );
        }
    }

    /**
     * @return void
     */
    public static function checkIfDefaultCustomerGroupIsSet(): void
    {
        $defaultCustomerGroup = Config::get(Config::OPTIONS_DEFAULT_CUSTOMER_GROUP);

        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)) {
            global $wpdb;
            $sql                     = SqlHelper::customerGroupPull();
            $b2bMarketCustomerGroups = (new Db($wpdb))->query($sql);
            if (
                count($b2bMarketCustomerGroups) > 0
                && ! in_array($defaultCustomerGroup, array_column($b2bMarketCustomerGroups, 'ID'))
            ) {
                add_action('admin_notices', [ self::class, 'default_customer_group_not_updated' ]);
            }
        }
    }

    /**
     * @return void
     */
    public static function checkIfPullCustomerGroupIsSet(): void
    {
        if (
            SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)
            && ! Config::has(Config::OPTIONS_PULL_CUSTOMER_GROUPS)
        ) {
            add_action('admin_notices', [ self::class, 'pull_customer_group_not_updated' ]);
        }
    }

    public static function jtlconnector_plugin_row_meta( $links, $file )//phpcs:ignore
    {
        if (str_contains($file, 'woo-jtl-connector.php')) {
            $url       = esc_url('https://guide.jtl-software.de/jtl/Kategorie:JTL-Connector:WooCommerce');
            $new_links = [
                '<a target="_blank" href="' . $url . '">' . __('Documentation', JTLWCC_TEXT_DOMAIN) . '</a>',
            ];
            $links     = array_merge($links, $new_links);
        }

        return $links;
    }

    public static function settings_link( $links = [] )//phpcs:ignore
    {
        $settings_link = '<a href="admin.php?page=woo-jtl-connector-information">' . __(
            'Settings',
            JTLWCC_TEXT_DOMAIN
        ) . '</a>';

        array_unshift($links, $settings_link);

        return $links;
    }

    // </editor-fold>

    /**
     * @param array $field
     *
     * @return void
     */
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public static function jtl_date_field(array $field): void
    {
        $option_value = $field['default'];// get_option($field['id'], $field['default']);

        ?>

        <div class="form-group row">
            <label for="<?= $field['id'] ?>" class="col-12 col-form-label"><?= $field['title'] ?></label>
            <div class="col-12">
                <input class="form-control" type="date"
                       value="<?= isset($field['value'])
                                  && ! is_null($field['value'])
                                  && $field['value'] !== '' ? $field['value'] : $option_value ?>"
                       id="<?= $field['id'] ?>"
                       name="<?= $field['id'] ?>">
            </div>
            <?php
            if (isset($field['helpBlock']) && $field['helpBlock'] !== '') {
                ?>
                <small id="<?= $field['id'] ?>_helpBlock" class="form-text text-muted col-12">
                    <?= $field['helpBlock'] ?>
                </small>
                <?php
            }
            ?>
        </div>
        <?php
    }

    // <editor-fold defaultstate="collapsed" desc="Update">

    /**
     * @param array $field
     *
     * @return void
     */
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public static function connector_password_field(array $field): void
    {
        ?>
        <div class="form-group row">
            <label class="col-12" for="<?= $field['id'] ?>"><?= $field['title'] ?></label>
            <div class="input-group col-12">
                <input type="text"
                       class="form-control"
                       aria-label="Connector Password"
                       aria-describedby="<?= $field['id'] ?>_btn"
                       id="<?= $field['id'] ?>"
                       value="<?= $field['value'] ?>"
                       readonly="readonly">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary"
                            type="button"
                            title="Copy"
                            id="<?= $field['id'] ?>_btn"
                            onclick="
                                let text = document.getElementById('connector_password').value;
                                let dummy = document.createElement('textarea');
                                document.body.appendChild(dummy);
                                dummy.value = text;
                                dummy.select();
                                document.execCommand('copy');
                                document.body.removeChild(dummy);
                        ">Copy
                    </button>
                </div>
            </div>
            <?php
            if (isset($field['helpBlock']) && $field['helpBlock'] !== '') {
                ?>
                <small id="<?= $field['id'] ?>_helpBlock" class="form-text text-muted col-12">
                    <?= $field['helpBlock'] ?>
                </small>
                <?php
            }
            ?>
        </div>
        <?php
    }

    /**
     * @param array $field
     *
     * @return void
     */
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public static function connector_url_field(array $field): void
    {
        ?>
        <div class="form-group row">
            <label class="col-12" for="<?= $field['id'] ?>"><?= $field['title'] ?></label>
            <div class="input-group col-12">
                <input type="text"
                       class="form-control"
                       aria-label="Connector URL"
                       aria-describedby="<?= $field['id'] ?>_btn"
                       id="<?= $field['id'] ?>"
                       value="<?= $field['value'] ?>"
                       readonly="readonly">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary"
                            type="button"
                            title="Copy"
                            id="<?= $field['id'] ?>_btn"
                            onclick="
                                let text = document.getElementById('connector_url').value;
                                let dummy = document.createElement('textarea');
                                document.body.appendChild(dummy);
                                dummy.value = text;
                                dummy.select();
                                document.execCommand('copy');
                                document.body.removeChild(dummy);
                        ">Copy
                    </button>
                </div>
            </div>
            <?php
            if (isset($field['helpBlock']) && $field['helpBlock'] !== '') {
                ?>
                <small id="<?= $field['id'] ?>_helpBlock" class="form-text text-muted col-12">
                    <?= $field['helpBlock'] ?>
                </small>
                <?php
            }
            ?>
        </div>
        <?php
    }

    /**
     * @param array $field
     *
     * @return void
     */
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public static function not_compatible_plugins_field(array $field): void
    {
        ?>
        <div class="form-group row">
            <h2 class="col-12 mb-4"><?php echo $field['title']; ?></h2>
            <ul class="list-group col-12 pl-3">
                <?php
                $change = false;
                if (count($field['plugins']) > 0) {
                    foreach ($field['plugins'] as $key => $value) {
                        //phpcs:disable
                        ?>
                        <li class="list-group-item <?php $change ? print( 'list-group-item-light' ) : print( '' ); ?>"><?php print $value; ?></li> <?php
                        $change = ! $change;
                        //phpcs:enable
                    }
                }
                ?>
            </ul>
        </div>
        <?php
    }

    /**
     * @param array $field
     *
     * @return void
     */
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public static function jtlwcc_card(array $field): void
    {
        ?>
        <div class="card <?php echo isset($field['center']) && $field['center'] ? 'text-center' : ''; ?> col-12 pl-3
        <?php echo isset($field['color']) && $field['color'] !== '' ? $field['color'] : 'bg-light'; ?>">
            <div class="card-header bg-transparent
             <?php echo isset($field['color']) && $field['color'] !== '' ? $field['color'] : 'bg-light'; ?>
              <?php echo isset($field['text-color']) && $field['text-color'] !== '' ? $field['text-color'] : ''; ?>">
                <h5 class="card-title"><?php echo $field['title']; ?></h5>
            </div>
            <div class="card-body bg-transparent
            <?php echo isset($field['color']) && $field['color'] !== '' ? $field['color'] : 'bg-light'; ?>
                <?php echo isset($field['text-color']) && $field['text-color'] !== '' ? $field['text-color'] : ''; ?>">

                <p class="card-text"><?php echo $field['text']; ?></p>
                <!--  <a href="#" class="btn btn-primary">Go somewhere</a>-->
            </div>
        </div>
        <?php
    }

    /**
     * @param array $field
     *
     * @return void
     */
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public static function compatible_plugins_field(array $field): void
    {
        ?>
        <div class="form-group row">
            <h2 class="col-12 mb-4"><?php echo $field['title']; ?></h2>
            <ul class="list-group col-12 pl-3">
                <?php
                $change = false;
                if (count($field['plugins']) > 0) {
                    foreach ($field['plugins'] as $key => $value) {
                        //phpcs:disable
                        ?>
                        <li class="list-group-item <?php $change ? print( 'list-group-item-light' ) : print( '' ); ?>">
                            <?php print isset($value['Name']) && $value['Name'] !== '' ? $value['Name'] : $value['Name']; ?>
                            -
                            <?php print isset($value['Version']) && $value['Version'] !== '' ? $value['Version'] : $value['Version']; ?>
                            (<a target="_blank"
                                href="<?php print isset($value['AuthorURI']) && $value['AuthorURI'] !== '' ? $value['AuthorURI'] : '#'; ?>">
                                <?php print isset($value['Author']) && $value['Author'] !== '' ? $value['Author'] : $value['Author']; ?>
                            </a>)
                        </li>
                        <?php
                        //phpcs:enable
                        $change = ! $change;
                    }
                }
                ?>
            </ul>
        </div>
        <?php
    }

    /**
     * @param array $field
     *
     * @return void
     */
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public static function paragraph_field(array $field): void
    {
        ?>
        <div class="form-group row">
            <label for="statictext_<?= wc_sanitize_taxonomy_name($field['title']) ?>"
                   class="col-12 col-form-label"><?= $field['title'] ?></label>
            <div class="col-12">
                <input type="text" readonly class="form-control-plaintext"
                       id="statictext_<?= wc_sanitize_taxonomy_name($field['title']) ?>"
                       value="<?= $field['desc'] ?>">
                <?php
                if (isset($field['helpBlock']) && $field['helpBlock'] !== '') {
                    ?>
                    <small id="<?= $field['id'] ?>_helpBlock" class="form-text text-muted">
                        <?= $field['helpBlock'] ?>
                    </small>
                    <?php
                }
                ?>
            </div>
        </div>
        <?php
    }

    // <editor-fold defaultstate="collapsed" desc="Update 1.3.0">

    /**
     * @param array $field
     *
     * @return void
     */
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public static function active_true_false_radio_btn(array $field): void
    {
        //phpcs:disable
        ?>
        <div class="form-group row">
            <label class="col-12" for="true_false_radio_<?= $field['id'] ?>"><?= $field['title'] ?></label>
            <div class="true_false_radio col-12 " name="true_false_radio_<?= $field['id'] ?>">
                <div class="custom-control custom-radio">
                    <input type="radio" id="<?= $field['id'] ?>_1" name="<?= $field['id'] ?>" value="true"
                           class="custom-control-input "
                        <?php if ($field['value']) {
                            print 'checked';
                        } ?>
                    >
                    <label class="custom-control-label <?php if ($field['value']) {
                        print 'active';
                                                       } ?>" for="<?= $field['id'] ?>_1"><?= $field['trueText'] ?></label>
                </div>
                <div class="custom-control custom-radio">
                    <input type="radio" id="<?= $field['id'] ?>_2" name="<?= $field['id'] ?>" value="false"
                           class="custom-control-input "
                        <?php if (! $field['value']) {
                            print 'checked';
                        } ?>
                    >
                    <label class="custom-control-label  <?php if (! $field['value']) {
                        print 'active';
                                                        } ?>" for="<?= $field['id'] ?>_2"><?= $field['falseText'] ?></label>
                </div>
            </div>
            <?php
            if (isset($field['desc']) && $field['desc'] !== '') {
                ?>
                <small id="<?= $field['id'] ?>_desc" class="form-text text-muted col-12">
                    <?= $field['desc'] ?>
                </small>
                <?php
            }
            ?>
        </div>
        <?php
        //phpcs:enable
    }

    /**
     * @param array $field
     *
     * @return void
     */
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public static function jtl_connector_select(array $field): void
    {
        ?>
        <div class="form-group row">
            <label class="col-12" for="<?= $field['id'] ?>"><?= $field['title'] ?></label>
            <select required class="form-control custom-select col-12 ml-3" name="<?= $field['id'] ?>">
                <?php
                if (isset($field['options']) && is_array($field['options']) && count($field['options']) > 0) {
                    foreach ($field['options'] as $key => $ovalue) {
                        ?>
                        <option value="<?php print $key; ?>" <?php if ((string) $key === $field['value']) {
                            print 'selected';
                                       } ?>><?php print $ovalue; ?></option> <?php
                    }
                }
                ?>
            </select>
            <?php
            if (isset($field['helpBlock']) && $field['helpBlock'] !== '') {
                ?>
                <small id="<?= $field['id'] ?>_helpBlock" class="form-text text-muted col-12">
                    <?= $field['helpBlock'] ?>
                </small>
                <?php
            }
            ?>
        </div>
        <?php
    }
    // </editor-fold>

    // <editor-fold defaultstate="collapsed" desc="Update 1.3.2">

    /**
     * @param array $field
     *
     * @return void
     */
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public static function jtl_connector_multiselect(array $field): void
    {
        ?>
        <div class="form-group row">
            <label class="col-12" for="<?= $field['id'] ?>"><?= $field['title'] ?></label>
            <select required multiple class="form-control custom-select col-12 ml-3" name="<?= $field['id'] ?>[]">
                <?php
                if (isset($field['options']) && is_array($field['options']) && count($field['options']) > 0) {
                    foreach ($field['options'] as $key => $ovalue) { ?>
                        <option value="<?php print $key; ?>" <?php if (in_array($key, $field['value'])) {
                            print 'selected="selected"';
                                       } ?>><?php print $ovalue; ?> </option>
                    <?php }
                } ?>
            </select>
            <?php
            if (isset($field['helpBlock']) && $field['helpBlock'] !== '') {
                ?>
                <small id="<?= $field['id'] ?>_helpBlock" class="form-text text-muted col-12">
                    <?= $field['helpBlock'] ?>
                </small>
                <?php
            }
            ?>
        </div>
        <?php
    }

    /**
     * @param array $field
     *
     * @return void
     */
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public static function dev_log_btn(array $field): void
    {
        ?>
        <div class="form-group row">
            <div class="btn-group btn-group-lg col-12" role="group">
                <button type="button" id="downloadLogBtn"
                        class="btn btn-outline-success"><?= $field['downloadText'] ?></button>
                <button type="button" id="clearLogBtn"
                        class="btn btn-outline-danger"><?= $field['clearLogsText'] ?></button>
            </div>
        </div>
        <?php
    }

    // </editor-fold>

    // <editor-fold defaultstate="collapsed" desc="Update 1.5.0">

    /**
     * @param array $field
     *
     * @return void
     */
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public static function jtl_text_input(array $field): void
    {
        ?>
        <div class="form-group row">
            <label class="col-12" for="<?= $field['id'] ?>"><?= $field['title'] ?></label>
            <input
                type="text"
                class="form-control col-12 ml-3"
                id="<?= $field['id'] ?>"
                name="<?= $field['id'] ?>"
                value="<?= $field['value'] ?>"
            >
            <?php
            if (isset($field['helpBlock']) && $field['helpBlock'] !== '') {
                ?>
                <small id="<?= $field['id'] ?>_helpBlock" class="form-text text-muted col-12">
                    <?= $field['helpBlock'] ?>
                </small>
                <?php
            }
            ?>
        </div>
        <?php
    }
    // </editor-fold>

    // <editor-fold defaultstate="collapsed" desc="Update 1.6.0">

    /**
     * @param array $field
     *
     * @return void
     */
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public static function jtl_number_input(array $field): void
    {
        ?>
        <div class="form-group row">
            <label class="col-12" for="<?= $field['id'] ?>"><?= $field['title'] ?></label>
            <input
                type="number"
                class="form-control col-12 ml-3"
                id="<?= $field['id'] ?>"
                name="<?= $field['id'] ?>"
                value="<?= $field['value'] ?>"
            >
            <?php
            if (isset($field['helpBlock']) && $field['helpBlock'] !== '') {
                ?>
                <small id="<?= $field['id'] ?>_helpBlock" class="form-text text-muted col-12">
                    <?= $field['helpBlock'] ?>
                </small>
                <?php
            }
            ?>
        </div>
        <?php
    }
    // </editor-fold>

    // <editor-fold defaultstate="collapsed" desc="Update 1.7.1">

    /**
     * @param array $field
     *
     * @return void
     */
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public static function jtl_checkbox(array $field): void
    {
        ?>
        <div class="form-group row">
            <label class="col-12" for="<?= $field['id'] ?>"><?= $field['title'] ?></label>

            <input type="checkbox" class="form-control col-12" id="<?= $field['id'] ?>" name="<?= $field['id'] ?>"
                <?php if ($field['value']) {
                    print 'checked';
                } ?>>

            <textarea type="text" class="form-control" aria-label="Text input with checkbox"
                      readonly><?= $field['desc'] ?> </textarea>

        </div>
        <?php
    }
    // </editor-fold>

    // <editor-fold defaultstate="collapsed" desc="Error messages">

    /**
     * Save Settings
     *
     * @return void
     */
    public static function save(): void
    {
        $settings = $_REQUEST;

        foreach ($settings as $key => $item) {
            $cast = Config::JTLWCC_CONFIG[ $key ];

            switch ($cast) {
                case 'bool':
                    if (
                        strcmp($item, 'on') === 0
                        || strcmp($item, 'true') === 0
                        || strcmp($item, '1') === 0
                        || $item === true
                    ) {
                        $value = true;
                    } else {
                        $value = false;
                    }
                    break;
                case 'int':
                    $value = (int) $item;
                    break;
                case 'float':
                    $value = (float) $item;
                    break;
                case 'array':
                    $value = $item;
                    break;
                default:
                    $value = trim($item);
                    break;
            }


            Config::set($key, $value);
        }
        Config::updateDeveloperLoggingSettings(
            (bool) Config::get(
                Config::OPTIONS_DEVELOPER_LOGGING,
                false
            )
        );

        $request = $_SERVER["HTTP_REFERER"];

        wp_redirect($request, 301);
    }

    /**
     * @return void
     */
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public static function default_customer_group_not_updated(): void
    {
        $message  = __(
            'The default customer is not set. Please update the B2B-Market 
            default customer group in the JTL-Connector settings',
            JTLWCC_TEXT_DOMAIN
        );
        $message .= ': <a href="admin.php?page=woo-jtl-connector-customers">' . strtolower(__(
            'Customer Settings',
            JTLWCC_TEXT_DOMAIN
        )) . '</a>';

        echo '<div class="notice notice-error">
                <p class="pt-3 pb-3">
                    ' . $message . '
                </p>
            </div>';
    }

    /**
     * @return void
     */
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public static function pull_customer_group_not_updated(): void
    {
        $message  = __(
            'The pull customer groups are not set. Please update the 
            B2B-Market customer pull groups in the JTL-Connector settings',
            JTLWCC_TEXT_DOMAIN
        );
        $message .= ': <a href="admin.php?page=woo-jtl-connector-customers">' . strtolower(__(
            'Customer Settings',
            JTLWCC_TEXT_DOMAIN
        )) . '</a>';

        echo '<div class="notice notice-error">
                <p class="pt-3 pb-3">
                    ' . $message . '
                </p>
            </div>';
    }

    /**
     * @return void
     */
    public function update_failed(): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        self::jtlwcc_show_wordpress_error(__(
            'The linking table migration was not successful. Please use the forum for help.',
            JTLWCC_TEXT_DOMAIN
        ));
    }

    /**
     * @return void
     */
    public function directory_no_write_access(): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        self::jtlwcc_show_wordpress_error(sprintf(
            __(
                'Directory %s has no write access.',
                sys_get_temp_dir()
            ),
            JTLWCC_TEXT_DOMAIN
        ));
    }

    /**
     * @return void
     */
    public function phar_extension(): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        self::jtlwcc_show_wordpress_error(__('PHP extension "phar" could not be found.', JTLWCC_TEXT_DOMAIN));
    }

    /**
     * @return void
     */
    public function suhosin_whitelist(): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        self::jtlwcc_show_wordpress_error(__(
            'PHP extension "phar" is not on the suhosin whitelist.',
            JTLWCC_TEXT_DOMAIN
        ));
    }
    // </editor-fold>
}