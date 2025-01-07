<?php

class BM
{
    public static $version = \false;
    /**
     * BM constructor.
     */
    public function __construct()
    {
    }
    /**
     * initialize plugin
     */
    public function init()
    {
    }
    /**
     * marketpress plugin improve
     */
    public function plugin_improver()
    {
    }
    /**
     * marketpress auto updater
     */
    public function check_auto_update()
    {
    }
    /**
     * Add admin notice if woocommerce is not activated
     *
     * @wp-hook admin_notices
     * @return void
     */
    public function get_activate_woocommerce_notice()
    {
    }
    /**
     * PHP version Notice.
     *
     * @wp-hook admin_notices
     *
     * @access public
     * @static
     *
     * @return void
     */
    public static function php_version_notice()
    {
    }
    /**
     * Add admin notice if rbp is activated
     * @return void
     */
    public function get_activate_rbp_notice()
    {
    }
    /**
     * add options for activation
     * @return void
     */
    public function set_activate_option($networkwide)
    {
    }
    /**
     * Recalculate unit prices on products if Germanized plugin is active.
     * @param  bool $networkwide
     * @return void
     */
    public function recalculate_germanized_unit_prices($networkwide)
    {
    }
}
abstract class MarketPress_Improve_Plugin
{
    protected $wp_version = \null;
    protected $php_version = \null;
    protected $mysql_version = \null;
    protected $wc_version = \null;
    protected $theme = \null;
    protected $plugin_version = \null;
    protected $plugin_slug = \null;
    protected $plugin_data = array();
    /**
     * Construct
     *
     * @since 	1.0
     * @final
     * @return 	void
     */
    public final function __construct()
    {
    }
    /**
     * Handle users reaction
     *
     * @since 	1.0
     * @final
     * @wp-hook admin-init
     * @return 	void
     */
    public final function user_reaction()
    {
    }
    /**
     * Display Admin Notice
     *
     * @since 	1.0
     * @final
     * @wp-hook admin_notices
     * @return 	void
     */
    public final function admin_notice()
    {
    }
    /**
     * Get Admin Notice Success Text
     *
     * @since 	1.0
     * @return 	String
     */
    protected function get_notice_success_text()
    {
    }
    /**
     * Get Admin Notice Text
     *
     * @since 	1.0
     * @return 	String
     */
    protected function get_notice_text()
    {
    }
    /**
     * Get "Agree Button" text
     *
     * @since 	1.0
     * @return 	String
     */
    protected function get_agree_button_text()
    {
    }
    /**
     * Get "Disagree Button" text
     *
     * @since 	1.0
     * @return 	String
     */
    protected function get_disagree_button_text()
    {
    }
    /**
     * Get Plugin Name
     *
     * @since 	1.0
     * @abstract
     * @return 	String
     */
    protected abstract function get_plugin_name();
    /**
     * Get Plugin Slug
     *
     * @since 	1.0
     * @abstract
     * @return 	String
     */
    protected abstract function get_plugin_slug();
    /**
     * Get Plugin Version
     *
     * @since 	1.0
     * @abstract
     * @return 	String
     */
    protected abstract function get_plugin_version();
    /**
     * Get Plugin Data
     *
     * @since 	1.0
     * @abstract
     * @return 	Array
     */
    protected abstract function get_plugin_data();
}
class MarketPress_Improve_B2B_Market extends \MarketPress_Improve_Plugin
{
    /**
     * Get Plugin Name
     *
     * @since 	1.0
     * @return 	String
     */
    protected final function get_plugin_name()
    {
    }
    /**
     * Get Plugin Slug
     *
     * @since 	1.0
     * @return 	String
     */
    protected final function get_plugin_slug()
    {
    }
    /**
     * Get Plugin Version
     *
     * @since 	1.0
     * @return 	String
     */
    protected final function get_plugin_version()
    {
    }
    /**
     * Get Plugin Data
     *
     * @since 	1.0
     * @return 	Array
     */
    protected final function get_plugin_data()
    {
    }
}
/**
 * Class MarketPress_Auto_Update
 */
class MarketPress_Auto_Update_B2B
{
    /**
     * The URL for the update check
     *
     * @since	0.1
     * @var		string
     */
    public $url_update_check = '';
    /**
     * The URL for the update package
     *
     * @since	0.1
     * @var		string
     */
    public $url_update_package = '';
    /**
     * The holder for all our licenses
     *
     * @since	0.1
     * @var		array
     */
    public $licenses = '';
    /**
     * The license key
     *
     * @since	0.1
     * @var		array
     */
    public $key = '';
    /**
     * The URL for the key check
     *
     * @since	0.1
     * @var		string
     */
    public $url_key_check = '';
    /**
     * Activation Error
     *
     * @var string
     */
    public $activation_error;
    public $page;
    public $menu_hook;
    public static $cache;
    /**
     * @return Woocommerce_German_Market_Auto_Update
     */
    public static function get_instance()
    {
    }
    /**
     * Load textdomain
     *
     * @var string
     */
    public static function load_textdomain()
    {
    }
    /**
     * Setting up some data, all vars and start the hooks
     * needs from main plugin: plugin_name, plugin_base_name, plugin_url
     *
     * @param   stdClass $plugindata
     *
     * @return  void
     */
    public function setup($plugindata)
    {
    }
    /**
     * Update Error Message When License Expired
     *
     * @wp-hook upgrader_pre_download
     * @param Mixed $reply
     * @param Mixed $package
     * @return Mixed
     */
    public function update_error_message_license_expired($reply, $package, $wp_upgrader, $hook_extra)
    {
    }
    /**
     * Get "WC tested up to" by API
     *
     * @wp-hook woocommerce_get_plugins_with_header
     * @wp-hook woocommerce_get_plugins_for_woocommerce
     * @param Array $matches
     * @param String $header
     * @param Array $plugins
     * @return Array
     */
    function woocommerce_tested_up_to($matches)
    {
    }
    /**
     * Save WC Tested Up To Data from API
     *
     * @param String $response
     * @return void
     */
    function update_wc_tested_up_to($response)
    {
    }
    /**
     * add admin notices for license errors and warnings
     *
     * @wp-hook admin_notices
     * @return void
     */
    public function admin_notice()
    {
    }
    /**
     * license menu in plugin
     *
     * @wp-hook woocommerce_de_ui_left_menu_items
     * @param Array $menu
     * @return menu
     */
    public function add_menu($menu)
    {
    }
    /**
     * license menu in plugin - callback function
     *
     * @return void
     */
    public function license_menu()
    {
    }
    /**
     * get license error messages
     *
     * @param String $status
     * @param Array $args
     * @return String
     */
    public function get_error_message_for_license($status, $args = array())
    {
    }
    /**
     * add css for autoupdate
     *
     * @uses	wp_enqueue_style, plugin_dir_url, untrailingslashit
     */
    public function print_styles_and_scripts()
    {
    }
    /**
     * Setting up the key
     *
     * @since	0.1
     * @uses	get_site_option
     * @return	void
     */
    public function get_key()
    {
    }
    /**
     * Checks over the transient-update-check for plugins if new version of
     * this plugin os available and is it, shows a update-message into
     * the backend and register the update package in the transient object
     *
     * @since	0.1
     * @param	object $transient
     * @uses	wp_remote_get, wp_remote_retrieve_body, get_site_option,
     * 			get_site_transient, set_site_transient
     * @return	object $transient
     */
    public function check_plugin_version($transient)
    {
    }
    /**
     * Disables the checkup
     *
     * @since	0.1
     * @param	object $transient
     * @return	object $transient
     */
    public function dont_check_plugin_version($transient)
    {
    }
    /**
     * Things to do when license check does not return status true
     */
    public function license_key_checkup_before_return_false($status = '')
    {
    }
    /**
     * Reset Plugin Transients
     */
    public function reset_plugin_transient()
    {
    }
    /**
     * Set Access Expires Date
     *
     * @param String $access_expires_date
     * @return void
     */
    public function set_access_expires_date($access_expires_date)
    {
    }
    /**
     * Set Current Product Version from API
     *
     * @param String $version
     * @return void
     */
    public function set_current_product_version($version)
    {
    }
    /**
     * Check the license-key and cache the response
     *
     * @param 	String $key
     * @return	String
     */
    public function get_license_key_checkup($key = '')
    {
    }
    /**
     * Check the license-key and caches the returned value
     * in an option
     *
     * @since	0.1
     * @uses	wp_remote_retrieve_body, wp_remote_get, update_site_option, is_wp_error,
     * 			delete_site_option
     * @return	boolean
     */
    public function license_key_checkup($key = '')
    {
    }
    /**
     * Checks the cached state of the license checkup
     *
     * @since	0.1
     * @uses	get_site_option
     * @return	boolean
     */
    public function license_check()
    {
    }
    /**
     * Removes the plugins key from the licenses
     *
     * @since	0.1
     * @uses	update_site_option, wp_safe_redirect, admin_url
     * @return	void
     */
    public function remove_license_key()
    {
    }
    /**
     * Display the upgrade notice in the plugin listing
     */
    public function license_update_notice($plugin_data, $response)
    {
    }
    /**
     * Show Update Details
     *
     * @wp-hook plugins_api
     * @param StdObject $data
     * @param String $action
     * @param Array $args
     * @return StdObject
     */
    public function provide_plugin_info($data, $action = \null, $args = \null)
    {
    }
}
/**
 * Class which handles all price calculations
 */
