<?php

/**
 * @wordpress-plugin
 * Plugin Name: Advanced Shipment Tracking for WooCommerce 
 * Plugin URI: https://www.zorem.com/products/woocommerce-advanced-shipment-tracking/ 
 * Description: Add shipment tracking information to your WooCommerce orders and provide customers with an easy way to track their orders. Shipment tracking Info will appear in customers accounts (in the order panel) and in WooCommerce order complete email. 
 * Version: 3.5.2
 * Author: zorem
 * Author URI: https://www.zorem.com 
 * License: GPL-2.0+
 * License URI: 
 * Text Domain: woo-advanced-shipment-tracking 
 * WC tested up to: 7.4.0
*/
class Zorem_Woocommerce_Advanced_Shipment_Tracking
{
    /**
     * WooCommerce Advanced Shipment Tracking version.
     *
     * @var string
     */
    public $version = '3.5.2';
    /**
     * Initialize the main plugin function
     */
    public function __construct()
    {
    }
    /**
     * Callback on activation and allow to activate if pro deactivated
     *
     * @since  1.0.0
     */
    public function on_activation()
    {
    }
    /**
     * Display WC active notice
     */
    public function notice_activate_wc()
    {
    }
    /*
     * init when class loaded
     */
    public function init()
    {
    }
    /**
     * Send email when order status change to 'Partial Shipped'	 
     */
    public function email_trigger_partial_shipped($order_id, $order = \false)
    {
    }
    /**
     * Send email when order status change to 'Updated Tracking'	 
     */
    public function email_trigger_updated_tracking($order_id, $order = \false)
    {
    }
    /*** Method load Language file ***/
    public function wst_load_textdomain()
    {
    }
    /**
     * Gets the absolute plugin path without a trailing slash, e.g.
     * /path/to/wp-content/plugins/plugin-directory.
     *
     * @return string plugin path
     */
    public function get_plugin_path()
    {
    }
    /**
     * Register shipment tracking routes.
     *
     * @since 1.5.0
     */
    public function rest_api_register_routes()
    {
    }
    /*
     * include file on plugin load
     */
    public function on_plugins_loaded()
    {
    }
    /*
     * return plugin directory URL
     */
    public function plugin_dir_url()
    {
    }
    /*
     * Plugin uninstall code 
     */
    public function uninstall_notice()
    {
    }
    /*
     * Functon for reassign order status on plugin deactivation
     */
    public function reassign_order_status()
    {
    }
    /**
     * Add plugin action links.
     *
     * Add a link to the settings page on the plugins.php page.
     *
     * @since 2.6.5
     *
     * @param  array  $links List of existing plugin action links.
     * @return array         List of modified plugin action links.
     */
    public function ast_plugin_action_links($links)
    {
    }
}
/**
 * Customer Completed Order Email.
 *
 * Order complete emails are sent to the customer when the order is marked complete and usual indicates that the order has been shipped.
 *
 * @class       WC_Email_Customer_Partial_Shipped_Order
 * @version     2.0.0
 * @package     WooCommerce/Classes/Emails
 * @extends     WC_Email
 */
class WC_Email_Customer_Partial_Shipped_Order extends \WC_Email
{
    /**
     * Constructor.
     */
    public function __construct()
    {
    }
    /**
     * Trigger the sending of this email.
     *
     * @param int            $order_id The order ID.
     * @param WC_Order|false $order Order object.
     */
    public function trigger($order_id, $order = \false)
    {
    }
    /**
     * Get email subject.
     *
     * @since  3.1.0
     * @return string
     */
    public function get_default_subject()
    {
    }
    /**
     * Get email heading.
     *
     * @since  3.1.0
     * @return string
     */
    public function get_default_heading()
    {
    }
    /**
     * Get content html.
     *
     * @return string
     */
    public function get_content_html()
    {
    }
    /**
     * Get content plain.
     *
     * @return string
     */
    public function get_content_plain()
    {
    }
    /**
     * Default content to show below main email content.
     *
     * @since 3.7.0
     * @return string
     */
    public function get_default_additional_content()
    {
    }
}
/**
 * Customer Completed Order Email.
 *
 * Order complete emails are sent to the customer when the order is marked complete and usual indicates that the order has been shipped.
 *
 * @class       WC_Email_Customer_Updated_Tracking_Order
 * @version     2.0.0
 * @package     WooCommerce/Classes/Emails
 * @extends     WC_Email
 */
