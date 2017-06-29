<?php

/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */
final class JtlConnectorAdmin
{
    const OPTIONS_TOKEN = 'jtlconnector_password';
    const OPTIONS_COMPLETED_ORDERS = 'jtlconnector_completed_orders';
    const OPTIONS_PULL_ORDERS_SINCE = 'jtlconnector_pull_orders_since';
    const OPTIONS_VARIATION_NAME_FORMAT = 'jtlconnector_variation_name_format';

    const OPTIONS_INSTALLED_VERSION = 'jtlconnector_installed_version';
    const OPTIONS_UPDATE_FAILED = 'jtlconnector_update_failed';

    private static $initiated = false;

    // <editor-fold defaultstate="collapsed" desc="Activation">
    public static function plugin_activation()
    {
        if (!woocommerce_activated()) {
            deactivate_plugins(__FILE__);
            add_action('admin_notices', 'woocommerce_not_activated');
        } elseif (version_compare(WC()->version, '2.3.0', '<')) {
            deactivate_plugins(__FILE__);
            add_action('admin_notices', 'wrong_woocommerce_version');
        }
        try {
            self::run_system_check();
            self::activate_linking();
            self::activate_checksum();
            self::activate_category_tree();
            self::create_constraints();
            add_option(self::OPTIONS_TOKEN, self::create_password());
            add_option(self::OPTIONS_COMPLETED_ORDERS, 'yes');
            add_option(self::OPTIONS_PULL_ORDERS_SINCE, '');
            add_option(self::OPTIONS_VARIATION_NAME_FORMAT, '');
            add_option(self::OPTIONS_INSTALLED_VERSION, CONNECTOR_VERSION);
        } catch (\jtl\Connector\Core\Exception\MissingRequirementException $exc) {
            if (is_admin() && (!defined('DOING_AJAX') || !DOING_AJAX)) {
                deactivate_plugins(__FILE__);
                wp_die($exc->getMessage());
            } else {
                return;
            }
        }
    }

    private static function run_system_check()
    {
        try {
            if (file_exists(CONNECTOR_DIR . '/connector.phar')) {
                if (is_writable(sys_get_temp_dir())) {
                    self::run_phar_check();
                } else {
                    add_action('admin_notices', 'directory_no_write_access');
                }
            }
            \jtl\Connector\Core\System\Check::run();
        } catch (\Exception $e) {
            wp_die($e->getMessage());
        }
    }

    private static function run_phar_check()
    {
        if (!extension_loaded('phar')) {
            add_action('admin_notices', 'phar_extension');
        }
        if (extension_loaded('suhosin')) {
            if (strpos(ini_get('suhosin.executor.include.whitelist'), 'phar') === false) {
                add_action('admin_notices', 'suhosin_whitelist');
            }
        }
    }