class BM_Show_Discounts
{
    /**
     * Returns instance of BM_Price.
     *
     * @return object
     */
    public static function get_instance()
    {
    }
    /**
     * BM_Show_Discounts constructor.
     */
    public function __construct()
    {
    }
    /**
     * Show the discount on single item price.
     *
     * @param string $price the current price.
     * @param object $item current cart object.
     * @param string $cart_item_key current cart item key.
     * @return string
     */
    public function show_discount_on_item_price($price, $item, $cart_item_key)
    {
    }
    /**
     * Show discount on subtotal.
     *
     * @param string $subtotal subtotal as string.
     * @param array  $item cart item array with data.
     * @param string $cart_item_key unique hash string for item.
     * @return string
     */
    public function show_discount_on_subtotal($subtotal, $item, $cart_item_key)
    {
    }
    /**
     * Handles the RRP output.
     *
     * @Hook woocommerce_get_price_html
     *
     * @param string     $price current price string.
     * @param WC_Product $product current product object.
     *
     * @return string
     */
    public function show_rrp_and_price($price, $product)
    {
    }
    /**
     * Handles the RRP output.
     *
     * @Hook woocommerce_single_product_summary
     *
     * @return void
     */
    public function show_rrp()
    {
    }
    /**
     * Handles the RRP output.
     *
     * @param string $price current price string.
     * @param object $product current product object.
     * @param bool   $combine show rrp together with price html
     *
     * @return string
     */
    public function show_rrp_html($price, $product, $combine = \true)
    {
    }
    /**
     * Show if we have same prices on a variation.
     *
     * @param string     $price_html
     * @param WC_Product $product
     *
     * @return string
     */
    public function maybe_manipulate_variable_price_html($price_html, $product)
    {
    }
    /**
     * Handles the bluk discount message output.
     *
     * @param string $price_html current price string.
     * @param object $product current product object.
     * @return string
     */
    public function show_bulk_discount($price_html, $product)
    {
    }
    /**
     * Handles bulk output message after title.
     *
     * @return string
     */
    public function show_bulk_discount_after_title()
    {
    }
    /**
     * Show discount table for bulk prices.
     *
     * @return void
     */
    public function show_discount_table()
    {
    }
    /**
     * Show discount table per variation.
     *
     * @param  array $variations given variations.
     * @return array
     */
    public function show_discount_table_variation($variations)
    {
    }
    /**
     * Show totals on product page.
     *
     * @return void
     */
    public function show_discount_totals()
    {
    }
    /**
     * Overwrites WooCommerce variation.php template for bulk price support.
     *
     * @param string $template given template.
     * @param string $template_name template name.
     * @param string $template_path given path.
     * @return string
     */
    public function load_variation_template($template, $template_name, $template_path)
    {
    }
}
class BM_User
{
    /**
     * Returns instance of BM_Price.
     *
     * @return object
     */
    public static function get_instance()
    {
    }
    /**
     * BM_User constructor.
     */
    public function __construct()
    {
    }
    /**
     * hooks
     */
    public function init()
    {
    }
    /**
     * @param $post_id
     */
    public function add_customer_group($post_id)
    {
    }
    /**
     * @param $post_id
     */
    public function delete_customer_group($post_id)
    {
    }
    /**
     * @return array
     */
    public function get_all_customer_groups()
    {
    }
    /**
     * @return array
     */
    public static function get_all_customer_group_ids()
    {
    }
}
class CSP
{
    /**
     * initialize hooks and requirements
     */
    public static function init()
    {
    }
}
class CSP_ShippingManager
{
    /**
     * Singleton.
     *
     * @static
     * @var class
     */
    static $instance = \null;
    /**
     * Singleton getInstance.
     *
     * @access public
     * @static
     *
     * @return class CSP_ShippingManager
     */
    public static function get_instance()
    {
    }
    /**
     * CSP_ShippingManager constructor.
     */
    public function __construct()
    {
    }
    /**
     * @param $rates
     * @param $package
     *
     * @return mixed
     */
    public function disable_shipping_method_for_group($rates, $package)
    {
    }
    public static function update_shipping_options_for_group()
    {
    }
    /**
     * Hide other shipping rates if free shipping is available.
     *
     * @param  array $rates given rates.
     * @return array
     */
    public function hide_shipping_if_free_available($rates)
    {
    }
}
class CSP_PaymentManager
{
    /**
     * @var array
     */
    public $group;
    /**
     * CSP_PaymentManager constructor.
     */
    public function __construct()
    {
    }
    /**
     * @param $available_gateways
     *
     * @return mixed
     */
    public function disable_payment_option_for_group($available_gateways)
    {
    }
    public static function update_payment_options_for_group()
    {
    }
}
class CSP_Options
{
    /**
     * @param $items
     *
     * @return mixed
     */
    public function add_menu_item($items)
    {
    }
    /**
     * @return array|mixed|void
     */
    public function payment_tab()
    {
    }
    /**
     * @return array|mixed|void
     */
    public function shipping_method_tab()
    {
    }
}
class RGN
{
    /**
     * initialize addon
     */
    public static function init()
    {
    }
    /**
     * script enqueues
     */
    public static function registration_scripts()
    {
    }
}
/**
 * Feature Name: The Validator
 * Version:      1.0
 * Author:       MarketPress
 * Author URI:   http://marketpress.com
 */
/**
 * This class calls the VAT validator at http://ec.europa.eu/taxation_customs/vies/vatRequest.html
 * and checks the given input against it. If the VAT Validator is not available there is
 * a RegEx Fallback.
 *
 * Example:
 *
 * 	if ( isset( $_POST[ 'country_code' ] ) && isset( $_POST[ 'uid' ] ) ) {
 * 		$validator = new WC_VAT_Validator( array ( $_POST[ 'country_code' ], $_POST[ 'uid' ] ) );
 * 		if ( $validator->is_valid() )
 * 			echo 'Valid VAT';
 * 		else
 * 			echo 'invalid VAT';
 * }
 *
 * $validator = new WC_VAT_Validator( array( 'DE', '263849534' ) );
 * $validator->is_valid();
 */
class RGN_VAT_Validator
{
    public function __construct($input, $billing_country = '')
    {
    }
    /**
     *
     * @param mixed $input
     *
     * @return boolean
     */
    public function is_valid()
    {
    }
    /**
     * Sets a new input array, throwing errors along the way if anything's sketchy
     * TRUE if nothing's sketchy, otherwise FALSE
     *
     * @param mixed $input
     *
     * @return boolean
     */
    public function set_input($input)
    {
    }
    /**
     * Check if there are elements in the errors array
     *
     * @return boolean
     */
    public function has_errors()
    {
    }
    /**
     * Returns an array of all error messages occured during the last validation attempt
     *
     * @return string
     */
    public function get_error_messages()
    {
    }
    /**
     * Returns an array of all error codes occured during the last validation attempt
     *
     * @return string
     */
    public function get_error_codes()
    {
    }
    /**
     * Returns the description of the current error code
     *
     * @return string
     */
    public function get_last_error_message()
    {
    }
    /**
     * Returns the current error code
     *
     * @return type
     */
    public function get_last_error_code()
    {
    }
}
/**
 * Double Opt-in Customer Registration Email
 *
 * @class 		WGM_Email_Double_Opt_In_Customer_Registration
 * @version		2.1.0
 * @package		WooCommerce/Classes/Emails
 * @author 		MarketPress
 * @extends 	WC_Email
 */
class RGN_Double_Opt_In_Email extends \WC_Email
{
    /**
     * Constructor
     */
    function __construct()
    {
    }
    /**
     * get_type function.
     *
     * @return string
     */
    public function get_email_type()
    {
    }
    /**
     * Trigger function.
     *
     * @access public
     * @return void
     */
    function trigger($customer_id, $activation_link, $user_email, $user_login, $user_pass = '', $resend = \false)
    {
    }
    /**
     * get_content_html function.
     *
     * @access public
     * @return string
     */
    function get_content_html()
    {
    }
    /**
     * get_content_plain function.
     *
     * @access public
     * @return string
     */
    function get_content_plain()
    {
    }
}
/**
 * Class bm_Double_Opt_In_Customer_Registration
 *
 * Add Double Opt In Customer Registration
 *
 * @author  MarketPress
 */