class WC_Email_Customer_Updated_Tracking_Order extends \WC_Email
{
    /**
     * Constructor.
     */
    public function __construct()
    {
    }
    /**
     * Trigger the sending of this email.
     *
     * @param int            $order_id The order ID.
     * @param WC_Order|false $order Order object.
     */
    public function trigger($order_id, $order = \false)
    {
    }
    /**
     * Get email subject.
     *
     * @since  3.1.0
     * @return string
     */
    public function get_default_subject()
    {
    }
    /**
     * Get email heading.
     *
     * @since  3.1.0
     * @return string
     */
    public function get_default_heading()
    {
    }
    /**
     * Get content html.
     *
     * @return string
     */
    public function get_content_html()
    {
    }
    /**
     * Get content plain.
     *
     * @return string
     */
    public function get_content_plain()
    {
    }
    /**
     * Default content to show below main email content.
     *
     * @since 3.7.0
     * @return string
     */
    public function get_default_additional_content()
    {
    }
}
class WC_AST_Admin_Notices_Under_WC_Admin
{
    /**
     * Initialize the main plugin function
     */
    public function __construct()
    {
    }
    /**
     * Get the class instance
     *
     * @return WC_Advanced_Shipment_Tracking_Admin_notice
     */
    public static function get_instance()
    {
    }
    /*
     * init from parent mail class
     */
    public function init()
    {
    }
    public function admin_notices_for_ast_pro()
    {
    }
}
class WC_Advanced_Shipment_Tracking_Install
{
    /**
     * Initialize the main plugin function
     */
    public function __construct()
    {
    }
    /**
     * Get the class instance
     *
     * @return WC_Advanced_Shipment_Tracking_Install
     */
    public static function get_instance()
    {
    }
    /*
     * init from parent mail class
     */
    public function init()
    {
    }
    /**
     * Define plugin activation function
     *
     * Create Table
     *
     * Insert data 
     *
     * 
     */
    public function woo_shippment_tracking_install()
    {
    }
    /*
     * function for create shipping provider table
     */
    public function create_shippment_tracking_table()
    {
    }
    public function ast_insert_shipping_providers()
    {
    }
    /*
     * check if all column exist in shipping provider database
     */
    public function check_all_column_exist()
    {
    }
    /*
     * database update
     */
    public function update_database_check()
    {
    }
    /*
     * function for update order meta from shipment_status to ts_shipment_status for filter order by shipment status
     */
    public function update_ts_shipment_status_order_mete($page)
    {
    }
    /**
     * Function for add provider image in uploads directory under wp-content/uploads/ast-shipping-providers
     */
    public function add_provider_image_in_upload_directory()
    {
    }
    /**
     * Get providers list from trackship and update providers in database
     */
    public function update_shipping_providers()
    {
    }
    /**
     * Get providers list from trackship and update providers in database
     */
    public function ast_insert_shipping_provider()
    {
    }
}
class WC_Advanced_Shipment_Tracking_Settings
{
    /**
     * Initialize the main plugin function
     */
    public function __construct()
    {
    }
    /**
     * Get the class instance
     *
     * @return WC_Advanced_Shipment_Tracking_Settings
     */
    public static function get_instance()
    {
    }
    /*
     * init from parent mail class
     */
    public function init()
    {
    }
    /** 
     * Register new status : Delivered
     **/
    public function register_order_status()
    {
    }
    /*
     * add status after completed
     */
    public function add_delivered_to_order_statuses($order_statuses)
    {
    }
    /*
     * Adding the custom order status to the default woocommerce order statuses
     */
    public function include_custom_order_status_to_reports($statuses)
    {
    }
    /*
     * mark status as a paid.
     */
    public function delivered_woocommerce_order_is_paid_statuses($statuses)
    {
    }
    /*
     * add bulk action
     * Change order status to delivered
     */
    public function add_bulk_actions($bulk_actions)
    {
    }
    /*
     * add order again button for delivered order status	
     */
    public function add_reorder_button_delivered($statuses)
    {
    }
    /*
     * Add delivered action button in preview order list to change order status from completed to delivered
     */
    public function additional_admin_order_preview_buttons_actions($actions, $order)
    {
    }
    /*
     * Add action button in order list to change order status from completed to delivered
     */
    public function add_delivered_order_status_actions_button($actions, $order)
    {
    }
    /** 
     * Register new status : Updated Tracking
     **/
    public function register_updated_tracking_order_status()
    {
    }
    /** 
     * Register new status : Partially Shipped
     **/
    public function register_partial_shipped_order_status()
    {
    }
    /*
     * add status after completed
     */
    public function add_updated_tracking_to_order_statuses($order_statuses)
    {
    }
    /*
     * add status after completed
     */
    public function add_partial_shipped_to_order_statuses($order_statuses)
    {
    }
    /*
     * Adding the updated-tracking order status to the default woocommerce order statuses
     */
    public function include_updated_tracking_order_status_to_reports($statuses)
    {
    }
    /*
     * Adding the partial-shipped order status to the default woocommerce order statuses
     */
    public function include_partial_shipped_order_status_to_reports($statuses)
    {
    }
    /*
     * mark status as a paid.
     */
    public function updated_tracking_woocommerce_order_is_paid_statuses($statuses)
    {
    }
    /*
     * Give download permission to updated tracking order status
     */
    public function add_updated_tracking_to_download_permission($data, $order)
    {
    }
    /*
     * mark status as a paid.
     */
    public function partial_shipped_woocommerce_order_is_paid_statuses($statuses)
    {
    }
    /*
     * Give download permission to partial shipped order status
     */
    public function add_partial_shipped_to_download_permission($data, $order)
    {
    }
    /*
     * add bulk action
     * Change order status to Updated Tracking
     */
    public function add_bulk_actions_updated_tracking($bulk_actions)
    {
    }
    /*
     * add bulk action
     * Change order status to Partially Shipped
     */
    public function add_bulk_actions_partial_shipped($bulk_actions)
    {
    }
    /*
     * add order again button for delivered order status	
     */
    public function add_reorder_button_partial_shipped($statuses)
    {
    }
    /*
     * add order again button for delivered order status	
     */
    public function add_reorder_button_updated_tracking($statuses)
    {
    }
    /*
     * add Updated Tracking in order status email customizer
     */
    public function wcast_order_status_email_type($order_status)
    {
    }
    /*
     * Rename WooCommerce Order Status
     */
    public function wc_renaming_order_status($order_statuses)
    {
    }
    /*
     * define the woocommerce_register_shop_order_post_statuses callback 
     * rename filter 
     * rename from completed to shipped
     */
    public function filter_woocommerce_register_shop_order_post_statuses($array)
    {
    }
    /*
     * rename bulk action
     */
    public function modify_bulk_actions($bulk_actions)
    {
    }
    /*
     * Add class in admin settings page
     */
    public function ahipment_tracking_admin_body_class($classes)
    {
    }
    public function ast_open_inline_tracking_form_fun()
    {
    }
    /**
     * Update Partially Shipped order email enable/disable in customizer
     */
    public function save_partial_shipped_email($data)
    {
    }
    /**
     * Synch provider function 
     */
    public function sync_providers_fun()
    {
    }
    /**
     * Output html of added provider from sync providers
     */
    public function added_html($added_data)
    {
    }
    /**
     * Output html of updated provider from sync providers
     */
    public function updated_html($updated_data)
    {
    }
    /**
     * Output html of deleted provider from sync providers
     */
    public function deleted_html($deleted_data)
    {
    }
}
class WC_Advanced_Shipment_Tracking_Actions
{
    public function __construct()
    {
    }
    /**
     * Get the class instance
     *
     * @return WC_Advanced_Shipment_Tracking_Actions
     */
    public static function get_instance()
    {
    }
    /**
     * Get shipping providers from database
     */
    public function get_providers()
    {
    }
    /**
     * Get shipping providers from database for WooCommerce App
     */
    public function get_providers_for_app()
    {
    }
    /**
     * Load admin styles.
     */
    public function admin_styles()
    {
    }
    /**
     * Define shipment tracking column in admin orders list.
     *
     * @since 1.6.1
     *
     * @param array $columns Existing columns
     *
     * @return array Altered columns
     */
    public function shop_order_columns($columns)
    {
    }
    /**
     * Render shipment tracking in custom column.
     *
     * @since 1.6.1
     *
     * @param string $column Current column
     */
    public function render_shop_order_columns($column)
    {
    }
    public function render_woocommerce_page_orders_columns($column_name, $order)
    {
    }
    /**
     * Get content for shipment tracking column.
     *
     * @since 1.6.1
     *
     * @param int $order_id Order ID
     *
     * @return string Column content to render
     */
    public function get_shipment_tracking_column($order_id)
    {
    }
    /**
     * Add the meta box for shipment info on the order page
     */
    public function add_meta_box()
    {
    }
    /**
     * Returns a HTML node for a tracking item for the admin meta box
     */
    public function display_html_tracking_item_for_meta_box($order_id, $item)
    {
    }
    /**
     * Show the meta box for shipment info on the order page
     */
    public function meta_box($post_or_order_object)
    {
    }
    /*
     * Function for mark order as html
     */
    public function mark_order_as_fields_html()
    {
    }
    /*
     * Function for add tracking button in order details page
     */
    public function ast_add_tracking_btn()
    {
    }
    /**
     * Order Tracking Get All Order Items AJAX
     *
     * Function for getting all tracking items associated with the order
     */
    public function get_meta_box_items_ajax()
    {
    }
    /**
     * Get shipping provider custom name or name	 
     */
    public function get_ast_provider_name_callback($provider_name, $results)
    {
    }
    /**
     * Get shipping provider image src 
     */
    public function get_shipping_provdider_src_callback($results)
    {
    }
    /**
     * Order Tracking Save
     *
     * Function for saving tracking items
     */
    public function save_meta_box($post_id, $post)
    {
    }
    /**
     * Order Tracking Save AJAX
     *
     * Function for saving tracking items via AJAX
     */
    public function save_meta_box_ajax()
    {
    }
    /**
     * Order Tracking Save AJAX
     *
     * Function for saving tracking items via AJAX
     */
    public function save_inline_tracking_number()
    {
    }
    /**
     * Order Tracking Delete
     *
     * Function to delete a tracking item
     */
    public function meta_box_delete_tracking()
    {
    }
    /**
     * Display Shipment info in the frontend (order view/tracking page).
     */
    public function show_tracking_info_order($order_id)
    {
    }
    /**
     * Adds a new column Track to the "My Orders" table in the account.
     *
     * @param string[] $columns the columns in the orders table
     * @return string[] updated columns
     */
    public function add_column_my_account_orders($columns)
    {
    }
    /**
     * Adds data to the custom "Track" column in "My Account > Orders".
     *
     * @param \WC_Order $order the order object for the row
     */
    public function add_column_my_account_orders_ast_track_column($actions, $order)
    {
    }
    /**
     * Display shipment info in customer emails.
     *
     * @version 1.6.8
     *
     * @param WC_Order $order         Order object.
     * @param bool     $sent_to_admin Whether the email is being sent to admin or not.
     * @param bool     $plain_text    Whether email is in plain text or not.
     * @param WC_Email $email         Email object.
     */
    public function email_display($order, $sent_to_admin, $plain_text = \null, $email = \null)
    {
    }
    /**
     * Prevents data being copied to subscription renewals
     */
    public function woocommerce_subscriptions_renewal_order_meta_query($order_meta_query, $original_order_id, $renewal_order_id, $new_order_role)
    {
    }
    /*
     * Works out the final tracking provider and tracking link and appends then to the returned tracking item
     *
     */
    public function get_formatted_tracking_item($order_id, $tracking_item)
    {
    }
    public function check_ts_tracking_page_for_tracking_item($order_id, $tracking_item, $status)
    {
    }
    /**
     * Deletes a tracking item from post_meta array
     *
     * @param int    $order_id    Order ID
     * @param string $tracking_id Tracking ID
     *
     * @return bool True if tracking item is deleted successfully
     */
    public function delete_tracking_item($order_id, $tracking_id)
    {
    }
    /*
     * Adds a tracking item to the post_meta array
     *
     * @param int   $order_id    Order ID
     * @param array $tracking_items List of tracking item
     *
     * @return array Tracking item
     */
    public function add_tracking_item($order_id, $args)
    {
    }
    public function seach_tracking_number_in_items($tracking_number, $tracking_items)
    {
    }
    /*
     * Adds a tracking item to the post_meta array from external system programatticaly
     *
     * @param int   $order_id    Order ID
     * @param array $tracking_items List of tracking item
     *
     * @return array Tracking item
     */
    public function insert_tracking_item($order_id, $args)
    {
    }
    /**
     * Saves the tracking items array to post_meta.
     *
     * @param int   $order_id       Order ID
     * @param array $tracking_items List of tracking item
     */
    public function save_tracking_items($order_id, $tracking_items)
    {
    }
    /**
     * Gets a single tracking item from the post_meta array for an order.
     *
     * @param int    $order_id    Order ID
     * @param string $tracking_id Tracking ID
     * @param bool   $formatted   Wether or not to reslove the final tracking
     *                            link and provider in the returned tracking item.
     *                            Default to false.
     *
     * @return null|array Null if not found, otherwise array of tracking item will be returned
     */
    public function get_tracking_item($order_id, $tracking_id, $formatted = \false)
    {
    }
    /*
     * Gets all tracking itesm fron the post meta array for an order
     *
     * @param int  $order_id  Order ID
     * @param bool $formatted Wether or not to reslove the final tracking link
     *                        and provider in the returned tracking item.
     *                        Default to false.
     *
     * @return array List of tracking items
     */
    public function get_tracking_items($order_id, $formatted = \false)
    {
    }
    /**
     * Gets the absolute plugin path without a trailing slash, e.g.
     * /path/to/wp-content/plugins/plugin-directory
     *
     * @return string plugin path
     */
    public function get_plugin_path()
    {
    }
    /**
     * Validation code add tracking info form
     */
    public function custom_validation_js()
    {
    }
    /*
     * Get formated order id
     */
    public function get_formated_order_id($order_id)
    {
    }
    /*
     * Return option value for customizer
     */
    public function get_option_value_from_array($array, $key, $default_value)
    {
    }
    /*
     * Return checkbox option value for customizer
     */
    public function get_checkbox_option_value_from_array($array, $key, $default_value)
    {
    }
}
/**
 * Handles email sending
 */