    private static function activate_linking()
    {
        global $wpdb;

        $query = '
            CREATE TABLE IF NOT EXISTS `%s` (
                `endpoint_id` BIGINT(20) unsigned NOT NULL,
                `host_id` INT(10) unsigned NOT NULL,
                PRIMARY KEY (`endpoint_id`, `host_id`),
                INDEX (`host_id`),
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci';

        $wpdb->query(sprintf($query, 'jtl_connector_link_category'));
        $wpdb->query(sprintf($query, 'jtl_connector_link_product'));
        $wpdb->query(sprintf($query, 'jtl_connector_link_order'));
        $wpdb->query(sprintf($query, 'jtl_connector_link_payment'));
        $wpdb->query(sprintf($query, 'jtl_connector_link_crossselling'));

        $wpdb->query('
            CREATE TABLE IF NOT EXISTS `jtl_connector_link_customer` (
                `endpoint_id` VARCHAR(255) NOT NULL,
                `host_id` INT(10) unsigned NOT NULL,
                `is_guest` BIT,
                PRIMARY KEY (`endpoint_id`, `host_id`, `is_guest`),
                INDEX (`host_id`, `is_guest`),
                INDEX (`endpoint_id`, `is_guest`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci'
        );

        $wpdb->query('
            CREATE TABLE IF NOT EXISTS `jtl_connector_link_image` (
                `endpoint_id` VARCHAR(255) NOT NULL,
                `host_id` INT(10) NOT NULL,
                `type` INT unsigned NOT NULL,
                PRIMARY KEY (`endpoint_id`, `host_id`, `type`),
                INDEX (`host_id`, `type`),
                INDEX (`endpoint_id`, `type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci'
        );

        self::add_constraints_for_multi_linking_tables();
    }

    private static function activate_checksum()
    {
        global $wpdb;

        $wpdb->query('
            CREATE TABLE IF NOT EXISTS `jtl_connector_product_checksum` (
                `product_id` BIGINT(20) unsigned NOT NULL,
                `type` tinyint unsigned NOT NULL,
                `checksum` varchar(255) NOT NULL,
                PRIMARY KEY (`product_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci');
    }

    private static function activate_category_tree()
    {
        global $wpdb;

        $wpdb->query('
            CREATE TABLE IF NOT EXISTS `jtl_connector_category_level` (
                `category_id` BIGINT(20) unsigned NOT NULL,
                `level` int(10) unsigned NOT NULL,
                `sort` int(10) unsigned NOT NULL,
                PRIMARY KEY (`category_id`),
                INDEX (`level`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci');
    }

    private static function create_constraints()
    {
        global $wpdb;

        $engine = $wpdb->get_var(sprintf("
            SELECT ENGINE
            FROM information_schema.TABLES
            WHERE TABLE_NAME = '{$wpdb->posts}' AND TABLE_SCHEMA = '%s'",
            DB_NAME
        ));

        if ($engine === 'InnoDB') {
            $wpdb->query("
                ALTER TABLE `jtl_connector_product_checksum`
                ADD CONSTRAINT `jtl_connector_product_checksum1` FOREIGN KEY (`product_id`) REFERENCES {$wpdb->posts} (`ID`) ON DELETE CASCADE ON UPDATE NO ACTION"
            );
        }

        $engine = $wpdb->get_var(sprintf("
            SELECT ENGINE
            FROM information_schema.TABLES
            WHERE TABLE_NAME = '{$wpdb->terms}' AND TABLE_SCHEMA = '%s'",
            DB_NAME
        ));

        if ($engine === 'InnoDB') {
            $wpdb->query("
                ALTER TABLE `jtl_connector_category_level`
                ADD CONSTRAINT `jtl_connector_category_level1` FOREIGN KEY (`category_id`) REFERENCES {$wpdb->terms} (`term_id`) ON DELETE CASCADE ON UPDATE NO ACTION"
            );
        }
    }

    private static function create_password()
    {
        if (function_exists('com_create_guid') === true) {
            return trim(com_create_guid(), '{}');
        }

        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }

    // </editor-fold>

    public static function plugin_deactivation()
    {
        delete_option(self::OPTIONS_TOKEN);
    }

    public static function init()
    {
        if (!self::$initiated) {
            self::init_hooks();
        }
    }

    public static function init_hooks()
    {
        self::$initiated = true;

        add_filter('plugin_row_meta', ['JtlConnectorAdmin', 'jtlconnector_plugin_row_meta'], 10, 2);
        add_action('woocommerce_settings_tabs_array', ['JtlConnectorAdmin', 'add_settings_tab'], 50);
        add_action('woocommerce_settings_tabs_jtlconnector', ['JtlConnectorAdmin', 'display_page'], 1);
        add_action('woocommerce_settings_save_jtlconnector', ['JtlConnectorAdmin', 'save']);
        add_action('woocommerce_admin_field_date', ['JtlConnectorAdmin', 'date_field']);

        self::update();
    }

    public static function jtlconnector_plugin_row_meta($links, $file)
    {
        if (strpos($file, 'jtlconnector.php') !== false) {
            $url = esc_url('http://guide.jtl-software.de/jtl/Kategorie:JTL-Connector:WooCommerce');
            $new_links = [
                '<a target="_blank" href="' . $url . '">' . __('Documentation', TEXT_DOMAIN) . '</a>',
            ];
            $links = array_merge($links, $new_links);
        }

        return $links;
    }

    // <editor-fold defaultstate="collapsed" desc="Settings">
    public static function add_settings_tab($tabs)
    {
        $tabs[TEXT_DOMAIN] = 'JTL-Connector';

        return $tabs;
    }

    public static function settings_link($links)
    {
        $link = 'admin.php?page=wc-settings&tab=jtlconnector';
        $settings_link = '<a href="' . $link . '">' . __('Settings', TEXT_DOMAIN) . '</a>';
        array_unshift($links, $settings_link);

        return $links;
    }

    public static function display_page()
    {
        require_once 'settings.php';
    }

    public static function get_settings()
    {
        $settings = apply_filters('woocommerce_settings_jtlconnector', [
            [
                'title' => __('Options', TEXT_DOMAIN),
                'type'  => 'title',
                'desc'  => __('Settings for the usage of the connector. By default the completed orders are pulled with no time limit.', TEXT_DOMAIN),
            ],
            [
                'title' => __('Pull completed orders', TEXT_DOMAIN),
                'type'  => 'checkbox',
                'desc'  => __('Choose when having a large amount of data and low server specifications.', TEXT_DOMAIN),
                'id'    => self::OPTIONS_COMPLETED_ORDERS,
            ],
            [
                'title'    => __('Pull orders since', TEXT_DOMAIN),
                'type'     => 'date',
                'desc_tip' => __('Define a start date for pulling of orders.', TEXT_DOMAIN),
                'id'       => self::OPTIONS_PULL_ORDERS_SINCE,
            ],
            [
                'title'    => __('Variation name format', TEXT_DOMAIN),
                'type'     => 'select',
                'id'       => self::OPTIONS_VARIATION_NAME_FORMAT,
                'options'  => [
                    ''                => __('Variation #22 of Product name', TEXT_DOMAIN),
                    'space'           => __('Variation #22 of Product name Color: black, Size: S', TEXT_DOMAIN),
                    'brackets'        => __('Variation #22 of Product name (Color: black, Size: S)', TEXT_DOMAIN),
                    'space_parent'    => __('Product name Color: black, Size: S', TEXT_DOMAIN),
                    'brackets_parent' => __('Product name (Color: black, Size: S)', TEXT_DOMAIN),
                ],
                'desc_tip' => __('Define how the child product name is formatted.', TEXT_DOMAIN),
            ],
            'section_end' => [
                'type' => 'sectionend',
            ],
        ]);

        return apply_filters('woocommerce_get_settings_jtlconnector', $settings);
    }

    public static function date_field(array $field)
    {
        $option_value = get_option($field['id'], $field['default']);

        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?= $field['id'] ?>"><?= $field['title'] ?></label>
                <span class="woocommerce-help-tip" data-tip="<?= $field['desc_tip'] ?>"></span>
            </th>
            <td class="forminp forminp-select">
                <input id="<?= $field['id'] ?>" name="<?= $field['id'] ?>" value="<?= $option_value ?>" type="date">
                <span class="description"><?= $field['desc'] ?></span>
            </td>
        </tr>
        <?php
    }

    public static function save()
    {
        $settings = self::get_settings();
        WC_Admin_Settings::save_fields($settings);
    }

    // </editor-fold>

    private static function update()
    {
        $installed_version = \get_option(self::OPTIONS_INSTALLED_VERSION, '');
        $installed_version = version_compare($installed_version, '1.3.0', '<') ? '' : $installed_version;

        switch ($installed_version) {
            case '':
                self::update_to_multi_linking();
            case '1.3.0':
            case '1.3.1':
                self::update_multi_linking_endpoint_types();
            case '1.3.2':
            case '1.3.3':
            case '1.3.4':
            case '1.3.5':
            case '1.4.0':
        }

        \update_option(self::OPTIONS_INSTALLED_VERSION, CONNECTOR_VERSION);
    }

    // <editor-fold defaultstate="collapsed" desc="Update 1.3.0">
    private static function update_to_multi_linking()
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
            $type = (int)$type->type;
            $tableName = self::get_table_name($type);
            $result = $result && $wpdb->query("
                INSERT INTO `{$tableName}` (`host_id`, `endpoint_id`)
                SELECT `host_id`, `endpoint_id` FROM `jtl_connector_link` WHERE `type` = {$type}
            ");
        }

        if ($result) {
            $wpdb->query('DROP TABLE IF EXISTS `jtl_connector_link`');
            $wpdb->query('COMMIT');
        } else {
            $wpdb->query('ROLLBACK');
            update_option(self::OPTIONS_UPDATE_FAILED, 'yes');
            add_action('admin_notices', 'update_failed');
        }
    }

    private static function get_table_name($type)
    {
        switch ($type) {
            case \jtl\Connector\Linker\IdentityLinker::TYPE_CATEGORY:
                return 'jtl_connector_link_category';
            case \jtl\Connector\Linker\IdentityLinker::TYPE_CUSTOMER:
                return 'jtl_connector_link_customer';
            case \jtl\Connector\Linker\IdentityLinker::TYPE_PRODUCT:
                return 'jtl_connector_link_product';
            case \jtl\Connector\Linker\IdentityLinker::TYPE_IMAGE:
                return 'jtl_connector_link_image';
            case \jtl\Connector\Linker\IdentityLinker::TYPE_CUSTOMER_ORDER:
                return 'jtl_connector_link_order';
            case \jtl\Connector\Linker\IdentityLinker::TYPE_PAYMENT:
                return 'jtl_connector_link_payment';
            case \jtl\Connector\Linker\IdentityLinker::TYPE_CROSSSELLING:
                return 'jtl_connector_link_crossselling';
        }

        return null;
    }
    // </editor-fold>

    // <editor-fold defaultstate="collapsed" desc="Update 1.3.2">
    private static function update_multi_linking_endpoint_types()
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
        $wpdb->query(sprintf('
            UPDATE `jtl_connector_link_customer` 
            SET `is_guest` = 1
            WHERE `endpoint_id` LIKE "%s_%%"',
            \jtl\Connector\WooCommerce\Utility\Id::GUEST_PREFIX
        ));
        $wpdb->query(sprintf('
            UPDATE `jtl_connector_link_customer` 
            SET `is_guest` = 0
            WHERE `endpoint_id` NOT LIKE "%s_%%"',
            \jtl\Connector\WooCommerce\Utility\Id::GUEST_PREFIX
        ));

        // Add type column for images instead of using a prefix
        $wpdb->query('ALTER TABLE `jtl_connector_link_image` ADD COLUMN `type` INT(4) unsigned');
        $updateImageLinkingTable = '
            UPDATE `jtl_connector_link_image` 
            SET `type` = %d, `endpoint_id` = SUBSTRING(`endpoint_id`, 3)
            WHERE `endpoint_id` LIKE "%s_%%"';
        $wpdb->query(sprintf($updateImageLinkingTable,
            \jtl\Connector\Linker\IdentityLinker::TYPE_CATEGORY,
            \jtl\Connector\WooCommerce\Utility\Id::CATEGORY_PREFIX
        ));
        $wpdb->query(sprintf($updateImageLinkingTable,
            \jtl\Connector\Linker\IdentityLinker::TYPE_PRODUCT,
            \jtl\Connector\WooCommerce\Utility\Id::PRODUCT_PREFIX
        ));

        self::add_constraints_for_multi_linking_tables();
    }

    private static function add_constraints_for_multi_linking_tables()
    {
        global $wpdb;

        $engine = $wpdb->get_var(sprintf("
            SELECT ENGINE
            FROM information_schema.TABLES
            WHERE TABLE_NAME = '{$wpdb->posts}' AND TABLE_SCHEMA = '%s'",
            DB_NAME
        ));

        if ($engine === 'InnoDB') {
            $wpdb->query("
                ALTER TABLE `jtl_connector_link_product`
                ADD CONSTRAINT `jtl_connector_link_product_1` FOREIGN KEY (`endpoint_id`) REFERENCES `{$wpdb->posts}` (`ID`) ON DELETE CASCADE ON UPDATE NO ACTION"
            );
            $wpdb->query("
                ALTER TABLE `jtl_connector_link_order`
                ADD CONSTRAINT `jtl_connector_link_order_1` FOREIGN KEY (`endpoint_id`) REFERENCES `{$wpdb->posts}` (`ID`) ON DELETE CASCADE ON UPDATE NO ACTION"
            );
            $wpdb->query("
                ALTER TABLE `jtl_connector_link_payment`
                ADD CONSTRAINT `jtl_connector_link_payment_1` FOREIGN KEY (`endpoint_id`) REFERENCES `{$wpdb->posts}` (`ID`) ON DELETE CASCADE ON UPDATE NO ACTION"
            );
            $wpdb->query("
                ALTER TABLE `jtl_connector_link_crossselling`
                ADD CONSTRAINT `jtl_connector_link_crossselling_1` FOREIGN KEY (`endpoint_id`) REFERENCES `{$wpdb->posts}` (`ID`) ON DELETE CASCADE ON UPDATE NO ACTION"
            );
        }
        $engine = $wpdb->get_var(sprintf("
            SELECT ENGINE
            FROM information_schema.TABLES
            WHERE TABLE_NAME = '{$wpdb->terms}' AND TABLE_SCHEMA = '%s'",
            DB_NAME
        ));

        if ($engine === 'InnoDB') {
            $wpdb->query("
                ALTER TABLE `jtl_connector_link_category`
                ADD CONSTRAINT `jtl_connector_link_category_1` FOREIGN KEY (`endpoint_id`) REFERENCES `{$wpdb->terms}` (`term_id`) ON DELETE CASCADE ON UPDATE NO ACTION"
            );
        }
    }
    // </editor-fold>

    // <editor-fold defaultstate="collapsed" desc="Error messages">
    function update_failed()
    {
        self::show_wordpress_error(__('The linking table migration was not successful. Please use the forum for help.', TEXT_DOMAIN));
    }

    function directory_no_write_access()
    {
        self::show_wordpress_error(sprintf(__('Directory %s has no write access.', sys_get_temp_dir()), TEXT_DOMAIN));
    }

    function phar_extension()
    {
        self::show_wordpress_error(__('PHP extension "phar" could not be found.', TEXT_DOMAIN));
    }

    function suhosin_whitelist()
    {
        self::show_wordpress_error(__('PHP extension "phar" could not be found.', TEXT_DOMAIN));
    }

    private function show_wordpress_error($message)
    {
        echo '<div class="error"><p><b>JTL-Connector:</b>&nbsp;' . $message . '</p></div>';
    }
    // </editor-fold>
}