class RGN_Double_Opt_In_Registration
{
    /**
     * @var bool
     */
    public static bool $send_pw_link = \false;
    /**
     * Init Hooks and filters
     *
     * @static
     * @return void
     */
    public static function init()
    {
    }
    /**
     * Start Scheduler in Backend
     *
     * @since 3.9.2
     * @static
     * @wp-hook admin_init
     * @return void
     */
    public static function start_scheduler()
    {
    }
    /**
     * Auto Delete users that have not activate their account
     *
     * @since 3.9.2
     * @static
     * @wp-hook b2b_market_double_opt_in_auto_delete
     * @return void
     */
    public static function b2b_market_double_opt_in_auto_delete()
    {
    }
    /**
     * Auto Delete: Return Extra Text
     * Replace placeholder [days]
     *
     * @since 3.9.2
     * @static
     * @return String
     */
    public static function get_autodelete_extra_text()
    {
    }
    /**
     * Management: Make Notice in Backend for Resending Mails
     *
     * @since 3.9.2
     * @static
     * @wp-hook admin_notices-users
     * @return void
     */
    public static function bulk_admin_notice()
    {
    }
    /**
     * Management: Bulk Action: Resend Emails
     *
     * @since 3.9.2
     * @static
     * @wp-hook handle_bulk_actions-users
     * @param String redirect_to
     * @param String $doaction
     * @param Array $post_ids
     * @return String
     */
    public static function bulk_resend_email($redirect_to, $doaction, $post_ids)
    {
    }
    /**
     * Management: Bulk Action: Manual Activation
     *
     * @since 3.10.2
     * @static
     * @wp-hook handle_bulk_actions-users
     * @param String redirect_to
     * @param String $doaction
     * @param Array $post_ids
     * @return String
     */
    public static function bulk_manual_activation($redirect_to, $doaction, $post_ids)
    {
    }
    /**
     * Management: Add Bulk Action
     *
     * @since 3.9.2
     * @static
     * @wp-hook bulk_actions-users
     * @param Array $actions
     * @return Array
     */
    public static function bulk_resend_email_select($actions)
    {
    }
    /**
     * Management: Filter User in Backend
     *
     * @since 3.9.2
     * @static
     * @wp-hook pre_get_users
     * @param WP_Query $query
     * @return void
     */
    public static function filter_user($query)
    {
    }
    /**
     * Management: Show Select Box for Filtering and Submit Button in User Backend
     *
     * @since 3.9.2
     * @static
     * @wp-hook restrict_manage_users
     * @param String $top_or_bottom
     * @return void
     */
    public static function filter_user_table($top_or_bottom)
    {
    }
    /**
     * Management: Render User Column in Backend
     *
     * @since 3.9.2
     * @static
     * @wp-hook manage_users_custom_column
     * @param Array $columns
     * @param String $val
     * @param String $column_name
     * @param Integer $user_id
     * @return String
     */
    public static function render_user_tabe_column($val, $column_name, $user_id)
    {
    }
    /**
     * Management: Add User Column in Backend
     *
     * @since 3.9.2
     * @static
     * @wp-hook manage_users_columns
     * @param Array $columns
     * @return Array
     */
    public static function user_tabe_column($columns)
    {
    }
    /**
     * User is logged in after registration on my account page => logout user if activation status is not activated
     *
     * @since 3.5.1
     * @static
     * @wp-hook wp
     * @return void
     */
    public static function logout_user_my_account_page()
    {
    }
    /**
     * User is logged in after registration => logout user if activation status is not activated
     *
     * @static
     * @wp-hook woocommerce_thankyou
     * @return void
     */
    public static function logout_user()
    {
    }
    /**
     * Deactivate WooCommerce 'created customer notification' email
     *
     * @static
     * @hook woocommerce_email
     * @return void
     */
    public static function deactive_woocommerce_created_customer_notification($object)
    {
    }
    /**
     * Activate bm 'created customer notification' email
     *
     * @static
     * @hook woocommerce_created_customer_notification
     * @return void
     */
    public static function woocommerce_created_customer_notification($customer_id, $new_customer_data = array(), $password_generated = \false, $resend = \false)
    {
    }
    /**
     * What happens if an user wants to activate the new user account
     *
     * @static
     * @hook woocommerce_created_customer_notification
     * @return void
     */
    public static function check_activation_action()
    {
    }
    /**
     * No user login for users without activation
     *
     * @static
     * @hook wp_authenticate_user
     * @param WP_User $user
     * @param String $password
     * @return WP_User (or throws an error)
     */
    public static function wp_authenticate_user($user, $password)
    {
    }
}
class RGN_UserMeta
{
    /**
     * RGN_UserMeta constructor.
     */
    public function __construct()
    {
    }
    /**
     * @param $user
     */
    public function add_customer_profile_fields($user)
    {
    }
    /**
     * @param $errors
     * @param $update
     * @param $user
     */
    public function validate_customer_profile_fields($errors, $update, $user)
    {
    }
    /**
     * @param $user_id
     *
     * @return bool
     */
    public function update_customer_profile_fields($user_id)
    {
    }
}
/**
 * Class to handle adress filtering in WooCommerce.
 */
class RGN_Address
{
    /**
     * Returns instance of MPCN_Address.
     *
     * @return object
     */
    public static function get_instance()
    {
    }
    /**
     * Constructor for MPCN_Address.
     */
    public function __construct()
    {
    }
    /**
     * Adding custom placeholder to WoocCmmerce formatted billing address
     *
     * @param  array $address_formats array of address formats.
     * @return array
     */
    public function admin_localisation_fields($address_formats)
    {
    }
    /**
     * Cnr placeholder replacement to WooCommerce formatted billing address
     *
     * @param array $replacements array of strings for replacement.
     * @param array $args array of additional arguments.
     * @return array
     */
    public function set_formatted_address_replacement($replacements, $args)
    {
    }
    /**
     * Get the cnr meta value to be displayed in admin Order edit pages
     *
     * @param array  $address array of adress fields.
     * @param object $order current order object.
     * @return array
     */
    public function add_billing_order_fields($address, $order)
    {
    }
}
class RGN_Registration
{
    /**
     * Constructor for RGN_Registration
     */
    public function __construct()
    {
    }
    /**
     * Output the registration fields markup
     *
     * @return void
     */
    public function ouptput_registration_fields()
    {
    }
    /**
     * Return the registration fields
     *
     * @return array
     */
    public function get_registration_fields()
    {
    }
    /**
     * Add registration fields to checkout
     *
     * @param array $checkout_fields array of current registration fields.
     * @return array
     */
    public function add_checkout_fields($checkout_fields)
    {
    }
    /**
     * Registration fields validation
     *
     * @param object $validation_errors current validation errors.
     * @return object
     */
    public function validate_registration_fields($validation_errors)
    {
    }
    /**
     * Save registration fields to account
     *
     * @param int $customer_id current customer id.
     * @return void
     */
    public function save_registration_fields($customer_id)
    {
    }
    /**
     * For German Market Add-On "EU VAT Number Check"
     * Prefill checkout b2b vat id if current default value is empty
     *
     * @param  String $vat_id
     * @return String
     */
    public function add_vat_to_checkout($vat_id)
    {
    }
}
class RGN_Helper
{
    /**
     * @return array
     */
    public static function get_selectable_groups()
    {
    }
    /**
     * @return array
     */
    public static function get_net_tax_groups()
    {
    }
}
class RGN_Options
{
    /**
     * IE_Options constructor.
     */
    public function __construct()
    {
    }
    /**
     * @param $items
     *
     * @return mixed
     */
    public function add_menu_item($items)
    {
    }
    /**
     * @return array|mixed|void
     */
    public function registration_tab()
    {
    }
    /**
     * @return array|mixed|void
     */
    public function general_tab()
    {
    }
}
class IE_Migrator
{
    /**
     * IE_Migrator constructor.
     */
    public function __construct()
    {
    }
    /**
     * initialize migrate process
     */
    public function migrate()
    {
    }
    /**
     * get all rbp defined groups
     *
     * @return mixed|string|void
     */
    public function get_groups()
    {
    }
    /**
     * create customer group and user role
     *
     * @param $group
     * @param $group_name
     */
    public function migrate_group($group_slug, $group_name)
    {
    }
    /**
     * get rules for given product
     *
     * @param $product_id
     *
     * @return array
     */
    public function get_product_rules($product_id)
    {
    }
    /**
     * get rules for given product
     *
     * @param $product_id
     *
     * @return array
     */
    public function get_variation_rules($variation_id)
    {
    }
    /**
     * migrate product rules
     */
    public function migrate_products($group_id, $group_slug)
    {
    }
    /**
     * migrate variation rules
     */
    public function migrate_variations($group_id, $group_slug)
    {
    }
    /**
     * migrate global price options
     */
    public function migrate_global_prices()
    {
    }
    /**
     * get visibility status from group slug
     *
     * @param $group
     *
     * @return bool
     */
    public function get_group_visibility_status($group)
    {
    }
    /**
     * @param $product_id
     *
     * @return array
     */
    public function get_product_whitelist_data($product_id)
    {
    }
    /**
     * do migration for whitelist
     */
    public function migrate_whitelist($group_id, $group_slug)
    {
    }
    /**
     * migrate users
     *
     * @param $group
     */
    public function migrate_user_with_group_meta($group_slug)
    {
    }
    /**
     * Migrate 1.0.1 bulk prices to 1.0.2
     *
     * @return void
     */
    public static function migrate_101_bulk_prices()
    {
    }
}
class IE_Importer
{
    /**
     * IE_Importer constructor.
     */
    public function __construct()
    {
    }
    /**
     * Runs the importer
     *
     * @return void
     */
    public function import()
    {
    }
}
class IE_Exporter
{
    /**
     * IE_Exporter constructor.
     */
    public function __construct()
    {
    }
    /**
     * @return array
     */
    public function get_export_options()
    {
    }
    /**
     * @param $export_groups
     *
     * @return array
     */
    public function get_export_data($export_groups)
    {
    }
    /**
     * Triggered from ajax call.
     */
    public function trigger_export()
    {
    }
}
/**
 * Import/Export class
 */