class WC_Advanced_Shipment_Tracking_Email_Manager
{
    /**
     * Constructor sets up actions
     */
    public function __construct()
    {
    }
    /**
     * Code for include delivered email class
     */
    public function custom_init_emails($emails)
    {
    }
    /**
     * Code for format email content
     */
    public function email_content($email_content, $order_id, $order)
    {
    }
    /**
     * Filter in custom email templates with priority to child themes
     *
     * @param string $template the email template file.
     * @param string $template_name name of email template.
     * @param string $template_path path to email template.	 
     * @return string
     */
    public function filter_locate_template($template, $template_name, $template_path)
    {
    }
    public function completed_email_heading($email_heading, $order)
    {
    }
    public function completed_email_subject($email_subject, $order)
    {
    }
}
class WC_Advanced_Shipment_Tracking_Admin
{
    /**
     * Initialize the main plugin function
     */
    public function __construct()
    {
    }
    /**
     * Get the class instance
     *
     * @return WC_Advanced_Shipment_Tracking_Admin
     */
    public static function get_instance()
    {
    }
    /*
     * init from parent mail class
     */
    public function init()
    {
    }
    /*
     * Get shipped orders
     */
    public function get_shipped_orders()
    {
    }
    /**
     * Load admin styles.
     */
    public function admin_styles($hook)
    {
    }
    /*
     * Admin Menu add function
     * WC sub menu
     */
    public function register_woocommerce_menu()
    {
    }
    public function hide_admin_notices_from_settings()
    {
    }
    /*
     * callback for Shipment Tracking page
     */
    public function woocommerce_advanced_shipment_tracking_page_callback()
    {
    }
    /*
     * callback for Shipment Tracking menu array
     */
    public function get_ast_tab_settings_data()
    {
    }
    /*
     * callback for Shipment Tracking general settings data
     */
    public function get_ast_tab_general_settings_data()
    {
    }
    /*
     * callback for HTML function for Shipment Tracking menu
     */
    public function get_html_menu_tab($arrays, $tab_class = 'tab_input')
    {
    }
    /*
     * get UL html of fields
     */
    public function get_html_ul($arrays)
    {
    }
    public function get_add_tracking_options()
    {
    }
    public function get_customer_view_options()
    {
    }
    public function get_shipment_tracking_api_options()
    {
    }
    /*
     * get updated tracking status settings array data
     * return array
     */
    public function get_updated_tracking_data()
    {
    }
    /*
     * get Partially Shipped array data
     * return array
     */
    public function get_partial_shipped_data()
    {
    }
    /*
     * get settings tab array data
     * return array
     */
    public function get_delivered_data()
    {
    }
    /*
     * get Order Status data
     * return array
     */
    public function get_osm_data()
    {
    }
    /*
     * settings form save
     */
    public function wc_ast_settings_form_update_callback()
    {
    }
    /*
     * Change style of delivered order label
     */
    public function footer_function()
    {
    }
    /*
     * Ajax call for upload tracking details into order from bulk upload
     */
    public function upload_tracking_csv_fun()
    {
    }
    /*
     * Function for autocompleted order after adding all product through TPI 
     */
    public function autocomplete_order_after_adding_all_products($order_id, $status_shipped, $products_list)
    {
    }
    /*
     * Function for get already added product in TPI
     */
    public function get_all_added_product_list_with_qty($order_id)
    {
    }
    /*
     * Updated order status to Shipped(Completed), Partially Shipped, Updated Tracking
     */
    public function update_order_status_after_adding_tracking($status_shipped, $order)
    {
    }
    /**
     * Check if the value is a valid date
     *
     * @param mixed $value
     *
     * @return boolean
     */
    public function isDate($date, $format = 'd-m-Y')
    {
    }
    /*
     * Change completed order email title to Shipped Order
     */
    public function change_completed_woocommerce_email_title($email_title, $email)
    {
    }
    /*
     * Add action button in order list to change order status from completed to delivered
     */
    public function add_delivered_order_status_actions_button($actions, $order)
    {
    }
    /*
     * Get providers list html
     */
    public function get_provider_html($default_shippment_providers, $status)
    {
    }
    /*
     * filter shipping providers by stats
     */
    public function filter_shipiing_provider_by_status_fun()
    {
    }
    /*
     * Check if valid json
     */
    public function isJSON($string)
    {
    }
    /*
     * Update shipment provider status
     */
    public function update_shipment_status_fun()
    {
    }
    /**
     * Update default provider function 
     */
    public function update_default_provider_fun()
    {
    }
    /**
     * Create slug from title
     */
    public static function create_slug($text)
    {
    }
    /*
     * Delet provide by ajax
     */
    public function woocommerce_shipping_provider_delete()
    {
    }
    /**
     * Get shipping provider details fun 
     */
    public function get_provider_details_fun()
    {
    }
    /**
     * Update custom shipping provider and returen html of it
     */
    public function update_custom_shipment_provider_fun()
    {
    }
    /**
     * Reset default provider
     */
    public function reset_default_provider_fun()
    {
    }
    /**
     * Update bulk status of providers to active
     */
    public function update_provider_status_fun()
    {
    }
    /**
     * Add bulk filter for Shipping provider in orders list
     *
     * @since 2.4
     */
    public function filter_orders_by_shipping_provider()
    {
    }
    /**
     * Process bulk filter action for shipment status orders
     *
     * @since 3.0.0
     * @param array $vars query vars without filtering
     * @return array $vars query vars with (maybe) filtering
     */
    public function filter_orders_by_shipping_provider_query($vars)
    {
    }
    /**
     * Process bulk filter action for shipment status orders
     *
     * @since 2.7.4
     * @param array $vars query vars without filtering
     * @return array $vars query vars with (maybe) filtering
     */
    public function filter_orders_by_tracking_number_query($search_fields)
    {
    }
    /*
     * get tracking provider slug (ts_slug) from database
     * 
     * return provider slug
     */
    public function get_provider_slug_from_name($tracking_provider_name)
    {
    }
    /*
     * function for add more provider btn
     */
    public function add_more_api_provider()
    {
    }
}
class Ast_Customizer
{
    // WooCommerce email classes.
    public static $email_types_class_names = array(
        'completed' => 'WC_Email_Customer_Completed_Order',
        //AST custom status
        'partial_shipped' => 'WC_Email_Customer_Partial_Shipped_Order',
    );
    public static $email_types_order_status = array(
        'completed' => 'completed',
        //AST custom status
        'partial_shipped' => 'partial-shipped',
    );
    /**
     * Get the class instance
     *
     * @since  1.0
     * @return Ast_Customizer
     */
    public static function get_instance()
    {
    }
    /**
     * Initialize the main plugin function
     * 
     * @since  1.0
     */
    public function __construct()
    {
    }
    /*
     * init function
     *
     * @since  1.0
     */
    public function init()
    {
    }
    /*
     * Admin Menu add function
     *
     * @since  2.4
     * WC sub menu 
     */
    public function register_woocommerce_menu()
    {
    }
    /*
     * Add admin javascript
     *
     * @since  2.4
     * WC sub menu 
     */
    public function admin_footer_enqueue_scripts()
    {
    }
    /*
     * callback for settingsPage
     *
     * @since  2.4
     */
    public function settingsPage()
    {
    }
    /*
     * Add admin javascript
     *
     * @since 1.0
     */
    public function customizer_enqueue_scripts()
    {
    }
    /*
     * save settings function
     */
    public function customizer_save_email_settings()
    {
    }
    /**
     * Code for initialize default value for customizer
     */
    public function ast_generate_defaults()
    {
    }
    public function customize_setting_options_func()
    {
    }
    /*
     * Get html of fields
     */
    public function get_html($arrays)
    {
    }
    /**
     * Get the email order status
     *
     * @param string $email_template the template string name.
     */
    public function get_email_order_status($email_template)
    {
    }
    /**
     * Get the email class name
     *
     * @param string $email_template the email template slug.
     */
    public function get_email_class_name($email_template)
    {
    }
    /**
     * Get the email content
     *
     */
    public function get_preview_email($send_email = \false, $email_addresses = \null)
    {
    }
    public function allowed_css_tags($tags)
    {
    }
    public function safe_style_css($styles)
    {
    }
    /**
     * Get WooCommerce order for preview
     *
     * @param string $order_status
     * @return object
     */
    public static function get_wc_order_for_preview($order_status = \null, $order_id = \null)
    {
    }
    /**
     * Get Order Ids
     *
     * @return array
     */
    public static function get_order_ids()
    {
    }
    /**
     * Get preview URL(admin load url)
     *
     */
    public function get_email_preview_url($status)
    {
    }
    /**
     * Get preview URL(front load url)
     *
     */
    public function get_custom_preview_url($status)
    {
    }
}
class WC_AST_Tracker
{
    /**
     * Initialize the main plugin function
     */
    public function __construct()
    {
    }
    /**
     * Get the class instance
     *
     * @return WC_Advanced_Shipment_Tracking_Settings
     */
    public static function get_instance()
    {
    }
    /*
     * init from parent mail class
     */
    public function init()
    {
    }
    public function usage_data_signup_box()
    {
    }
    public function ast_activate_usage_data_fun()
    {
    }
    public function ast_skip_usage_data_fun()
    {
    }
    public function send_tracking_data()
    {
    }
    /**
     * Get all the tracking data.
     *
     * @return array
     */
    public function get_tracking_data()
    {
    }
    /**
     * Get the current theme info, theme name and version.
     *
     * @return array
     */
    public function get_theme_info()
    {
    }
    /**
     * Get WordPress related data.
     *
     * @return array
     */
    public function get_wordpress_info()
    {
    }
    /**
     * Get server related info.
     *
     * @return array
     */
    public function get_server_info()
    {
    }
    /**
     * Get all plugins grouped into activated or not.
     *
     * @return array
     */
    public function get_all_plugins()
    {
    }
    /**
     * Get a list of all active shipping methods.
     *
     * @return array
     */
    public function get_active_shipping_methods()
    {
    }
}
/**
 * REST API shipment tracking controller.
 *
 * Handles requests to /orders/shipment-tracking endpoint.
 *
 * @since 1.5.0
 */
