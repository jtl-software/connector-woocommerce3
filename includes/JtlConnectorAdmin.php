<?php

declare(strict_types=1);

use Jtl\Connector\Core\Definition\IdentityType;
use Jtl\Connector\Core\Exception\MissingRequirementException;
use Jtl\Connector\Core\System\Check;
use Jtl\Connector\Core\Model\CustomerGroup as CustomerGroupModel;
use Jtl\Connector\Core\Model\CustomerGroupI18n as CustomerGroupI18nModel;
use JtlWooCommerceConnector\Controllers\GlobalData\CustomerGroupController;
use JtlWooCommerceConnector\Utilities\Config;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\Id;
use JtlWooCommerceConnector\Utilities\LinkTableNames;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final class JtlConnectorAdmin //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    private static bool $initiated = false;

    /**
     * @return void
     */
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

        jtlwcc_clear_connector_cache(false);

        $parsedFile = (array) Yaml::parseFile(JTLWCC_CONNECTOR_DIR . '/build-config.yaml');

        if (jtlwcc_woocommerce_deactivated()) {
            jtlwcc_deactivate_plugin();
            add_action('admin_notices', 'jtlwcc_woocommerce_not_activated');
        } elseif (
            \array_key_exists('min_wc_version', $parsedFile) &&
            version_compare(
                $version,
                trim($parsedFile['min_wc_version']),
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
                wp_die(esc_html($exc->getMessage()));
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
                $wpdb->query("DROP TABLE IF EXISTS `" . esc_sql($oldPrefix . $table) . "`");
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
                    $wpdb->query("
    CREATE TABLE IF NOT EXISTS `" . esc_sql($prefix . $table) . "` (
        `endpoint_id` BIGINT(20) unsigned NOT NULL,
        `host_id` INT(10) unsigned NOT NULL,
        PRIMARY KEY (`endpoint_id`, `host_id`),
        INDEX (`host_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
                }
                /** @phpstan-ignore booleanNot.alwaysTrue */
            } elseif ($oldExists && !$newExists) {
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
     * @return string[]
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
     * @param string $oldName
     * @param string $newName
     *
     * @return void
     */
    private static function renameTable(string $oldName, string $newName): void
    {
        global $wpdb;

        $wpdb->query("RENAME TABLE `" . esc_sql($oldName) . "` TO `" . esc_sql($newName) . "`");
    }

    /**
     * @param Db $db
     * @return void
     * @throws \Psr\Log\InvalidArgumentException
     */
    private static function activate_category_tree(Db $db): void //phpcs:ignore
    {
        $wpdb   = $db->getWpDb();
        $prefix = $wpdb->prefix . 'jtl_connector_';
        $engine = $wpdb->get_var($wpdb->prepare(
            "SELECT ENGINE
            FROM information_schema.TABLES
            WHERE TABLE_NAME = %s AND TABLE_SCHEMA = %s",
            $wpdb->terms,
            DB_NAME
        ));

        // phpcs:ignore WordPress.DB -- esc_sql returns string for string input
        $wpdb->query("
            CREATE TABLE IF NOT EXISTS `" . esc_sql($prefix) . "category_level` (
                `category_id` BIGINT(20) unsigned NOT NULL,
                `level` int(10) unsigned NOT NULL,
                `sort` int(10) unsigned NOT NULL,
                PRIMARY KEY (`category_id`),
                INDEX (`level`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

        if ($engine === 'InnoDB') {
            if (!$db->checkIfFKExists($prefix . 'category_level', 'jtl_connector_category_level1')) {
                // phpcs:ignore WordPress.DB -- esc_sql returns string for string input
                $wpdb->query(
                    "ALTER TABLE `" . esc_sql($prefix) . "category_level`
                    ADD CONSTRAINT `jtl_connector_category_level1` FOREIGN KEY (`category_id`)
                    REFERENCES `" . esc_sql($wpdb->terms) . "` (`term_id`) ON DELETE CASCADE ON UPDATE NO ACTION"
                );
            }
        }
    }

    /**
     * @param string $prefix
     *
     * @return void
     */
    private static function activate_checksum(string $prefix): void //phpcs:ignore
    {
        global $wpdb;

        $engine = $wpdb->get_var($wpdb->prepare(
            "SELECT ENGINE
            FROM information_schema.TABLES
            WHERE TABLE_NAME = %s AND TABLE_SCHEMA = %s",
            $wpdb->posts,
            DB_NAME
        ));

        // phpcs:ignore WordPress.DB -- esc_sql returns string for string input
        $wpdb->query("
            CREATE TABLE IF NOT EXISTS `" . esc_sql($prefix) . "product_checksum` (
                `product_id` BIGINT(20) unsigned NOT NULL,
                `type` tinyint unsigned NOT NULL,
                `checksum` varchar(255) NOT NULL,
                PRIMARY KEY (`product_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

        if ($engine === 'InnoDB') {
            $db = new Db($wpdb);
            if (!$db->checkIfFKExists($prefix . 'product_checksum', 'jtl_connector_product_checksum1')) {
                // phpcs:ignore WordPress.DB -- esc_sql returns string for string input
                $wpdb->query(
                    "ALTER TABLE `" . esc_sql($prefix) . "product_checksum`
                    ADD CONSTRAINT `jtl_connector_product_checksum1` FOREIGN KEY (`product_id`)
                    REFERENCES `" . esc_sql($wpdb->posts) . "` (`ID`) ON DELETE CASCADE ON UPDATE NO ACTION"
                );
            }
        }
    }

    /**
     * @return void
     */
    private static function createCustomerLinkingTable(): void
    {
        global $wpdb;
        $wpdb->query('
            CREATE TABLE IF NOT EXISTS `' . esc_sql(LinkTableNames::CUSTOMER) . '` (
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
            CREATE TABLE IF NOT EXISTS `' . esc_sql(LinkTableNames::CUSTOMER_GROUP) . '` (
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
            CREATE TABLE IF NOT EXISTS `' . esc_sql(LinkTableNames::IMAGE) . '` (
                `endpoint_id` VARCHAR(255) NOT NULL,
                `host_id` INT(10) NOT NULL,
                `type` INT unsigned NOT NULL,
                PRIMARY KEY (`endpoint_id`, `host_id`, `type`),
                INDEX (`host_id`, `type`),
                INDEX (`endpoint_id`, `type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci');
    }

    /**
     * @param  Db $db
     * @return void
     * @throws \Psr\Log\InvalidArgumentException
     */
    private static function createManufacturerLinkingTable(Db $db): void
    {
        global $wpdb;

        $wpdb->query("
            CREATE TABLE IF NOT EXISTS `" . esc_sql($wpdb->prefix . LinkTableNames::MANUFACTURER) . "` (
                `endpoint_id` BIGINT(20) unsigned NOT NULL,
                `host_id` INT(10) unsigned NOT NULL,
                PRIMARY KEY (`endpoint_id`, `host_id`),
                INDEX (`host_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

        $engine = $wpdb->get_var($wpdb->prepare(
            "SELECT ENGINE
            FROM information_schema.TABLES
            WHERE TABLE_NAME = %s AND TABLE_SCHEMA = %s",
            $wpdb->posts,
            DB_NAME
        ));

        if ($engine === 'InnoDB') {
            if (
                !$db->checkIfFKExists(
                    $wpdb->prefix . LinkTableNames::MANUFACTURER,
                    'jtl_connector_link_manufacturer_1'
                )
            ) {
                $wpdb->query(
                    "ALTER TABLE `" . esc_sql($wpdb->prefix . LinkTableNames::MANUFACTURER) . "`
                    ADD CONSTRAINT `jtl_connector_link_manufacturer_1` FOREIGN KEY (`endpoint_id`)
                    REFERENCES `" . esc_sql($wpdb->terms) . "` (`term_id`) ON DELETE CASCADE ON UPDATE NO ACTION"
                );
            }
        }
    }

    /**
     * @return void
     */
    protected static function createTaxClassLinkingTable(): void
    {
        global $wpdb;

        $wpdb->query(
            "CREATE TABLE IF NOT EXISTS `" . esc_sql($wpdb->prefix . LinkTableNames::TAX_CLASS) . "` (
                `endpoint_id` VARCHAR(200) NOT NULL,
                `host_id` INT(10) unsigned NOT NULL,
                PRIMARY KEY (`endpoint_id`),
                UNIQUE (`host_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci"
        );
    }

    // </editor-fold>

    /**
     * @param string $prefix
     * @param Db     $db
     * @return void
     * @throws \Psr\Log\InvalidArgumentException
     */
    private static function add_constraints_for_multi_linking_tables(string $prefix, Db $db): void //phpcs:ignore
    {
        global $wpdb;

        $engine = $wpdb->get_var($wpdb->prepare(
            "SELECT ENGINE
            FROM information_schema.TABLES
            WHERE TABLE_NAME = %s AND TABLE_SCHEMA = %s",
            $wpdb->posts,
            DB_NAME
        ));

        if ($engine === 'InnoDB') {
            if (! $db->checkIfFKExists($prefix . 'product', 'jtl_connector_link_product_1')) {
                // phpcs:ignore WordPress.DB -- esc_sql returns string for string input
                $wpdb->query(
                    "ALTER TABLE `" . esc_sql($prefix) . "product`
                    ADD CONSTRAINT `jtl_connector_link_product_1` FOREIGN KEY (`endpoint_id`)
                    REFERENCES `" . esc_sql($wpdb->posts) . "` (`ID`) ON DELETE CASCADE ON UPDATE NO ACTION"
                );
            }
            if (! $db->checkIfFKExists($prefix . 'order', 'jtl_connector_link_order_1')) {
                // phpcs:ignore WordPress.DB -- esc_sql returns string for string input
                $wpdb->query(
                    "ALTER TABLE `" . esc_sql($prefix) . "order`
                    ADD CONSTRAINT `jtl_connector_link_order_1` FOREIGN KEY (`endpoint_id`)
                    REFERENCES `" . esc_sql($wpdb->posts) . "` (`ID`) ON DELETE CASCADE ON UPDATE NO ACTION"
                );
            }
            if (! $db->checkIfFKExists($prefix . 'payment', 'jtl_connector_link_payment_1')) {
                // phpcs:ignore WordPress.DB -- esc_sql returns string for string input
                $wpdb->query(
                    "ALTER TABLE `" . esc_sql($prefix) . "payment`
                    ADD CONSTRAINT `jtl_connector_link_payment_1` FOREIGN KEY (`endpoint_id`)
                    REFERENCES `" . esc_sql($wpdb->posts) . "` (`ID`) ON DELETE CASCADE ON UPDATE NO ACTION"
                );
            }
            if (! $db->checkIfFKExists($prefix . 'crossselling', 'jtl_connector_link_crossselling_1')) {
                // phpcs:ignore WordPress.DB -- esc_sql returns string for string input
                $wpdb->query(
                    "ALTER TABLE `" . esc_sql($prefix) . "crossselling`
                    ADD CONSTRAINT `jtl_connector_link_crossselling_1` FOREIGN KEY (`endpoint_id`)
                    REFERENCES `" . esc_sql($wpdb->posts) . "` (`ID`) ON DELETE CASCADE ON UPDATE NO ACTION"
                );
            }
            if (! $db->checkIfFKExists($prefix . 'category', 'jtl_connector_link_category_1')) {
                // phpcs:ignore WordPress.DB -- esc_sql returns string for string input
                $wpdb->query(
                    "ALTER TABLE `" . esc_sql($prefix) . "category`
                    ADD CONSTRAINT `jtl_connector_link_category_1` FOREIGN KEY (`endpoint_id`)
                    REFERENCES `" . esc_sql($wpdb->terms) . "` (`term_id`) ON DELETE CASCADE ON UPDATE NO ACTION"
                );
            }
            if (! $db->checkIfFKExists($prefix . 'specific', 'jtl_connector_link_specific_1')) {
                // phpcs:ignore WordPress.DB -- esc_sql returns string for string input
                $wpdb->query(
                    "ALTER TABLE `" . esc_sql($prefix) . "specific`
                    ADD CONSTRAINT `jtl_connector_link_specific_1` FOREIGN KEY (`endpoint_id`)
                    REFERENCES `"
                    . esc_sql($wpdb->prefix . 'woocommerce_attribute_taxonomies')
                    . "` (`attribute_id`) ON DELETE CASCADE ON UPDATE NO ACTION"
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
            return trim((string) com_create_guid(), '{}');
        }

        return sprintf(
            '%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
            random_int(0, 65535),
            random_int(0, 65535),
            random_int(0, 65535),
            random_int(16384, 20479),
            random_int(32768, 49151),
            random_int(0, 65535),
            random_int(0, 65535),
            random_int(0, 65535)
        );
    }

    /**
     * @param string $featuresJsonPath
     * @return void
     * @throws Exception
     */
    public static function loadFeaturesJson(string $featuresJsonPath): void
    {
        $features = Config::get(Config::OPTIONS_FEATURES_JSON);
        if (! empty($features) && is_string($features)) {
            $featuresJson = json_decode($features, true);
            if (is_array($featuresJson)) {
                $saveResult = file_put_contents($featuresJsonPath, json_encode($featuresJson, JSON_PRETTY_PRINT));
                if ($saveResult === false) {
                    throw new Exception(sprintf("Cannot save features in %s file.", esc_html($featuresJsonPath)), 100);
                }
            }
        } else {
            $features = json_decode((string) file_get_contents($featuresJsonPath), true);
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
     * @param  Db $db
     * @return void
     * @throws ParseException
     * @throws \Psr\Log\InvalidArgumentException
     * @throws \InvalidArgumentException
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

        $wooJtlConnectorLoadCssAndJs = function ($hook): void {
            // your-slug => The slug name to refer to this menu used in "add_submenu_page"
            // tools_page => refers to Tools top menu, so it's a Tools' sub-menu page
            if (! str_starts_with($hook, 'jtl-connector_page_woo-')) {
                return;
            }

            wp_enqueue_style(
                'bootstrap4',
                JTLWCC_CONNECTOR_DIR_URL . '/assets/css/bootstrap.min.css',
                [],
                '4.3.1'
            );
            wp_enqueue_style(
                'custom-css-jtl',
                JTLWCC_CONNECTOR_DIR_URL . '/includes/css/custom.css'
            );
            wp_enqueue_script(
                'boot1',
                JTLWCC_CONNECTOR_DIR_URL . '/assets/js/bootstrap.bundle.min.js',
                [ 'jquery' ],
                '4.3.1',
                true
            );
        };

        $wooJtlConnectorAddAdminMenu = function (): void {

            $wooJtlConnectorInformationPage = function (): void {
                JtlConnectorAdmin::displayPageNew(
                    'information_page',
                    __('Connector information', 'woo-jtl-connector')
                );
            };

            $wooJtlConnectorAdvancedPage = function (): void {
                JtlConnectorAdmin::displayPageNew(
                    'advanced_page',
                    __('Advanced Settings', 'woo-jtl-connector'),
                    true
                );
            };

            $wooJtlConnectorDeliveryTimePage = function (): void {
                JtlConnectorAdmin::displayPageNew(
                    'delivery_time_page',
                    __('Delivery time', 'woo-jtl-connector'),
                    true
                );
            };

            $wooJtlConnectorCustomerOrderPage = function (): void {
                JtlConnectorAdmin::displayPageNew(
                    'customer_order_page',
                    __('Customer order', 'woo-jtl-connector'),
                    true
                );
            };

            $wooJtlConnectorCustomersPage = function (): void {
                JtlConnectorAdmin::displayPageNew(
                    'customers_page',
                    __('Customers', 'woo-jtl-connector'),
                    true
                );
            };

            $wooJtlConnectorDeveloperSettingsPage = function (): void {
                JtlConnectorAdmin::displayPageNew(
                    'developer_settings_page',
                    __('Developer Settings', 'woo-jtl-connector'),
                    true
                );
            };

            add_menu_page(
                __('JTL-Connector', 'woo-jtl-connector'),
                __('JTL-Connector', 'woo-jtl-connector'),
                'manage_woocommerce',
                'woo-jtl-connector',
                function (): void {
                },
                '',
                55.5
            );

            add_submenu_page(
                'woo-jtl-connector',
                __('JTL-Connector:Information', 'woo-jtl-connector'),
                __('Information', 'woo-jtl-connector'),
                'manage_woocommerce',
                'woo-jtl-connector-information',
                function () use ($wooJtlConnectorInformationPage): void {
                    $wooJtlConnectorInformationPage();
                }
            );

            add_submenu_page(
                'woo-jtl-connector',
                __('JTL-Connector:Advanced', 'woo-jtl-connector'),
                __('Advanced Settings', 'woo-jtl-connector'),
                'manage_woocommerce',
                'woo-jtl-connector-advanced',
                function () use ($wooJtlConnectorAdvancedPage): void {
                    $wooJtlConnectorAdvancedPage();
                }
            );

            add_submenu_page(
                'woo-jtl-connector',
                __('JTL-Connector:Delivery times', 'woo-jtl-connector'),
                __('Delivery times', 'woo-jtl-connector'),
                'manage_woocommerce',
                'woo-jtl-connector-delivery-time',
                function () use ($wooJtlConnectorDeliveryTimePage): void {
                    $wooJtlConnectorDeliveryTimePage();
                }
            );

            add_submenu_page(
                'woo-jtl-connector',
                __('JTL-Connector:Customer orders', 'woo-jtl-connector'),
                __('Customer orders', 'woo-jtl-connector'),
                'manage_woocommerce',
                'woo-jtl-connector-customer-order',
                function () use ($wooJtlConnectorCustomerOrderPage): void {
                    $wooJtlConnectorCustomerOrderPage();
                }
            );

            add_submenu_page(
                'woo-jtl-connector',
                __('JTL-Connector:Customers', 'woo-jtl-connector'),
                __('Customers', 'woo-jtl-connector'),
                'manage_woocommerce',
                'woo-jtl-connector-customers',
                function () use ($wooJtlConnectorCustomersPage): void {
                    $wooJtlConnectorCustomersPage();
                }
            );

            add_submenu_page(
                'woo-jtl-connector',
                __('JTL-Connector:Developer Settings', 'woo-jtl-connector'),
                __('Developer Settings', 'woo-jtl-connector'),
                'manage_woocommerce',
                'woo-jtl-connector-developer-settings',
                function () use ($wooJtlConnectorDeveloperSettingsPage): void {
                    $wooJtlConnectorDeveloperSettingsPage();
                }
            );

            remove_submenu_page('woo-jtl-connector', 'woo-jtl-connector');
        };

        //NEW PAGE
        add_action('admin_menu', function () use ($wooJtlConnectorAddAdminMenu): void {
            $wooJtlConnectorAddAdminMenu();
        });
        add_action('admin_enqueue_scripts', function ($hook) use ($wooJtlConnectorLoadCssAndJs): void {
            $wooJtlConnectorLoadCssAndJs($hook);
        });

        #//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
        // function woo_jtl_connector_add_admin_menu(): void

        // /**
        // * @param string|null $hook
        // *
        // * @return void
        // */
        //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
        // function woo_jtl_connector_loadCssAndJs(?string $hook): void

        // /**
        // * @return void
        // */
        #//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
        // function woo_jtl_connector_information_page(): void

        // /**
        // * @return void
        // */
        #//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
        // function woo_jtl_connector_advanced_page(): void

        // /**
        // * @return void
        // */
        #//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
        // function woo_jtl_connector_delivery_time_page(): void

        // /**
        // * @return void
        // */
        #//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
        // function woo_jtl_connector_customer_order_page(): void

        // /**
        // * @return void
        // */
        #//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
        // function woo_jtl_connector_customers_page(): void

        // /**
        // * @return void
        // */
        //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
        // function woo_jtl_connector_developer_settings_page(): void

        self::update($db);
    }

    /**
     * @param string $page
     * @param string $title
     * @param bool   $submit
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    public static function displayPageNew(
        ?string $page,
        string $title = 'Connector information',
        bool $submit = false
    ): void {
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
                          action="<?php echo esc_url(admin_url('admin-post.php'));
                            ?>?action=settings_save_woo-jtl-connector"
                          enctype="multipart/form-data">
                        <?php wp_nonce_field('settings_save_woo-jtl-connector'); ?>
                        <div class="form-group row">
                            <h2 class="col-12"><?php echo esc_html($title); ?></h2>
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
                        woocommerce_admin_fields($options);
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
     * @return array<int, array<string, mixed>>
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
                'woo-jtl-connector'
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
                'woo-jtl-connector'
            ),
            'id'        => 'connector_url',
            'value'     => esc_url(get_bloginfo('url') . '/index.php/jtlconnector/'),
        ];

        //Add connector password field
        $fields[] = [
            'title'     => __('Connector Password', 'woo-jtl-connector'),
            'type'      => 'connector_password',
            'helpBlock' => __(
                'This secret password will be used for identifying that your JTL-Wawi ist allowed to pull/push data.',
                'woo-jtl-connector'
            ),
            'id'        => 'connector_password',
            'value'     => Config::get(Config::OPTIONS_TOKEN),
        ];

        //Add connector version field
        $fields[] = [
            'title'     => 'Connector Version',
            'type'      => 'paragraph',
            'helpBlock' => __('This is your current installed connector version.', 'woo-jtl-connector'),
            'desc'      => Config::get(Config::OPTIONS_INSTALLED_VERSION),
        ];

        //Add sectionend
        $fields[] = [
            'type' => 'sectionend',
        ];

        //Add extend plugin informations
        if (\count(SupportedPlugins::getSupported()) > 0) {
            $fields[] = [
                'title'   => __('These activated plugins extend the JTL-Connector:', 'woo-jtl-connector'),
                'type'    => 'compatible_plugins_field',
                'plugins' => SupportedPlugins::getSupported(),
            ];
        }

        //Add Incompatible plugin informations
        $fields[] = [
            'title'   => __('Incompatible with these plugins:', 'woo-jtl-connector'),
            'type'    => 'not_compatible_plugins_field',
            'plugins' => SupportedPlugins::getNotSupportedButActive(false, true, true),
        ];

        $fields[] = [
            'title'      => __('Important information', 'woo-jtl-connector'),
            'type'       => 'jtlwcc_card',
            'color'      => 'border-warning',
            'text-color' => 'text-warning',
            'center'     => true,
            'text'       => __(
                'Similar plugins, like the <b>not compatible plugins</b> which 
                    are listed here, might be incompatible too!',
                'woo-jtl-connector'
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
        $notSupportedButActive = SupportedPlugins::getNotSupportedButActive();
        if (is_array($notSupportedButActive) && count($notSupportedButActive) > 0) {
            $notSupportedButActiveAsString = SupportedPlugins::getNotSupportedButActive(true);
            if (\is_string($notSupportedButActiveAsString)) {
                self::jtlwcc_show_wordpress_error(
                    sprintf(
                        // translators: %s: list of unsupported plugin names

                        __('The listed plugins can cause problems when using the connector: %s', 'woo-jtl-connector'),
                        $notSupportedButActiveAsString
                    )
                );
            }
        }
    }

    /**
     * @param string $message
     *
     * @return void
     */
    public static function jtlwcc_show_wordpress_error(string $message): void //phpcs:ignore
    {
        echo '<div class="alert alert-danger" id="jtlwcc_plugin_error" role="alert">
                    <p><b>JTL-Connector:</b>&nbsp;'
                    . wp_kses_post($message) . '</p>
                </div>';
    }

    /**
     * @return array<int, array<string, mixed>>
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
                'woo-jtl-connector'
            ),
        ];

        //Add sectionend
        $fields[] = [
            'type' => 'sectionend',
        ];

        //Add variation specific radio field
        $fields[] = [
            'title'     => __('Variation specifics', 'woo-jtl-connector'),
            'type'      => 'active_true_false_radio',
            'desc'      => __(
                'Enable if you want to show your customers the variation as specific (Default : Enabled).',
                'woo-jtl-connector'
            ),
            'id'        => Config::OPTIONS_SHOW_VARIATION_SPECIFICS_ON_PRODUCT_PAGE,
            'value'     => Config::get(Config::OPTIONS_SHOW_VARIATION_SPECIFICS_ON_PRODUCT_PAGE),
            'trueText'  => __('Enabled', 'woo-jtl-connector'),
            'falseText' => __('Disabled', 'woo-jtl-connector'),
        ];

        $fields[] = [
            'title'     => __('Delete unknown attributes', 'woo-jtl-connector'),
            'type'      => 'active_true_false_radio',
            'desc'      => __(
                'Enable if you want to delete unknown attributes on push (Default : Disabled).',
                'woo-jtl-connector'
            ),
            'id'        => Config::OPTIONS_DELETE_UNKNOWN_ATTRIBUTES,
            'value'     => Config::get(Config::OPTIONS_DELETE_UNKNOWN_ATTRIBUTES),
            'trueText'  => __('Enabled', 'woo-jtl-connector'),
            'falseText' => __('Disabled', 'woo-jtl-connector'),
        ];

        //Add custom properties radio field
        $fields[] = [
            'title'     => __('Custom properties', 'woo-jtl-connector'),
            'type'      => 'active_true_false_radio',
            'desc'      => __(
                'If you activate this option, custom fields from JTL-Wawi will be handled 
                as attributes in the shop. After changing this option, full-sync is required (Default : Enabled).',
                'woo-jtl-connector'
            ),
            'id'        => Config::OPTIONS_SEND_CUSTOM_PROPERTIES,
            'value'     => Config::get(Config::OPTIONS_SEND_CUSTOM_PROPERTIES),
            'trueText'  => __('Enabled', 'woo-jtl-connector'),
            'falseText' => __('Disabled', 'woo-jtl-connector'),
        ];

        //Add gtin/ean radio field
        $fields[] = [
            'title'     => __('GTIN / EAN', 'woo-jtl-connector'),
            'type'      => 'active_true_false_radio',
            'desc'      => __(
                'Enable if you want to use the GTIN field for ean. 
                (Default : Enabled / Required plugin: WooCommerce Germanized).',
                'woo-jtl-connector'
            ),
            'id'        => Config::OPTIONS_USE_GTIN_FOR_EAN,
            'value'     => Config::get(Config::OPTIONS_USE_GTIN_FOR_EAN),
            'trueText'  => __('Enabled', 'woo-jtl-connector'),
            'falseText' => __('Disabled', 'woo-jtl-connector'),
        ];

        //Allow html in attributes
        $fields[] = [
            'title'     => __('Allow HTML in product attributes', 'woo-jtl-connector'),
            'type'      => 'active_true_false_radio',
            'desc'      => __(
                'Enable if you want to allow saving HTML in product attributes (Default : Disabled)',
                'woo-jtl-connector'
            ),
            'id'        => Config::OPTIONS_ALLOW_HTML_IN_PRODUCT_ATTRIBUTES,
            'value'     => Config::get(Config::OPTIONS_ALLOW_HTML_IN_PRODUCT_ATTRIBUTES),
            'trueText'  => __('Enabled', 'woo-jtl-connector'),
            'falseText' => __('Disabled', 'woo-jtl-connector'),
        ];

        //Add sectionend
        $fields[] = [
            'type' => 'sectionend',
        ];


        return $fields;
    }

    /**
     * @return array<int, array<string, mixed>>
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
                'woo-jtl-connector'
            ),
        ];

        //Add sectionend
        $fields[] = [
            'type' => 'sectionend',
        ];

        //Add delivery time calculation radio field
        $fields[] = [
            'title'     => __('DeliveryTime Calculation', 'woo-jtl-connector'),
            'type'      => 'jtl_connector_select',
            'id'        => Config::OPTIONS_USE_DELIVERYTIME_CALC,
            'value'     => Config::get(Config::OPTIONS_USE_DELIVERYTIME_CALC),
            'options'   => [
                'delivery_time_calc' => __('Lieferzeit Berechnung nutzen', 'woo-jtl-connector'),
                'delivery_status'    => __('Lieferstatus nutzen', 'woo-jtl-connector'),
                'deactivated'        => __('Deaktiviert', 'woo-jtl-connector'),
            ],
            'helpBlock' => __(
                "Enable if you want to use delivery time calculation. <br>
                        Delivery time calculation: Let JTL Wawi calculate the delivery time. <br>
                        Delivery status: Use the delivery status as delivery time. <br>
                        Deactivated: Don't use delivery time. <br>
                        (Default : Delivery time calculation / Required plugin: WooCommerce Germanized).",
                'woo-jtl-connector'
            ),
        ];

        //Add dont use zero values radio field
        $fields[] = [
            'title'     => __('Dont use zero values for delivery time', 'woo-jtl-connector'),
            'type'      => 'active_true_false_radio',
            'desc'      => __(
                'Enable if you dont want to use zero values for delivery time. (Default : Enabled).',
                'woo-jtl-connector'
            ),
            'id'        => Config::OPTIONS_DISABLED_ZERO_DELIVERY_TIME,
            'value'     => Config::get(Config::OPTIONS_DISABLED_ZERO_DELIVERY_TIME),
            'trueText'  => __('Enabled', 'woo-jtl-connector'),
            'falseText' => __('Disabled', 'woo-jtl-connector'),
        ];

        //Add prefix for delivery time textinput field
        $fields[] = [
            'title'     => __('Prefix for delivery time', 'woo-jtl-connector'),
            'type'      => 'jtl_text_input',
            'id'        => Config::OPTIONS_PRAEFIX_DELIVERYTIME,
            'value'     => Config::get(Config::OPTIONS_PRAEFIX_DELIVERYTIME),
            'helpBlock' => __("Define the prefix like\n'ca. 4 Days'.", 'woo-jtl-connector'),
        ];

        //Add suffix for delivery time textinput field
        $fields[] = [
            'title'     => __('Suffix for delivery time', 'woo-jtl-connector'),
            'type'      => 'jtl_text_input',
            'id'        => Config::OPTIONS_SUFFIX_DELIVERYTIME,
            'value'     => Config::get(Config::OPTIONS_SUFFIX_DELIVERYTIME),
            'helpBlock' => __("Define the Suffix like\n'ca. 4 work days'.", 'woo-jtl-connector'),
        ];

        //Use next available inflow date if needed
        $fields[] = [
            'title'     => __('Consider available inflow date for shipping', 'woo-jtl-connector'),
            'type'      => 'active_true_false_radio',
            'desc'      => __(
                'Enable if you want that connector calculate shipping time based on next a
                vailable inflow date from supplier when stock is 0',
                'woo-jtl-connector'
            ),
            'id'        => Config::OPTIONS_CONSIDER_SUPPLIER_INFLOW_DATE,
            'value'     => Config::get(Config::OPTIONS_CONSIDER_SUPPLIER_INFLOW_DATE),
            'trueText'  => __('Enabled', 'woo-jtl-connector'),
            'falseText' => __('Disabled', 'woo-jtl-connector'),
        ];

        //Add sectionend
        $fields[] = [
            'type' => 'sectionend',
        ];

        return $fields;
    }

    /**
     * @return array<int, array<string, mixed>>
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
                'woo-jtl-connector'
            ),
        ];

        //Add sectionend
        $fields[] = [
            'type' => 'sectionend',
        ];

        //Add variation specific radio field

        //Add pull order since date field
        $fields[] = [
            'title'     => __('Pull orders since', 'woo-jtl-connector'),
            'type'      => 'jtl_date_field',
            // 'default'  => '2019-03-22',
            'value'     => Config::get(Config::OPTIONS_PULL_ORDERS_SINCE),
            'helpBlock' => __('Define a start date for pulling of orders.', 'woo-jtl-connector'),
            'id'        => Config::OPTIONS_PULL_ORDERS_SINCE,
        ];
        $fields[] = [
            'title'     => __('Recalculate order when has coupons', 'woo-jtl-connector'),
            'type'      => 'active_true_false_radio',
            'desc'      => __(
                'When option is enabled, connector will recalculate order when coupons were applied to order.',
                'woo-jtl-connector'
            ),
            'id'        => Config::OPTIONS_RECALCULATE_COUPONS_ON_PULL,
            'value'     => Config::get(Config::OPTIONS_RECALCULATE_COUPONS_ON_PULL),
            'trueText'  => __('Enabled', 'woo-jtl-connector'),
            'falseText' => __('Disabled', 'woo-jtl-connector'),
        ];
        $fields[] = [
            'title'     => __('Default order statuses to import', 'woo-jtl-connector'),
            'type'      => 'jtl_connector_multiselect',
            'options'   => wc_get_order_statuses(),
            'id'        => Config::OPTIONS_DEFAULT_ORDER_STATUSES_TO_IMPORT,
            'value'     => Config::get(
                Config::OPTIONS_DEFAULT_ORDER_STATUSES_TO_IMPORT,
                [ 'wc-pending', 'wc-processing', 'wc-on-hold' ]
            ),
            'helpBlock' => __(
                'Order statuses that should be imported. Default: pending, processing, on hold, completed',
                'woo-jtl-connector'
            ),
        ];

        $paymentGateways = [];
        if (WC()->payment_gateways() instanceof WC_Payment_Gateways) {
            $paymentGateways = WC()->payment_gateways()->payment_gateways();
            $paymentGateways = array_combine(array_keys($paymentGateways), array_column($paymentGateways, 'title'));
        }

        $fields[] = [
            'title'   => __(
                'Import payments with following payment types only when order 
                is completed (usually manual payment types)',
                'woo-jtl-connector'
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
            'title'     => __('Delay time in seconds before order import', 'woo-jtl-connector'),
            'type'      => 'jtl_number_input',
            'value'     => Config::get(Config::OPTIONS_IGNORE_ORDERS_YOUNGER_THAN),
            'helpBlock' => __('Define the delay time in seconds before new orders get imported.', 'woo-jtl-connector'),
            'id'        => Config::OPTIONS_IGNORE_ORDERS_YOUNGER_THAN,
        ];

        //Add custom checkout fields input field
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_CHECKOUT_FIELD_EDITOR_FOR_WOOCOMMERCE)) {
            $fields[] = [
                'title'     => __('Custom Checkout Fields', 'woo-jtl-connector'),
                'type'      => 'jtl_text_input',
                'id'        => Config::OPTIONS_CUSTOM_CHECKOUT_FIELDS,
                'value'     => Config::get(Config::OPTIONS_CUSTOM_CHECKOUT_FIELDS),
                'helpBlock' => __(
                    "Define what custom fields should be imported to Wawi. Comma-separated.",
                    'woo-jtl-connector'
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
     * @return array<int, array<string, mixed>>
     * @throws \InvalidArgumentException
     * @throws Exception
     */
    private static function getCustomersFields(): array
    {
        $fields = [];

        self::notCompatiblePluginsError();


        $fields[] = [
            'type' => 'title',
            'desc' => '',
        ];


        $fields[] = [
            'type' => 'sectionend',
        ];

        $fields[] = [
            'title'     => __('Limit Customer Pull', 'woo-jtl-connector'),
            'type'      => 'jtl_connector_select',
            'id'        => Config::OPTIONS_LIMIT_CUSTOMER_QUERY_TYPE,
            'value'     => Config::get(
                Config::OPTIONS_LIMIT_CUSTOMER_QUERY_TYPE,
                Config::JTLWCC_CONFIG_DEFAULTS[ Config::OPTIONS_LIMIT_CUSTOMER_QUERY_TYPE ]
            ),
            'options'   => [
                'no_filter'           => __('No Limit', 'woo-jtl-connector'),
                'last_imported_order' => __('Since last pulled Order ID', 'woo-jtl-connector'),
                'not_imported'        => __('Only from not pulled Order', 'woo-jtl-connector'),
                'fixed_date'          => __('Since fixed Date', 'woo-jtl-connector'),
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
                'woo-jtl-connector'
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
            $result = $db->query($sql) ?? [];
            $result = array_diff($result, [ 'guest' ]);
            /** @var array<string, string> $role */
            foreach ($result as $role) {
                $roles[ $role['post_name'] ] = translate_user_role($role['post_title']);
            }

            $fields[] = [
                'title'     => __('Customer Groups to Pull (only with no Limit)', 'woo-jtl-connector'),
                'type'      => 'jtl_connector_multiselect',
                'options'   => $roles,
                'id'        => Config::OPTIONS_PULL_CUSTOMER_GROUPS,
                'value'     => Config::get(Config::OPTIONS_PULL_CUSTOMER_GROUPS, []),
                'helpBlock' => __(
                    'Pull Customers with this Customer Groups, only respected if no Limit is defined. 
                    <br> Guests are always pulled. ',
                    'woo-jtl-connector'
                ),
            ];


            $customerGroups = ( new CustomerGroupController($db, $util) )->pull();
            $options        = [];

            foreach ($customerGroups as $key => $customerGroup) {
                if (count($customerGroup->getI18ns()) > 0) {
                    $i18n                                              = $customerGroup->getI18ns()[0];
                    $options[ $customerGroup->getId()->getEndpoint() ] = $i18n->getName();
                }
            }


            $fields[] = [
                'title'     => __('B2B-Market/WooCommerce default customer group', 'woo-jtl-connector'),
                'type'      => 'jtl_connector_select',
                'id'        => Config::OPTIONS_DEFAULT_CUSTOMER_GROUP,
                'value'     => Config::get(Config::OPTIONS_DEFAULT_CUSTOMER_GROUP),
                'options'   => $options,
                'helpBlock' => __('Define which customer group is default.', 'woo-jtl-connector'),
            ];
        }


        $fields[] = [
            'type' => 'sectionend',
        ];

        return $fields;
    }

    /**
     * @return array<int, array<string, mixed>>
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
                'woo-jtl-connector'
            ),
        ];

        //Add dev log radio field
        $fields[] = [
            'title'     => __('Dev-Logs', 'woo-jtl-connector'),
            'type'      => 'active_true_false_radio',
            'desc'      => __(
                'Enable JTL-Connector dev-logs for debugging (Default : Disabled).',
                'woo-jtl-connector'
            ),
            'id'        => Config::OPTIONS_DEVELOPER_LOGGING,
            'value'     => Config::get(Config::OPTIONS_DEVELOPER_LOGGING),
            'trueText'  => __('Enabled', 'woo-jtl-connector'),
            'falseText' => __('Disabled', 'woo-jtl-connector'),
        ];

        //Add dev log buttons
        $fields[] = [
            'type'          => 'dev_log_btn',
            'downloadText'  => __('Download', 'woo-jtl-connector'),
            'clearLogsText' => __('Clear logs', 'woo-jtl-connector'),
        ];

        $fields[] = [
            'title'     => __('Recommend WooCommerce Settings', 'woo-jtl-connector'),
            'type'      => 'active_true_false_radio',
            'desc'      => __(
                'JTL-Wawi set automatically stable settings (Default : Enabled). 
                Disable this at your own risk!',
                'woo-jtl-connector'
            ),
            'id'        => Config::OPTIONS_AUTO_WOOCOMMERCE_OPTIONS,
            'value'     => Config::get(Config::OPTIONS_AUTO_WOOCOMMERCE_OPTIONS),
            'trueText'  => __('Enabled', 'woo-jtl-connector'),
            'falseText' => __('Disabled', 'woo-jtl-connector'),
        ];
        //phpcs:disable
        $fields[] = [
            'title'      => __('Important information', 'woo-jtl-connector'),
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
                'woo-jtl-connector'
            ),
        ];

        //phpcs:enable
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_GERMAN_MARKET)) {
            $fields[] = [
                'title'     => __('Recommend German Market Settings', 'woo-jtl-connector'),
                'type'      => 'active_true_false_radio',
                'desc'      => __(
                    'JTL-Wawi set automatically stable settings (Default : Enabled). Disable this at your own risk!',
                    'woo-jtl-connector'
                ),
                'id'        => Config::OPTIONS_AUTO_GERMAN_MARKET_OPTIONS,
                'value'     => Config::get(Config::OPTIONS_AUTO_GERMAN_MARKET_OPTIONS),
                'trueText'  => __('Enabled', 'woo-jtl-connector'),
                'falseText' => __('Disabled', 'woo-jtl-connector'),
            ];
            //phpcs:disable
            $fields[] = [
                'title'      => __('Important information', 'woo-jtl-connector'),
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
                    'woo-jtl-connector'
                ),
            ];
            //phpcs:enable
        }

        //CURRENT DISBALED THIS
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)) {
            $fields[] = [
                'title'     => __('Recommend B2B Market Settings', 'woo-jtl-connector'),
                'type'      => 'active_true_false_radio',
                'desc'      => __(
                    'JTL-Wawi set automatically stable settings (Default : Enabled). Disable this at your own risk!',
                    'woo-jtl-connector'
                ),
                'id'        => Config::OPTIONS_AUTO_B2B_MARKET_OPTIONS,
                'value'     => Config::get(Config::OPTIONS_AUTO_B2B_MARKET_OPTIONS),
                'trueText'  => __('Enabled', 'woo-jtl-connector'),
                'falseText' => __('Disabled', 'woo-jtl-connector'),
            ];
// $fields[] = [
// 'title' => __('Important information', 'woo-jtl-connector'),
// 'type' => 'jtlwcc_card',
// 'color' => 'border-info',
// 'text-color' => 'text-info',
// 'center' => false,
// 'text' => __('Similar plugins, like the <b>not compatible
// plugins</b> which are listed here, might be incompatible too!',
// JTLWCC_TEXT_DOMAIN),
// ];
        }

        //Add sectionend
        $fields[] = [
            'type' => 'sectionend',
        ];

        return $fields;
    }

    // <editor-fold defaultstate="collapsed" desc="CustomOutputFields">

    /**
     * @param string $page
     * @return void
     */
    private static function displayNanvigation(string $page): void
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
                       'woo-jtl-connector'
                   ); ?></a>
                <a class="flex-sm-fill text-sm-center nav-link <?php if (strcmp($page, 'advanced_page') === 0) {
                    print 'active';
                                                               } ?>"
                   href="admin.php?page=woo-jtl-connector-advanced"><?php print __(
                       'Advanced Settings',
                       'woo-jtl-connector'
                   ); ?></a>
                <a class="flex-sm-fill text-sm-center nav-link <?php if (strcmp($page, 'delivery_time_page') === 0) {
                    print 'active';
                                                               } ?>"
                   href="admin.php?page=woo-jtl-connector-delivery-time"><?php print __(
                       'Delivery times',
                       'woo-jtl-connector'
                   ); ?></a>
                <a class="flex-sm-fill text-sm-center nav-link <?php if (strcmp($page, 'customer_order_page') === 0) {
                    print 'active';
                                                               } ?>"
                   href="admin.php?page=woo-jtl-connector-customer-order"><?php print __(
                       'Customer orders',
                       'woo-jtl-connector'
                   ); ?></a>
                <a class="flex-sm-fill text-sm-center nav-link <?php if (strcmp($page, 'customers_page') === 0) {
                    print 'active';
                                                               } ?>"
                   href="admin.php?page=woo-jtl-connector-customers"><?php print __(
                       'Customers',
                       'woo-jtl-connector'
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
                       'woo-jtl-connector'
                   ); ?></a>
                <a class="flex-sm-fill text-sm-center nav-link"
                   href="https://guide.jtl-software.de/jtl-connector/woocommerce/"
                   target="_blank"><?php print __(
                       'JTL-Guide',
                       'woo-jtl-connector'
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
     * @throws \InvalidArgumentException
     */
    private static function update(Db $db): void
    {
        $wpdb = $db->getWpDb();

        $installed_version = Config::get(Config::OPTIONS_INSTALLED_VERSION, '');

        if (!\is_string($installed_version)) {
            throw new \InvalidArgumentException(
                "Expected installed_version to be a string, got " . esc_html(gettype($installed_version)) . " instead."
            );
        }

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
                $wpdb->query("DROP TABLE IF EXISTS `" . esc_sql($wpdb->prefix . LinkTableNames::CUSTOMER_GROUP) . "`");
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
            case '2.1.0':
            case '2.2.0':
            case '2.3.0':
            case '2.3.1':
            case '2.3.2':
            case '2.3.3':
            case '2.4.0':
            case '2.4.1':
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

    /**
     * @param  Db $db
     * @return void
     * @throws \Psr\Log\InvalidArgumentException
     */
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
                'UPDATE `%s' . LinkTableNames::IMAGE . '` SET `type` = %d WHERE `type` = %d',
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

        $result = true;
        $wpdb->query('START TRANSACTION');

        /** @phpstan-ignore booleanAnd.leftAlwaysTrue */
        $result = $result && $wpdb->query("CREATE TABLE IF NOT EXISTS `" . esc_sql(LinkTableNames::CATEGORY) . "` (
                `endpoint_id` varchar(255) NOT NULL,
                `host_id` INT(10) NOT NULL,
                PRIMARY KEY (`endpoint_id`, `host_id`),
                INDEX (`host_id`),
                INDEX (`endpoint_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
        $result = $result && $wpdb->query("CREATE TABLE IF NOT EXISTS `" . esc_sql(LinkTableNames::CUSTOMER) . "` (
                `endpoint_id` varchar(255) NOT NULL,
                `host_id` INT(10) NOT NULL,
                PRIMARY KEY (`endpoint_id`, `host_id`),
                INDEX (`host_id`),
                INDEX (`endpoint_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
        $result = $result && $wpdb->query("CREATE TABLE IF NOT EXISTS `" . esc_sql(LinkTableNames::PRODUCT) . "` (
                `endpoint_id` varchar(255) NOT NULL,
                `host_id` INT(10) NOT NULL,
                PRIMARY KEY (`endpoint_id`, `host_id`),
                INDEX (`host_id`),
                INDEX (`endpoint_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
        $result = $result && $wpdb->query("CREATE TABLE IF NOT EXISTS `" . esc_sql(LinkTableNames::IMAGE) . "` (
                `endpoint_id` varchar(255) NOT NULL,
                `host_id` INT(10) NOT NULL,
                PRIMARY KEY (`endpoint_id`, `host_id`),
                INDEX (`host_id`),
                INDEX (`endpoint_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
        $result = $result && $wpdb->query("CREATE TABLE IF NOT EXISTS `" . esc_sql(LinkTableNames::ORDER) . "` (
                `endpoint_id` varchar(255) NOT NULL,
                `host_id` INT(10) NOT NULL,
                PRIMARY KEY (`endpoint_id`, `host_id`),
                INDEX (`host_id`),
                INDEX (`endpoint_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
        $result = $result && $wpdb->query("CREATE TABLE IF NOT EXISTS `" . esc_sql(LinkTableNames::PAYMENT) . "` (
                `endpoint_id` varchar(255) NOT NULL,
                `host_id` INT(10) NOT NULL,
                PRIMARY KEY (`endpoint_id`, `host_id`),
                INDEX (`host_id`),
                INDEX (`endpoint_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
        $result = $result && $wpdb->query("CREATE TABLE IF NOT EXISTS `" . esc_sql(LinkTableNames::CROSSSELLING) . "` (
                `endpoint_id` varchar(255) NOT NULL,
                `host_id` INT(10) NOT NULL,
                PRIMARY KEY (`endpoint_id`, `host_id`),
                INDEX (`host_id`),
                INDEX (`endpoint_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

        $types = $wpdb->get_results('SELECT type FROM `jtl_connector_link` GROUP BY type');

        foreach ($types as $type) {
            $type      = (int) $type->type;
            $tableName = self::get_table_name($type);
            if ($tableName === null) {
                continue;
            }
            $result = $result && $wpdb->query($wpdb->prepare(
                "INSERT INTO `" . esc_sql($tableName) . "` (`host_id`, `endpoint_id`)
                SELECT `host_id`, `endpoint_id` FROM `jtl_connector_link` WHERE `type` = %d",
                $type
            ));
        }

        if ($result) {
            $wpdb->query('DROP TABLE IF EXISTS `jtl_connector_link`');
            $wpdb->query('COMMIT');
        } else {
            $wpdb->query('ROLLBACK');
            Config::set(Config::OPTIONS_UPDATE_FAILED, 'yes');
            add_action('admin_notices', function (): void {
                self::jtlwcc_show_wordpress_error(__(
                    'The linking table migration was not successful. Please use the forum for help.',
                    'woo-jtl-connector'
                ));
            });
        }
    }

    /**
     * @param int $type
     *
     * @return string|null
     */
    private static function get_table_name(int $type ): ?string//phpcs:ignore
    {
        switch ($type) {
            case IdentityType::CATEGORY:
                return LinkTableNames::CATEGORY;
            case IdentityType::CUSTOMER:
                return LinkTableNames::CUSTOMER;
            case IdentityType::PRODUCT:
                return LinkTableNames::PRODUCT;
            case 16:
                return LinkTableNames::IMAGE;
            case IdentityType::CUSTOMER_ORDER:
                return LinkTableNames::ORDER;
            case IdentityType::PAYMENT:
                return LinkTableNames::PAYMENT;
            case IdentityType::CROSS_SELLING:
                return LinkTableNames::CROSSSELLING;
        }

        return null;
    }

    /**
     * @param Db $db
     * @return void
     * @throws \Psr\Log\InvalidArgumentException
     */
    private static function update_multi_linking_endpoint_types(Db $db): void //phpcs:ignore
    {
        global $wpdb;

        // Modify varchar endpoint_id to integer
        $wpdb->query("ALTER TABLE `" . esc_sql(LinkTableNames::ORDER) . "` MODIFY `endpoint_id` BIGINT(20) unsigned");
        $wpdb->query("ALTER TABLE `" . esc_sql(LinkTableNames::PAYMENT) . "` MODIFY `endpoint_id` BIGINT(20) unsigned");
        $wpdb->query("ALTER TABLE `" . esc_sql(LinkTableNames::PRODUCT) . "` MODIFY `endpoint_id` BIGINT(20) unsigned");
        $wpdb->query(
            "ALTER TABLE `" . esc_sql(LinkTableNames::CROSSSELLING)
            . "` MODIFY `endpoint_id` BIGINT(20) unsigned"
        );
        $wpdb->query(
            "ALTER TABLE `" . esc_sql(LinkTableNames::CATEGORY)
            . "` MODIFY `endpoint_id` BIGINT(20) unsigned"
        );

        // Add is_guest column for customers instead of using a prefix
        $wpdb->query('ALTER TABLE `' . esc_sql(LinkTableNames::CUSTOMER) . '` ADD COLUMN `is_guest` BIT');
        $wpdb->query($wpdb->prepare(
            'UPDATE `' . esc_sql(LinkTableNames::CUSTOMER) . '` 
            SET `is_guest` = 1
            WHERE `endpoint_id` LIKE %s',
            Id::GUEST_PREFIX . '_%'
        ));
        $wpdb->query($wpdb->prepare(
            'UPDATE `' . esc_sql(LinkTableNames::CUSTOMER) . '` 
            SET `is_guest` = 0
            WHERE `endpoint_id` NOT LIKE %s',
            Id::GUEST_PREFIX . '_%'
        ));

        // Add type column for images instead of using a prefix
        $wpdb->query('ALTER TABLE `' . esc_sql(LinkTableNames::IMAGE) . '` ADD COLUMN `type` INT(4) unsigned');
        $wpdb->query($wpdb->prepare(
            'UPDATE `' . esc_sql(LinkTableNames::IMAGE) . '` 
            SET `type` = %d, `endpoint_id` = SUBSTRING(`endpoint_id`, 3)
            WHERE `endpoint_id` LIKE %s',
            IdentityType::CATEGORY,
            Id::CATEGORY_PREFIX . '_%'
        ));
        $wpdb->query($wpdb->prepare(
            'UPDATE `' . esc_sql(LinkTableNames::IMAGE) . '` 
            SET `type` = %d, `endpoint_id` = SUBSTRING(`endpoint_id`, 3)
            WHERE `endpoint_id` LIKE %s',
            IdentityType::PRODUCT,
            Id::PRODUCT_PREFIX . '_%'
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

        $wpdb->query("
            CREATE TABLE IF NOT EXISTS `" . esc_sql(LinkTableNames::SPECIFIC) . "` (
                `endpoint_id` BIGINT(20) unsigned NOT NULL,
                `host_id` INT(10) unsigned NOT NULL,
                PRIMARY KEY (`endpoint_id`, `host_id`),
                INDEX (`host_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
        $wpdb->query("
            CREATE TABLE IF NOT EXISTS `" . esc_sql(LinkTableNames::SPECIFIC_VALUE) . "` (
                `endpoint_id` BIGINT(20) unsigned NOT NULL,
                `host_id` INT(10) unsigned NOT NULL,
                PRIMARY KEY (`endpoint_id`, `host_id`),
                INDEX (`host_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

        $engine = $wpdb->get_var($wpdb->prepare(
            "SELECT ENGINE
            FROM information_schema.TABLES
            WHERE TABLE_NAME = %s AND TABLE_SCHEMA = %s",
            $wpdb->posts,
            DB_NAME
        ));

        if ($engine === 'InnoDB') {
            if (
                ! $db->checkIfFKExists(
                    LinkTableNames::CATEGORY,
                    'jtl_connector_link_category_1'
                )
            ) {
                $wpdb->query(
                    "ALTER TABLE `" . esc_sql(LinkTableNames::CATEGORY) . "`
                            ADD CONSTRAINT `jtl_connector_link_category_1`
                            FOREIGN KEY (`endpoint_id`) 
                            REFERENCES `" . esc_sql($wpdb->terms)
                            . "` (`term_id`) ON DELETE CASCADE ON UPDATE NO ACTION"
                );
            }

            $table = $wpdb->prefix . 'woocommerce_attribute_taxonomies';
            if (
                ! $db->checkIfFKExists(
                    LinkTableNames::SPECIFIC,
                    'jtl_connector_link_specific_1'
                )
            ) {
                $wpdb->query("
                ALTER TABLE `" . esc_sql(LinkTableNames::SPECIFIC) . "`
                ADD CONSTRAINT `jtl_connector_link_specific_1` FOREIGN KEY (`endpoint_id`) 
                REFERENCES `" . esc_sql($table) . "` (`attribute_id`) ON DELETE CASCADE ON UPDATE NO ACTION");
            }
        }
    }

    /**
     * @return void
     */
    private static function set_linking_table_name_prefix_correctly(): void //phpcs:ignore
    {
        global $wpdb;

        $tables = [
            'jtl_connector_category_level',
            LinkTableNames::CATEGORY,
            LinkTableNames::CROSSSELLING,
            LinkTableNames::CUSTOMER,
            LinkTableNames::IMAGE,
            LinkTableNames::ORDER,
            LinkTableNames::PAYMENT,
            LinkTableNames::PRODUCT,
            LinkTableNames::SHIPPING_CLASS,
            LinkTableNames::SPECIFIC,
            LinkTableNames::SPECIFIC_VALUE,
            'jtl_connector_product_checksum',
        ];
        foreach ($tables as $table) {
            $wpdb->query("RENAME TABLE `" . esc_sql($table) . "` TO `" . esc_sql($wpdb->prefix . $table) . "`");
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
                switch_to_blog((int) $site->blog_id);
                if (in_array(Config::get(Config::OPTIONS_USE_DELIVERYTIME_CALC), [ "1", "0" ], true)) {
                    update_blog_option(
                        (int) $site->blog_id,
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
                is_array($b2bMarketCustomerGroups)
                && count($b2bMarketCustomerGroups) > 0
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

    /**
     * @param string[] $links
     * @param string   $file
     * @return string[]
     */
    public static function jtlconnector_plugin_row_meta(array $links, string $file ):array //phpcs:ignore
    {
        if (\str_contains($file, 'woo-jtl-connector.php')) {
            $url       = esc_url('https://guide.jtl-software.de/jtl/Kategorie:JTL-Connector:WooCommerce');
            $new_links = [
                '<a target="_blank" href="' . $url . '">' . __('Documentation', 'woo-jtl-connector') . '</a>',
            ];
            $links     = array_merge($links, $new_links);
        }

        return $links;
    }

    /**
     * @param string[] $links
     * @return string[]
     */
    public static function settings_link(array $links = [] ): array //phpcs:ignore
    {
        $settings_link = '<a href="admin.php?page=woo-jtl-connector-information">' . __(
            'Settings',
            'woo-jtl-connector'
        ) . '</a>';

        array_unshift($links, $settings_link);

        return $links;
    }

    // </editor-fold>

    /**
     * @param array<string, string|null> $field
     *
     * @return void
     */
    public static function jtl_date_field(array $field): void //phpcs:ignore
    {
        $option_value = (string) $field['default'];
        $fieldId      = esc_attr((string) $field['id']);
        $fieldTitle   = esc_html((string) $field['title']);
        $fieldValue   = isset($field['value'])
            && $field['value'] !== ''
            ? esc_attr((string) $field['value'])
            : esc_attr($option_value);

        ?>

        <div class="form-group row">
            <label for="<?php echo esc_attr($fieldId); ?>"
                   class="col-12 col-form-label">
                <?php echo esc_html($fieldTitle); ?>
            </label>
            <div class="col-12">
                <input class="form-control" type="date"
                       value="<?php echo esc_attr($fieldValue); ?>"
                       id="<?php echo esc_attr($fieldId); ?>"
                       name="<?php echo esc_attr($fieldId); ?>">
            </div>
            <?php
            if (isset($field['helpBlock']) && $field['helpBlock'] !== '') {
                ?>
                <small id="<?php echo esc_attr($fieldId); ?>_helpBlock"
                       class="form-text text-muted col-12">
                    <?php echo wp_kses_post((string) $field['helpBlock']); ?>
                </small>
                <?php
            }
            ?>
        </div>
        <?php
    }

    // <editor-fold defaultstate="collapsed" desc="Update">

    /**
     * @param array<string, string|bool> $field
     *
     * @return void
     */
    public static function connector_password_field(array $field): void //phpcs:ignore
    {
        $fieldId    = esc_attr((string) $field['id']);
        $fieldTitle = esc_html((string) $field['title']);
        $fieldValue = esc_attr((string) $field['value']);
        ?>
        <div class="form-group row">
            <label class="col-12"
                   for="<?php echo esc_attr($fieldId); ?>">
                <?php echo esc_html($fieldTitle); ?>
            </label>
            <div class="input-group col-12">
                <input type="text"
                       class="form-control"
                       aria-label="Connector Password"
                       aria-describedby="<?php echo esc_attr($fieldId); ?>_btn"
                       id="<?php echo esc_attr($fieldId); ?>"
                       value="<?php echo esc_attr($fieldValue); ?>"
                       readonly="readonly">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary"
                            type="button"
                            title="Copy"
                            id="<?php echo esc_attr($fieldId); ?>_btn"
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
                <small id="<?php echo esc_attr($fieldId); ?>_helpBlock"
                       class="form-text text-muted col-12">
                    <?php echo wp_kses_post((string) $field['helpBlock']); ?>
                </small>
                <?php
            }
            ?>
        </div>
        <?php
    }

    /**
     * @param array<string, string|bool> $field
     *
     * @return void
     */
    public static function connector_url_field(array $field): void //phpcs:ignore
    {
        $fieldId    = esc_attr((string) $field['id']);
        $fieldTitle = esc_html((string) $field['title']);
        $fieldValue = esc_url((string) $field['value']);
        ?>
        <div class="form-group row">
            <label class="col-12"
                   for="<?php echo esc_attr($fieldId); ?>">
                <?php echo esc_html($fieldTitle); ?>
            </label>
            <div class="input-group col-12">
                <input type="text"
                       class="form-control"
                       aria-label="Connector URL"
                       aria-describedby="<?php echo esc_attr($fieldId); ?>_btn"
                       id="<?php echo esc_attr($fieldId); ?>"
                       value="<?php echo esc_attr($fieldValue); ?>"
                       readonly="readonly">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary"
                            type="button"
                            title="Copy"
                            id="<?php echo esc_attr($fieldId); ?>_btn"
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
                <small id="<?php echo esc_attr($fieldId); ?>_helpBlock"
                       class="form-text text-muted col-12">
                    <?php echo wp_kses_post((string) $field['helpBlock']); ?>
                </small>
                <?php
            }
            ?>
        </div>
        <?php
    }

    /**
     * @param array<string, string|bool|array<int, string>> $field
     *
     * @return void
     */
    public static function not_compatible_plugins_field(array $field): void //phpcs:ignore
    {
        /** @var string $title */
        $title = $field['title'];
        /** @var array<string, string> $plugins */
        $plugins = $field['plugins'];
        ?>
        <div class="form-group row">
            <h2 class="col-12 mb-4"><?php echo esc_html($title); ?></h2>
            <ul class="list-group col-12 pl-3">
                <?php
                $change = false;
                if (count($plugins) > 0) {
                    foreach ($plugins as $key => $value) {
                        $liClass = $change
                            ? 'list-group-item list-group-item-light'
                            : 'list-group-item';
                        ?>
                        <li class="<?php echo esc_attr($liClass); ?>">
                            <?php echo esc_html($value); ?>
                        </li>
                        <?php
                        $change = ! $change;
                    }
                }
                ?>
            </ul>
        </div>
        <?php
    }

    /**
     * @param array<string, string|bool> $field
     *
     * @return void
     */
    public static function jtlwcc_card(array $field): void //phpcs:ignore
    {
        $center    = isset($field['center']) && $field['center']
            ? 'text-center' : '';
        $color     = isset($field['color']) && $field['color'] !== ''
            ? esc_attr((string) $field['color']) : 'bg-light';
        $textColor = isset($field['text-color'])
            && $field['text-color'] !== ''
            ? esc_attr((string) $field['text-color']) : '';
        ?>
        <div class="card <?php echo esc_attr($center); ?> col-12 pl-3
        <?php echo esc_attr($color); ?>">
            <div class="card-header bg-transparent
             <?php echo esc_attr($color); ?>
              <?php echo esc_attr($textColor); ?>">
                <h5 class="card-title">
                    <?php echo esc_html((string) $field['title']); ?>
                </h5>
            </div>
            <div class="card-body bg-transparent
            <?php echo esc_attr($color); ?>
                <?php echo esc_attr($textColor); ?>">

                <p class="card-text">
                    <?php echo wp_kses_post((string) $field['text']); ?>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * @param array<string, string|bool|array<int, array<string, string|bool>>> $field
     *
     * @return void
     */
    public static function compatible_plugins_field(array $field): void //phpcs:ignore
    {
        /** @var string $title */
        $title = $field['title'];
        /** @var array<int, array<string, string|null>> $plugins */
        $plugins = $field['plugins'];
        ?>
        <div class="form-group row">
            <h2 class="col-12 mb-4"><?php echo esc_html($title); ?></h2>
            <ul class="list-group col-12 pl-3">
                <?php
                $change = false;
                if (count($plugins) > 0) {
                    foreach ($plugins as $key => $value) {
                        $liClass   = $change
                            ? 'list-group-item list-group-item-light'
                            : 'list-group-item';
                        $name      = esc_html((string) ($value['Name'] ?? ''));
                        $version   = esc_html(
                            (string) ($value['Version'] ?? '')
                        );
                        $authorUri = isset($value['AuthorURI'])
                            && $value['AuthorURI'] !== ''
                            ? esc_url((string) $value['AuthorURI'])
                            : '#';
                        $author    = esc_html(
                            (string) ($value['Author'] ?? '')
                        );
                        ?>
                        <li class="<?php echo esc_attr($liClass); ?>">
                            <?php echo esc_html($name); ?>
                            -
                            <?php echo esc_html($version); ?>
                            (<a target="_blank"
                                href="<?php echo esc_url($authorUri); ?>">
                                <?php echo esc_html($author); ?>
                            </a>)
                        </li>
                        <?php
                        $change = ! $change;
                    }
                }
                ?>
            </ul>
        </div>
        <?php
    }

    /**
     * @param array<string, string|bool> $field
     *
     * @return void
     */
    public static function paragraph_field(array $field): void //phpcs:ignore
    {
        /** @var string $title */
        $title          = $field['title'];
        $sanitizedTitle = esc_attr(
            wc_sanitize_taxonomy_name($title)
        );
        ?>
        <div class="form-group row">
            <label for="statictext_<?php echo esc_attr($sanitizedTitle); ?>"
                   class="col-12 col-form-label">
                <?php echo esc_html($title); ?>
            </label>
            <div class="col-12">
                <input type="text" readonly class="form-control-plaintext"
                       id="statictext_<?php echo esc_attr($sanitizedTitle); ?>"
                       value="<?php echo esc_attr((string) $field['desc']); ?>">
                <?php
                if (isset($field['helpBlock']) && $field['helpBlock'] !== '') {
                    $fieldId = esc_attr((string) $field['id']);
                    ?>
                    <small id="<?php echo esc_attr($fieldId); ?>_helpBlock"
                           class="form-text text-muted">
                        <?php
                        echo wp_kses_post((string) $field['helpBlock']);
                        ?>
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
     * @param array<string, string|bool> $field
     *
     * @return void
     */
    public static function active_true_false_radio_btn(array $field): void //phpcs:ignore
    {
        $fieldId    = esc_attr((string) $field['id']);
        $fieldTitle = esc_html((string) $field['title']);
        $trueText   = esc_html((string) $field['trueText']);
        $falseText  = esc_html((string) $field['falseText']);
        //phpcs:disable
        ?>
        <div class="form-group row">
            <label class="col-12"
                   for="true_false_radio_<?php echo esc_attr($fieldId); ?>">
                <?php echo esc_html($fieldTitle); ?>
            </label>
            <div class="true_false_radio col-12 "
                 name="true_false_radio_<?php echo esc_attr($fieldId); ?>">
                <div class="custom-control custom-radio">
                    <input type="radio"
                           id="<?php echo esc_attr($fieldId); ?>_1"
                           name="<?php echo esc_attr($fieldId); ?>"
                           value="true"
                           class="custom-control-input "
                        <?php if ($field['value']) {
                            print 'checked';
                        } ?>
                    >
                    <label class="custom-control-label <?php if ($field['value']) {
                        print 'active';
                                                       } ?>"
                           for="<?php echo esc_attr($fieldId); ?>_1">
                        <?php echo esc_html($trueText); ?>
                    </label>
                </div>
                <div class="custom-control custom-radio">
                    <input type="radio"
                           id="<?php echo esc_attr($fieldId); ?>_2"
                           name="<?php echo esc_attr($fieldId); ?>"
                           value="false"
                           class="custom-control-input "
                        <?php if (! $field['value']) {
                            print 'checked';
                        } ?>
                    >
                    <label class="custom-control-label  <?php if (! $field['value']) {
                        print 'active';
                                                        } ?>"
                           for="<?php echo esc_attr($fieldId); ?>_2">
                        <?php echo esc_html($falseText); ?>
                    </label>
                </div>
            </div>
            <?php
            if (isset($field['desc']) && $field['desc'] !== '') {
                ?>
                <small id="<?php echo esc_attr($fieldId); ?>_desc"
                       class="form-text text-muted col-12">
                    <?php echo wp_kses_post((string) $field['desc']); ?>
                </small>
                <?php
            }
            ?>
        </div>
        <?php
        //phpcs:enable
    }

    /**
     * @param array<string, string|bool|array<string, string>> $field
     *
     * @return void
     */
    public static function jtl_connector_select(array $field): void //phpcs:ignore
    {
        /** @var string $title */
        $title = $field['title'];
        /** @var string $id */
        $id = $field['id'];
        /** @var string $helpBlock */
        $helpBlock = $field['helpBlock'];
        $escId     = esc_attr($id);
        ?>
        <div class="form-group row">
            <label class="col-12"
                   for="<?php echo esc_attr($escId); ?>">
                <?php echo esc_html($title); ?>
            </label>
            <select required
                    class="form-control custom-select col-12 ml-3"
                    name="<?php echo esc_attr($escId); ?>">
                <?php
                if (
                    isset($field['options'])
                    && is_array($field['options'])
                    && count($field['options']) > 0
                ) {
                    foreach ($field['options'] as $key => $ovalue) {
                        $selected = (string) $key === $field['value']
                            ? ' selected' : '';
                        ?>
                        <option value="<?php echo esc_attr((string) $key); ?>"<?php echo esc_attr($selected); ?>>
                            <?php echo esc_html((string) $ovalue); ?>
                        </option>
                        <?php
                    }
                }
                ?>
            </select>
            <?php
            if ($helpBlock !== '') {
                ?>
                <small id="<?php echo esc_attr($escId); ?>_helpBlock"
                       class="form-text text-muted col-12">
                    <?php echo wp_kses_post($helpBlock); ?>
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
     * @param array<string, string|bool|array<int|string, string>> $field
     *
     * @return void
     */
    public static function jtl_connector_multiselect(array $field): void //phpcs:ignore
    {
        /** @var string $title */
        $title = $field['title'];
        /** @var string $id */
        $id = $field['id'];
        /** @var string $helpBlock */
        $helpBlock = $field['helpBlock'];
        /** @var array<int, string> $statusValues */
        $statusValues = $field['value'];
        $escId        = esc_attr($id);
        ?>
        <div class="form-group row">
            <label class="col-12"
                   for="<?php echo esc_attr($escId); ?>">
                <?php echo esc_html($title); ?>
            </label>
            <select required multiple
                    class="form-control custom-select col-12 ml-3"
                    name="<?php echo esc_attr($escId); ?>[]">
                <?php
                if (
                    isset($field['options'])
                    && is_array($field['options'])
                    && count($field['options']) > 0
                ) {
                    foreach ($field['options'] as $key => $ovalue) {
                        ?>
                        <option value="<?php echo esc_attr((string) $key); ?>"
                            <?php selected(in_array($key, $statusValues)); ?>>
                            <?php echo esc_html((string) $ovalue); ?>
                        </option>
                    <?php }
                } ?>
            </select>
            <?php
            if ($helpBlock !== '') {
                ?>
                <small id="<?php echo esc_attr($escId); ?>_helpBlock"
                       class="form-text text-muted col-12">
                    <?php echo wp_kses_post($helpBlock); ?>
                </small>
                <?php
            }
            ?>
        </div>
        <?php
    }

    /**
     * @param array<string, string> $field
     *
     * @return void
     */
    public static function dev_log_btn(array $field): void //phpcs:ignore
    {
        ?>
        <div class="form-group row">
            <div class="btn-group btn-group-lg col-12" role="group">
                <button type="button" id="downloadLogBtn"
                        class="btn btn-outline-success"><?php echo esc_html($field['downloadText']); ?></button>
                <button type="button" id="clearLogBtn"
                        class="btn btn-outline-danger"><?php echo esc_html($field['clearLogsText']); ?></button>
            </div>
        </div>
        <?php
    }

    // </editor-fold>

    // <editor-fold defaultstate="collapsed" desc="Update 1.5.0">

    /**
     * @param array<string, string|bool> $field
     *
     * @return void
     */
    public static function jtl_text_input(array $field): void //phpcs:ignore
    {
        $fieldId    = esc_attr((string) $field['id']);
        $fieldTitle = esc_html((string) $field['title']);
        $fieldValue = esc_attr((string) $field['value']);
        ?>
        <div class="form-group row">
            <label class="col-12"
                   for="<?php echo esc_attr($fieldId); ?>">
                <?php echo esc_html($fieldTitle); ?>
            </label>
            <input
                type="text"
                class="form-control col-12 ml-3"
                id="<?php echo esc_attr($fieldId); ?>"
                name="<?php echo esc_attr($fieldId); ?>"
                value="<?php echo esc_attr($fieldValue); ?>"
            >
            <?php
            if (isset($field['helpBlock']) && $field['helpBlock'] !== '') {
                ?>
                <small id="<?php echo esc_attr($fieldId); ?>_helpBlock"
                       class="form-text text-muted col-12">
                    <?php echo wp_kses_post((string) $field['helpBlock']); ?>
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
     * @param array<string, string|bool> $field
     *
     * @return void
     */
    public static function jtl_number_input(array $field): void //phpcs:ignore
    {
        $fieldId    = esc_attr((string) $field['id']);
        $fieldTitle = esc_html((string) $field['title']);
        $fieldValue = esc_attr((string) $field['value']);
        ?>
        <div class="form-group row">
            <label class="col-12"
                   for="<?php echo esc_attr($fieldId); ?>">
                <?php echo esc_html($fieldTitle); ?>
            </label>
            <input
                type="number"
                class="form-control col-12 ml-3"
                id="<?php echo esc_attr($fieldId); ?>"
                name="<?php echo esc_attr($fieldId); ?>"
                value="<?php echo esc_attr($fieldValue); ?>"
            >
            <?php
            if (isset($field['helpBlock']) && $field['helpBlock'] !== '') {
                ?>
                <small id="<?php echo esc_attr($fieldId); ?>_helpBlock"
                       class="form-text text-muted col-12">
                    <?php echo wp_kses_post((string) $field['helpBlock']); ?>
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
     * @param array<string, string|bool> $field
     *
     * @return void
     */
    public static function jtl_checkbox(array $field): void //phpcs:ignore
    {
        $fieldId    = esc_attr((string) $field['id']);
        $fieldTitle = esc_html((string) $field['title']);
        ?>
        <div class="form-group row">
            <label class="col-12"
                   for="<?php echo esc_attr($fieldId); ?>">
                <?php echo esc_html($fieldTitle); ?>
            </label>

            <input type="checkbox" class="form-control col-12"
                   id="<?php echo esc_attr($fieldId); ?>"
                   name="<?php echo esc_attr($fieldId); ?>"
                <?php if ($field['value']) {
                    print 'checked';
                } ?>>

            <textarea type="text" class="form-control"
                      aria-label="Text input with checkbox"
                      readonly><?php echo esc_textarea((string) $field['desc']); ?> </textarea>

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
        if (!current_user_can('manage_woocommerce')) {
            wp_die('', '', ['response' => 403]);
        }

        check_admin_referer('settings_save_woo-jtl-connector');

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

        $request = wp_get_referer();

        if ($request) {
            wp_safe_redirect($request, 301);
        } else {
            wp_safe_redirect(admin_url('admin.php?page=woo-jtl-connector'), 301);
        }
        exit;
    }

    /**
     * @return void
     */
    public static function default_customer_group_not_updated(): void //phpcs:ignore
    {
        $message  = esc_html(__(
            'The default customer is not set. Please update the B2B-Market 
            default customer group in the JTL-Connector settings',
            'woo-jtl-connector'
        ));
        $message .= ': <a href="admin.php?page=woo-jtl-connector-customers">'
            . esc_html(strtolower(__(
                'Customer Settings',
                'woo-jtl-connector'
            ))) . '</a>';

        echo '<div class="notice notice-error">
                <p class="pt-3 pb-3">
                    ' . wp_kses_post($message) . '
                </p>
            </div>';
    }

    /**
     * @return void
     */
    public static function pull_customer_group_not_updated(): void //phpcs:ignore
    {
        $message  = esc_html(__(
            'The pull customer groups are not set. Please update the 
            B2B-Market customer pull groups in the JTL-Connector settings',
            'woo-jtl-connector'
        ));
        $message .= ': <a href="admin.php?page=woo-jtl-connector-customers">'
            . esc_html(strtolower(__(
                'Customer Settings',
                'woo-jtl-connector'
            ))) . '</a>';

        echo '<div class="notice notice-error">
                <p class="pt-3 pb-3">
                    ' . wp_kses_post($message) . '
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
            'woo-jtl-connector'
        ));
    }

    /**
     * @return void
     */
    public function directory_no_write_access(): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        self::jtlwcc_show_wordpress_error(sprintf(
            // translators: %s: temporary directory path
            __(
                'Directory %s has no write access.',
                'woo-jtl-connector'
            ),
            esc_html(sys_get_temp_dir())
        ));
    }

    /**
     * @return void
     */
    public function phar_extension(): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        self::jtlwcc_show_wordpress_error(__('PHP extension "phar" could not be found.', 'woo-jtl-connector'));
    }

    /**
     * @return void
     */
    public function suhosin_whitelist(): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        self::jtlwcc_show_wordpress_error(__(
            'PHP extension "phar" is not on the suhosin whitelist.',
            'woo-jtl-connector'
        ));
    }
    // </editor-fold>
}