class IE
{
    /**
     * Initialize migration addon
     *
     * @return void
     */
    public static function init()
    {
    }
    /**
     * Enqueue scripts
     *
     * @return void
     */
    public static function exporter_scripts()
    {
    }
}
class IE_Options
{
    /**
     * IE_Options constructor.
     */
    public function __construct()
    {
    }
    /**
     * @param $items
     *
     * @return mixed
     */
    public function add_menu_item($items)
    {
    }
    /**
     * @return array|mixed|void
     */
    public function export_tab()
    {
    }
    /**
     * @return array|mixed|void
     */
    public function import_tab()
    {
    }
    /**
     * @return array|mixed|void
     */
    public function migrator_tab()
    {
    }
}
/**
 * Class to handle min and max quantities
 */
class BM_Quantities
{
    /**
     * Return an instance of BM_Quantities class
     *
     * @return object
     */
    public static function get_instance()
    {
    }
    /**
     * Filter input args on product page based on meta
     *
     * @param array $args
     * @param object $product
     * @return void
     */
    public function bm_product_quantity($args, $product)
    {
    }
    /**
     * Set min qty in shop loop.
     *
     * @param  array  $args list of arguments.
     * @param  object $product current product object.
     * @return array
     */
    public function set_default_loop_qty($args, $product)
    {
    }
    /**
     * Filter input args on product page for variation based on meta
     *
     * @param array $args
     * @return void
     */
    public function bm_variation_quantity($args)
    {
    }
    /**
     * Validate cart quantity
     *
     * @param array $passed
     * @param int $product_id
     * @param int $quantity
     * @param string $variation_id
     * @param string $variations
     * @return void
     */
    public function bm_add_to_cart_quantity($passed, $product_id, $quantity, $variation_id = '', $variations = '')
    {
    }
    /**
     * Is allready in cart?
     *
     * @param int $product_id
     * @return void
     */
    public function get_quantity_in_cart($product_id)
    {
    }
    /**
     * Is allready in cart and try update?
     *
     * @param int $product_id
     * @return void
     */
    public function get_quantity_in_cart_update($product_id, $cart_item_key = '')
    {
    }
    /**
     * Matched quantity in cart?
     *
     * @param bool  $passed
     * @param array  $cart_item_key
     * @param array  $values
     * @param int  $quantity
     * @return void
     */
    public function bm_cart_update_quantity($passed, $cart_item_key, $values, $quantity)
    {
    }
    /**
     * Set default quantity if available.
     *
     * @param  int $quantity given qty.
     * @return int
     */
    public function set_default_qty($quantity)
    {
    }
    /**
     * Check cart quantity before calculate totals to make sure customer group has not changed due to login.
     *
     * @param object $cart current cart object.
     * @return void
     */
    public function check_cart_qty($cart)
    {
    }
}
/**
 * Class that handles product meta data.
 */
class BM_Product_Meta
{
    /**
     * Constructor for BM_Product_Meta
     */
    public function __construct()
    {
    }
    /**
     * Add tab in product edit screen
     *
     * @param array $product_data_tabs current tabs.
     * @return array
     */
    public function add_product_admin_tab($product_data_tabs)
    {
    }
    /**
     * Add fields to admin tab;
     *
     * @return void
     */
    public function add_product_admin_tab_fields()
    {
    }
    /**
     * Add rrp meta field.
     *
     * @param  int $product_id given post id.
     * @return void
     */
    protected function get_rrp_meta($product_id)
    {
    }
    /**
     * Get group price meta fields.
     *
     * @param  int $product_id given post id.
     * @return void
     */
    protected function get_group_price_meta($product_id)
    {
    }
    /**
     * Add bulk price meta fields.
     *
     * @param  int $product_id given post id.
     * @return void
     */
    protected function get_bulk_price_meta($product_id)
    {
    }
    /**
     * Add quantity meta fields
     *
     * @param  int $product_id given post id.
     * @return void
     */
    protected function get_quantities_meta($product_id)
    {
    }
    /**
     * Save meta fields
     *
     * @param int    $product_id current post id.
     * @param object $post current post object.
     * @return void
     */
    public function save_meta($post_id)
    {
    }
}
/**
 * Class which handles the frontend pricing display
 */
class BM_Public
{
    /**
     * BM_Public constructor.
     */
    public function __construct()
    {
    }
    /**
     * Render global discount message box
     */
    public function global_discount_message()
    {
    }
    /**
     * Handler for enqueue frontend scripts
     */
    public function add_frontend_assets()
    {
    }
}
/**
 * Class which handles the tax status
 */
class BM_Tax
{
    /**
     * Shop tax setting
     *
     * @var string
     */
    public $shop_tax;
    /**
     * Cart tax setting
     *
     * @var string
     */
    public $cart_tax;
    /**
     * Returns instance of BM_Price.
     *
     * @return object
     */
    public static function get_instance()
    {
    }
    /**
     * Constructor
     */
    public function __construct()
    {
    }
    /**
     * Filter the tax status based on user group settings
     *
     * @param string $value given tax value.
     * @return string
     */
    public function filter_tax_display($value)
    {
    }
    /**
     * Filter the tax status based on user group settings
     *
     * @param string $value
     * @return void
     */
    public function filter_wcevc_general_tax_display($value)
    {
    }
    /**
     * Add a hash to the current user session to apply tax settings for variations
     *
     * @param string $hash
     * @return void
     */
    public function tax_display_add_hash_user_id($hash)
    {
    }
    /**
     * Get price with correct tax display.
     *
     * @param  WC_Product $product current product object.
     * @param  float      $price current price without formatting.
     *
     * @return float
     */
    public static function get_tax_price($product, $price)
    {
    }
}
class BM_Update_Price
{
    /**
     * Returns instance of BM_Price.
     *
     * @return object
     */
    public static function get_instance()
    {
    }
    /**
     * Maybe enqueue assets
     *
     * @return void
     */
    public static function load_assets()
    {
    }
    /**
     * Add hidden id for js live price
     *
     * @return void
     */
    public static function add_hidden_id_field()
    {
    }
    /**
     * Live update price with ajax
     *
     * @return void
     */
    public static function update_price()
    {
    }
}
class BM_ListTable extends \WP_List_Table
{
    /**
     * BM_ListTable constructor.
     */
    public function __construct()
    {
    }
    /**
     * add edit and delete links under group title
     *
     * @param $item
     *
     * @return string
     */
    public function column_customer_group($item)
    {
    }
    /**
     * add column for group price
     *
     * @param $item
     *
     * @return string
     */
    public function column_group_slug($item)
    {
    }
    /**
     * add column for bulk prices
     *
     * @param $item
     *
     * @return string
     */
    public function column_pricing_used($item)
    {
    }
    /**
     * add column for products
     *
     * @param $item
     *
     * @return mixed|string
     */
    public function column_tax_display($item)
    {
    }
    /**
     * get all columns
     *
     * @return array
     */
    public function get_columns()
    {
    }
    /**
     * get sortable columns
     *
     * @return array
     */
    public function get_sortable_columns()
    {
    }
    /**
     * get bulk actions
     *
     * @return array
     */
    public function get_bulk_actions()
    {
    }
    /**
     * process bulk actions
     */
    public function process_bulk_action()
    {
    }
    /**
     *  prepare loop for output
     */
    public function prepare_items()
    {
    }
    /**
     * modify usort for reordering groups
     *
     * @param $a
     * @param $b
     *
     * @return int
     */
    public function usort_reorder($a, $b)
    {
    }
}
class BM_Options
{
    /**
     * Singletone get_instance
     *
     * @static
     * @return BM_Options
     */
    public static function get_instance()
    {
    }
    /**
     * Add submenu
     *
     * @wp-hook admin_menu
     * @access public
     */
    public function add_bm_submenu()
    {
    }
    /**
     * Force regenerating price hashes when 'all customers' group saved.
     *
     * @acces public
     * @static
     *
     * @return void
     */
    public static function save_bm_options()
    {
    }
    /**
     * Output type bm_repeater_fields
     *
     * @access public
     * @return void
     */
    public function bm_repeatable($value)
    {
    }
    /**
     * Output type bm_repeater_fields
     *
     * @access public
     * @return void
     */
    public function bm_group_repeatable($value)
    {
    }
    /**
     * Output type wgm_ui_checkbox
     *
     * @access public
     * @hook woocommerce_admin_field_wgm_ui_checkbox
     * @return void
     */
    public function bm_ui_checkbox($value)
    {
    }
    /**
     * Output a code textarea
     *
     * @param string $value given value.
     * @return void
     */
    public static function output_code($value)
    {
    }
    /**
     * Enqueue build in code mirror js for code fields.
     *
     * @param string $hook current hook.
     * @return void
     */
    public function codemirror_enqueue_scripts($hook)
    {
    }
    /**
     * Save type wgm_ui_checkbox
     *
     * @access public
     * @hook woocommerce_admin_settings_sanitize_option
     *
     * @param Mixed $value
     * @param Array $option
     * @param Mixed $raw_value
     *
     * @return $value
     */
    public function woocommerce_admin_settings_sanitize_option($value, $option, $raw_value)
    {
    }
    /**
     * Add Submenu to WooCommerce Menu
     *
     * @add_submenu_page
     * @access public
     */
    public function render_bm_menu()
    {
    }
    /**
     * Render Options for global
     *
     * @return void
     */
    public function groups_tab()
    {
    }
    /**
     * Render Options for global
     *
     * @access public
     * @return array
     */
    public function global_tab()
    {
    }
    /**
     * Render Options for misc
     *
     * @access public
     * @return array
     */
    public function misc_tab()
    {
    }
    /**
     * Render options for 'WooCommerce German Market'
     *
     * @return array
     */
    public function german_market_tab()
    {
    }
    /**
     * Render options for Administration
     *
     * @return array
     */
    public function admin_tab()
    {
    }
    /**
     * Render options for Price Display
     *
     * @return array
     */
    public function price_display_tab()
    {
    }
    /**
     * Render Add-On Tab
     *
     * @access public
     * @return void
     */
    public static function render_add_ons()
    {
    }
    /**
     * Get Video Div
     *
     * @access privat
     * @static
     *
     * @param String $text
     * @param String $url
     *
     * @return String
     */
    public static function get_video_layer($url)
    {
    }
}
class BM_Edit_Group
{
    /**
     * @var string
     */
    public $meta_prefix;
    /**
     * @var string|void
     */
    public $slug;
    /**
     * @var string
     */
    public $group_admin_url;
    /**
     * BM_Edit_Group constructor.
     */
    public function __construct()
    {
    }
    /**
     * initialize settings area
     */
    public function init()
    {
    }
    /**
     * Handling repeatable fields for group prices.
     *
     * @param int $group_id current group id.
     * @return void
     */
    public function group_price_output($group_id)
    {
    }
    /**
     * Handling repeatable fields for bulk prices.
     *
     * @param int $group_id current group id.
     * @return void
     */
    public function bulk_price_output($group_id)
    {
    }
    /**
     * output conditional fields
     *
     * @param $group_id
     */
    public function conditional_display_output($group_id)
    {
    }
    /**
     * output tax fields
     *
     * @param $group_id
     */
    public function tax_control_output($group_id)
    {
    }
    /**
     * output price fields
     *
     * @param $group_id
     */
    public function price_control_output($group_id)
    {
    }
    /**
     * output automatic action fields
     *
     * @param $group_id
     */
    public function automatic_actions_output($group_id)
    {
    }
    /**
     * Save meta.
     *
     * @param int $group_id current group id.
     * @return void
     */
    public function save($group_id)
    {
    }
}
/**
 * Class which handles the frontend pricing display
 */