class WC_Advanced_Shipment_Tracking_REST_API_Controller extends \WC_REST_Controller
{
    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wc-ast/v3';
    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'orders/(?P<order_id>[\\d]+)/shipment-trackings';
    /**
     * Post type.
     *
     * @var string
     */
    protected $post_type = 'shop_order';
    /**
     * Set namespace
     *
     * @return WC_Advanced_Shipment_Tracking_REST_API_Controller
     */
    public function set_namespace($namespace)
    {
    }
    /**
     * Register the routes for trackings.
     */
    public function register_routes()
    {
    }
    /**
     * Check whether a given request has permission to read order shipment-trackings.
     *
     * @param  WP_REST_Request $request Full details about the request.
     * @return WP_Error|boolean
     */
    public function get_items_permissions_check($request)
    {
    }
    /**
     * Check if a given request has access create order shipment-tracking.
     *
     * @param  WP_REST_Request $request Full details about the request.
     * @return boolean
     */
    public function create_item_permissions_check($request)
    {
    }
    /**
     * Check if a given request has access to read a order shipment-tracking.
     *
     * @param  WP_REST_Request $request Full details about the request.
     * @return WP_Error|boolean
     */
    public function get_item_permissions_check($request)
    {
    }
    /**
     * Check if a given request has access delete a order shipment-tracking.
     *
     * @param  WP_REST_Request $request Full details about the request.
     * @return boolean
     */
    public function delete_item_permissions_check($request)
    {
    }
    /**
     * Checks if an order ID is a valid order.
     *
     * @param int $order_id
     * @return bool
     * @since 1.6.4
     */
    public function is_valid_order_id($order_id)
    {
    }
    /**
     * Get shipment-trackings from an order.
     *
     * @param WP_REST_Request $request
     * @return array
     */
    public function get_items($request)
    {
    }
    /**
     * Get shipment-tracking providers.
     *
     * @param WP_REST_Request $request
     * @return array
     */
    public function get_providers($request)
    {
    }
    /**
     * Create a single order shipment-tracking.
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function create_item($request)
    {
    }
    /**
     * Get a single order shipment-tracking.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_item($request)
    {
    }
    /**
     * Delete a single order shipment-tracking.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error
     */
    public function delete_item($request)
    {
    }
    /**
     * Prepare a single order shipment-note output for response.
     *
     * @param array           $tracking_item Shipment tracking item
     * @param WP_REST_Request $request       Request object
     *
     * @return WP_REST_Response $response Response data
     */
    public function prepare_item_for_response($tracking_item, $request)
    {
    }
    /**
     * Prepare links for the request.
     *
     * @param int   $order_id          Order ID
     * @param array $shipment_tracking Shipment tracking item
     *
     * @return array Links for the given order shipment-tracking.
     */
    protected function prepare_links($order_id, $tracking_item)
    {
    }
    /**
     * Get the Order Notes schema, conforming to JSON Schema.
     *
     * @return array
     */
    public function get_item_schema()
    {
    }
    /**
     * Get the query params for collections.
     *
     * @return array
     */
    public function get_collection_params()
    {
    }
}
class WC_Advanced_Shipment_Tracking_Admin_Notice
{
    /**
     * Initialize the main plugin function
     */
    public function __construct()
    {
    }
    /**
     * Get the class instance
     *
     * @return WC_Advanced_Shipment_Tracking_Admin_Notice
     */
    public static function get_instance()
    {
    }
    /*
     * init from parent mail class
     */
    public function init()
    {
    }
    public function ast_settings_admin_notice()
    {
    }
    public function ast_settings_admin_notice_ignore()
    {
    }
    public function ast_fulfillment_survay()
    {
    }
    public function ast_fulfillment_survay_ignore()
    {
    }
    /*
     * Display admin notice on plugin install or update
     */
    public function ast_pro_v_3_4_admin_notice()
    {
    }
    /*
     * Dismiss admin notice for trackship
     */
    public function ast_pro_v_3_4_admin_notice_ignore()
    {
    }
    /*
     * Dismiss admin notice for trackship
     */
    public function ast_pro_admin_notice_ignore()
    {
    }
    /*
     * Display admin notice on plugin install or update
     */
    public function ast_db_update_notice()
    {
    }
    /*
     * Dismiss admin notice for trackship
     */
    public function ast_db_update_notice_ignore()
    {
    }
}
/**
 * Returns an instance of Zorem_Woocommerce_Advanced_Shipment_Tracking.
 *
 * @since 1.6.5
 * @version 1.6.5
 *
 * @return Zorem_Woocommerce_Advanced_Shipment_Tracking
*/
function wc_advanced_shipment_tracking()
{
}
/**
 * Returns an instance of zorem_woocommerce_advanced_shipment_tracking.
 *
 * @since 1.6.5
 * @version 1.6.5
 *
 * @return zorem_woocommerce_advanced_shipment_tracking
*/
function WC_AST_Admin_Notices_Under_WC_Admin()
{
}
/**
 * Adds a tracking number to an order.
 *
 * @param int         $order_id        		The order id of the order you want to
 *                                     		attach this tracking number to.
 * @param string      $tracking_number 		The tracking number.
 * @param string      $tracking_provider	The tracking provider name.
 * @param int         $date_shipped    		The timestamp of the shipped date.
 *                                     		This is optional, if not set it will
 *                                     		use current time.
 * @param int 		  $status_shipped		0=no,1=shipped,2=partial shipped(if partial shipped order status is enabled)
 */
function ast_insert_tracking_number($order_id, $tracking_number, $tracking_provider, $date_shipped = \null, $status_shipped = 0)
{
}
/**
 * Adds a tracking number to an order.
 *
 * @param int         $order_id        		The order id of the order you want to
 *                                     		attach this tracking number to.
 * @param string      $tracking_number 		The tracking number.
 * @param string      $tracking_provider	The tracking provider slug.
 * @param int         $date_shipped    		The timestamp of the shipped date.
 *                                     		This is optional, if not set it will
 *                                     		use current time.
 * @param int 		  $status_shipped		0=no,1=shipped,2=partial shipped(if partial shipped order status is enabled)
 */
function ast_add_tracking_number($order_id, $tracking_number, $tracking_provider, $date_shipped = \null, $status_shipped = 0)
{
}
function ast_get_tracking_items($order_id)
{
}
function ast_get_product_id_by_sku($sku = \false)
{
}
/**
 * Html code for tools tab
 */
$wc_ast_api_key = \get_option('wc_ast_api_key');
/**
 * Html code for shipping providers tab
 */
$wc_ast_api_key = \get_option('wc_ast_api_key');
/**
 * Returns an instance of zorem_woocommerce_advanced_shipment_tracking.
 *
 * @since 1.6.5
 * @version 1.6.5
 *
 * @return zorem_woocommerce_advanced_shipment_tracking
*/
function wc_advanced_shipment_tracking_email_class()
{
}