class BM_Shortcode
{
    /**
     * BM_Shortcode constructor.
     */
    public function __construct()
    {
    }
    /**
     * Filter excerpt and remove shortcode if exists.
     *
     * @param string $excerpt given excerpt.
     *
     * @return string
     */
    public function remove_shortcode_from_cart_excerpt($excerpt)
    {
    }
    /**
     * Render bulk price table shortcode.
     *
     * @param [type] $atts list of arguments.
     *
     * @return void
     */
    public function bulk_price_table($atts)
    {
    }
    /**
     * Shortcode for group based content display
     *
     * @param array $atts
     * @param string $content
     *
     * @return void
     */
    public function conditional_customer_group_output($atts, $content = \null)
    {
    }
    /**
     * Show B2B group price for a given product id.
     *
     * @param array       $atts shortcode params
     * @param string|null $content html output
     * @param string      $tag shortcode tag
     *
     * @return string|void
     */
    public function show_b2b_product_price($atts = array(), $content = \null, $tag = '')
    {
    }
    /**
     * Show current customer group
     *
     * @param array $atts list of arguments.
     *
     * @return string
     */
    public function show_current_customer_group($atts)
    {
    }
}
/**
 * Class which handles all conditional logic
 */
class BM_Conditionals
{
    /**
     * Get current user groups for a the current logged in user
     *
     * @return int
     */
    public static function get_validated_customer_group()
    {
    }
    /**
     * Checks if cart amount match customer group setting for min amount.
     *
     * @return void
     */
    public static function is_cart_min_amount_passed()
    {
    }
    /**
     * Checks if checkout amount match customer group setting for min amount.
     *
     * @return void
     */
    public static function is_checkout_min_amount_passed()
    {
    }
}
/**
 * Class that handles product meta data.
 */
class BM_Variation_Meta
{
    /**
     * Constructor for BM_Variation_Meta
     */
    public function __construct()
    {
    }
    /**
     * Add fields to variation.
     *
     * @param array  $loop current lopp.
     * @param array  $variation_data varation data.
     * @param object $variation current variation.
     * @return void
     */
    public function add_variation_fields($loop, $variation_data, $variation)
    {
    }
    /**
     * Add rrp meta field.
     *
     * @param  int $variation_id given post id.
     * @return void
     */
    protected function get_rrp_meta($variation_id)
    {
    }
    /**
     * Get group price meta fields.
     *
     * @param  int $variation_id given post id.
     * @return void
     */
    protected function get_group_price_meta($variation_id)
    {
    }
    /**
     * Add bulk price meta fields.
     *
     * @param  int $variation_id given post id.
     * @return void
     */
    protected function get_bulk_price_meta($variation_id)
    {
    }
    /**
     * Save meta fields
     *
     * @param int $variation_id current post id.
     * @return void
     */
    public function save_variation_meta($variation_id)
    {
    }
    /**
     * Adds the qty metabox.
     *
     * @param array $post_type array of post types.
     * @return void
     */
    public function add_qty_metabox($post_type)
    {
    }
    /**
     * Render qty addon metabox.
     *
     * @param WP_Post $post The post object.
     */
    public function render_qty_addon($post)
    {
    }
    /**
     * Save the meta when the post is saved.
     *
     * @Hook woocommerce_process_product_meta
     *
     * @param int $post_id The ID of the post being saved.
     * @param WP_Post $post
     */
    public function save_qty_metabox($post_id, $post = \null)
    {
    }
}
/**
 * Deprecated: Class to handle old live prices.
 */
class BM_Live_Price
{
    /**
     * Deprecated: Show single product price based on serverside quantity
     *
     * @param string $price current price.
     * @param object $product current product object.
     * @return string
     */
    public static function single_product_price($price, $product)
    {
    }
    /**
     * Deprecated: Get single cheapest price
     *
     * @param string $price current price.
     * @param object $product current product object.
     * @param int    $group_id current group id.
     * @return string
     */
    public static function get_cheapest_price($price, $product, $group_id)
    {
    }
}
/**
 * Class to handle price calculation in B2B Market.
 */
class BM_Price
{
    /**
     * @var int
     */
    public static int $set_price_prio = 10;
    /**
     * Returns instance of BM_Price.
     *
     * @acces public
     * @static
     *
     * @return object
     */
    public static function get_instance()
    {
    }
    /**
     * Constructor for BM_Price.
     *
     * @acces public
     *
     * @return void
     */
    public function __construct()
    {
    }
    /**
     * Set B2B price filter before Cross-Sell template part.
     *
     * @Hook woocommerce_before_template_part
     *
     * @acces public
     *
     * @param string $template_name template file
     *
     * @return void
     */
    public function before_cross_sales($template_name)
    {
    }
    /**
     * Remove B2B price filter after Cross-Sell template part.
     *
     * @Hook woocommerce_after_template_part
     *
     * @acces public
     *
     * @param string $template_name template file
     *
     * @return void
     */
    public function after_cross_sales($template_name)
    {
    }
    /**
     * Looking for B2B price for Cross-Sells on cart and checkout page.
     *
     * @acces public
     *
     * @param string     $price
     * @param WC_Product $product
     *
     * @return float|string
     */
    public function set_price_in_cross_sales_in_cart($price, $product)
    {
    }
    /**
     * Deactivate percentage badge conditionally.
     *
     * @acces public
     *
     * @param  bool $option given option.
     *
     * @return bool
     */
    public function modify_percentage_discount($option)
    {
    }
    /**
     * Get calculated price.
     *
     * @acces public
     * @static
     *
     * @param  string $price current price.
     * @param  object $product current product object.
     * @param  int    $group_id current group id.
     * @param  int    $qty current quantity of the product.
     *
     * @return float
     */
    public static function get_price($price, $product, $group_id, $qty)
    {
    }
    /**
     * Set price.
     *
     * @acces public
     *
     * @param  float  $price current price.
     * @param  object $product current product object.
     *
     * @return float
     */
    public function set_price($price, $product)
    {
    }
    /**
     * Set regular price if sale price and cheapest price is available.
     *
     * @acces public
     *
     * @param float      $regular_price given regular price.
     * @param WC_Product $product given product object.
     *
     * @return float
     *
     * @throws Exception
     */
    public function set_regular_price($regular_price, $product)
    {
    }
    /**
     * Set price for variable products.
     *
     * @Hook woocommerce_variation_prices_price
     *
     * @acces public
     *
     * @param float  $price current price.
     * @param object $variation current variation.
     * @param object $product current product.
     *
     * @return float
     */
    public function set_variable_price($price, $variation, $product)
    {
    }
    /**
     * Set regular price for variable products.
     *
     * @Hook woocommerce_variation_prices_regular_price
     *
     * @acces public
     *
     * @param float  $price current price.
     * @param object $variation current variation.
     * @param object $product current product.
     *
     * @return float
     */
    public function set_variable_regular_price($price, $variation, $product)
    {
    }
    /**
     * Handles cache busting for variations.
     *
     * @acces public
     *
     * @param  string $hash current hash for caching.
     *
     * @return string
     */
    public function regenerate_hash($hash)
    {
    }
    /**
     * Recalculate the item price if options from WooCommerce TM Extra Product Options attached.
     *
     * @acces public
     *
     * @param object $cart current cart object.
     *
     * @return object
     */
    public function recalculate_prices($cart)
    {
    }
    /**
     * Reenable price filter after mini cart.
     *
     * @acces public
     *
     * @return void
     */
    public function reenable_prices()
    {
    }
    /**
     * Check if product is in sub category of given category.
     *
     * @acces public
     * @static
     *
     * @param  int $category given category as ID.
     * @param  int $product_id current product ID.
     *
     * @return bool
     */
    public static function product_in_descendant_category($category, $product_id)
    {
    }
    /**
     * Show sale price based on conditions.
     *
     * @acces public
     *
     * @param float $price current price.
     * @param float $regular_price current regular price.
     * @param float $sale_price current sale price.
     *
     * @return string
     */
    public function show_sale_price($price, $regular_price, $sale_price)
    {
    }
    /**
     * Show or hide sale badge.
     *
     * @acces public
     *
     * @param string $span_class_onsale_esc_html_sale_woocommerce_span current html string.
     * @param object $post post object.
     * @param object $product product object.
     *
     * @return string
     */
    public function show_sale_badge($span_class_onsale_esc_html_sale_woocommerce_span, $post, $product)
    {
    }
    /**
     * Show / hide sale badge in Atomion.
     *
     * @Access PUBLIC
     *
     * @param string $text given discount text.
     * @param object $post current post object.
     * @param object $product current product object.
     * @param string $discount_setting current discount setting.
     * @param float  $discount given discount.
     * @param string $sale_text sale text.
     *
     * @return string
     */
    public function show_sale_price_atomion($text, $post, $product, $discount_setting, $discount, $sale_text)
    {
    }
    /**
     * Set grouped price HTML.
     *
     * @acces public
     *
     * @param string $price_this_get_price_suffix price with suffix.
     * @param object $instance given object instance.
     * @param array  $child_prices list of prices.
     *
     * @return string
     */
    public function set_grouped_price_html($price_this_get_price_suffix, $instance, $child_prices)
    {
    }
    /**
     * Get cheapest bulk price from given id.
     *
     * @acces public
     * @static
     *
     * @param int $product_id given product id.
     * @param int $group_id given group_id.
     *
     * @return array
     */
    public static function get_cheapest_bulk_price($product_id, $group_id = \false)
    {
    }
    /**
     * Recalculate atomion sale percentage.
     *
     * @acces public
     *
     * @param float  $discount given discount value.
     * @param object $product given product object.
     *
     * @return int
     */
    public function calculate_atomion_sale_percentage($discount, $product)
    {
    }
    /**
     * Returns the price html for variable product.
     *
     * @acces public
     *
     * @param string $price price html
     * @param object $product product object
     *
     * @return string
     */
    public function set_variable_price_html($price, $product)
    {
    }
    /**
     * Modify price html based on sale badge option and prices.
     *
     * @acces public
     *
     * @param string $price price html
     * @param object $product product object
     *
     * @return string
     */
    public function set_price_html($price, $product)
    {
    }
    /**
     * Returns if we should show the sale badge for B2B Market discounts.
     *
     * @Hook woocommerce_product_is_on_sale
     *
     * @acces public
     *
     * @param bool   $on_sale
     * @param object $product
     *
     * @return bool
     */
    public function product_is_on_sale($on_sale, $product)
    {
    }
    /**
     * Check if variable product is on sale.
     *
     * @acces public
     *
     * @param bool   $on_sale
     * @param object $product
     * @param mixed  $group_id
     * @param string $show_sale_badge
     *
     * @return bool
     */
    public function check_variable_product_is_on_sale($on_sale, $product, $group_id, $show_sale_badge)
    {
    }
    /**
     * Check if simple product is on sale.
     *
     * @acces public
     *
     * @param bool   $on_sale woocommerce given bool value
     * @param object $product product object
     * @param mixed  $group_id group id if is set or empty value
     * @param string $show_sale_badge sale badge option from group setting
     *
     * @return bool
     */
    public function check_product_is_on_sale($on_sale, $product, $group_id, $show_sale_badge)
    {
    }
    /**
     * Calculating unit price with Woocommerce German Market plugin.
     *
     * @acces public
     * @static
     *
     * @param float             $price product price
     * @param object|WC_Product $product product object
     * @param int               $qty product quantity
     *
     * @return array
     */
    public static function calculate_unit_price($price, $product, $qty = 1)
    {
    }
    /**
     * Set complete product price for WGM Unit Price calculation.
     *
     * @param float             $price
     * @param object|WC_Product $product
     *
     * @return float|int
     */
    public static function calculate_unit_price_set_wgm_price($price, $product)
    {
    }
}
/**
 * Class to handle Whitelist / Blacklist
 */
class BM_Whitelist
{
    /**
     * Returns instance of BM_Price.
     *
     * @return object
     */
    public static function get_instance()
    {
    }
    /**
     * BM_Conditionals constructor.
     */
    public function __construct()
    {
    }
    /**
     * Set whitelist for WooCommerce Blocks.
     *
     * @Hook parse_query
     * @access public
     * @param  object query object
     * @return void
     */
    public function set_woocommerce_blocks_whitelist($wp_query)
    {
    }
    /**
     * Get the products on whitelist
     *
     * @return array
     */
    public function get_products_whitelist()
    {
    }
    /**
     * Get categories for whitelist / blacklist
     *
     * @return array
     */
    public function get_categories_whitelist()
    {
    }
    /**
     * Set whitelist
     *
     * @param object $query
     * @return void
     */
    public function set_whitelist($query)
    {
    }
    /**
     * Set whitelist for category views on shop page.
     *
     * @param  array $terms array of terms.
     * @param  array $taxonomies array of taxonomies.
     * @param  array $args array of arguments.
     * @return array
     */
    public function set_shop_category_view_whitelist($terms, $taxonomies, $args)
    {
    }
    /**
     * Set whitelist / blacklist for related products
     *
     * @param array $related_posts
     * @param int $product_id
     * @param array $args
     * @return void
     */
    public function set_related_whitelist($related_posts, $product_id, $args)
    {
    }
    /**
     * Set whitelist / blacklist for upsells
     *
     * @param [type] $relatedIds
     * @param [type] $product
     * @return void
     */
    public function set_upsell_whitelist($relatedIds, $product)
    {
    }
    /**
     * Set whitelist / blacklist for widgets
     *
     * @param array $query_args
     * @return void
     */
    public function set_widget_whitelist($query_args)
    {
    }
    /**
     * Set whitelist / blacklist for category widgets
     *
     * @param array $query_args
     * @return void
     */
    public function set_widget_category_whitelist($query_args)
    {
    }
    /**
     * Set whitelist / blacklist for search
     *
     * @param array $query
     * @return void
     */
    public function set_search_whitelist($query)
    {
    }
    /**
     * Set redirects based on whitelist / blacklist
     *
     * @return void
     */
    public function redirect_based_on_whitelist()
    {
    }
    /**
     * Checks if product in cart is on blacklist
     *
     * @return void
     */
    public function is_cart_item_whitelist()
    {
    }
    /**
     * Checks if product in checkout is on blacklist
     *
     * @param  array  $data cart data.
     * @param  object $errors cart errors.
     * @return boolean
     */
    public function is_checkout_item_whitelist($data, $errors)
    {
    }
}
class BM_Automatic_Actions
{
    /**
     * @var string
     */
    public $meta_prefix;
    /**
     * BM_Automatic_Actions constructor.
     */
    public function __construct()
    {
    }
    /**
     * Generate discount for first order.
     *
     * @Hook woocommerce_before_cart
     *
     * @acces public
     *
     * @return void
     * @throws Exception
     */
    public function add_first_order_discount()
    {
    }
    /**
     * Generate discount coupon in cart if possible.
     *
     * @Hook woocommerce_before_cart
     *
     * @acces public
     *
     * @return void
     */
    public function add_goods_discount()
    {
    }
    /**
     * @Hook on_action_cart_item_quantity_update
     *
     * @access public
     *
     * @param string $cart_item_key
     * @param int    $quantity
     * @param int    $old_quantity
     *
     * @return void
     */
    public function on_action_cart_item_quantity_update($cart_item_key, $quantity, $old_quantity)
    {
    }
    /**
     * Generate discount coupon in cart if possible.
     *
     * @Hook woocommerce_before_cart
     *
     * @acces public
     *
     * @return void
     */
    public function add_cart_discount()
    {
    }
    /**
     * Checks if a coupon still exists.
     *
     * @acces public
     * @static
     *
     * @param string $coupon_code
     *
     * @return bool
     * @throws Exception
     */
    public static function is_coupon_valid($coupon_code)
    {
    }
    /**
     * Check if its first customer order.
     *
     * @acces public
     * @static
     *
     * @return bool
     */
    public static function is_first_order()
    {
    }
    /**
     * Generate discount coupon.
     *
     * @acces protected
     *
     * @param string       $coupon_code
     * @param string       $discount_type
     * @param float        $discount_amount
     * @param string       $discount_name
     * @param string|array $allowed_products
     * @param string|array $allowed_cats
     * @param bool         $first_order
     *
     * @return WC_Coupon coupon object
     */
    protected function generate_coupon($coupon_code, $discount_type, $discount_amount, $discount_name, $allowed_products, $allowed_cats, $first_order = \false)
    {
    }
    /**
     * Returns the discount type.
     *
     * @acces public
     * @static
     *
     * @param string $discount_type
     *
     * @return string
     */
    public static function get_discount_type($discount_type)
    {
    }
    public function replace_coupon_label_with_description($label, $coupon)
    {
    }
}
/**
 * Class which handles all the helper functions
 */
class BM_Helper
{
    /**
     * BM_Helper constructor.
     */
    public function __construct()
    {
    }
    /**
     * Get available products
     *
     * @return void|array
     */
    public static function get_available_products()
    {
    }
    /**
     * Get avaialable product categories
     *
     * @return array
     */
    public static function get_available_categories()
    {
    }
    /**
     * Get current posttype from admin page
     *
     * @return void
     */
    public static function get_current_post_type()
    {
    }
    /**
     * force delete customer_groups
     *
     * @param $post_id
     */
    public function skip_trash($post_id)
    {
    }
    /**
     * Delete all options for customer group
     *
     * @param int $postid
     * @return void
     */
    public function clear_options($postid)
    {
    }
    /**
     * Checks if array is empty
     *
     * @param array $array
     * @return boolean
     */
    public static function is_array_empty($array)
    {
    }
    /**
     * Check if the current visit is a rest api call
     *
     * @return boolean
     */
    public static function is_rest()
    {
    }
    /**
     * Returns true if ajax is executed from frontend.
     *
     * @access public
     * @return Boolean
     */
    public static function is_frontend_ajax()
    {
    }
    /**
     * Return translated object id(s) if WPML is supported.
     *
     * @acces public
     * @static
     *
     * @param int|array $object_id object id or ids
     * @param string $type type of object like 'post' or 'category'
     *
     * @return int|array
     */
    public static function get_translated_object_ids($object_id, $type)
    {
    }
    /**
     * Returns the group slug.
     *
     * @acces public
     * @static
     *
     * @param int|null $group_id group id
     *
     * @return string|void
     */
    public static function get_group_slug($group_id)
    {
    }
    /**
     * Force regenerating product price hashes.
     *
     * @acces public
     * @static
     *
     * @return void
     */
    public static function force_regenerate_woocommerce_price_hashes()
    {
    }
}
/**
 * Class to handle admin orders with B2B Market
 */
class BM_Admin_Orders
{
    /**
     * Returns instance of BM_Admin_Orders.
     *
     * @return object
     */
    public static function get_instance()
    {
    }
    /**
     * Constructor for BM_Admin_Orders.
     */
    public function __construct()
    {
    }
    /**
     * Reset order customer in auto-draft order if select field got resetted.
     *
     * @Hook wp_ajax_reset_order_customer_id
     *
     * @return void
     */
    public function reset_order_customer_id()
    {
    }
    /**
     * @Hook wp_ajax_update_order_customer_id
     *
     * @return void
     */
    public function update_order_customer_id()
    {
    }
    /**
     * This function is fired when adding products to a manual order (after modal panel).
     *
     * @Hook woocommerce_ajax_order_items_added
     *
     * @param array    $added_items
     * @param WC_Order $order
     *
     * @return void
     */
    public function ajax_order_items_added($added_items, $order)
    {
    }
    /**
     * Applies the cheapest price to items from order.
     *
     * @Hook woocommerce_before_order_object_save
     *
     * @param object $order current post  object.
     * @param array  $data_store object with the current store abstraction.
     *
     * @return void
     */
    public function apply_cheapest_price_to_items($order, $data_store)
    {
    }
    /**
     * Get customer group by user id.
     *
     * @param  int $user_id given user id.
     * @return int
     */
    public static function get_customer_group_by_id($user_id)
    {
    }
    /**
     * Prevent order updates from frontend.
     *
     * @param  int $order_id given order id.
     * @return void
     */
    public function prevent_price_update($order_id)
    {
    }
}
/**
 * Class to handle compatibilities with different themes and plugins.
 */
class BM_Compatibilities
{
    /**
     * Returns instance of BM_Price.
     *
     * @return object
     */
    public static function get_instance()
    {
    }
    /**
     * Constructor for BM_Price.
     */
    public function __construct()
    {
    }
}
class BM_Admin
{
    /**
     * BM_Admin constructor.
     */
    public function __construct()
    {
    }
    /**
     * hooks and includes for other classes
     */
    public function init()
    {
    }
    /**
     * init for addons
     */
    public function addons_init()
    {
    }
    /**
     * handler for enqueue admin scripts
     */
    public function add_admin_assets()
    {
    }
    /**
     * register post type "customer_groups"
     */
    public function register_post_type()
    {
    }
    /**
     * Add special groups initially
     *
     * @return void
     */
    public function add_special_groups()
    {
    }
    /**
     * Add Admin notices, infos for other MarketPress products
     *
     * @wp-hook 	admin_notices
     * @return 		void
     */
    public function marketpress_notices()
    {
    }
    /**
     * Add Admin notices German Market and B2B Market
     *
     * @wp-hook 	admin_notices
     * @return 		void
     */
    public function marketpress_notices_gm_and_atomion()
    {
    }
    /**
     * Load JavaScript so you can dismiss the MarketPress Plugin Notice
     *
     * @wp-hook admin_enqueue_scripts
     * @return void
     */
    public function backend_script_market_press_notices()
    {
    }
    /**
     * Dismiss MarketPress Notice
     *
     * @wp-hook wp_ajax_atomion_dismiss_marketprss_notice
     * @return void
     */
    public function backend_script_market_press_dismiss_notices()
    {
    }
    /**
     * Enqueue migrator scripts to submit upgrade for 1.0.8.3 via AJAX.
     *
     * @return void
     */
    public function upgrade_scripts()
    {
    }
    /**
     * Add Admin notices for B2B Market 1.0.8 migration.
     *
     * @return void
     */
    public function upgrade_notice()
    {
    }
    /**
     * Dismiss upgrade notice.
     *
     * @return void
     */
    public function dismiss_upgrade_notice()
    {
    }
    public function delete_related_postmeta($postid)
    {
    }
    /**
     * Add customer group column header
     *
     * @param  array $columns array of columns.
     * @return array
     */
    public function add_customer_groups_column_header($columns)
    {
    }
    /**
     * Add custom group column content
     *
     * @param array $column array of columns.
     * @return void
     */
    public function add_customer_groups_column_content($column)
    {
    }
    /**
     * Add customer selector to admin bar
     *
     * @return void
     */
    public function add_customer_group_admin_selector()
    {
    }
    /**
     * Assign customer groups via ajax
     *
     * @return void
     */
    public function assign_customer_group()
    {
    }
    /**
     * Run B2B Market 1.0.8 migration.
     *
     * @return void
     */
    public function run_update_migration()
    {
    }
    /**
     * Create form config file with ajax or cron.
     *
     * @return void
     */
    public function handle_migration()
    {
    }
}
/**
 * Plugin Name:  B2B Market
 * Plugin URI:   https://marketpress.de/shop/plugins/b2b-market/
 * Description:  B2B solution for WooCommerce with role-based pricing and simultaneous sales to B2B and B2C.
 * Version:      1.0.11.1
 * Author:       MarketPress
 * Author URI:   https://marketpress.de
 * Plugin URI:   https://marketpress.com/shop/plugins/woocommerce/b2b-market/
 * Update URI:   https://marketpress.com/shop/plugins/woocommerce/b2b-market/
 * Licence:      GPLv3
 * Text Domain:  b2b-market
 * Domain Path:  /languages
 * WC requires at least: 5.1.0+
 * WC tested up to: 6.6.1
 */
\define('B2B_PLUGIN_PATH', \untrailingslashit(\plugin_dir_path(__FILE__)));
\define('B2B_ADDON_PATH', \untrailingslashit(\plugin_dir_path(__FILE__)) . \DIRECTORY_SEPARATOR . 'src' . \DIRECTORY_SEPARATOR . 'addons' . \DIRECTORY_SEPARATOR);
\define('B2B_TEMPLATE_PATH', \untrailingslashit(\plugin_dir_path(__FILE__)) . \DIRECTORY_SEPARATOR . 'templates' . \DIRECTORY_SEPARATOR . 'woocommerce' . \DIRECTORY_SEPARATOR);
\define('B2B_PLUGIN_URL', \untrailingslashit(\plugin_dir_url(__FILE__)));
\define('B2B_REQUIRED_PHP_VERSION', '7.4');
/**
 * Loads admin syles
 *
 * @wp-hook	admin_enqueue_scripts
 * @return	void
 */
function slack_connector_admin_enqueue_styles()
{
}
/**
 * Loads all modules
 *
 * @wp-hook	slack_connector_load_modules
 * @return	void
 */
function slack_connector_load_modules()
{
}
/**
 * Register custom post type
 *
 * @wp-hook	init
 * @return	void
 */
function slack_connector_register_custom_post_type()
{
}
/**
 * Most important function in this plugin
 * Send the slack notification
 * See slack documentation for $args
 *
 * @param 	string $url
 * @param 	array $args
 * @return	array | WP_Error
 */
function slack_connector_send_message($url, $args)
{
}
/**
 * Adds a conditional ".min" suffix to the
 * file name when SCRIPT_DEBUG is NOT set to TRUE.
 *
 * @return	string
 */
function slack_connector_get_script_suffix()
{
}
/**
 * Check if our admin page is shown
 *
 * @return boolean
 */
function slack_connector_is_admin()
{
}
/**
 * Check $plugin is activated or (!) network activated
 *
 * @param 	string $plugin
 * @return 	boolean
 */
function slack_connector_is_plugin_activated($plugin)
{
}
/**
 * See Slack documentation
 * It's quite difficult to get the correct string format so that slack will send your message
 *
 * @param 	string $string
 * @return 	string
 */
function slack_connector_prepare_string_for_slack($param_string)
{
}
/**
 * See Slack documentation
 * Format a link
 * Do this after Calling slack_connector_prepare_string_for_slack!
 *
 * @param 	string $url
 * @return 	string $title
 */
function slack_connector_get_slack_url($url, $title)
{
}
/**
 * Send test notification, called via ajax
 *
 *
 * @wp-hook wp_ajax_slack_connector_test_notification
 * @param 	string $url
 * @return 	void
 */
function slack_connector_test_notification()
{
}
/**
 * Loads admin scripts
 *
 * @wp-hook	admin_enqueue_scripts
 * @return	void
 */
function slack_connector_admin_enqueue_scripts()
{
}
/**
 * Save Meta Data for our CPT
 *
 * @wp-hook	save_post
 * @param 	Integer $post_id
 * @return	void
 */
function slack_connector_save_post($post_ID)
{
}
/**
 * Add General Meta Boxes to our CPT
 *
 * @wp-hook	save_post
 * @param 	Integer $post_id
 * @return	void
 */
function slack_connector_meta_bxoes()
{
}
/**
 * Webhook URL Metabox
 *
 * @add_meta_box
 * @param 	WP_Post $post
 * @param 	Array $metabox
 * @return	void
 */
function slack_connector_meta_box_webhook_url($post, $metabox)
{
}
/**
 * Makes the general input fields for the meta box of a module.
 * Every module should use it.
 * Includes "Slack Username", "Emoji"
 *
 * @param 	String $module_slug
 * @return	void
 */
function slack_connector_general_input_fiels_for_meta_boxes_of_modules($module_slug, $module_name, $post)
{
}
/**
 * Save Meta Data for our CPT - WooCommerce
 *
 * @wp-hook	slack_connector_save_post
 * @param 	Integer $post_id
 * @return	void
 */
function slack_connector_save_post_woocommerce($post_ID)
{
}
/**
 * New Order: Detect if we have to send a notifcation, check all cases, distribute to other functions
 *
 * @wp-hook	woocommerce_new_order
 * @param 	Integer  $order_id
 * @param 	Object   $posted_data
 * @param 	WC_Order $order
 * @return	void
 */
function slack_connector_woocommerce_checkout_order_processed($order_id, $order)
{
}
/**
 * Send Notification New Order / Product Sale
 *
 * @param 	WP_Post $channel
 * @param 	WC_Order $order
 * @param 	Array $items
 * @return	void
 */
function slack_connector_woocommerce_new_order_product_sale($channel, $order, $items)
{
}
/**
 * Check if we have to send a notification because the order item is one of the chosen products
 *
 * @param 	WP_Post $channel
 * @param 	Array $item
 * @param 	WC_Order $order
 * @return	void
 */
function slack_connector_woocommerce_checkout_order_processed_new_order_product_sale($channel, $item, $order)
{
}
/**
 * Check if we have to send a notification because the order item is one of the chosen product categories
 *
 * @param 	WP_Post $channel
 * @param 	Array $item
 * @param 	WC_Order $order
 * @return	void
 */
function slack_connector_woocommerce_checkout_order_processed_new_order_product_category_sale($channel, $item, $order)
{
}
/**
 * List all order items with url and optional price
 *
 * @param 	WC_Order $order
 * @return	String
 */
function slack_connector_woocommerce_list_items($items, $channel, $order, $price = \false)
{
}
/**
 * List all order items without url and without price
 *
 * @param 	WC_Order $order
 * @return	String
 */
function slack_connector_woocommerce_list_items_light_version($items, $channel, $order)
{
}
/**
 * Get Attributes for variations
 *
 * @param 	Array $item
 * @param 	WC_Order $order
 * @return	String
 */
function slack_connector_woocommerce_list_items_get_attributs($item, $order)
{
}
/**
 * Low Stock Notification
 *
 * @wp-hook	woocommerce_low_stock
 * @param 	WC_Product $product
 * @return	void
 */
function slack_connector_woocommerce_low_stock($product)
{
}
/**
 * Out of Stock Notification
 *
 * @wp-hook	woocommerce_no_stock
 * @param 	WC_Product $product
 * @return	void
 */
function slack_connector_woocommerce_no_stock($product)
{
}
/**
 * New Customer Notification
 *
 * @wp-hook	woocommerce_created_customer
 * @param 	Integer $customer_id
 * @param 	Array $new_customer_data
 * @param 	Boolean $password_generated
 * @return	void
 */
function slack_connector_woocommerce_new_customer($customer_id, $new_customer_data, $password_generated)
{
}
/**
 * New Review Notification
 *
 * @wp-hook	comment_post
 * @param 	Integer $comment_ID
 * @param 	Integer $comment_approved
 * @param 	Array $commentdata since WordPress 4.5
 * @return	void
 */
function slack_connector_woocommerce_new_review($comment_ID, $comment_approved, $comment_data)
{
}
/**
 * Add meta boxes for WooCommerce Module
 *
 * @wp-hook	slack_connector_meta_boxes
 * @return	void
 */
function slack_connector_meta_boxes_woocommerce()
{
}
/**
 * WooCommerce Metabox
 *
 * @add_meta_box
 * @param 	WP_Post $post
 * @param 	Array $metabox
 * @return	void
 */
function slack_connector_meta_box_woocommerce($post, $metabox)
{
}
/**
 * Load WooCommerce module
 *
 * @wp-hook	slack_connector_init_module
 * @return	void
 */
function slack_connector_woocommerce_init()
{
}
// Define needed constants
\define('MPSC_PLUGIN_URL', \untrailingslashit(\plugin_dir_url(__FILE__)));
\define('MPSC_PLUGIN_PATH', \untrailingslashit(\plugin_dir_path(__FILE__)));
\define('MPSC_BASEFILE', \plugin_basename(__FILE__));
\define('MPSC_APPLICATION_DIR', \untrailingslashit(\dirname(__FILE__)) . \DIRECTORY_SEPARATOR . 'application');
\define('MPSC_MODULE_DIR', \MPSC_APPLICATION_DIR . \DIRECTORY_SEPARATOR . 'modules');
/**
 * Loads all the files and registers all actions and filters
 *
 * @wp-hook	plugins_loaded
 * @return	void
 */
function slack_connector_init()
{
}
/**
* Load text domain
*
* @since 1.0
* @static
* @hook init
* @return void
*/
function slack_connector_load_text_domain()
{
}
/**
 * Initialize all update price hooks
 *
 * @return void
 */
function init_bm_update_price()
{
}
/**
 * Initialize all calculation hooks
 *
 * @return void
 */
function init_bm_show_discounts()
{
}
/**
 * Initialize all whitelist hooks
 *
 * @return void
 */
function init_bm_whitelist()
{
}
/**
 * Remove tax from prices.
 *
 * @param  string $value excl|incl.
 * @return string
 */
function bm_remove_tax($value)
{
}
/**
 * Filter tax in e-mails.
 *
 * @param object $order given order object.
 * @param bool   $sent_to_admin bool send to admin.
 * @param string $plain_text email text.
 * @param int    $email email id.
 * @return void
 */
function bm_set_tax_mails($order, $sent_to_admin, $plain_text, $email)
{
}
/**
 * Initialize min max quantity hooks
 *
 * @return void
 */
function init_bm_min_max_quantities()
{
}
/**
 * Initialize all price_display hooks
 *
 * @return void
 */
function init_bm_hide_prices()
{
}