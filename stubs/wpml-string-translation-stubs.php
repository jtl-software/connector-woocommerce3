<?php

namespace {
    class WPML_Package_Exception extends \Exception
    {
        public $type;
        public function __construct($type = '', $message = '', $code = 0)
        {
        }
    }
    class WPML_ST_Package_Factory
    {
        public function __construct(\WPML_WP_Cache_Factory $cache_factory = \null)
        {
        }
        /**
         * @param \stdClass|\WPML_Package|array|int $package_data
         *
         * @return WPML_Package
         */
        public function create($package_data)
        {
        }
    }
    class WPML_Package_Translation_Schema
    {
        const OPTION_NAME = 'wpml-package-translation-db-updates-run';
        const REQUIRED_VERSION = '0.0.2';
        static function run_update()
        {
        }
        public static function build_icl_strings_columns_if_required()
        {
        }
    }
    class WPML_Package_Translation_HTML_Packages
    {
        var $load_priority = 101;
        public function __construct()
        {
        }
        public function loaded()
        {
        }
        public static function package_translation_menu()
        {
        }
        public function package_translation_menu_options($package_kind_options)
        {
        }
        /**
         * @param array<\WPML_Package> $packages
         */
        public function package_translation_menu_body($packages)
        {
        }
        public function render_package_table_columns($position)
        {
        }
        public function render_string_package_status($string_count, $translation_in_progress, $default_package_language)
        {
        }
    }
    class WPML_Package_Helper
    {
        const PREFIX_BATCH_STRING = 'batch-string-';
        protected $registered_strings;
        function __construct()
        {
        }
        public function set_package_factory(\WPML_ST_Package_Factory $factory)
        {
        }
        /**
         * @param int $package_id
         */
        protected function delete_package($package_id)
        {
        }
        /**
         * @param int $package_id
         *
         * @return array
         */
        protected function get_strings_ids_from_package_id($package_id)
        {
        }
        /**
         * @param int $package_id
         */
        protected function delete_package_strings($package_id)
        {
        }
        protected function loaded()
        {
        }
        /**
         * @param string             $string_value
         * @param string             $string_name
         * @param array|WPML_Package $package
         * @param string             $string_title
         * @param string             $string_type
         */
        final function register_string_action($string_value, $string_name, $package, $string_title, $string_type)
        {
        }
        /**
         * @param int                               $default
         * @param \stdClass|\WPML_Package|array|int $package
         * @param string                            $string_name
         * @param string                            $string_value
         *
         * @return bool|int|mixed
         */
        function string_id_from_package_filter($default, $package, $string_name, $string_value)
        {
        }
        function string_title_from_id_filter($default, $string_id)
        {
        }
        /**
         * @param string             $string_value
         * @param string             $string_name
         * @param array|WPML_Package $package
         * @param string             $string_title
         * @param string             $string_type
         *
         * @return string
         */
        public final function register_string_for_translation($string_value, $string_name, $package, $string_title, $string_type)
        {
        }
        final function get_string_context_from_package($package)
        {
        }
        /**
         * @param WPML_Package $package
         * @param string       $string_name
         * @param string       $string_title
         * @param string       $string_type
         * @param string       $string_value
         *
         * @return bool|int|mixed
         */
        final function register_string_with_wpml($package, $string_name, $string_title, $string_type, $string_value)
        {
        }
        /**
         * @param string|mixed     $string_value
         * @param string           $string_name
         * @param array|object|int $package
         *
         * @return string|mixed
         */
        final function translate_string($string_value, $string_name, $package)
        {
        }
        final function get_translated_strings($strings, $package)
        {
        }
        final function set_translated_strings($translations, $package)
        {
        }
        final function get_translatable_types($types)
        {
        }
        /**
         * @param  WPML_Package|null        $item
         * @param  int|WP_Post|WPML_Package $package
         * @param  string                   $type
         *
         * @return null|WPML_Package
         */
        public final function get_translatable_item($item, $package, $type = 'package')
        {
        }
        final function get_post_title($title, $package_id)
        {
        }
        final function get_editor_string_name($name, $package)
        {
        }
        final function get_editor_string_style($style, $field_type, $package)
        {
        }
        public final function get_element_id_from_package_filter($default, $package_id)
        {
        }
        public final function get_package_type($type, $post_id)
        {
        }
        public final function get_package_type_prefix($type, $post_id)
        {
        }
        /**
         * @param string        $language_for_element
         * @param \WPML_Package $current_document
         *
         * @return null|string
         */
        public final function get_language_for_element($language_for_element, $current_document)
        {
        }
        protected final function get_package_context($package)
        {
        }
        final function delete_packages_ajax()
        {
        }
        final function delete_package_action($name, $kind)
        {
        }
        /** @param int $post_id */
        public final function remove_post_packages($post_id)
        {
        }
        protected final function delete_packages($packages_ids)
        {
        }
        final function change_package_lang_ajax()
        {
        }
        /**
         * @param WPML_Package $package
         *
         * @return int
         */
        public static final function create_new_package(\WPML_Package $package)
        {
        }
        final function get_external_id_from_package($package)
        {
        }
        final function get_string_context($package)
        {
        }
        final function get_package_id($package, $from_cache = \true)
        {
        }
        public final function get_all_packages()
        {
        }
        protected final function is_a_package($element)
        {
        }
        protected function verify_ajax_call($ajax_action)
        {
        }
        protected function sanitize_string_name($string_name)
        {
        }
        public function refresh_packages()
        {
        }
        public function change_language_of_strings($strings, $lang)
        {
        }
        public function change_language_of_strings_in_domain($domain, $langs, $to_lang)
        {
        }
        /**
         * @param null|array $packages
         * @param int        $post_id
         *
         * @return WPML_Package[]
         */
        public function get_post_string_packages($packages, $post_id)
        {
        }
        /**
         * @param int         $string_id
         * @param string      $language
         * @param string|null $value
         * @param int|bool    $status
         * @param int|null    $translator_id
         * @param int|null    $translation_service
         * @param int|null    $batch_id
         */
        public function add_string_translation_action($string_id, $language, $value = \null, $status = \false, $translator_id = \null, $translation_service = \null, $batch_id = \null)
        {
        }
        /**
         * @param mixed $package
         * @param int   $package_id
         *
         * @return WPML_Package
         */
        public function get_string_package($package, $package_id)
        {
        }
        public function start_string_package_registration_action($package)
        {
        }
        public function delete_unused_package_strings_action($package)
        {
        }
    }
    class WPML_Package_Admin_Lang_Switcher
    {
        public function __construct($package, $args)
        {
        }
        function admin_language_switcher()
        {
        }
        function add_meta_box()
        {
        }
        function add_js()
        {
        }
    }
    class WPML_Package
    {
        const CACHE_GROUP = 'WPML_Package';
        public $ID;
        public $view_link;
        public $edit_link;
        public $is_translation;
        public $string_data;
        public $title;
        public $new_title;
        public $kind_slug;
        public $kind;
        public $trid;
        public $name;
        public $translation_element_type;
        public $post_id;
        /**
         * @param stdClass|WPML_Package|array|int|WP_Post $data_item
         */
        function __construct($data_item)
        {
        }
        public function __get($property)
        {
        }
        public function __set($property, $value)
        {
        }
        public function __isset($property)
        {
        }
        public function __unset($property)
        {
        }
        public function get_translation_element_type()
        {
        }
        public function get_package_post_id()
        {
        }
        public function get_element_type_prefix()
        {
        }
        public function set_package_post_data()
        {
        }
        public function update_strings_data()
        {
        }
        /**
         * @param bool $refresh
         *
         * @return mixed
         */
        public function get_package_strings($refresh = \false)
        {
        }
        public function set_strings_language($language_code)
        {
        }
        public function create_new_package_record()
        {
        }
        public function update_package_record()
        {
        }
        public function get_package_id()
        {
        }
        public function sanitize_string_name($string_name)
        {
        }
        /**
         * @param string $string_value
         * @param string $sanitized_string_name
         *
         * @return string|mixed
         */
        function translate_string($string_value, $sanitized_string_name)
        {
        }
        function get_string_context_from_package()
        {
        }
        public function get_string_id_from_package($string_name, $string_value)
        {
        }
        function get_translated_strings($strings)
        {
        }
        function set_translated_strings($translations)
        {
        }
        public function has_kind_and_name()
        {
        }
        /**
         * @return bool|mixed
         */
        protected function package_exists()
        {
        }
        public function get_package_element_type()
        {
        }
        /**
         * @return string|null
         */
        public function get_package_language()
        {
        }
        public function are_all_strings_included($strings)
        {
        }
        public function flush_cache()
        {
        }
    }
    class WPML_Package_Translation_UI
    {
        var $load_priority = 101;
        const MENU_SLUG = 'wpml-package-management';
        public function __construct()
        {
        }
        public function loaded()
        {
        }
        public function main_menu_configured($menu_id, $root_slug)
        {
        }
        /**
         * @param string $menu_id
         */
        public function menu($menu_id)
        {
        }
        function admin_register_scripts()
        {
        }
        function admin_enqueue_scripts($hook)
        {
        }
    }
    class WPML_Package_TM_Jobs
    {
        /**
         * @var WPML_Package|null
         */
        protected $package;
        protected function __construct($package)
        {
        }
        public final function validate_translations($create_if_missing = \true)
        {
        }
        protected final function get_trid($create_if_missing = \true)
        {
        }
        public final function set_language_details($language_code = \null)
        {
        }
        /**
         * @param int|WP_Post|WPML_Package $package
         * @return WPML_Package
         */
        public final function get_translatable_item($package)
        {
        }
        public final function delete_translation_jobs()
        {
        }
        public final function delete_translations()
        {
        }
        public final function get_post_translations()
        {
        }
        protected final function update_translation_job_needs_update($job_id)
        {
        }
        final function update_translation_job($rid, $post)
        {
        }
        protected function get_translation_job_id($rid)
        {
        }
    }
    class WPML_Package_Translation_Metabox
    {
        public $metabox_data;
        /**
         * WPML_Package_Translation_Metabox constructor.
         *
         * @param stdClass|WPML_Package|array|int $package
         * @param \wpdb                           $wpdb
         * @param \SitePress                      $sitepress
         * @param array<string,mixed>             $args
         */
        public function __construct($package, $wpdb, $sitepress, $args = array())
        {
        }
        public function get_package_language_name()
        {
        }
        function get_metabox()
        {
        }
        public function get_metabox_status()
        {
        }
        function get_post_translations()
        {
        }
    }
    class WPML_Package_Translation extends \WPML_Package_Helper
    {
        var $load_priority = 100;
        var $package_translation_active;
        var $admin_lang_switcher = \null;
        function __construct()
        {
        }
        function loaded(\SitePress $sitepress = \null)
        {
        }
        public function add_title_db_location($locations)
        {
        }
        function get_package_edit_url($url, $post_id)
        {
        }
        public function get_package_title($title, $kind, $id)
        {
        }
        function get_package_view_link($link, $post_id, $hide_if_missing_link = \false)
        {
        }
        function get_package_edit_link($link, $post_id, $hide_if_missing_link = \false)
        {
        }
        /**
         * @param stdClass|WPML_Package|array|int $package
         * @param array<string,mixed>             $args
         */
        function show_language_selector($package, $args = array())
        {
        }
        /**
         * @param stdClass|WPML_Package|array|int $package
         * @param array<string,mixed>             $args
         */
        function show_admin_bar_language_selector($package, $args = array())
        {
        }
        function cleanup_translation_jobs_basket_packages($translation_jobs_basket)
        {
        }
        public function update_translation_jobs_basket($translation_jobs_cart, $translation_jobs_basket, $item_type)
        {
        }
        public function basket_items_types($item_types)
        {
        }
        function is_external($result, $type)
        {
        }
        public function get_element_type($type, $element)
        {
        }
        /**
         * @param array<string,string> $attributes
         *
         * @return string
         */
        public function attributes_to_string($attributes)
        {
        }
        /**
         * @param string $kind_slug
         *
         * @return string
         */
        public static function get_package_element_type($kind_slug)
        {
        }
        /**
         * @param array<string,string> $package
         *
         * @return bool
         */
        public function package_has_kind($package)
        {
        }
        /**
         * @param array<string,string> $package
         *
         * @return bool
         */
        public function package_has_name($package)
        {
        }
        /**
         * @param array<string,string> $package
         *
         * @return bool
         */
        public function package_has_title($package)
        {
        }
        /**
         * @param array<string,string> $package
         *
         * @return bool
         */
        public function package_has_kind_and_name($package)
        {
        }
        /**
         * @param string $string_name
         *
         * @return mixed
         */
        public function sanitize_string_with_underscores($string_name)
        {
        }
        function new_external_item($type, $package_item, $get_string_data = \false)
        {
        }
        function get_package_from_external_id($post_id)
        {
        }
        function _get_package_strings($package_item)
        {
        }
        function get_link($item, $package_item, $anchor, $hide_empty)
        {
        }
        /**
         * Update translations
         *
         * @param int  $package_id
         * @param bool $is_new       - set to true for newly created form (first save without fields)
         * @param bool $needs_update - when deleting single field we do not need to change the translation status of the form
         *
         * @internal param array $item - package information
         */
        function update_package_translations($package_id, $is_new, $needs_update = \true)
        {
        }
        /**
         * Functions to update translations when packages are modified in admin
         *
         * @param int       $rid
         * @param \stdClass|WPML_Package $post
         */
        function update_icl_translate($rid, $post)
        {
        }
        function get_string_context_title($context, $string_details)
        {
        }
        function get_string_title($title, $string_details)
        {
        }
        function _get_post_translations($package)
        {
        }
        function _is_translation_in_progress($package)
        {
        }
        function _delete_translation_job($package_id)
        {
        }
        public function add_to_basket($data)
        {
        }
        function _no_wpml_warning()
        {
        }
        public function tm_dashboard_sql_filter($sql)
        {
        }
        public function save_package_translations($element_type_prefix, $job, $decoder)
        {
        }
    }
    class WPML_Package_TM extends \WPML_Package_TM_Jobs
    {
        public function __construct($package)
        {
        }
        public function get_translation_statuses()
        {
        }
        public function is_translation_in_progress()
        {
        }
        /**
         * Update translations
         *
         * @param bool $is_new_package
         * @param bool $needs_update - when deleting single field we do not need to change the translation status of the form
         *
         * @return bool
         */
        public function update_package_translations($is_new_package, $needs_update = \true)
        {
        }
        public function add_package_to_basket($translation_action, $source_language, $target_language)
        {
        }
        /**
         * @param string $source_language
         * @param string $target_language
         *
         * @throws WPML_Package_Exception
         */
        public function send_package_to_basket($source_language, $target_language)
        {
        }
        public function is_in_basket($target_lang)
        {
        }
    }
    class WPML_Package_ST
    {
        public function get_string_element($string_id, $column = \false)
        {
        }
        public function get_string_title($title, $string_details)
        {
        }
    }
    abstract class WPML_Admin_Text_Functionality
    {
        public final function is_blacklisted($option_name)
        {
        }
        protected function read_admin_texts_recursive($keys, $admin_text_context, $type, &$arr_context, &$arr_type)
        {
        }
        /**
         * @param string $key     Name of option to retrieve. Expected to not be SQL-escaped.
         * @param mixed  $default Value to return in case the string does not exists.
         *
         * @return mixed Value set for the option.
         */
        public function get_option_without_filtering($key, $default = \false)
        {
        }
    }
    class WPML_Admin_Text_Configuration extends \WPML_Admin_Text_Functionality
    {
        /**
         * @param string|stdClass $file_or_object
         */
        function __construct($file_or_object = '')
        {
        }
        function get_config_array()
        {
        }
    }
}
namespace WPML\ST\AdminTexts {
    class UI implements \IWPML_Backend_Action_Loader
    {
        // shouldShow :: Collection -> bool
        public static function shouldShow(\WPML\Collect\Support\Collection $data)
        {
        }
        public static function localize(\WPML\Collect\Support\Collection $model)
        {
        }
        /**
         * @return callable|null
         */
        public function create()
        {
        }
    }
}
namespace {
    class WPML_Admin_Text_Import extends \WPML_Admin_Text_Functionality
    {
        function __construct(\WPML_ST_Records $st_records, \WPML_WP_API $wp_api)
        {
        }
        /**
         * @param array  $admin_texts
         * @param string $config_handler_hash
         */
        function parse_config(array $admin_texts, $config_handler_hash)
        {
        }
    }
}
namespace WPML\Ajax\ST\AdminText {
    class Register implements \WPML\Ajax\IHandler
    {
        public function __construct(\WPML_Admin_Texts $adminTexts)
        {
        }
        /**
         * Registers or Unregisters an option for translation depending
         * on the `state` data.
         *
         * @param Collection $data
         *
         * @return Either
         */
        public function run(\WPML\Collect\Support\Collection $data)
        {
        }
        /**
         * string $state -> string [key1][key2][name] -> array [ key1 => [ key2 => [ name => $state ] ] ]
         *
         * @param string $state
         * @param string $option
         *
         * @return array
         */
        public static function flatToHierarchical($state, $option)
        {
        }
    }
}
namespace {
    class WPML_Admin_Texts extends \WPML_Admin_Text_Functionality
    {
        const DOMAIN_NAME_PREFIX = 'admin_texts_';
        /**
         * @param TranslationManagement   $tm_instance
         * @param WPML_String_Translation $st_instance
         */
        public function __construct(&$tm_instance, &$st_instance)
        {
        }
        public function icl_register_admin_options($array, $key = '', $option = array())
        {
        }
        public function getModelForRender()
        {
        }
        /**
         * @param Collection $options
         *
         * @return Collection
         */
        public function getModel(\WPML\Collect\Support\Collection $options)
        {
        }
        /**
         * @param Collection $flattened
         * @param array      $item
         *
         * @return Collection
         */
        public function flattenModelItems(\WPML\Collect\Support\Collection $flattened, array $item)
        {
        }
        /**
         * @param  callable $isRegistered  - string -> string -> bool.
         * @param  mixed    $value
         * @param  string   $name
         * @param  string   $key
         * @param  array    $stack
         *
         * @return array
         */
        public function getItemModel(callable $isRegistered, $value, $name, $key = '', $stack = [])
        {
        }
        public function getOptions()
        {
        }
        public function icl_st_set_admin_options_filters()
        {
        }
        /**
         * @param array $options
         */
        public function force_translate_admin_options($options)
        {
        }
        /**
         * @param string $option
         */
        public function add_filter_for($option)
        {
        }
        public function icl_st_translate_admin_string($option_value, $key = '', $name = '', $root_level = \true)
        {
        }
        /**
         * Signature: getKeys :: string [key1][key2][name] => Collection [key1, key2, name].
         *
         * @param string $option
         *
         * @return Collection
         */
        public static function getKeysParts($option)
        {
        }
        public function clear_cache_for_option($option_name)
        {
        }
        /**
         * @param string|array $old_value
         * @param string|array $value
         * @param string       $option_name
         * @param string       $name
         * @param string       $sub_key
         */
        public function on_update_original_value($old_value, $value, $option_name, $name = '', $sub_key = '')
        {
        }
        public function migrate_original_values()
        {
        }
        /**
         * Returns a function to lazy load the migration
         *
         * @return Closure
         */
        public static function get_migrator()
        {
        }
    }
    interface IWPML_ST_String_Scanner
    {
        public function scan();
    }
    class WPML_String_Scanner
    {
        const DEFAULT_DOMAIN = 'default';
        /**
         * @param string|NULL $type 'plugin' or 'theme'
         */
        protected $current_type;
        protected $current_path;
        protected $text_domain;
        /** @var WPML_ST_File_Hashing */
        protected $file_hashing;
        /**
         * WPML_String_Scanner constructor.
         *
         * @param WP_Filesystem_Base   $wp_filesystem
         * @param WPML_ST_File_Hashing $file_hashing
         */
        public function __construct(\WP_Filesystem_Base $wp_filesystem, \WPML_ST_File_Hashing $file_hashing)
        {
        }
        protected function scan_starting($scanning)
        {
        }
        protected function scan_response()
        {
        }
        protected final function init_text_domain($text_domain)
        {
        }
        protected function get_domains_found()
        {
        }
        protected function get_default_domain()
        {
        }
        protected function maybe_register_string($value, $gettext_context)
        {
        }
        protected function set_stats($key, $item)
        {
        }
        public function store_results($string, $domain, $_gettext_context, $file, $line)
        {
        }
        public function track_string($text, $context, $kind = \ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_PAGE, $file = \null, $line = \null)
        {
        }
        protected function add_stat($text)
        {
        }
        protected function get_scan_stats()
        {
        }
        protected function add_scanned_file($file)
        {
        }
        protected function get_scanned_files()
        {
        }
        protected function cleanup_wrong_contexts()
        {
        }
        protected function copy_old_translations($contexts, $prefix)
        {
        }
        protected function remove_notice($notice_id)
        {
        }
        /**
         * @return WPML_ST_DB_Mappers_Strings
         */
        public function get_strings_mapper()
        {
        }
        /**
         * @param WPML_ST_DB_Mappers_Strings $strings_mapper
         */
        public function set_strings_mapper(\WPML_ST_DB_Mappers_Strings $strings_mapper)
        {
        }
        /**
         * @return WPML_ST_DB_Mappers_String_Positions
         */
        public function get_string_positions_mapper()
        {
        }
        /**
         * @param WPML_ST_DB_Mappers_String_Positions $string_positions_mapper
         */
        public function set_string_positions_mapper(\WPML_ST_DB_Mappers_String_Positions $string_positions_mapper)
        {
        }
        /**
         * @return WPML_File_Name_Converter
         */
        public function get_file_name_converter()
        {
        }
        /**
         * @param WPML_File_Name_Converter $converter
         */
        public function set_file_name_converter(\WPML_File_Name_Converter $converter)
        {
        }
        /**
         * @return WPML_File
         */
        protected function get_wpml_file()
        {
        }
        /** @return bool */
        protected function scan_php_and_mo_files()
        {
        }
        protected function scan_only_mo_files()
        {
        }
    }
    class WPML_Theme_String_Scanner extends \WPML_String_Scanner implements \IWPML_ST_String_Scanner
    {
        public function scan()
        {
        }
    }
    class WPML_Plugin_String_Scanner extends \WPML_String_Scanner implements \IWPML_ST_String_Scanner
    {
        public function scan()
        {
        }
    }
    class WPML_PO_Import
    {
        public function __construct($file_name)
        {
        }
        public function has_strings()
        {
        }
        public function get_strings()
        {
        }
        public function get_errors()
        {
        }
    }
    class WPML_PO_Parser
    {
        public static function create_po($strings, $pot_only = \false)
        {
        }
        public static function get_po_file_header()
        {
        }
    }
    class WPML_ST_MO_Downloader
    {
        const LOCALES_XML_FILE = 'http://d2pf4b3z51hfy8.cloudfront.net/wp-locales.xml.gz';
        const CONTEXT = 'WordPress';
        function __construct()
        {
        }
        function set_lang_map_from_csv()
        {
        }
        function updates_check($args = array())
        {
        }
        function show_updates()
        {
        }
        function save_preferences()
        {
        }
        function save_settings()
        {
        }
        function get_option($name)
        {
        }
        function load_xml()
        {
        }
        function get_mo_file_urls($wplocale)
        {
        }
        function get_translation_files()
        {
        }
        function get_translations($language, $args = array())
        {
        }
        function save_translations($data, $language, $version = \false)
        {
        }
    }
    class WPML_Localization
    {
        /**
         * WPML_Localization constructor.
         *
         * @param wpdb $wpdb
         */
        public function __construct(\wpdb $wpdb)
        {
        }
        public function get_theme_localization_stats($theme_localization_domains = array())
        {
        }
        public function get_domain_stats($localization_domains, $default, $no_wordpress = \false, $count_in_progress_as_completed = \false)
        {
        }
        public function get_localization_stats($component_type)
        {
        }
        public function get_wrong_plugin_localization_stats()
        {
        }
        public function get_wrong_theme_localization_stats()
        {
        }
        public function does_theme_require_rescan()
        {
        }
        public function get_most_popular_domain($plugin)
        {
        }
    }
    class WPML_ST_String_Update
    {
        /**
         * WPML_ST_String_Update constructor.
         *
         * @param wpdb $wpdb
         */
        public function __construct(\wpdb $wpdb)
        {
        }
        /**
         * Updates an original string without changing its id or its translations
         *
         * @param string     $domain
         * @param string     $name
         * @param string     $old_value
         * @param string     $new_value
         * @param bool|false $force_complete , @see \WPML_ST_String_Update::handle_status_change
         *
         * @return int|null
         */
        public function update_string($domain, $name, $old_value, $new_value, $force_complete = \false)
        {
        }
        /**
         * @param string $string
         * @return string
         */
        function sanitize_string($string)
        {
        }
    }
    /**
     * Class WPML_String_Translation
     */
    class WPML_String_Translation
    {
        const CACHE_GROUP = 'wpml-string-translation';
        /** @var  SitePress $sitepress */
        protected $sitepress;
        /**
         * @param SitePress              $sitepress
         * @param WPML_ST_String_Factory $string_factory
         */
        public function __construct(\SitePress $sitepress, \WPML_ST_String_Factory $string_factory)
        {
        }
        /**
         * Sets up basic actions hooked by ST
         */
        public function set_basic_hooks()
        {
        }
        /**
         * Populates the internal cache for all language codes.
         *
         * @used-by WPML_String_Translation::get_string_filter to not load string filters
         *                                                     for languages that do not
         *                                                     exist.
         * @used-by WPML_String_Translation::get_admin_string_filter See above.
         */
        function init_active_languages()
        {
        }
        function load()
        {
        }
        public function admin_script_change_string_lang()
        {
        }
        public function admin_script_change_string_domain()
        {
        }
        public function admin_scripts()
        {
        }
        function init()
        {
        }
        function plugin_localization()
        {
        }
        /**
         * @param string       $context
         * @param string       $name
         * @param string|false $original_value
         * @param boolean|null $has_translation
         * @param null|string  $target_lang
         *
         * @return string|bool
         * @since 2.2.3
         *
         */
        function translate_string($context, $name, $original_value = \false, &$has_translation = \null, $target_lang = \null)
        {
        }
        function add_message($text, $type = 'updated')
        {
        }
        function show_messages()
        {
        }
        function ajax_calls($call, $data)
        {
        }
        /**
         * @param string $menu_id
         */
        function menu($menu_id)
        {
        }
        function plugin_action_links($links, $file)
        {
        }
        public function localization_type_ui()
        {
        }
        function scan_theme_for_strings()
        {
        }
        function scan_plugins_for_strings()
        {
        }
        function plugin_po_file_download($file = \false, $recursion = 0)
        {
        }
        /**
         * @param string $string value of a string
         * @param string $lang_code language code of the string
         *
         * @return int number of words in the string
         */
        public function estimate_word_count($string, $lang_code)
        {
        }
        function cancel_remote_translation($rid)
        {
        }
        function cancel_local_translation($id, $return_original_id = \false)
        {
        }
        /**
         * @param string $value
         * @param string $old_value
         *
         * @return array|string
         */
        function pre_update_option_blogname($value, $old_value)
        {
        }
        /**
         * @param string $value
         * @param string $old_value
         *
         * @return array|string
         */
        function pre_update_option_blogdescription($value, $old_value)
        {
        }
        /**
         * @param string       $option name of the option
         * @param string|array $value new value of the option
         * @param string|array $old_value currently saved value for the option
         *
         * @return string|array the value actually to be written into the wp_options table
         */
        function pre_update_option_settings($option, $value, $old_value)
        {
        }
        /**
         * Instantiates a new admin option translation object
         *
         * @param string $option_name
         * @param string $language_code
         *
         * @return WPML_ST_Admin_Option_Translation
         */
        public function get_admin_option($option_name, $language_code = '')
        {
        }
        /**
         * @return WPML_ST_String_Factory
         */
        public function string_factory()
        {
        }
        /**
         * @param string $lang_code
         */
        public function clear_string_filter($lang_code)
        {
        }
        /**
         * @param string $lang
         *
         * @return WPML_Displayed_String_Filter
         */
        public function get_string_filter($lang)
        {
        }
        /**
         * @param string $lang
         *
         * @return mixed|\WPML_Register_String_Filter|null
         * @throws \WPML\Auryn\InjectionException
         */
        public function get_admin_string_filter($lang)
        {
        }
        /**
         * @deprecated 3.3 - Each string has its own language now.
         */
        public function get_strings_language($language = '')
        {
        }
        public function delete_all_string_data($string_id)
        {
        }
        public function get_strings_settings()
        {
        }
        /**
         * @param null $empty   Not used, but needed for the hooked filter
         * @param int  $string_id
         *
         * @return null|string
         */
        public function get_string_status_filter($empty = \null, $string_id = 0)
        {
        }
        /**
         * @param int|null $default     Set the default value to return in case no string or more than one string is found
         * @param array    $string_data {
         *
         * @type string    $context
         * @type string    $name        Optional
         *                           }
         * @return int|null If there is more than one string_id, it will return the value set in $default.
         */
        public function get_string_id_filter($default = \null, $string_data = array())
        {
        }
        /**
         * @param null   $empty   Not used, but needed for the hooked filter
         * @param string $domain
         * @param string $name
         *
         * @return null|string
         */
        public function get_string_language_filter($empty = \null, $domain = '', $name = '')
        {
        }
        /**
         * @param WPML_WP_Cache $cache
         */
        public function set_cache(\WPML_WP_Cache $cache)
        {
        }
        /**
         * @return WPML_WP_Cache
         */
        public function get_cache()
        {
        }
        function check_db_for_gettext_context()
        {
        }
        public function initialize_wp_and_widget_strings()
        {
        }
        /**
         * Returns the language the current string is to be translated into.
         *
         * @param string|bool|null $name
         *
         * @return string
         */
        public function get_current_string_language($name)
        {
        }
        public function should_use_admin_language()
        {
        }
        /**
         * @return string
         */
        public function get_admin_language()
        {
        }
        public function wpml_language_has_switched()
        {
        }
        public function change_string_lang_ajax_callback()
        {
        }
        public function change_string_lang_of_domain_ajax_callback()
        {
        }
    }
    // autoload_real.php @generated by Composer
    class ComposerAutoloaderInit290c5107dfb4a496f808712301ed7f86
    {
        public static function loadClassLoader($class)
        {
        }
        /**
         * @return \Composer\Autoload\ClassLoader
         */
        public static function getLoader()
        {
        }
    }
}
namespace Composer\Autoload {
    class ComposerStaticInit290c5107dfb4a496f808712301ed7f86
    {
        public static $classMap = array('Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php', 'IWPML_ST_Rewrite_Rule_Filter' => __DIR__ . '/../..' . '/classes/slug-translation/iwpml-st-rewrite-rule-filter.php', 'IWPML_ST_String_Scanner' => __DIR__ . '/../..' . '/classes/strings-scanning/iwpml-st-string-scanner.php', 'IWPML_ST_Translations_File' => __DIR__ . '/../..' . '/classes/translations-file-scan/translations-file/iwpml-st-translations-file.php', 'IWPML_St_Upgrade_Command' => __DIR__ . '/../..' . '/classes/upgrade/interface-iwpml_st_upgrade_command.php', 'WPML\\Ajax\\ST\\AdminText\\Register' => __DIR__ . '/../..' . '/inc/admin-texts/Register.php', 'WPML\\ST\\API\\Fns' => __DIR__ . '/..' . '/wpml/st-api/core/Fns.php', 'WPML\\ST\\Actions' => __DIR__ . '/../..' . '/classes/actions/Actions.php', 'WPML\\ST\\AdminTexts\\UI' => __DIR__ . '/../..' . '/inc/admin-texts/UI.php', 'WPML\\ST\\Basket\\Status' => __DIR__ . '/../..' . '/classes/basket/Status.php', 'WPML\\ST\\Batch\\Translation\\Convert' => __DIR__ . '/../..' . '/classes/batch-translation/Convert.php', 'WPML\\ST\\Batch\\Translation\\Hooks' => __DIR__ . '/../..' . '/classes/batch-translation/Hooks.php', 'WPML\\ST\\Batch\\Translation\\Module' => __DIR__ . '/../..' . '/classes/batch-translation/Module.php', 'WPML\\ST\\Batch\\Translation\\Records' => __DIR__ . '/../..' . '/classes/batch-translation/Records.php', 'WPML\\ST\\Batch\\Translation\\Status' => __DIR__ . '/../..' . '/classes/batch-translation/Status.php', 'WPML\\ST\\Batch\\Translation\\StringTranslations' => __DIR__ . '/../..' . '/classes/batch-translation/StringTranslations.php', 'WPML\\ST\\Batch\\Translation\\Strings' => __DIR__ . '/../..' . '/classes/batch-translation/Strings.php', 'WPML\\ST\\Container\\Config' => __DIR__ . '/../..' . '/classes/container/Config.php', 'WPML\\ST\\DB\\Mappers\\DomainsRepository' => __DIR__ . '/../..' . '/classes/db-mappers/DomainsRepository.php', 'WPML\\ST\\DB\\Mappers\\Hooks' => __DIR__ . '/../..' . '/classes/db-mappers/Hooks.php', 'WPML\\ST\\DB\\Mappers\\StringTranslations' => __DIR__ . '/../..' . '/classes/db-mappers/StringTranslations.php', 'WPML\\ST\\DB\\Mappers\\StringsRetrieve' => __DIR__ . '/../..' . '/classes/db-mappers/StringsRetrieve.php', 'WPML\\ST\\DB\\Mappers\\Update' => __DIR__ . '/../..' . '/classes/db-mappers/Update.php', 'WPML\\ST\\DisplayAsTranslated\\CheckRedirect' => __DIR__ . '/../..' . '/classes/slug-translation/CheckRedirect.php', 'WPML\\ST\\Gettext\\AutoRegisterSettings' => __DIR__ . '/../..' . '/classes/gettext-hooks/AutoRegisterSettings.php', 'WPML\\ST\\Gettext\\Filters\\IFilter' => __DIR__ . '/../..' . '/classes/gettext-hooks/filters/IFilter.php', 'WPML\\ST\\Gettext\\Filters\\StringHighlighting' => __DIR__ . '/../..' . '/classes/gettext-hooks/filters/StringHighlighting.php', 'WPML\\ST\\Gettext\\Filters\\StringTracking' => __DIR__ . '/../..' . '/classes/gettext-hooks/filters/StringTracking.php', 'WPML\\ST\\Gettext\\Filters\\StringTranslation' => __DIR__ . '/../..' . '/classes/gettext-hooks/filters/StringTranslation.php', 'WPML\\ST\\Gettext\\Hooks' => __DIR__ . '/../..' . '/classes/gettext-hooks/Hooks.php', 'WPML\\ST\\Gettext\\HooksFactory' => __DIR__ . '/../..' . '/classes/gettext-hooks/HooksFactory.php', 'WPML\\ST\\Gettext\\Settings' => __DIR__ . '/../..' . '/classes/gettext-hooks/Settings.php', 'WPML\\ST\\JED\\Hooks\\Sync' => __DIR__ . '/../..' . '/classes/translation-files/jed/Hooks/Sync.php', 'WPML\\ST\\MO\\File\\Builder' => __DIR__ . '/../..' . '/classes/MO/File/Builder.php', 'WPML\\ST\\MO\\File\\FailureHooks' => __DIR__ . '/../..' . '/classes/MO/File/FailureHooks.php', 'WPML\\ST\\MO\\File\\FailureHooksFactory' => __DIR__ . '/../..' . '/classes/MO/File/FailureHooksFactory.php', 'WPML\\ST\\MO\\File\\Generator' => __DIR__ . '/../..' . '/classes/MO/File/Generator.php', 'WPML\\ST\\MO\\File\\MOFactory' => __DIR__ . '/../..' . '/classes/MO/File/MOFactory.php', 'WPML\\ST\\MO\\File\\Manager' => __DIR__ . '/../..' . '/classes/MO/File/Manager.php', 'WPML\\ST\\MO\\File\\ManagerFactory' => __DIR__ . '/../..' . '/classes/MO/File/ManagerFactory.php', 'WPML\\ST\\MO\\File\\makeDir' => __DIR__ . '/../..' . '/classes/MO/File/makeDir.php', 'WPML\\ST\\MO\\Generate\\DomainsAndLanguagesRepository' => __DIR__ . '/../..' . '/classes/MO/Generate/DomainsAndLanguagesRepository.php', 'WPML\\ST\\MO\\Generate\\MissingMOFile' => __DIR__ . '/../..' . '/classes/MO/Generate/GenerateMissingMOFile.php', 'WPML\\ST\\MO\\Generate\\MultiSite\\Condition' => __DIR__ . '/../..' . '/classes/MO/Generate/MultiSite/Condition.php', 'WPML\\ST\\MO\\Generate\\MultiSite\\Executor' => __DIR__ . '/../..' . '/classes/MO/Generate/MultiSite/Executor.php', 'WPML\\ST\\MO\\Generate\\Process\\MultiSiteProcess' => __DIR__ . '/../..' . '/classes/MO/Generate/Process/MultiSiteProcess.php', 'WPML\\ST\\MO\\Generate\\Process\\Process' => __DIR__ . '/../..' . '/classes/MO/Generate/Process/Process.php', 'WPML\\ST\\MO\\Generate\\Process\\ProcessFactory' => __DIR__ . '/../..' . '/classes/MO/Generate/Process/ProcessFactory.php', 'WPML\\ST\\MO\\Generate\\Process\\SingleSiteProcess' => __DIR__ . '/../..' . '/classes/MO/Generate/Process/SingleSiteProcess.php', 'WPML\\ST\\MO\\Generate\\Process\\Status' => __DIR__ . '/../..' . '/classes/MO/Generate/Process/Status.php', 'WPML\\ST\\MO\\Generate\\Process\\SubSiteValidator' => __DIR__ . '/../..' . '/classes/MO/Generate/Process/SubSiteValidator.php', 'WPML\\ST\\MO\\Generate\\StringsRetrieveMOOriginals' => __DIR__ . '/../..' . '/classes/MO/Generate/StringsRetrieveMOOriginals.php', 'WPML\\ST\\MO\\Hooks\\CustomTextDomains' => __DIR__ . '/../..' . '/classes/MO/Hooks/CustomTextDomains.php', 'WPML\\ST\\MO\\Hooks\\DetectPrematurelyTranslatedStrings' => __DIR__ . '/../..' . '/classes/MO/Hooks/DetectPrematurelyTranslatedStrings.php', 'WPML\\ST\\MO\\Hooks\\Factory' => __DIR__ . '/../..' . '/classes/MO/Hooks/Factory.php', 'WPML\\ST\\MO\\Hooks\\LanguageSwitch' => __DIR__ . '/../..' . '/classes/MO/Hooks/LanguageSwitch.php', 'WPML\\ST\\MO\\Hooks\\LoadMissingMOFiles' => __DIR__ . '/../..' . '/classes/MO/Hooks/LoadMissingMOFiles.php', 'WPML\\ST\\MO\\Hooks\\LoadTextDomain' => __DIR__ . '/../..' . '/classes/MO/Hooks/LoadTextDomain.php', 'WPML\\ST\\MO\\Hooks\\PreloadThemeMoFile' => __DIR__ . '/../..' . '/classes/MO/Hooks/PreloadThemeMoFile.php', 'WPML\\ST\\MO\\Hooks\\StringsLanguageChanged' => __DIR__ . '/../..' . '/classes/MO/Hooks/StringsLanguageChanged.php', 'WPML\\ST\\MO\\Hooks\\Sync' => __DIR__ . '/../..' . '/classes/MO/Hooks/Sync.php', 'WPML\\ST\\MO\\JustInTime\\DefaultMO' => __DIR__ . '/../..' . '/classes/MO/JustInTime/DefaultMO.php', 'WPML\\ST\\MO\\JustInTime\\MO' => __DIR__ . '/../..' . '/classes/MO/JustInTime/MO.php', 'WPML\\ST\\MO\\JustInTime\\MOFactory' => __DIR__ . '/../..' . '/classes/MO/JustInTime/MOFactory.php', 'WPML\\ST\\MO\\LoadedMODictionary' => __DIR__ . '/../..' . '/classes/MO/LoadedMODictionary.php', 'WPML\\ST\\MO\\Notice\\RegenerationInProgressNotice' => __DIR__ . '/../..' . '/classes/MO/Notice/RegenerationInProgressNotice.php', 'WPML\\ST\\MO\\Plural' => __DIR__ . '/../..' . '/classes/MO/Plural.php', 'WPML\\ST\\MO\\Scan\\UI\\Factory' => __DIR__ . '/../..' . '/classes/translations-file-scan/UI/Factory.php', 'WPML\\ST\\MO\\Scan\\UI\\InstalledComponents' => __DIR__ . '/../..' . '/classes/translations-file-scan/UI/InstalledComponents.php', 'WPML\\ST\\MO\\Scan\\UI\\Model' => __DIR__ . '/../..' . '/classes/translations-file-scan/UI/Model.php', 'WPML\\ST\\MO\\Scan\\UI\\UI' => __DIR__ . '/../..' . '/classes/translations-file-scan/UI/UI.php', 'WPML\\ST\\MO\\WPLocaleProxy' => __DIR__ . '/../..' . '/classes/MO/WPLocaleProxy.php', 'WPML\\ST\\Main\\Ajax\\FetchCompletedStrings' => __DIR__ . '/../..' . '/classes/string-translation-ui/ajax/FetchCompletedStrings.php', 'WPML\\ST\\Main\\Ajax\\FetchTranslationMemory' => __DIR__ . '/../..' . '/classes/translation-memory/FetchTranslationMemory.php', 'WPML\\ST\\Main\\Ajax\\SaveTranslation' => __DIR__ . '/../..' . '/classes/string-translation-ui/ajax/SaveTranslation.php', 'WPML\\ST\\Main\\UI' => __DIR__ . '/../..' . '/classes/string-translation-ui/UI.php', 'WPML\\ST\\PackageTranslation\\Assign' => __DIR__ . '/../..' . '/classes/package-translation/Assign.php', 'WPML\\ST\\PackageTranslation\\Hooks' => __DIR__ . '/../..' . '/classes/package-translation/Hooks.php', 'WPML\\ST\\Package\\Domains' => __DIR__ . '/../..' . '/classes/package/class-domains.php', 'WPML\\ST\\Rest\\Base' => __DIR__ . '/../..' . '/classes/API/rest/Base.php', 'WPML\\ST\\Rest\\FactoryLoader' => __DIR__ . '/../..' . '/classes/API/rest/FactoryLoader.php', 'WPML\\ST\\Rest\\MO\\Import' => __DIR__ . '/../..' . '/classes/API/rest/mo/Import.php', 'WPML\\ST\\Rest\\MO\\PreGenerate' => __DIR__ . '/../..' . '/classes/API/rest/mo/PreGenerate.php', 'WPML\\ST\\Rest\\Settings' => __DIR__ . '/../..' . '/classes/API/rest/settings/Settings.php', 'WPML\\ST\\Shortcode' => __DIR__ . '/../..' . '/classes/Shortcode.php', 'WPML\\ST\\Shortcode\\Hooks' => __DIR__ . '/../..' . '/classes/shortcode/Hooks.php', 'WPML\\ST\\Shortcode\\LensFactory' => __DIR__ . '/../..' . '/classes/shortcode/LensFactory.php', 'WPML\\ST\\Shortcode\\TranslationHandler' => __DIR__ . '/../..' . '/classes/shortcode/TranslationHandler.php', 'WPML\\ST\\SlugTranslation\\Hooks\\Hooks' => __DIR__ . '/../..' . '/classes/slug-translation/RewriteRules/Hooks.php', 'WPML\\ST\\SlugTranslation\\Hooks\\HooksFactory' => __DIR__ . '/../..' . '/classes/slug-translation/RewriteRules/HooksFactory.php', 'WPML\\ST\\Storage\\StoragePerLanguageInterface' => __DIR__ . '/../..' . '/classes/Storage/StoragePerLanguageInterface.php', 'WPML\\ST\\Storage\\WpTransientPerLanguage' => __DIR__ . '/../..' . '/classes/Storage/WpTransientPerLanguage.php', 'WPML\\ST\\StringsCleanup\\Ajax\\InitStringsRemoving' => __DIR__ . '/../..' . '/classes/strings-cleanup/ajax/InitStringsRemoving.php', 'WPML\\ST\\StringsCleanup\\Ajax\\RemoveStringsFromDomains' => __DIR__ . '/../..' . '/classes/strings-cleanup/ajax/RemoveStringsFromDomains.php', 'WPML\\ST\\StringsCleanup\\UI' => __DIR__ . '/../..' . '/classes/strings-cleanup/UI.php', 'WPML\\ST\\StringsCleanup\\UntranslatedStrings' => __DIR__ . '/../..' . '/classes/strings-cleanup/UntranslatedStrings.php', 'WPML\\ST\\StringsFilter\\Provider' => __DIR__ . '/../..' . '/classes/filters/strings-filter/Provider.php', 'WPML\\ST\\StringsFilter\\QueryBuilder' => __DIR__ . '/../..' . '/classes/filters/strings-filter/QueryBuilder.php', 'WPML\\ST\\StringsFilter\\StringEntity' => __DIR__ . '/../..' . '/classes/filters/strings-filter/StringEntity.php', 'WPML\\ST\\StringsFilter\\TranslationEntity' => __DIR__ . '/../..' . '/classes/filters/strings-filter/TranslationEntity.php', 'WPML\\ST\\StringsFilter\\TranslationReceiver' => __DIR__ . '/../..' . '/classes/filters/strings-filter/TranslationReceiver.php', 'WPML\\ST\\StringsFilter\\Translations' => __DIR__ . '/../..' . '/classes/filters/strings-filter/Translations.php', 'WPML\\ST\\StringsFilter\\TranslationsObjectStorage' => __DIR__ . '/../..' . '/classes/filters/strings-filter/TranslationsObjectStorage.php', 'WPML\\ST\\StringsFilter\\Translator' => __DIR__ . '/../..' . '/classes/filters/strings-filter/Translator.php', 'WPML\\ST\\TranslateWpmlString' => __DIR__ . '/../..' . '/classes/TranslateWpmlString.php', 'WPML\\ST\\TranslationFile\\Builder' => __DIR__ . '/../..' . '/classes/translation-files/Builder.php', 'WPML\\ST\\TranslationFile\\Domains' => __DIR__ . '/../..' . '/classes/translation-files/Domains.php', 'WPML\\ST\\TranslationFile\\DomainsLocalesMapper' => __DIR__ . '/../..' . '/classes/translation-files/DomainsLocalesMapper.php', 'WPML\\ST\\TranslationFile\\EntryQueries' => __DIR__ . '/../..' . '/classes/translations-file-scan/EntryQueries.php', 'WPML\\ST\\TranslationFile\\Hooks' => __DIR__ . '/../..' . '/classes/translation-files/Hooks.php', 'WPML\\ST\\TranslationFile\\Manager' => __DIR__ . '/../..' . '/classes/translation-files/Manager.php', 'WPML\\ST\\TranslationFile\\QueueFilter' => __DIR__ . '/../..' . '/classes/translations-file-scan/QueueFilter.php', 'WPML\\ST\\TranslationFile\\StringEntity' => __DIR__ . '/../..' . '/classes/translation-files/StringEntity.php', 'WPML\\ST\\TranslationFile\\StringsRetrieve' => __DIR__ . '/../..' . '/classes/translation-files/StringsRetrieve.php', 'WPML\\ST\\TranslationFile\\Sync\\FileSync' => __DIR__ . '/../..' . '/classes/translation-files/Sync/FileSync.php', 'WPML\\ST\\TranslationFile\\Sync\\TranslationUpdates' => __DIR__ . '/../..' . '/classes/translation-files/Sync/TranslationUpdates.php', 'WPML\\ST\\TranslationFile\\UpdateHooks' => __DIR__ . '/../..' . '/classes/translation-files/UpdateHooks.php', 'WPML\\ST\\TranslationFile\\UpdateHooksFactory' => __DIR__ . '/../..' . '/classes/translation-files/UpdateHooksFactory.php', 'WPML\\ST\\Troubleshooting\\AjaxFactory' => __DIR__ . '/../..' . '/classes/Troubleshooting/AjaxFactory.php', 'WPML\\ST\\Troubleshooting\\BackendHooks' => __DIR__ . '/../..' . '/classes/Troubleshooting/BackendHooks.php', 'WPML\\ST\\Troubleshooting\\Cleanup\\Database' => __DIR__ . '/../..' . '/classes/Troubleshooting/Cleanup/Database.php', 'WPML\\ST\\Troubleshooting\\RequestHandle' => __DIR__ . '/../..' . '/classes/Troubleshooting/RequestHandle.php', 'WPML\\ST\\Upgrade\\Command\\MigrateMultilingualWidgets' => __DIR__ . '/../..' . '/classes/upgrade/Command/MigrateMultilingualWidgets.php', 'WPML\\ST\\Upgrade\\Command\\RegenerateMoFilesWithStringNames' => __DIR__ . '/../..' . '/classes/upgrade/Command/RegenerateMoFilesWithStringNames.php', 'WPML\\ST\\Utils\\LanguageResolution' => __DIR__ . '/../..' . '/classes/utilities/LanguageResolution.php', 'WPML\\ST\\WP\\App\\Resources' => __DIR__ . '/../..' . '/classes/utilities/Resources.php', 'WPML_Admin_Notifier' => __DIR__ . '/../..' . '/classes/class-wpml-admin-notifier.php', 'WPML_Admin_Text_Configuration' => __DIR__ . '/../..' . '/inc/admin-texts/wpml-admin-text-configuration.php', 'WPML_Admin_Text_Functionality' => __DIR__ . '/../..' . '/inc/admin-texts/wpml-admin-text-functionality.class.php', 'WPML_Admin_Text_Import' => __DIR__ . '/../..' . '/inc/admin-texts/wpml-admin-text-import.class.php', 'WPML_Admin_Texts' => __DIR__ . '/../..' . '/inc/admin-texts/wpml-admin-texts.class.php', 'WPML_Autoregister_Save_Strings' => __DIR__ . '/../..' . '/classes/filters/autoregister/class-wpml-autoregister-save-strings.php', 'WPML_Change_String_Domain_Language_Dialog' => __DIR__ . '/../..' . '/classes/string-translation-ui/class-wpml-change-string-domain-language-dialog.php', 'WPML_Change_String_Language_Select' => __DIR__ . '/../..' . '/classes/string-translation-ui/class-wpml-change-string-language-select.php', 'WPML_Core_Version_Check' => __DIR__ . '/..' . '/wpml-shared/wpml-lib-dependencies/src/dependencies/class-wpml-core-version-check.php', 'WPML_Dependencies' => __DIR__ . '/..' . '/wpml-shared/wpml-lib-dependencies/src/dependencies/class-wpml-dependencies.php', 'WPML_Displayed_String_Filter' => __DIR__ . '/../..' . '/classes/filters/strings-filter/class-wpml-displayed-string-filter.php', 'WPML_File_Name_Converter' => __DIR__ . '/../..' . '/classes/strings-scanning/class-wpml-file-name-converter.php', 'WPML_Language_Of_Domain' => __DIR__ . '/../..' . '/classes/class-wpml-language-of-domain.php', 'WPML_Localization' => __DIR__ . '/../..' . '/inc/wpml-localization.class.php', 'WPML_PHP_Version_Check' => __DIR__ . '/..' . '/wpml-shared/wpml-lib-dependencies/src/dependencies/class-wpml-php-version-check.php', 'WPML_PO_Import' => __DIR__ . '/../..' . '/inc/gettext/wpml-po-import.class.php', 'WPML_PO_Import_Strings' => __DIR__ . '/../..' . '/classes/po-import/class-wpml-po-import-strings.php', 'WPML_PO_Import_Strings_Scripts' => __DIR__ . '/../..' . '/classes/po-import/class-wpml-po-import-strings-scripts.php', 'WPML_PO_Parser' => __DIR__ . '/../..' . '/inc/gettext/wpml-po-parser.class.php', 'WPML_Package' => __DIR__ . '/../..' . '/inc/package-translation/inc/wpml-package.class.php', 'WPML_Package_Admin_Lang_Switcher' => __DIR__ . '/../..' . '/inc/package-translation/inc/wpml-package-admin-lang-switcher.class.php', 'WPML_Package_Exception' => __DIR__ . '/../..' . '/inc/package-translation/inc/wpml-package-translation-exception.class.php', 'WPML_Package_Helper' => __DIR__ . '/../..' . '/inc/package-translation/inc/wpml-package-translation-helper.class.php', 'WPML_Package_ST' => __DIR__ . '/../..' . '/inc/package-translation/inc/wpml-package-translation-st.class.php', 'WPML_Package_TM' => __DIR__ . '/../..' . '/inc/package-translation/inc/wpml-package-translation-tm.class.php', 'WPML_Package_TM_Jobs' => __DIR__ . '/../..' . '/inc/package-translation/inc/wpml-package-translation-tm-jobs.class.php', 'WPML_Package_Translation' => __DIR__ . '/../..' . '/inc/package-translation/inc/wpml-package-translation.class.php', 'WPML_Package_Translation_HTML_Packages' => __DIR__ . '/../..' . '/inc/package-translation/inc/wpml-package-translation-html-packages.class.php', 'WPML_Package_Translation_Metabox' => __DIR__ . '/../..' . '/inc/package-translation/inc/wpml-package-translation-metabox.class.php', 'WPML_Package_Translation_Schema' => __DIR__ . '/../..' . '/inc/package-translation/inc/wpml-package-translation-schema.class.php', 'WPML_Package_Translation_UI' => __DIR__ . '/../..' . '/inc/package-translation/inc/wpml-package-translation-ui.class.php', 'WPML_Plugin_String_Scanner' => __DIR__ . '/../..' . '/inc/gettext/wpml-plugin-string-scanner.class.php', 'WPML_Post_Slug_Translation_Records' => __DIR__ . '/../..' . '/classes/slug-translation/post/wpml-post-slug-translation-records.php', 'WPML_Register_String_Filter' => __DIR__ . '/../..' . '/classes/filters/strings-filter/class-wpml-register-string-filter.php', 'WPML_Rewrite_Rule_Filter' => __DIR__ . '/../..' . '/classes/slug-translation/class-wpml-rewrite-rule-filter.php', 'WPML_Rewrite_Rule_Filter_Factory' => __DIR__ . '/../..' . '/classes/slug-translation/wpml-rewrite-rule-filter-factory.php', 'WPML_ST_Admin_Blog_Option' => __DIR__ . '/../..' . '/classes/admin-texts/class-wpml-st-admin-blog-option.php', 'WPML_ST_Admin_Option_Translation' => __DIR__ . '/../..' . '/classes/admin-texts/class-wpml-st-admin-option-translation.php', 'WPML_ST_Admin_String' => __DIR__ . '/../..' . '/classes/class-wpml-st-admin-string.php', 'WPML_ST_Blog_Name_And_Description_Hooks' => __DIR__ . '/../..' . '/classes/filters/class-wpml-st-blog-name-and-description-hooks.php', 'WPML_ST_Bulk_Strings_Insert' => __DIR__ . '/../..' . '/classes/db-mappers/class-wpml-st-bulk-strings-insert.php', 'WPML_ST_Bulk_Strings_Insert_Exception' => __DIR__ . '/../..' . '/classes/db-mappers/class-wpml-st-bulk-strings-insert.php', 'WPML_ST_Bulk_Update_Strings_Status' => __DIR__ . '/../..' . '/classes/db-mappers/class-wpml-st-bulk-update-strings-status.php', 'WPML_ST_DB_Mappers_String_Positions' => __DIR__ . '/../..' . '/classes/db-mappers/class-wpml-st-db-mappers-string-positions.php', 'WPML_ST_DB_Mappers_Strings' => __DIR__ . '/../..' . '/classes/db-mappers/class-wpml-st-db-mappers-strings.php', 'WPML_ST_Element_Slug_Translation_UI' => __DIR__ . '/../..' . '/classes/slug-translation/wpml-st-element-slug-translation-ui.php', 'WPML_ST_Element_Slug_Translation_UI_Model' => __DIR__ . '/../..' . '/classes/slug-translation/wpml-st-element-slug-translation-ui-model.php', 'WPML_ST_File_Hashing' => __DIR__ . '/../..' . '/classes/strings-scanning/class-wpml-st-file-hashing.php', 'WPML_ST_ICL_String_Translations' => __DIR__ . '/../..' . '/classes/records/class-wpml-st-icl-string-translations.php', 'WPML_ST_ICL_Strings' => __DIR__ . '/../..' . '/classes/records/class-wpml-st-icl-strings.php', 'WPML_ST_Initialize' => __DIR__ . '/../..' . '/classes/class-wpml-st-initialize.php', 'WPML_ST_JED_Domain' => __DIR__ . '/../..' . '/classes/translation-files/jed/wpml-st-jed-domain.php', 'WPML_ST_JED_File_Builder' => __DIR__ . '/../..' . '/classes/translation-files/jed/wpml-st-jed-file-builder.php', 'WPML_ST_JED_File_Manager' => __DIR__ . '/../..' . '/classes/translation-files/jed/wpml-st-jed-file-manager.php', 'WPML_ST_MO_Downloader' => __DIR__ . '/../..' . '/inc/auto-download-locales.php', 'WPML_ST_Models_String' => __DIR__ . '/../..' . '/classes/db-mappers/class-wpml-st-models-string.php', 'WPML_ST_Models_String_Translation' => __DIR__ . '/../..' . '/classes/db-mappers/class-wpml-st-models-string-translation.php', 'WPML_ST_Package_Cleanup' => __DIR__ . '/../..' . '/classes/package-translation/class-wpml-st-package-cleanup.php', 'WPML_ST_Package_Factory' => __DIR__ . '/../..' . '/inc/package-translation/inc/wpml-package-factory.class.php', 'WPML_ST_Package_Storage' => __DIR__ . '/../..' . '/classes/package-translation/class-wpml-st-package-storage.php', 'WPML_ST_Plugin_Localization_UI' => __DIR__ . '/../..' . '/classes/menus/theme-plugin-localization-ui/strategy/class-wpml-st-plugin-localization-ui.php', 'WPML_ST_Plugin_Localization_UI_Factory' => __DIR__ . '/../..' . '/classes/menus/theme-plugin-localization-ui/factory/class-wpml-st-plugin-localization-ui-factory.php', 'WPML_ST_Plugin_Localization_Utils' => __DIR__ . '/../..' . '/classes/menus/theme-plugin-localization-ui/class-st-plugin-localization-ui-utils.php', 'WPML_ST_Plugin_String_Scanner_Factory' => __DIR__ . '/../..' . '/classes/strings-scanning/factory/class-wpml-st-plugin-string-scanner-factory.php', 'WPML_ST_Post_Slug_Translation_Settings' => __DIR__ . '/../..' . '/classes/slug-translation/post/wpml-st-post-slug-translation-settings.php', 'WPML_ST_Privacy_Content' => __DIR__ . '/../..' . '/classes/privacy/class-wpml-st-privacy-content.php', 'WPML_ST_Privacy_Content_Factory' => __DIR__ . '/../..' . '/classes/privacy/class-wpml-st-privacy-content-factory.php', 'WPML_ST_Records' => __DIR__ . '/../..' . '/classes/records/class-wpml-st-records.php', 'WPML_ST_Remote_String_Translation_Factory' => __DIR__ . '/../..' . '/classes/actions/class-wpml-st-remote-string-translation-factory.php', 'WPML_ST_Repair_Strings_Schema' => __DIR__ . '/../..' . '/classes/upgrade/repair-schema/wpml-st-repair-strings-schema.php', 'WPML_ST_Reset' => __DIR__ . '/../..' . '/classes/class-wpml-st-reset.php', 'WPML_ST_Scan_Dir' => __DIR__ . '/../..' . '/classes/utilities/wpml-st-scan-dir.php', 'WPML_ST_Script_Translations_Hooks' => __DIR__ . '/../..' . '/classes/translation-files/jed/wpml-st-script-translations-hooks.php', 'WPML_ST_Script_Translations_Hooks_Factory' => __DIR__ . '/../..' . '/classes/translation-files/jed/wpml-st-script-translations-hooks-factory.php', 'WPML_ST_Settings' => __DIR__ . '/../..' . '/classes/class-wpml-st-settings.php', 'WPML_ST_Slug' => __DIR__ . '/../..' . '/classes/slug-translation/wpml-st-slug.php', 'WPML_ST_Slug_Custom_Type' => __DIR__ . '/../..' . '/classes/slug-translation/custom-types/wpml-st-slug-custom-type.php', 'WPML_ST_Slug_Custom_Type_Factory' => __DIR__ . '/../..' . '/classes/slug-translation/custom-types/wpml-st-slug-custom-type-factory.php', 'WPML_ST_Slug_New_Match' => __DIR__ . '/../..' . '/classes/slug-translation/new-match-finder/wpml-st-slug-new-match.php', 'WPML_ST_Slug_New_Match_Finder' => __DIR__ . '/../..' . '/classes/slug-translation/new-match-finder/wpml-st-slug-new-match-finder.php', 'WPML_ST_Slug_Translation_API' => __DIR__ . '/../..' . '/classes/slug-translation/wpml-st-slug-translation-api.php', 'WPML_ST_Slug_Translation_Custom_Types_Repository' => __DIR__ . '/../..' . '/classes/slug-translation/custom-types/wpml-st-slug-translation-custom-types-repository.php', 'WPML_ST_Slug_Translation_Post_Custom_Types_Repository' => __DIR__ . '/../..' . '/classes/slug-translation/custom-types/wpml-st-slug-translation-post-custom-types-repository.php', 'WPML_ST_Slug_Translation_Settings' => __DIR__ . '/../..' . '/classes/slug-translation/wpml-st-slug-translation-settings.php', 'WPML_ST_Slug_Translation_Settings_Factory' => __DIR__ . '/../..' . '/classes/slug-translation/wpml-st-slug-translation-settings-factory.php', 'WPML_ST_Slug_Translation_Strings_Sync' => __DIR__ . '/../..' . '/classes/slug-translation/wpml-st-slug-translation-strings-sync.php', 'WPML_ST_Slug_Translation_Taxonomy_Custom_Types_Repository' => __DIR__ . '/../..' . '/classes/slug-translation/custom-types/wpml-st-slug-translation-taxonomy-custom-types-repository.php', 'WPML_ST_Slug_Translation_UI_Factory' => __DIR__ . '/../..' . '/classes/slug-translation/wpml-st-slug-translation-ui-factory.php', 'WPML_ST_Slug_Translation_UI_Save' => __DIR__ . '/../..' . '/classes/slug-translation/wpml-st-slug-translation-ui-save.php', 'WPML_ST_Slug_Translations' => __DIR__ . '/../..' . '/classes/slug-translation/custom-types/wpml-st-slug-translations.php', 'WPML_ST_String' => __DIR__ . '/../..' . '/classes/class-wpml-st-string.php', 'WPML_ST_String_Dependencies_Builder' => __DIR__ . '/../..' . '/classes/utilities/string-dependencies/wpml-st-string-dependencies-builder.php', 'WPML_ST_String_Dependencies_Node' => __DIR__ . '/../..' . '/classes/utilities/string-dependencies/wpml-st-string-dependencies-node.php', 'WPML_ST_String_Dependencies_Records' => __DIR__ . '/../..' . '/classes/utilities/string-dependencies/wpml-st-string-dependencies-records.php', 'WPML_ST_String_Factory' => __DIR__ . '/../..' . '/classes/class-wpml-st-string-factory.php', 'WPML_ST_String_Positions' => __DIR__ . '/../..' . '/classes/string-tracking/class-wpml-st-string-positions.php', 'WPML_ST_String_Positions_In_Page' => __DIR__ . '/../..' . '/classes/string-tracking/class-wpml-st-string-positions-in-page.php', 'WPML_ST_String_Positions_In_Source' => __DIR__ . '/../..' . '/classes/string-tracking/class-wpml-st-string-positions-in-source.php', 'WPML_ST_String_Statuses' => __DIR__ . '/../..' . '/classes/class-wpml-st-string-statuses.php', 'WPML_ST_String_Tracking_AJAX' => __DIR__ . '/../..' . '/classes/string-tracking/class-wpml-st-string-tracking-ajax.php', 'WPML_ST_String_Tracking_AJAX_Factory' => __DIR__ . '/../..' . '/classes/string-tracking/class-wpml-st-string-tracking-ajax-factory.php', 'WPML_ST_String_Translation_AJAX_Hooks_Factory' => __DIR__ . '/../..' . '/classes/string-translation/class-wpml-st-string-translation-ajax-hooks-factory.php', 'WPML_ST_String_Translation_Priority_AJAX' => __DIR__ . '/../..' . '/classes/string-translation/class-wpml-st-string-translation-priority-ajax.php', 'WPML_ST_String_Update' => __DIR__ . '/../..' . '/inc/wpml-st-string-update.class.php', 'WPML_ST_Strings' => __DIR__ . '/../..' . '/classes/class-wpml-st-strings.php', 'WPML_ST_Strings_Stats' => __DIR__ . '/../..' . '/classes/strings-scanning/class-wpml-st-strings-stats.php', 'WPML_ST_Support_Info' => __DIR__ . '/../..' . '/classes/support/class-wpml-st-support-info.php', 'WPML_ST_Support_Info_Filter' => __DIR__ . '/../..' . '/classes/support/class-wpml-st-support-info-filter.php', 'WPML_ST_TM_Jobs' => __DIR__ . '/../..' . '/classes/wpml-tm/class-wpml-st-tm-jobs.php', 'WPML_ST_Tax_Slug_Translation_Settings' => __DIR__ . '/../..' . '/classes/slug-translation/taxonomy/wpml-st-tax-slug-translation-settings.php', 'WPML_ST_Taxonomy_Labels_Translation' => __DIR__ . '/../..' . '/classes/filters/class-wpml-st-taxonomy-labels-translation.php', 'WPML_ST_Taxonomy_Labels_Translation_Factory' => __DIR__ . '/../..' . '/classes/filters/class-wpml-st-taxonomy-labels-translation-factory.php', 'WPML_ST_Taxonomy_Strings' => __DIR__ . '/../..' . '/classes/filters/taxonomy-strings/wpml-st-taxonomy-strings.php', 'WPML_ST_Term_Link_Filter' => __DIR__ . '/../..' . '/classes/slug-translation/taxonomy/wpml-st-term-link-filter.php', 'WPML_ST_Theme_Localization_UI' => __DIR__ . '/../..' . '/classes/menus/theme-plugin-localization-ui/strategy/class-wpml-st-theme-localization-ui.php', 'WPML_ST_Theme_Localization_UI_Factory' => __DIR__ . '/../..' . '/classes/menus/theme-plugin-localization-ui/factory/class-wpml-st-theme-localization-ui-factory.php', 'WPML_ST_Theme_Localization_Utils' => __DIR__ . '/../..' . '/classes/menus/theme-plugin-localization-ui/class-st-theme-localization-ui-utils.php', 'WPML_ST_Theme_Plugin_Hooks' => __DIR__ . '/../..' . '/classes/strings-scanning/class-wpml-st-theme-plugin-hooks.php', 'WPML_ST_Theme_Plugin_Hooks_Factory' => __DIR__ . '/../..' . '/classes/strings-scanning/factory/class-st-theme-plugin-hooks-factory.php', 'WPML_ST_Theme_Plugin_Localization_Options_Settings' => __DIR__ . '/../..' . '/classes/menus/theme-plugin-localization-ui/class-wpml-st-theme-plugin-localization-options-settings.php', 'WPML_ST_Theme_Plugin_Localization_Options_Settings_Factory' => __DIR__ . '/../..' . '/classes/menus/theme-plugin-localization-ui/factory/class-wpml-st-theme-plugin-localization-options-settings-factory.php', 'WPML_ST_Theme_Plugin_Localization_Options_UI' => __DIR__ . '/../..' . '/classes/menus/theme-plugin-localization-ui/class-st-theme-plugin-localization-options-ui.php', 'WPML_ST_Theme_Plugin_Localization_Options_UI_Factory' => __DIR__ . '/../..' . '/classes/menus/theme-plugin-localization-ui/factory/class-wpml-st-theme-plugin-localization-options-ui-factory.php', 'WPML_ST_Theme_Plugin_Localization_Resources' => __DIR__ . '/../..' . '/classes/menus/theme-plugin-localization-ui/class-wpml-st-theme-plugin-localization-resources.php', 'WPML_ST_Theme_Plugin_Localization_Resources_Factory' => __DIR__ . '/../..' . '/classes/menus/theme-plugin-localization-ui/factory/class-wpml-st-theme-plugin-localization-resources-factory.php', 'WPML_ST_Theme_Plugin_Scan_Dir_Ajax' => __DIR__ . '/../..' . '/classes/strings-scanning/wpml-st-theme-plugin-scan-dir-ajax.php', 'WPML_ST_Theme_Plugin_Scan_Dir_Ajax_Factory' => __DIR__ . '/../..' . '/classes/strings-scanning/factory/class-wpml-st-theme-plugin-scan-dir-ajax-factory.php', 'WPML_ST_Theme_Plugin_Scan_Files_Ajax' => __DIR__ . '/../..' . '/classes/strings-scanning/class-wpml-st-theme-plugin-scan-files-ajax.php', 'WPML_ST_Theme_Plugin_Scan_Files_Ajax_Factory' => __DIR__ . '/../..' . '/classes/strings-scanning/factory/class-wpml-st-theme-plugin-scan-files-ajax-factory.php', 'WPML_ST_Theme_String_Scanner_Factory' => __DIR__ . '/../..' . '/classes/strings-scanning/factory/class-wpml-st-theme-string-scanner-factory.php', 'WPML_ST_Themes_And_Plugins_Settings' => __DIR__ . '/../..' . '/classes/strings-scanning/class-wpml-themes-and-plugins-settings.php', 'WPML_ST_Themes_And_Plugins_Updates' => __DIR__ . '/../..' . '/classes/strings-scanning/class-wpml-themes-and-plugins-updates.php', 'WPML_ST_Translation_Memory' => __DIR__ . '/../..' . '/classes/translation-memory/class-wpml-st-translation-memory.php', 'WPML_ST_Translation_Memory_Records' => __DIR__ . '/../..' . '/classes/translation-memory/class-wpml-st-translation-memory-records.php', 'WPML_ST_Translations_File_Component_Details' => __DIR__ . '/../..' . '/classes/translations-file-scan/components/wpml-st-translations-file-component-details.php', 'WPML_ST_Translations_File_Component_Stats_Update_Hooks' => __DIR__ . '/../..' . '/classes/translations-file-scan/wpml-st-translations-file-component-stats-update-hooks.php', 'WPML_ST_Translations_File_Components_Find' => __DIR__ . '/../..' . '/classes/translations-file-scan/components/wpml-st-translations-file-components-find.php', 'WPML_ST_Translations_File_Components_Find_Plugin' => __DIR__ . '/../..' . '/classes/translations-file-scan/components/wpml-st-translations-file-components-find-plugin.php', 'WPML_ST_Translations_File_Components_Find_Theme' => __DIR__ . '/../..' . '/classes/translations-file-scan/components/wpml-st-translations-file-components-find-theme.php', 'WPML_ST_Translations_File_Dictionary' => __DIR__ . '/../..' . '/classes/translations-file-scan/wpml-st-translations-file-dictionary.php', 'WPML_ST_Translations_File_Dictionary_Storage' => __DIR__ . '/../..' . '/classes/translations-file-scan/dictionary/class-st-translations-file-dictionary-storage.php', 'WPML_ST_Translations_File_Dictionary_Storage_Table' => __DIR__ . '/../..' . '/classes/translations-file-scan/dictionary/class-st-translations-file-dicionary-storage-table.php', 'WPML_ST_Translations_File_Entry' => __DIR__ . '/../..' . '/classes/translations-file-scan/wpml-st-translations-file-entry.php', 'WPML_ST_Translations_File_JED' => __DIR__ . '/../..' . '/classes/translations-file-scan/translations-file/wpml-st-translations-file-jed.php', 'WPML_ST_Translations_File_Locale' => __DIR__ . '/../..' . '/classes/translations-file-scan/translations-file/wpml-st-translations-file-locale.php', 'WPML_ST_Translations_File_MO' => __DIR__ . '/../..' . '/classes/translations-file-scan/translations-file/wpml-st-translations-file-mo.php', 'WPML_ST_Translations_File_Queue' => __DIR__ . '/../..' . '/classes/translations-file-scan/wpml-st-translations-file-queue.php', 'WPML_ST_Translations_File_Registration' => __DIR__ . '/../..' . '/classes/translations-file-scan/wpml-st-translations-file-registration.php', 'WPML_ST_Translations_File_Scan' => __DIR__ . '/../..' . '/classes/translations-file-scan/wpml-st-translations-file-scan.php', 'WPML_ST_Translations_File_Scan_Charset_Validation' => __DIR__ . '/../..' . '/classes/translations-file-scan/charset-validation/wpml-st-translations-file-scan-charset-validation.php', 'WPML_ST_Translations_File_Scan_Db_Charset_Filter_Factory' => __DIR__ . '/../..' . '/classes/translations-file-scan/charset-validation/wpml-st-translations-file-scan-db-charset-validation-factory.php', 'WPML_ST_Translations_File_Scan_Db_Charset_Validation' => __DIR__ . '/../..' . '/classes/translations-file-scan/charset-validation/wpml-st-translations-file-scan-db-charset-validation.php', 'WPML_ST_Translations_File_Scan_Db_Table_List' => __DIR__ . '/../..' . '/classes/translations-file-scan/charset-validation/wpml-st-translations-file-scan-db-table-list.php', 'WPML_ST_Translations_File_Scan_Factory' => __DIR__ . '/../..' . '/classes/translations-file-scan/wpml-st-translations-file-scan-factory.php', 'WPML_ST_Translations_File_Scan_Storage' => __DIR__ . '/../..' . '/classes/translations-file-scan/wpml-st-translations-file-scan-storage.php', 'WPML_ST_Translations_File_Scan_UI_Block' => __DIR__ . '/../..' . '/classes/translations-file-scan/wpml-st-translations-file-scan-ui-block.php', 'WPML_ST_Translations_File_String_Status_Update' => __DIR__ . '/../..' . '/classes/translations-file-scan/wpml-st-translations-file-string-status-update.php', 'WPML_ST_Translations_File_Translation' => __DIR__ . '/../..' . '/classes/translations-file-scan/wpml-st-translations-file-translation.php', 'WPML_ST_Translations_File_Unicode_Characters_Filter' => __DIR__ . '/../..' . '/classes/translations-file-scan/wpml-st-translations-file-unicode-characters-filter.php', 'WPML_ST_Update_File_Hash_Ajax' => __DIR__ . '/../..' . '/classes/strings-scanning/class-wpml-st-update-file-hash-ajax.php', 'WPML_ST_Update_File_Hash_Ajax_Factory' => __DIR__ . '/../..' . '/classes/strings-scanning/factory/class-st-update-file-hash-ajax-factory.php', 'WPML_ST_Upgrade' => __DIR__ . '/../..' . '/classes/upgrade/class-wpml-st-upgrade.php', 'WPML_ST_Upgrade_Command_Factory' => __DIR__ . '/../..' . '/classes/upgrade/class-wpml-st-upgrade-command-factory.php', 'WPML_ST_Upgrade_Command_Not_Found_Exception' => __DIR__ . '/../..' . '/classes/upgrade/class-wpml-st-upgrade-command-not-found-exception.php', 'WPML_ST_Upgrade_DB_Longtext_String_Value' => __DIR__ . '/../..' . '/classes/upgrade/class-wpml-st-upgrade-db-longtext-string-value.php', 'WPML_ST_Upgrade_DB_String_Name_Index' => __DIR__ . '/../..' . '/classes/upgrade/class-wpml-st-upgrade-db-string-name-index.php', 'WPML_ST_Upgrade_DB_String_Packages' => __DIR__ . '/../..' . '/classes/upgrade/class-wpml-st-upgrade-db-string-packages.php', 'WPML_ST_Upgrade_DB_String_Packages_Word_Count' => __DIR__ . '/../..' . '/classes/upgrade/class-wpml-st-upgrade-db-string-packages-word-count.php', 'WPML_ST_Upgrade_DB_Strings_Add_Translation_Priority_Field' => __DIR__ . '/../..' . '/classes/upgrade/class-wpml-st-upgrade-db-strings-add-translation-priority-field.php', 'WPML_ST_Upgrade_Display_Strings_Scan_Notices' => __DIR__ . '/../..' . '/classes/upgrade/class-wpml-st-upgrade-display-strings-scan-notices.php', 'WPML_ST_Upgrade_MO_Scanning' => __DIR__ . '/../..' . '/classes/upgrade/class-wpml-st-upgrade-mo-scanning.php', 'WPML_ST_Upgrade_Migrate_Originals' => __DIR__ . '/../..' . '/classes/upgrade/class-wpml-st-upgrade-migrate-originals.php', 'WPML_ST_Upgrade_String_Index' => __DIR__ . '/../..' . '/classes/upgrade/class-wpml-st-upgrade-string-index.php', 'WPML_ST_User_Fields' => __DIR__ . '/../..' . '/classes/class-wpml-st-user-fields.php', 'WPML_ST_Verify_Dependencies' => __DIR__ . '/../..' . '/classes/class-wpml-st-verify-dependencies.php', 'WPML_ST_WCML_Taxonomy_Labels_Translation' => __DIR__ . '/../..' . '/classes/filters/class-wpml-st-wcml-taxonomy-labels-translation.php', 'WPML_ST_WP_Loaded_Action' => __DIR__ . '/../..' . '/classes/actions/class-wpml-st-wp-loaded-action.php', 'WPML_ST_Word_Count_Package_Records' => __DIR__ . '/../..' . '/classes/db-mappers/wpml-st-word-count-package-records.php', 'WPML_ST_Word_Count_String_Records' => __DIR__ . '/../..' . '/classes/db-mappers/wpml-st-word-count-string-records.php', 'WPML_Slug_Translation' => __DIR__ . '/../..' . '/classes/slug-translation/class-wpml-slug-translation.php', 'WPML_Slug_Translation_Factory' => __DIR__ . '/../..' . '/classes/slug-translation/wpml-slug-translation-factory.php', 'WPML_Slug_Translation_Records' => __DIR__ . '/../..' . '/classes/slug-translation/class-wpml-slug-translation-records.php', 'WPML_Slug_Translation_Records_Factory' => __DIR__ . '/../..' . '/classes/slug-translation/wpml-slug-translation-records-factory.php', 'WPML_String_Scanner' => __DIR__ . '/../..' . '/inc/gettext/wpml-string-scanner.class.php', 'WPML_String_Translation' => __DIR__ . '/../..' . '/inc/wpml-string-translation.class.php', 'WPML_String_Translation_Table' => __DIR__ . '/../..' . '/classes/string-translation-ui/class-wpml-string-translation-table.php', 'WPML_Strings_Translation_Priority' => __DIR__ . '/../..' . '/classes/string-translation/class-wpml-strings-translation-priority.php', 'WPML_TM_Filters' => __DIR__ . '/../..' . '/classes/filters/class-wpml-tm-filters.php', 'WPML_Tax_Slug_Translation_Records' => __DIR__ . '/../..' . '/classes/slug-translation/taxonomy/wpml-tax-slug-translation-records.php', 'WPML_Theme_String_Scanner' => __DIR__ . '/../..' . '/inc/gettext/wpml-theme-string-scanner.class.php', 'WPML_Translation_Priority_Select' => __DIR__ . '/../..' . '/classes/string-translation-ui/class-wpml-translation-priority-select.php');
        public static function getInitializer(\Composer\Autoload\ClassLoader $loader)
        {
        }
    }
}
namespace {
    /**
     * Class WPML_PHP_Version_Check
     */
    class WPML_PHP_Version_Check
    {
        /**
         * WPML_PHP_Version_Check constructor.
         *
         * @param string $required_version Required php version.
         * @param string $plugin_name      Plugin name.
         * @param string $plugin_file      Plugin file.
         * @param string $text_domain      Text domain.
         */
        public function __construct($required_version, $plugin_name, $plugin_file, $text_domain)
        {
        }
        /**
         * Check php version.
         *
         * @return bool
         */
        public function is_ok()
        {
        }
        /**
         * Show notice with php requirement.
         */
        public function php_requirement_message()
        {
        }
    }
    class WPML_Core_Version_Check
    {
        public static function is_ok($package_file_path)
        {
        }
    }
    /*
    Module Name: WPML Dependency Check Module
    Description: This is not a plugin! This module must be included in other plugins (WPML and add-ons) to handle compatibility checks
    Author: OnTheGoSystems
    Author URI: http://www.onthegosystems.com/
    Version: 2.1
    */
    /** @noinspection PhpUndefinedClassInspection */
    class WPML_Dependencies
    {
        protected static $instance;
        public $data_key = 'wpml_dependencies:';
        public $needs_validation_key = 'wpml_dependencies:needs_validation';
        protected function remove_old_admin_notices()
        {
        }
        public function run_validation_on_plugins_page()
        {
        }
        public function activated_plugin_action()
        {
        }
        public function deactivated_plugin_action()
        {
        }
        public function upgrader_process_complete_action($upgrader_object, $options)
        {
        }
        public function admin_notices_action()
        {
        }
        public function extra_plugin_headers_action(array $extra_headers = array())
        {
        }
        /**
         * @return WPML_Dependencies
         */
        public static function get_instance()
        {
        }
        public function get_plugins()
        {
        }
        public function init_plugins_action()
        {
        }
        public function get_plugins_validation()
        {
        }
        public function is_plugin_version_valid()
        {
        }
        public function get_expected_versions()
        {
        }
        public function has_invalid_plugins()
        {
        }
    }
}
namespace WPML\ST\API {
    /**
     * Class Fns
     * @package WPML\ST\API
     * @method static callable|void saveTranslation( ...$id, ...$lang, ...$translation, ...$state ) - Curried :: int → string → string → int → void
     * @method static callable|string|false getTranslation( ...$id, ...$lang ) - Curried :: int → string → string|false
     * @method static callable|array getTranslations( ...$id ) - Curried :: int → [lang => [value => string, status => int]]
     * @method static callable|bool updateStatus( ...$stringId, ...$language, ...$status ) - Curried :: int->string->int->bool
     * @method static callable|array getStringTranslationById( ...$stringTranslationId ) - Curried :: int → array
     * @method static callable|array getStringById( ...$stringId ) - Curried :: int → array
     */
    class Fns
    {
        use \WPML\Collect\Support\Traits\Macroable;
        public static function init()
        {
        }
    }
}
namespace {
    class WPML_ST_Settings
    {
        const SETTINGS_KEY = 'icl_st_settings';
        /**
         * @return array
         */
        public function get_settings()
        {
        }
        /**
         * @param string $name
         *
         * @return mixed|null
         */
        public function get_setting($name)
        {
        }
        /**
         * @param string $key
         * @param mixed  $value
         * @param bool   $save
         */
        public function update_setting($key, $value, $save = \false)
        {
        }
        public function delete_settings()
        {
        }
        public function save_settings()
        {
        }
    }
}
namespace WPML\ST\Shortcode {
    class LensFactory
    {
        public static function createLensForJobData()
        {
        }
        public static function createLensForProxyTranslations()
        {
        }
        public static function createLensForAssignIdInCTE()
        {
        }
    }
    class Hooks implements \IWPML_DIC_Action, \IWPML_Backend_Action, \IWPML_AJAX_Action, \IWPML_REST_Action
    {
        public function __construct(\WPML_ST_DB_Mappers_Strings $stringMapper)
        {
        }
        public function add_hooks()
        {
        }
    }
    /**
     * Class TranslationHandler
     *
     * @package WPML\ST\Shortcode
     *
     * @method static callable|mixed appendId(callable  ...$getStringRowByItsDomainAndValue, mixed ...$fieldData) - Curried :: (string->string->array)->mixed->mixed
     *
     * It appends "id" attribute to [wpml-string] shortcode.
     *
     * $getStringRowByItsDomainAndValue :: string->string->array
     *
     * @method static callable|mixed registerStringTranslation(callable ...$lens, mixed ...$data, callable ...$getTargetLanguage) - Curried :: callable->mixed->(mixed->string)->mixed
     *
     * It detects all [wpml-string] shortcodes in $jobData and registers string translations
     *
     * $getTargetLanguage :: mixed->string
     *
     * @method static callable|mixed restoreOriginalShortcodes(callable ...$getStringById, callable ...$lens, mixed ...$data) - Curried :: (int->string)->callable->mixed->mixed
     *
     * It detects all [wpml-string] shortcodes in $jobData and
     *  - removes "id" attribute
     *  - replaces translated inner text by its original value
     *
     * $getStringById :: int->array
     */
    class TranslationHandler
    {
        use \WPML\Collect\Support\Traits\Macroable;
        const SHORTCODE_PATTERN = '/\\[wpml-string.*?\\[\\/wpml-string\\]/';
        public static function init()
        {
        }
    }
}
namespace {
    class WPML_ST_User_Fields
    {
        public function __construct(\SitePress $sitepress, &$authordata)
        {
        }
        public function init_hooks()
        {
        }
        public function add_get_the_author_field_filters()
        {
        }
        /**
         * @param int $user_id
         */
        public function profile_update_action($user_id)
        {
        }
        /**
         * @param string $value
         * @param int    $user_id
         * @param int    $original_user_id
         *
         * @return string
         */
        public function get_the_author_field_filter($value, $user_id, $original_user_id)
        {
        }
        /**
         * This filter will only replace the "display_name" of the current author (in global $authordata)
         *
         * @param mixed|string|null $value
         *
         * @return mixed|string|null
         */
        public function the_author_filter($value)
        {
        }
        /**
         * @param int $user_id
         *
         * @return bool
         */
        public function is_user_role_translatable($user_id)
        {
        }
        /**
         * @return array
         */
        public function init_register_strings()
        {
        }
    }
    /**
     * WPML_ST_String_Statuses class
     *
     * Get the translation status text for the given status
     */
    class WPML_ST_String_Statuses
    {
        public static function get_status($status)
        {
        }
    }
}
namespace WPML\ST\Rest {
    abstract class Base extends \WPML\Rest\Base
    {
        /**
         * @return string
         */
        public function get_namespace()
        {
        }
    }
}
namespace WPML\ST\Rest\MO {
    class PreGenerate extends \WPML\ST\Rest\Base
    {
        public function __construct(\WPML\Rest\Adaptor $adaptor, \WPML\ST\MO\File\Manager $manager, \WPML\ST\MO\Generate\Process\ProcessFactory $processFactory)
        {
        }
        /**
         * @return array
         */
        function get_routes()
        {
        }
        function get_allowed_capabilities(\WP_REST_Request $request)
        {
        }
        public function generate()
        {
        }
    }
    class Import extends \WPML\ST\Rest\Base
    {
        /**
         * @return array
         */
        function get_routes()
        {
        }
        /**
         * @param \WP_REST_Request $request
         *
         * @return array
         */
        function get_allowed_capabilities(\WP_REST_Request $request)
        {
        }
        /**
         * @return array
         * @throws \WPML\Auryn\InjectionException
         */
        public function import(\WP_REST_Request $request)
        {
        }
    }
}
namespace WPML\ST\Rest {
    class Settings extends \WPML\ST\Rest\Base
    {
        public function __construct(\WPML\Rest\Adaptor $adaptor, \WPML\WP\OptionManager $option_manager)
        {
        }
        public function get_routes()
        {
        }
        public function get_allowed_capabilities(\WP_REST_Request $request)
        {
        }
        public function set(\WP_REST_Request $request)
        {
        }
    }
    /**
     * @author OnTheGo Systems
     */
    class FactoryLoader implements \IWPML_REST_Action_Loader, \IWPML_Deferred_Action_Loader
    {
        const REST_API_INIT_ACTION = 'rest_api_init';
        /**
         * @return string
         */
        public function get_load_action()
        {
        }
        public function create()
        {
        }
    }
}
namespace {
    class WPML_ST_TM_Jobs extends \WPML_WPDB_User
    {
        /**
         * @param wpdb $wpdb
         * WPML_ST_TM_Jobs constructor.
         */
        public function __construct(&$wpdb)
        {
        }
        /**
         * @param bool         $in_progress_status
         * @param array|object $job_arr
         *
         * @return bool true if a job is in progress for the given arguments
         */
        public function tm_external_job_in_progress_filter($in_progress_status, $job_arr)
        {
        }
        public function jobs_union_table_sql_filter($sql_statements, $args)
        {
        }
        /**
         * @param string $table
         *
         * @return string
         */
        public function filter_tm_post_job_table($table)
        {
        }
    }
}
namespace WPML\ST {
    class Shortcode
    {
        const STRING_DOMAIN = 'wpml-shortcode';
        public function __construct(\wpdb $wpdb)
        {
        }
        function init_hooks()
        {
        }
        /**
         * @param array  $attributes
         * @param string $value
         *
         * @return string
         */
        function render($attributes, $value)
        {
        }
    }
}
namespace {
    class WPML_Language_Of_Domain
    {
        /**
         * @param SitePress $sitepress
         */
        public function __construct(\SitePress $sitepress)
        {
        }
        public function get_language($domain)
        {
        }
        public function set_language($domain, $lang)
        {
        }
    }
    class WPML_Slug_Translation implements \IWPML_Action
    {
        const STRING_DOMAIN = 'WordPress';
        public function __construct(\SitePress $sitepress, \WPML_Slug_Translation_Records_Factory $slug_records_factory, \WPML_Get_LS_Languages_Status $ls_language_status, \WPML_ST_Term_Link_Filter $term_link_filter, \WPML_ST_Slug_Translation_Settings $slug_translation_settings)
        {
        }
        public function add_hooks()
        {
        }
        public function init()
        {
        }
        /**
         * @deprecated since 2.8.0, use the class `WPML_Post_Slug_Translation_Records` instead.
         *
         * @param string $type
         *
         * @return null|string
         */
        public static function get_slug_by_type($type)
        {
        }
        /**
         * This method is only for CPT
         *
         * @deprecated use `WPML_ST_Slug::filter_value` directly of the filter hook `wpml_get_translated_slug`
         *
         * @param string|false $slug_value
         * @param string       $post_type
         * @param string|bool  $language
         *
         * @return string
         */
        public function get_translated_slug($slug_value, $post_type, $language = \false)
        {
        }
        /**
         * @param array $value
         *
         * @return array
         * @deprecated Use WPML\ST\SlugTranslation\Hooks\Hooks::filter
         */
        public static function rewrite_rules_filter($value)
        {
        }
        /**
         * @param string  $post_link
         * @param WP_Post $post
         * @param bool    $leavename
         * @param bool    $sample
         *
         * @return mixed|string|WP_Error
         */
        public function post_type_link_filter($post_link, $post, $leavename, $sample)
        {
        }
        /**
         * @param int      $post_ID
         * @param \WP_Post $post
         */
        public function clear_post_link_cache($post_ID, $post)
        {
        }
        /**
         * Adds all translated custom post type slugs as valid query variables in addition to their original values
         *
         * @param array $qvars
         *
         * @return array
         */
        public function add_cpt_names($qvars)
        {
        }
        /**
         * @param WP_Query $query
         *
         * @return WP_Query
         */
        public function filter_pre_get_posts($query)
        {
        }
        /**
         * @param string $action
         */
        public static function gui_save_options($action)
        {
        }
        /**
         * @param string $slug
         *
         * @return string
         */
        public static function sanitize($slug)
        {
        }
        /**
         * @deprecated since 2.8.0, use the class `WPML_Post_Slug_Translation_Records` instead.
         */
        public static function register_string_for_slug($post_type, $slug)
        {
        }
        public function maybe_migrate_string_name()
        {
        }
    }
    interface IWPML_ST_Rewrite_Rule_Filter
    {
        public function rewrite_rules_filter($rules);
    }
}
namespace WPML\ST\SlugTranslation\Hooks {
    class HooksFactory
    {
        /**
         * @return Hooks
         */
        public function create()
        {
        }
    }
    class Hooks
    {
        /**
         * @param \WPML_Rewrite_Rule_Filter_Factory  $factory
         * @param \WPML_ST_Slug_Translation_Settings $slug_translation_settings
         */
        public function __construct(\WPML_Rewrite_Rule_Filter_Factory $factory, \WPML_ST_Slug_Translation_Settings $slug_translation_settings)
        {
        }
        public function add_hooks()
        {
        }
        public function init()
        {
        }
        /**
         * @param array $value
         *
         * @return array
         */
        public function filter($value)
        {
        }
        public function clearCache()
        {
        }
        /**
         * @param bool $hard
         *
         * @return mixed
         */
        public function flushRewriteRulesHard($hard)
        {
        }
    }
}
namespace {
    class WPML_Slug_Translation_Records_Factory
    {
        /**
         * @param string $type
         *
         * @return WPML_Post_Slug_Translation_Records|WPML_Tax_Slug_Translation_Records
         */
        public function create($type)
        {
        }
        /**
         * @return WPML_Tax_Slug_Translation_Records
         */
        public function createTaxRecords()
        {
        }
    }
    class WPML_Slug_Translation_Factory implements \IWPML_Frontend_Action_Loader, \IWPML_Backend_Action_Loader, \IWPML_AJAX_Action_Loader
    {
        const POST = 'post';
        const TAX = 'taxonomy';
        const INIT_PRIORITY = -1000;
        public function create()
        {
        }
    }
    abstract class WPML_Slug_Translation_Records
    {
        const CONTEXT_DEFAULT = 'default';
        const CONTEXT_WORDPRESS = 'WordPress';
        public function __construct(\wpdb $wpdb, \WPML_WP_Cache_Factory $cache_factory)
        {
        }
        /**
         * @param string $type
         *
         * @return WPML_ST_Slug
         */
        public function get_slug($type)
        {
        }
        /**
         * @deprecated use `get_slug` instead.
         *
         * @param string $type
         * @param string $lang
         *
         * @return null|string
         */
        public function get_translation($type, $lang)
        {
        }
        /**
         * @deprecated use `get_slug` instead.
         *
         * @param string $type
         * @param string $lang
         *
         * @return null|string
         */
        public function get_original($type, $lang = '')
        {
        }
        /**
         * @deprecated use `get_slug` instead.
         *
         * @param string $type
         *
         * @return int|null
         */
        public function get_slug_id($type)
        {
        }
        /**
         * @param string $type
         * @param string $slug
         *
         * @return int|null
         */
        public function register_slug($type, $slug)
        {
        }
        /**
         * @param string $type
         * @param string $slug
         */
        public function update_original_slug($type, $slug)
        {
        }
        /**
         * @deprecated use `get_slug` instead.
         *
         * @param string $type
         *
         * @return null|stdClass
         */
        public function get_original_slug_and_lang($type)
        {
        }
        /**
         * @deprecated use `get_slug` instead.
         *
         * @param string $type
         * @param bool   $only_status_complete
         *
         * @return array
         */
        public function get_element_slug_translations($type, $only_status_complete = \true)
        {
        }
        /**
         * @deprecated use `get_slug` instead.
         *
         * @param array $types
         *
         * @return array
         */
        public function get_all_slug_translations($types)
        {
        }
        /**
         * @deprecated use `get_slug` instead.
         *
         * @param string $type
         *
         * @return array
         */
        public function get_slug_translation_languages($type)
        {
        }
        /**
         * Use `WPML_ST_String` only for updating the values in the DB
         * because it does not have any caching feature.
         *
         * @param string $type
         *
         * @return null|WPML_ST_String
         */
        public function get_slug_string($type)
        {
        }
        /**
         * @param string $slug
         *
         * @return string
         */
        protected abstract function get_string_name($slug);
        /** @return string */
        protected abstract function get_element_type();
    }
    class WPML_Post_Slug_Translation_Records extends \WPML_Slug_Translation_Records
    {
        const STRING_NAME = 'URL slug: %s';
        /**
         * @param string $slug
         *
         * @return string
         */
        protected function get_string_name($slug)
        {
        }
        /** @return string */
        protected function get_element_type()
        {
        }
    }
    class WPML_ST_Slug_Translation_Settings
    {
        const KEY_ENABLED_GLOBALLY = 'wpml_base_slug_translation';
        /** @param bool $enabled */
        public function set_enabled($enabled)
        {
        }
        /** @return bool */
        public function is_enabled()
        {
        }
        public function is_translated($type_name)
        {
        }
        public function set_type($type, $is_type_enabled)
        {
        }
        public function save()
        {
        }
    }
    /**
     * @todo: Move these settings to an independent option
     *      like WPML_ST_Tax_Slug_Translation_Settings::OPTION_NAME
     */
    class WPML_ST_Post_Slug_Translation_Settings extends \WPML_ST_Slug_Translation_Settings
    {
        const KEY_IN_SITEPRESS_SETTINGS = 'posts_slug_translation';
        public function __construct(\SitePress $sitepress)
        {
        }
        /** @param bool $enabled */
        public function set_enabled($enabled)
        {
        }
        /**
         * @param string $type
         *
         * @return bool
         */
        public function is_translated($type)
        {
        }
        /**
         * @param string $type
         * @param bool   $is_enabled
         */
        public function set_type($type, $is_enabled)
        {
        }
        public function save()
        {
        }
    }
    class WPML_ST_Slug_New_Match
    {
        /**
         * @param string $value
         * @param bool   $preserve_original
         */
        public function __construct($value, $preserve_original)
        {
        }
        /**
         * @return string
         */
        public function get_value()
        {
        }
        /**
         * @return bool
         */
        public function should_preserve_original()
        {
        }
    }
    class WPML_ST_Slug_New_Match_Finder
    {
        /**
         * @param string                     $match
         * @param WPML_ST_Slug_Custom_Type[] $custom_types
         *
         * @return WPML_ST_Slug_New_Match
         */
        public function get($match, array $custom_types)
        {
        }
        /**
         * @param string                   $match
         * @param WPML_ST_Slug_Custom_Type $custom_type
         *
         * @return WPML_ST_Slug_New_Match
         */
        public function find_match_of_type($match, \WPML_ST_Slug_Custom_Type $custom_type)
        {
        }
    }
    class WPML_ST_Slug_Translation_UI_Factory
    {
        const POST = 'post';
        const TAX = 'taxonomy';
        const TEMPLATE_PATH = 'templates/slug-translation';
        public function create($type)
        {
        }
    }
    class WPML_ST_Slug_Translation_Strings_Sync implements \IWPML_Action
    {
        public function __construct(\WPML_Slug_Translation_Records_Factory $slug_records_factory, \WPML_ST_Slug_Translation_Settings_Factory $slug_settings_factory)
        {
        }
        public function add_hooks()
        {
        }
        /**
         * @param string       $taxonomy
         * @param string|array $object_type
         * @param array        $taxonomy_array
         */
        public function run_taxonomy_sync($taxonomy, $object_type, $taxonomy_array)
        {
        }
        /**
         * @param string       $post_type
         * @param WP_Post_Type $post_type_object
         */
        public function run_post_type_sync($post_type, $post_type_object)
        {
        }
        /**
         * @param string $rewrite_slug
         * @param string $type
         * @param string $element_type
         */
        public function sync_element_slug($rewrite_slug, $type, $element_type)
        {
        }
    }
    class WPML_ST_Element_Slug_Translation_UI
    {
        const TEMPLATE_FILE = 'slug-translation-ui.twig';
        public function __construct(\WPML_ST_Element_Slug_Translation_UI_Model $model, \IWPML_Template_Service $template_service)
        {
        }
        /** @return WPML_ST_Element_Slug_Translation_UI */
        public function init()
        {
        }
        /**
         * @param string                   $type_name
         * @param WP_Post_Type|WP_Taxonomy $custom_type
         *
         * @return string
         */
        public function render($type_name, $custom_type)
        {
        }
    }
    class WPML_ST_Slug
    {
        /** @param stdClass $data */
        public function set_lang_data(\stdClass $data)
        {
        }
        /** @return string */
        public function get_original_lang()
        {
        }
        /** @return string */
        public function get_original_value()
        {
        }
        /** @return int */
        public function get_original_id()
        {
        }
        /**
         * @param string $lang
         *
         * @return string
         */
        public function get_value($lang)
        {
        }
        /**
         * @param string $lang
         *
         * @return int
         */
        public function get_status($lang)
        {
        }
        /**
         * @param string $lang
         *
         * @return bool
         */
        public function is_translation_complete($lang)
        {
        }
        /** @return string|null */
        public function get_context()
        {
        }
        /** @return string|null */
        public function get_name()
        {
        }
        /** @return array */
        public function get_language_codes()
        {
        }
        /**
         * This method is used as a filter which returns the initial `$slug_value`
         * if no better value was found.
         *
         * @param string|false $slug_value
         * @param string       $lang
         *
         * @return string
         */
        public function filter_value($slug_value, $lang)
        {
        }
    }
}
namespace WPML\ST\DisplayAsTranslated {
    class CheckRedirect implements \IWPML_Frontend_Action
    {
        public function add_hooks()
        {
        }
        public static function checkForSlugTranslation($redirect, $post_id, $q)
        {
        }
    }
}
namespace {
    class WPML_ST_Slug_Translation_API implements \IWPML_Action
    {
        /**
         * The section indexes are hardcoded in `sitepress-multilingual-cms/menu/_custom_types_translation.php`
         */
        const SECTION_INDEX_POST = 7;
        const SECTION_INDEX_TAX = 8;
        public function __construct(\WPML_Slug_Translation_Records_Factory $records_factory, \WPML_ST_Slug_Translation_Settings_Factory $settings_factory, \IWPML_Current_Language $current_language, \WPML_WP_API $wp_api)
        {
        }
        public function add_hooks()
        {
        }
        public function init()
        {
        }
        /**
         * @param string      $slug_value
         * @param string      $type
         * @param string|bool $language
         * @param string      $element_type WPML_Slug_Translation_Factory::POST|WPML_Slug_Translation_Factory::TAX
         *
         * @return string
         */
        public function get_translated_slug_filter($slug_value, $type, $language = \false, $element_type = \WPML_Slug_Translation_Factory::POST)
        {
        }
        /**
         * @param string $languages
         * @param string $type
         * @param string $element_type WPML_Slug_Translation_Factory::POST|WPML_Slug_Translation_Factory::TAX
         *
         * @return array
         */
        public function get_slug_translation_languages_filter($languages, $type, $element_type = \WPML_Slug_Translation_Factory::POST)
        {
        }
        /**
         * @param string      $type
         * @param string|null $slug_value
         * @param string      $element_type WPML_Slug_Translation_Factory::POST|WPML_Slug_Translation_Factory::TAX
         */
        public function activate_slug_translation_action($type, $slug_value = \null, $element_type = \WPML_Slug_Translation_Factory::POST)
        {
        }
        /**
         * @param string $url
         * @param string $element_type WPML_Slug_Translation_Factory::POST or WPML_Slug_Translation_Factory::TAX
         *
         * @return string
         */
        public function get_slug_translation_url_filter($url, $element_type = \WPML_Slug_Translation_Factory::POST)
        {
        }
        /**
         * @param bool   $is_translated
         * @param string $type
         * @param string $element_type WPML_Slug_Translation_Factory::POST or WPML_Slug_Translation_Factory::TAX
         *
         * @return bool
         */
        public function type_slug_is_translated_filter($is_translated, $type, $element_type = \WPML_Slug_Translation_Factory::POST)
        {
        }
    }
    class WPML_Rewrite_Rule_Filter implements \IWPML_ST_Rewrite_Rule_Filter
    {
        /**
         * @param WPML_ST_Slug_Translation_Custom_Types_Repository[] $custom_types_repositories
         * @param WPML_ST_Slug_New_Match_Finder                      $new_match_finder
         */
        public function __construct(array $custom_types_repositories, \WPML_ST_Slug_New_Match_Finder $new_match_finder)
        {
        }
        /**
         * @param array|false|null $rules
         *
         * @return array
         */
        function rewrite_rules_filter($rules)
        {
        }
    }
    class WPML_ST_Element_Slug_Translation_UI_Model
    {
        public function __construct(\SitePress $sitepress, \WPML_ST_Slug_Translation_Settings $settings, \WPML_Slug_Translation_Records $slug_records, \WPML_Element_Sync_Settings $sync_settings, \WPML_Simple_Language_Selector $lang_selector)
        {
        }
        /**
         * @param string                   $type_name
         * @param WP_Post_Type|WP_Taxonomy $custom_type
         *
         * @return null|array
         */
        public function get($type_name, $custom_type)
        {
        }
    }
    class WPML_ST_Term_Link_Filter
    {
        const CACHE_GROUP = 'WPML_ST_Term_Link_Filter::replace_base_in_permalink_structure';
        public function __construct(\WPML_Tax_Slug_Translation_Records $slug_records, \SitePress $sitepress, \WPML_WP_Cache_Factory $cache_factory, \WPML_ST_Tax_Slug_Translation_Settings $tax_settings)
        {
        }
        /**
         * Filters the permalink structure for a terms before token replacement occurs
         * with the hook filter `pre_term_link` available since WP 4.9.0
         *
         * @see get_term_link
         *
         * @param false|string $termlink
         * @param WP_Term      $term
         *
         * @return false|string
         */
        public function replace_slug_in_termlink($termlink, $term)
        {
        }
    }
    class WPML_Tax_Slug_Translation_Records extends \WPML_Slug_Translation_Records
    {
        const STRING_NAME = 'URL %s tax slug';
        /**
         * @param string $slug
         *
         * @return string
         */
        protected function get_string_name($slug)
        {
        }
        /** @return string */
        protected function get_element_type()
        {
        }
    }
    class WPML_ST_Tax_Slug_Translation_Settings extends \WPML_ST_Slug_Translation_Settings
    {
        const OPTION_NAME = 'wpml_tax_slug_translation_settings';
        public function __construct()
        {
        }
        /** @param array $types */
        public function set_types(array $types)
        {
        }
        /** @return array */
        public function get_types()
        {
        }
        /**
         * @param string $taxonomy_name
         *
         * @return bool
         */
        public function is_translated($taxonomy_name)
        {
        }
        /**
         * @param string $taxonomy_name
         * @param bool   $is_enabled
         */
        public function set_type($taxonomy_name, $is_enabled)
        {
        }
        public function init()
        {
        }
        public function save()
        {
        }
    }
    class WPML_ST_Slug_Custom_Type_Factory
    {
        public function __construct(\SitePress $sitepress, \WPML_Slug_Translation_Records $slug_records, \WPML_ST_Slug_Translations $slug_translations)
        {
        }
        /**
         * @param string $name
         * @param bool   $display_as_translated
         *
         * @return WPML_ST_Slug_Custom_Type
         */
        public function create($name, $display_as_translated)
        {
        }
    }
    class WPML_ST_Slug_Translations
    {
        /**
         * @param WPML_ST_Slug $slug
         * @param bool         $display_as_translated_mode
         *
         * @return string
         */
        public function get(\WPML_ST_Slug $slug, $display_as_translated_mode)
        {
        }
    }
    interface WPML_ST_Slug_Translation_Custom_Types_Repository
    {
        /**
         * @return WPML_ST_Slug_Custom_Type[]
         */
        public function get();
    }
    class WPML_ST_Slug_Translation_Taxonomy_Custom_Types_Repository implements \WPML_ST_Slug_Translation_Custom_Types_Repository
    {
        public function __construct(\SitePress $sitepress, \WPML_ST_Slug_Custom_Type_Factory $custom_type_factory, \WPML_ST_Tax_Slug_Translation_Settings $settings_repository)
        {
        }
        public function get()
        {
        }
    }
    class WPML_ST_Slug_Translation_Post_Custom_Types_Repository implements \WPML_ST_Slug_Translation_Custom_Types_Repository
    {
        public function __construct(\SitePress $sitepress, \WPML_ST_Slug_Custom_Type_Factory $custom_type_factory)
        {
        }
        public function get()
        {
        }
    }
    /**
     * It may represent custom posts or custom taxonomies
     */
    class WPML_ST_Slug_Custom_Type
    {
        /**
         * WPML_ST_Slug_Custom_Type constructor.
         *
         * @param string   $name
         * @param bool     $display_as_translated
         * @param string   $slug
         * @param string   $slug_translation
         */
        public function __construct($name, $display_as_translated, $slug, $slug_translation)
        {
        }
        /**
         * @return string
         */
        public function get_name()
        {
        }
        /**
         * @return bool
         */
        public function is_display_as_translated()
        {
        }
        /**
         * @return string
         */
        public function get_slug()
        {
        }
        /**
         * @return string
         */
        public function get_slug_translation()
        {
        }
        /**
         * @return bool
         */
        public function is_using_tags()
        {
        }
    }
    class WPML_ST_Slug_Translation_Settings_Factory
    {
        /**
         * @throws InvalidArgumentException
         * @param string $element_type
         *
         * @return WPML_ST_Slug_Translation_Settings|WPML_ST_Tax_Slug_Translation_Settings|WPML_ST_Post_Slug_Translation_Settings
         */
        public function create($element_type = \null)
        {
        }
        /**
         * @return WPML_ST_Tax_Slug_Translation_Settings
         */
        public function createTaxSettings()
        {
        }
    }
    class WPML_ST_Slug_Translation_UI_Save implements \IWPML_Action
    {
        const ACTION_HOOK_FOR_POST = 'wpml_save_cpt_sync_settings';
        const ACTION_HOOK_FOR_TAX = 'wpml_save_taxonomy_sync_settings';
        public function __construct(\WPML_ST_Slug_Translation_Settings $settings, \WPML_Slug_Translation_Records $records, \SitePress $sitepress, \IWPML_WP_Element_Type $wp_element_type, $action_hook)
        {
        }
        public function add_hooks()
        {
        }
        public function save_element_type_slug_translation_options()
        {
        }
    }
    class WPML_Rewrite_Rule_Filter_Factory
    {
        /**
         * @param SitePress|null $sitepress
         *
         * @return WPML_Rewrite_Rule_Filter
         */
        public function create($sitepress = \null)
        {
        }
    }
}
namespace WPML\ST\Package {
    class Domains
    {
        public function __construct(\wpdb $wpdb)
        {
        }
        /**
         * @param string|null $domain
         *
         * @return bool
         */
        public function isPackage($domain)
        {
        }
        /**
         * @see \WPML_Package::get_string_context_from_package for how the package domain is built
         *
         * @return Collection
         */
        public function getDomains()
        {
        }
    }
}
namespace WPML\ST\TranslationFile {
    class StringsRetrieve
    {
        // We need to store the strings by key that is a combination of original and gettext context
        // The join needs to be something that is unlikely to be in either so we can split later.
        const KEY_JOIN = '::JOIN::';
        /**
         * @param \WPML\ST\DB\Mappers\StringsRetrieve $string_retrieve
         */
        public function __construct(\WPML\ST\DB\Mappers\StringsRetrieve $string_retrieve)
        {
        }
        /**
         * @param string $domain
         * @param string $language
         * @param bool   $modified_mo_only
         *
         * @return StringEntity[]
         */
        public function get($domain, $language, $modified_mo_only)
        {
        }
        /**
         * @param array $row_data
         *
         * @return string|null
         */
        public static function parseTranslation(array $row_data)
        {
        }
    }
    class UpdateHooks implements \IWPML_Action
    {
        public function __construct(\WPML\ST\TranslationFile\Manager $file_manager, \WPML\ST\TranslationFile\DomainsLocalesMapper $domains_locales_mapper, callable $resetDomainsCache = null)
        {
        }
        public function add_hooks()
        {
        }
        /** @param int $string_translation_id */
        public function add_to_update_queue($string_translation_id)
        {
        }
        public function process_update_queue_action()
        {
        }
        /**
         * @return array
         */
        public function process_update_queue()
        {
        }
        /**
         * @param string $domain
         * @param string $name
         * @param string $old_value
         * @param string $new_value
         * @param bool|false $force_complete
         * @param stdClass $string
         */
        public function refresh_after_update_original_string($domain, $name, $old_value, $new_value, $force_complete, $string)
        {
        }
        public function update_imported_file(\WPML_ST_Translations_File_Entry $file_entry)
        {
        }
        /**
         * It dispatches the regeneration of MO files for a specific domain in all active languages.
         *
         * @param string $domain
         */
        public function refresh_domain($domain)
        {
        }
        /**
         * We need to refresh before the strings are deleted,
         * otherwise we can't determine which domains to refresh.
         *
         * @param array $string_ids
         */
        public function refresh_before_remove_strings(array $string_ids)
        {
        }
    }
}
namespace WPML\ST\TranslationFile\Sync {
    class FileSync
    {
        public function __construct(\WPML\ST\TranslationFile\Manager $manager, \WPML\ST\TranslationFile\Sync\TranslationUpdates $translationUpdates, \WPML_ST_Translations_File_Locale $FileLocale)
        {
        }
        /**
         * Before to load the custom translation file, we'll:
         * - Re-generate it if it's missing or outdated.
         * - Delete it if we don't have custom translations.
         *
         * We will also sync the custom file when a native file is passed
         * because the custom file might never be loaded if it's missing.
         *
         * @param string|false $filePath
         * @param string       $domain
         */
        public function sync($filePath, $domain)
        {
        }
    }
    class TranslationUpdates
    {
        // The global constant is not defined yet.
        const ICL_STRING_TRANSLATION_COMPLETE = 10;
        public function __construct(\wpdb $wpdb, \WPML_Language_Records $languageRecords)
        {
        }
        /**
         * @param string $domain
         * @param string $locale
         *
         * @return int
         */
        public function getTimestamp($domain, $locale)
        {
        }
        public function reset()
        {
        }
    }
}
namespace WPML\ST\TranslationFile {
    class Hooks
    {
        public function __construct(\WPML_Action_Filter_Loader $action_loader, \WPML_ST_Upgrade $upgrade)
        {
        }
        public function install()
        {
        }
        public static function useFileSynchronization()
        {
        }
    }
    class UpdateHooksFactory
    {
        /** @return UpdateHooks */
        public static function create()
        {
        }
    }
    class StringEntity
    {
        /**
         * @param string      $original
         * @param array       $translations
         * @param null|string $context
         * @param null|string $original_plural
         * @param null|string $name
         */
        public function __construct($original, array $translations, $context = null, $original_plural = null, $name = null)
        {
        }
        /** @return string */
        public function get_original()
        {
        }
        /** @return array */
        public function get_translations()
        {
        }
        /** @return null|string */
        public function get_context()
        {
        }
        /**
         * @return string|null
         */
        public function get_original_plural()
        {
        }
        /**
         * @return string|null
         */
        public function get_name()
        {
        }
        /**
         * @param string $name
         */
        public function set_name($name)
        {
        }
    }
    class DomainsLocalesMapper
    {
        const ALIAS_STRINGS = 's';
        const ALIAS_STRING_TRANSLATIONS = 'st';
        public function __construct(\wpdb $wpdb, \WPML_Locale $locale)
        {
        }
        /**
         * @param array $string_translation_ids
         *
         * @return Collection of objects with properties `domain` and `locale`
         */
        public function get_from_translation_ids(array $string_translation_ids)
        {
        }
        /**
         * @param array $string_ids
         *
         * @return Collection of objects with properties `domain` and `locale`
         */
        public function get_from_string_ids(array $string_ids)
        {
        }
        /**
         * @param  callable $getActiveLanguages
         * @param  string   $domain
         *
         * @return array
         */
        public function get_from_domain(callable $getActiveLanguages, $domain)
        {
        }
    }
    class Domains
    {
        const MO_DOMAINS_CACHE_GROUP = 'WPML_ST_CACHE';
        const MO_DOMAINS_CACHE_KEY = 'wpml_string_translation_has_mo_domains';
        /**
         * Domains constructor.
         *
         * @param PackageDomains $package_domains
         * @param WPML_ST_Translations_File_Dictionary $file_dictionary
         */
        public function __construct(\wpdb $wpdb, \WPML\ST\Package\Domains $package_domains, \WPML_ST_Translations_File_Dictionary $file_dictionary)
        {
        }
        /**
         * @return Collection
         */
        public function getMODomains()
        {
        }
        public static function invalidateMODomainCache()
        {
        }
        /**
         * Returns a collection of MO domains that
         * WPML needs to automatically load.
         *
         * @return Collection
         */
        public function getCustomMODomains()
        {
        }
        /**
         * @return Collection
         */
        public function getJEDDomains()
        {
        }
        public static function resetCache()
        {
        }
        /**
         * Domains that are not handled with MO files,
         * but have direct DB queries.
         *
         * @return Collection
         */
        public static function getReservedDomains()
        {
        }
    }
}
namespace WPML\ST\MO\File {
    trait makeDir
    {
        /**
         * @var \WP_Filesystem_Direct
         */
        protected $filesystem;
        /** @return bool */
        public function maybeCreateSubdir()
        {
        }
        /**
         * This declaration throws a "Strict standards" warning in PHP 5.6.
         * @todo: Remove the comment when we drop support for PHP 5.6.
         */
        //abstract public static function getSubdir();
    }
}
namespace WPML\ST\TranslationFile {
    abstract class Manager
    {
        use \WPML\ST\MO\File\makeDir;
        const SUB_DIRECTORY = 'wpml';
        /** @var StringsRetrieve $strings */
        protected $strings;
        /** @var WPML_Language_Records $language_records */
        protected $language_records;
        /** @var Builder $builder */
        protected $builder;
        /** @var WPML_ST_Translations_File_Dictionary $file_dictionary */
        protected $file_dictionary;
        /** @var Domains $domains */
        protected $domains;
        public function __construct(\WPML\ST\TranslationFile\StringsRetrieve $strings, \WPML\ST\TranslationFile\Builder $builder, \WP_Filesystem_Direct $filesystem, \WPML_Language_Records $language_records, \WPML\ST\TranslationFile\Domains $domains)
        {
        }
        /**
         * @param string $domain
         * @param string $locale
         */
        public function remove($domain, $locale)
        {
        }
        public function write($domain, $locale, $content)
        {
        }
        /**
         * Builds and saves the .MO file.
         * Returns false if file doesn't exist, file path otherwise.
         *
         * @param string $domain
         * @param string $locale
         *
         * @return false|string
         */
        public function add($domain, $locale)
        {
        }
        /**
         * @param string $domain
         * @param string $locale
         *
         * @return string|null
         */
        public function get($domain, $locale)
        {
        }
        /**
         * @param string $domain
         * @param string $locale
         *
         * @return string
         */
        public function getFilepath($domain, $locale)
        {
        }
        /**
         * @param string $domain
         *
         * @return bool
         */
        public function handles($domain)
        {
        }
        /** @return string */
        public static function getSubdir()
        {
        }
        /**
         * @return string
         */
        protected abstract function getFileExtension();
        /**
         * @return bool
         */
        public abstract function isPartialFile();
        /**
         * @return Collection
         */
        protected abstract function getDomains();
    }
    abstract class Builder
    {
        /** @var string $plural_form */
        protected $plural_form = 'nplurals=2; plural=n != 1;';
        /** @var string $language */
        protected $language;
        /**
         * @param string $language
         *
         * @return Builder
         */
        public function set_language($language)
        {
        }
        /**
         * @param string $plural_form
         *
         * @return Builder
         */
        public function set_plural_form($plural_form)
        {
        }
        /**
         * @param StringEntity[] $strings
         * @return string
         */
        public abstract function get_content(array $strings);
    }
}
namespace {
    class WPML_ST_JED_File_Builder extends \WPML\ST\TranslationFile\Builder
    {
        public function __construct()
        {
        }
        /**
         * @param StringEntity[] $strings
         * @return string
         */
        public function get_content(array $strings)
        {
        }
    }
    class WPML_ST_JED_Domain
    {
        public static function get($domain, $handler)
        {
        }
    }
}
namespace WPML\ST\JED\Hooks {
    class Sync implements \IWPML_Frontend_Action, \IWPML_Backend_Action, \IWPML_DIC_Action
    {
        public function __construct(\WPML\ST\TranslationFile\Sync\FileSync $fileSync)
        {
        }
        public function add_hooks()
        {
        }
        /**
         * @param string|false $jedFile Path to the translation file to load. False if there isn't one.
         * @param string       $handler Name of the script to register a translation domain to.
         * @param string       $domain  The text domain.
         */
        public function syncCustomJedFile($jedFile, $handler, $domain)
        {
        }
    }
}
namespace {
    class WPML_ST_Script_Translations_Hooks_Factory implements \IWPML_Backend_Action_Loader, \IWPML_Frontend_Action_Loader
    {
        /**
         * Create hooks.
         *
         * @return array|IWPML_Action
         * @throws \WPML\Auryn\InjectionException Auryn Exception.
         */
        public function create()
        {
        }
    }
    class WPML_ST_JED_File_Manager extends \WPML\ST\TranslationFile\Manager
    {
        /**
         * @return string
         */
        protected function getFileExtension()
        {
        }
        /**
         * @return bool
         */
        public function isPartialFile()
        {
        }
        /**
         * @return Collection
         */
        protected function getDomains()
        {
        }
    }
    class WPML_ST_Script_Translations_Hooks implements \IWPML_Action
    {
        const PRIORITY_OVERRIDE_JED_FILE = 10;
        public function __construct(\WPML_ST_Translations_File_Dictionary $dictionary, \WPML_ST_JED_File_Manager $jed_file_manager, \WPML_File $wpml_file)
        {
        }
        public function add_hooks()
        {
        }
        /**
         * @param string $filepath
         * @param string $handler
         * @param string $domain
         *
         * @return string
         */
        public function override_jed_file($filepath, $handler, $domain)
        {
        }
    }
    class WPML_ST_Package_Cleanup
    {
        public function record_existing_strings(\WPML_Package $package)
        {
        }
        public function record_register_string(\WPML_Package $package, $string_id)
        {
        }
        public function delete_unused_strings(\WPML_Package $package)
        {
        }
    }
}
namespace WPML\ST\PackageTranslation {
    class Hooks implements \IWPML_Action, \IWPML_Backend_Action, \IWPML_Frontend_Action
    {
        public function add_hooks()
        {
        }
    }
}
namespace {
    /**
     * Created by PhpStorm.
     * User: bruce
     * Date: 16/06/17
     * Time: 10:57 AM
     */
    class WPML_ST_Package_Storage
    {
        /**
         * WPML_ST_Package_Storage constructor.
         *
         * @param int   $package_id
         * @param \wpdb $wpdb
         */
        public function __construct($package_id, \wpdb $wpdb)
        {
        }
        /**
         * @param string $string_title
         * @param string $string_type
         * @param string $string_value
         * @param int    $string_id
         *
         * @return bool
         */
        public function update($string_title, $string_type, $string_value, $string_id)
        {
        }
    }
}
namespace WPML\ST\PackageTranslation {
    class Assign
    {
        /**
         * Assign all strings from specified domain to existing package.
         *
         * @param  string $domainName
         * @param  int    $packageId
         *
         * @since 3.1.0
         */
        public static function stringsFromDomainToExistingPackage($domainName, $packageId)
        {
        }
        /**
         * Assign all strings from specified domain to new package which is created on fly.
         *
         * @param  string $domainName
         * @param  array  $packageData  {
         *
         * @type string $kind_slug e.g. toolset_forms
         * @type string $kind e.g. "Toolset forms"
         * @type string $name e.g. "1"
         * @type string $title e.g. "Form 1"
         * @type string $edit_link URL to edit page of resource
         * @type string $view_link (Optional) Url to frontend view page of resource
         * @type int $page_id (optional)
         * }
         * @since 3.1.0
         */
        public static function stringsFromDomainToNewPackage($domainName, array $packageData)
        {
        }
    }
}
namespace WPML\ST\StringsCleanup {
    class UntranslatedStrings
    {
        public function __construct(\wpdb $wpdb)
        {
        }
        /**
         * @param string[] $domains
         *
         * @return int
         */
        public function getCountInDomains($domains)
        {
        }
        /**
         * @param string[] $domains
         * @param int      $batchSize
         *
         * @return array
         */
        public function getFromDomains($domains, $batchSize)
        {
        }
        /**
         * @param int[] $stringIds
         *
         * @return int
         */
        public function remove($stringIds)
        {
        }
    }
    class UI implements \IWPML_Backend_Action_Loader
    {
        /**
         * @return callable|null
         */
        public function create()
        {
        }
        public static function localize()
        {
        }
    }
}
namespace WPML\ST\StringsCleanup\Ajax {
    class RemoveStringsFromDomains implements \WPML\Ajax\IHandler
    {
        const REMOVE_STRINGS_BATCH_SIZE = 500;
        public function run(\WPML\Collect\Support\Collection $data)
        {
        }
    }
    class InitStringsRemoving implements \WPML\Ajax\IHandler
    {
        public function run(\WPML\Collect\Support\Collection $data)
        {
        }
    }
}
namespace {
    class WPML_ST_Scan_Dir
    {
        const PLACEHOLDERS_ROOT = '<root>';
        /**
         * @param string $folder
         * @param array  $extensions
         * @param bool   $single_file
         * @param array  $ignore_folders
         *
         * @return array
         */
        public function scan($folder, array $extensions = array(), $single_file = \false, $ignore_folders = array())
        {
        }
    }
}
namespace WPML\ST\WP\App {
    class Resources
    {
        // enqueueApp :: string $app -> ( string $localizeData )
        public static function enqueueApp($app)
        {
        }
    }
}
namespace WPML\ST\Utils {
    class LanguageResolution
    {
        public function __construct(\SitePress $sitepress, \WPML_String_Translation $string_translation)
        {
        }
        /** @return bool|mixed|string|null */
        public function getCurrentLanguage()
        {
        }
        /**  */
        public function getCurrentLocale()
        {
        }
    }
}
namespace {
    class WPML_ST_String_Dependencies_Node
    {
        public function __construct($id = \null, $type = \null)
        {
        }
        public function get_id()
        {
        }
        public function get_type()
        {
        }
        public function set_needs_refresh($needs_refresh)
        {
        }
        public function get_needs_refresh()
        {
        }
        public function set_parent(\WPML_ST_String_Dependencies_Node $node)
        {
        }
        public function get_parent()
        {
        }
        public function add_child(\WPML_ST_String_Dependencies_Node $node)
        {
        }
        public function remove_child(\WPML_ST_String_Dependencies_Node $node)
        {
        }
        public function detach()
        {
        }
        /**
         * Iteration DFS in post-order
         *
         * @return WPML_ST_String_Dependencies_Node
         */
        public function get_next()
        {
        }
        /**
         * Search DFS in pre-order
         *
         * @param int    $id
         * @param string $type
         *
         * @return false|WPML_ST_String_Dependencies_Node
         */
        public function search($id, $type)
        {
        }
        public function iteration_completed()
        {
        }
        /**
         * @return string|stdClass
         */
        public function to_json()
        {
        }
        /**
         * @param string|self $object
         */
        public function from_json($object)
        {
        }
    }
    class WPML_ST_String_Dependencies_Builder
    {
        public function __construct(\WPML_ST_String_Dependencies_Records $records)
        {
        }
        /**
         * @param string|null $type
         * @param int         $id
         *
         * @return WPML_ST_String_Dependencies_Node
         */
        public function from($type, $id)
        {
        }
    }
    class WPML_ST_String_Dependencies_Records
    {
        public function __construct(\wpdb $wpdb)
        {
        }
        /**
         * @param string $type
         * @param int    $id
         *
         * @return int
         */
        public function get_parent_id_from($type, $id)
        {
        }
        /**
         * @param string $type
         * @param int    $id
         *
         * @return array
         */
        public function get_child_ids_from($type, $id)
        {
        }
    }
}
namespace WPML\ST\Basket {
    class Status
    {
        public static function add(array $translations, $languages)
        {
        }
    }
}
namespace {
    /**
     * WPML_ST_String class
     *
     * Low level access to string in Database
     *
     * NOTE: Don't use this class to process a large amount of strings as it doesn't
     * do any caching, etc.
     */
    class WPML_ST_String
    {
        protected $wpdb;
        /**
         * @param int  $string_id
         * @param wpdb $wpdb
         */
        public function __construct($string_id, \wpdb $wpdb)
        {
        }
        /**
         * @return int
         */
        public function string_id()
        {
        }
        /**
         * @return string|null
         */
        public function get_language()
        {
        }
        /**
         * @return string
         */
        public function get_value()
        {
        }
        /**
         * @return int
         */
        public function get_status()
        {
        }
        /**
         * @param string $language
         */
        public function set_language($language)
        {
        }
        /**
         * @return stdClass[]
         */
        public function get_translation_statuses()
        {
        }
        public function get_translations()
        {
        }
        /**
         * For a bulk update of all strings:
         *
         * @see WPML_ST_Bulk_Update_Strings_Status::run
         */
        public function update_status()
        {
        }
        /**
         * @param string           $language
         * @param string|null|bool $value
         * @param int|bool|false   $status
         * @param int|null         $translator_id
         * @param string|int|null  $translation_service
         * @param int|null         $batch_id
         *
         * @return bool|int id of the translation
         */
        public function set_translation($language, $value = \null, $status = \false, $translator_id = \null, $translation_service = \null, $batch_id = \null)
        {
        }
        public function set_location($location)
        {
        }
        /**
         * Set string wrap tag.
         * Used for SEO significance, can contain values as h1 ... h6, etc.
         *
         * @param string $wrap_tag Wrap tag.
         */
        public function set_wrap_tag($wrap_tag)
        {
        }
        /**
         * @param string $property
         * @param mixed  $value
         */
        protected function set_property($property, $value)
        {
        }
        /**
         * @param bool $translations sets whether to use original or translations table
         *
         * @return string
         */
        protected function from_where_snippet($translations = \false)
        {
        }
        public function exists()
        {
        }
        /** @return string|null */
        public function get_context()
        {
        }
        /** @return string|null */
        public function get_gettext_context()
        {
        }
        /** @return string|null */
        public function get_name()
        {
        }
    }
    class WPML_ST_Admin_Blog_Option extends \WPML_SP_User
    {
        /**
         * WPML_ST_Admin_Blog_Option constructor.
         *
         * @param SitePress               $sitepress
         * @param WPML_String_Translation $st_instance
         * @param string                  $option_name
         */
        public function __construct(&$sitepress, &$st_instance, $option_name)
        {
        }
        /**
         * @param string|array $old_value
         * @param string|array $new_value
         *
         * @return mixed
         */
        public function pre_update_filter($old_value, $new_value)
        {
        }
    }
    class WPML_ST_Admin_Option_Translation extends \WPML_SP_User
    {
        /**
         * WPML_ST_Admin_Option constructor.
         *
         * @param SitePress               $sitepress
         * @param WPML_String_Translation $st_instance
         * @param string                  $option_name
         * @param string                  $language
         */
        public function __construct(&$sitepress, &$st_instance, $option_name, $language = '')
        {
        }
        /**
         *
         * @param string         $option_name
         * @param string|array   $new_value
         * @param int|bool       $status
         * @param int            $translator_id
         * @param int            $rec_level
         *
         * @return boolean|mixed
         */
        public function update_option($option_name = '', $new_value = \null, $status = \false, $translator_id = \null, $rec_level = 0)
        {
        }
    }
    class WPML_ST_Translations_File_Locale
    {
        const PATTERN_SEARCH_LANG_JSON = '#DOMAIN_PLACEHOLDER(LOCALES_PLACEHOLDER)-[-_a-z0-9]+\\.json$#i';
        /**
         * @param SitePress   $sitepress
         * @param WPML_Locale $locale
         */
        public function __construct(\SitePress $sitepress, \WPML_Locale $locale)
        {
        }
        /**
         * It extracts language code from mo file path, examples
         * '/wp-content/languages/admin-pl_PL.mo' => 'pl'
         * '/wp-content/plugins/sitepress/sitepress-hr.mo' => 'hr'
         * '/wp-content/languages/fr_FR-4gh5e6d3g5s33d6gg51zas2.json' => 'fr_FR'
         * '/wp-content/plugins/my-plugin/languages/-my-plugin-fr_FR-my-handler.json' => 'fr_FR'
         *
         * @param string $filepath
         * @param string $domain
         *
         * @return string
         */
        public function get($filepath, $domain)
        {
        }
    }
    interface IWPML_ST_Translations_File
    {
        /**
         * @return WPML_ST_Translations_File_Translation[]
         */
        public function get_translations();
    }
    class WPML_ST_Translations_File_JED implements \IWPML_ST_Translations_File
    {
        const EMPTY_PROPERTY_NAME = '_empty_';
        const DECODED_EOT_CHAR = '"\\u0004"';
        const PLURAL_SUFFIX_PATTERN = ' [plural %d]';
        public function __construct($filepath)
        {
        }
        /**
         * @return WPML_ST_Translations_File_Translation[]
         */
        public function get_translations()
        {
        }
    }
    class WPML_ST_Translations_File_MO implements \IWPML_ST_Translations_File
    {
        /**
         * @param string $filepath
         */
        public function __construct($filepath)
        {
        }
        /**
         * @return WPML_ST_Translations_File_Translation[]
         */
        public function get_translations()
        {
        }
    }
    class WPML_ST_Translations_File_Component_Stats_Update_Hooks
    {
        /**
         * @param WPML_ST_Strings_Stats $string_stats
         */
        public function __construct(\WPML_ST_Strings_Stats $string_stats)
        {
        }
        public function add_hooks()
        {
        }
        /**
         * @param WPML_ST_Translations_File_Entry $file
         */
        public function update_stats(\WPML_ST_Translations_File_Entry $file)
        {
        }
    }
}
namespace WPML\ST\TranslationFile {
    class QueueFilter
    {
        /**
         * @param array $plugins
         * @param array $themes
         * @param array $other
         */
        public function __construct(array $plugins, array $themes, array $other)
        {
        }
        /**
         * @param WPML_ST_Translations_File_Entry $file
         *
         * @return bool
         */
        public function isSelected(\WPML_ST_Translations_File_Entry $file)
        {
        }
    }
}
namespace {
    class WPML_ST_Translations_File_Dictionary
    {
        /**
         * @param WPML_ST_Translations_File_Dictionary_Storage $storage
         */
        public function __construct(\WPML_ST_Translations_File_Dictionary_Storage $storage)
        {
        }
        /**
         * @param string $file_path
         *
         * @return WPML_ST_Translations_File_Entry|null
         */
        public function find_file_info_by_path($file_path)
        {
        }
        /**
         * @param WPML_ST_Translations_File_Entry $file
         */
        public function save(\WPML_ST_Translations_File_Entry $file)
        {
        }
        /**
         * @return WPML_ST_Translations_File_Entry[]
         */
        public function get_not_imported_files()
        {
        }
        public function clear_skipped()
        {
        }
        /**
         * @return WPML_ST_Translations_File_Entry[]
         */
        public function get_imported_files()
        {
        }
        /**
         * @param null|string $extension
         * @param null|string $locale
         *
         * @return array
         */
        public function get_domains($extension = \null, $locale = \null)
        {
        }
    }
}
namespace WPML\ST\TranslationFile {
    class EntryQueries
    {
        /**
         * @param string $type
         *
         * @return \Closure
         */
        public static function isType($type)
        {
        }
        /**
         * @param string $extension
         *
         * @return \Closure
         */
        public static function isExtension($extension)
        {
        }
        /**
         * @return \Closure
         */
        public static function getResourceName()
        {
        }
        /**
         * @return \Closure
         */
        public static function getDomain()
        {
        }
    }
}
namespace {
    class WPML_ST_Translations_File_Unicode_Characters_Filter
    {
        public function __construct()
        {
        }
        /**
         * @param WPML_ST_Translations_File_Translation[] $translations
         *
         * @return WPML_ST_Translations_File_Translation[]
         */
        public function filter(array $translations)
        {
        }
        /**
         * @param \WPML_ST_Translations_File_Translation $translation
         *
         * @return bool
         */
        public function is_valid(\WPML_ST_Translations_File_Translation $translation)
        {
        }
    }
    interface WPML_ST_Translations_File_Scan_Charset_Validation
    {
        /**
         * @return bool
         */
        public function is_valid();
    }
    class WPML_ST_Translations_File_Scan_Db_Charset_Validation implements \WPML_ST_Translations_File_Scan_Charset_Validation
    {
        /**
         * @param wpdb                                         $wpdb
         * @param WPML_ST_Translations_File_Scan_Db_Table_List $table_list
         */
        public function __construct(\wpdb $wpdb, \WPML_ST_Translations_File_Scan_Db_Table_List $table_list)
        {
        }
        /**
         * @return bool
         */
        public function is_valid()
        {
        }
    }
    class WPML_ST_Translations_File_Scan_Db_Table_List
    {
        /**
         * @param wpdb $wpdb
         */
        public function __construct(\wpdb $wpdb)
        {
        }
        /**
         * @return array
         */
        public function get_tables()
        {
        }
    }
    class WPML_ST_Translations_File_Scan_Db_Charset_Filter_Factory
    {
        public function __construct(\wpdb $wpdb)
        {
        }
        public function create()
        {
        }
    }
    class WPML_ST_Translations_File_String_Status_Update
    {
        /**
         * @param int  $number_of_secondary_languages
         * @param wpdb $wpdb
         */
        public function __construct($number_of_secondary_languages, \wpdb $wpdb)
        {
        }
        public function add_hooks()
        {
        }
        public function update_string_statuses(\WPML_ST_Translations_File_Entry $file)
        {
        }
    }
    class WPML_ST_Translations_File_Scan_UI_Block
    {
        const NOTICES_GROUP = 'wpml-st-mo-scan';
        const NOTICES_MO_SCANNING_BLOCKED = 'mo-scanning-blocked';
        /**
         * @param WPML_Notices $notices
         */
        public function __construct(\WPML_Notices $notices)
        {
        }
        public function block_ui()
        {
        }
        public function unblock_ui()
        {
        }
        public function disable_option_handler($model)
        {
        }
    }
    class WPML_ST_Translations_File_Entry
    {
        const NOT_IMPORTED = 'not_imported';
        const IMPORTED = 'imported';
        const PARTLY_IMPORTED = 'partly_imported';
        const FINISHED = 'finished';
        const SKIPPED = 'skipped';
        const PATTERN_SEARCH_LANG_MO = '#[-]?([a-z]+[_A-Z]*)\\.mo$#i';
        const PATTERN_SEARCH_LANG_JSON = '#([a-z]+[_A-Z]*)-[-a-z0-9]+\\.json$#i';
        /**
         * @param string $path
         * @param string $domain
         * @param string $status
         */
        public function __construct($path, $domain, $status = self::NOT_IMPORTED)
        {
        }
        /**
         * @return string
         */
        public function get_path()
        {
        }
        public function get_full_path()
        {
        }
        /**
         * @return string
         */
        public function get_path_hash()
        {
        }
        /**
         * @return string
         */
        public function get_domain()
        {
        }
        /**
         * @return int
         */
        public function get_status()
        {
        }
        /**
         * @param string $status
         */
        public function set_status($status)
        {
        }
        /**
         * @return int
         */
        public function get_imported_strings_count()
        {
        }
        /**
         * @param int $imported_strings_count
         */
        public function set_imported_strings_count($imported_strings_count)
        {
        }
        /**
         * @return int
         */
        public function get_last_modified()
        {
        }
        /**
         * @param int $last_modified
         */
        public function set_last_modified($last_modified)
        {
        }
        public function __get($name)
        {
        }
        /**
         * It extracts locale from mo file path, examples
         * '/wp-content/languages/admin-pl_PL.mo' => 'pl'
         * '/wp-content/plugins/sitepress/sitepress-hr.mo' => 'hr'
         *
         * @return null|string
         * @throws RuntimeException
         */
        public function get_file_locale()
        {
        }
        /**
         * @return string
         */
        public function get_component_type()
        {
        }
        /**
         * @param string $component_type
         */
        public function set_component_type($component_type)
        {
        }
        /**
         * @return string
         */
        public function get_component_id()
        {
        }
        /**
         * @param string $component_id
         */
        public function set_component_id($component_id)
        {
        }
        public function get_extension()
        {
        }
    }
    interface WPML_ST_Translations_File_Components_Find
    {
        /**
         * @param string $file
         *
         * @return string|null
         */
        public function find_id($file);
    }
    class WPML_ST_Translations_File_Components_Find_Plugin implements \WPML_ST_Translations_File_Components_Find
    {
        /**
         * @param WPML_Debug_BackTrace $debug_backtrace
         */
        public function __construct(\WPML_Debug_BackTrace $debug_backtrace)
        {
        }
        public function find_id($file)
        {
        }
    }
    class WPML_ST_Translations_File_Component_Details
    {
        /**
         * @param WPML_ST_Translations_File_Components_Find_Plugin $plugin_id_finder
         * @param WPML_ST_Translations_File_Components_Find_Theme  $theme_id_finder
         * @param WPML_File                                        $wpml_file
         */
        public function __construct(\WPML_ST_Translations_File_Components_Find_Plugin $plugin_id_finder, \WPML_ST_Translations_File_Components_Find_Theme $theme_id_finder, \WPML_File $wpml_file)
        {
        }
        /**
         * @param string $file_full_path
         *
         * @return array
         */
        public function find_details($file_full_path)
        {
        }
        /**
         * @param string $component_type
         * @param string $file_full_path
         *
         * @return null|string
         */
        public function find_id($component_type, $file_full_path)
        {
        }
        /**
         * @param string $file_full_path
         *
         * @return string
         */
        public function find_type($file_full_path)
        {
        }
        /**
         * @param string $file_full_path
         *
         * @return bool
         */
        public function is_component_active($file_full_path)
        {
        }
    }
    class WPML_ST_Translations_File_Components_Find_Theme implements \WPML_ST_Translations_File_Components_Find
    {
        /**
         * @param WPML_Debug_BackTrace $debug_backtrace
         * @param WPML_File            $file
         */
        public function __construct(\WPML_Debug_BackTrace $debug_backtrace, \WPML_File $file)
        {
        }
        public function find_id($file)
        {
        }
    }
    class WPML_ST_Translations_File_Queue
    {
        const DEFAULT_LIMIT = 20000;
        const TIME_LIMIT = 10;
        // seconds
        const LOCK_FIELD = '_wpml_st_file_scan_in_progress';
        /**
         * @param WPML_ST_Translations_File_Dictionary   $file_dictionary
         * @param WPML_ST_Translations_File_Scan         $file_scan
         * @param WPML_ST_Translations_File_Scan_Storage $file_scan_storage
         * @param WPML_Language_Records                  $language_records
         * @param int                                    $limit
         * @param WPML_Transient                         $transient
         */
        public function __construct(\WPML_ST_Translations_File_Dictionary $file_dictionary, \WPML_ST_Translations_File_Scan $file_scan, \WPML_ST_Translations_File_Scan_Storage $file_scan_storage, \WPML_Language_Records $language_records, $limit, \WPML_Transient $transient)
        {
        }
        /**
         * @param QueueFilter|null $queueFilter
         */
        public function import(\WPML\ST\TranslationFile\QueueFilter $queueFilter = \null)
        {
        }
        /**
         * @return bool
         */
        public function is_completed()
        {
        }
        /**
         * @return string[]
         */
        public function get_processed()
        {
        }
        /**
         * @return bool
         */
        public function is_processing()
        {
        }
        /**
         * @return int
         */
        public function get_pending()
        {
        }
        public function mark_as_finished()
        {
        }
        public function is_locked()
        {
        }
    }
    class WPML_ST_Translations_File_Scan_Factory
    {
        public function check_core_dependencies()
        {
        }
        /**
         * @return array
         */
        public function create_hooks()
        {
        }
        /**
         * @return WPML_ST_Translations_File_Queue
         */
        public function create_queue()
        {
        }
    }
    class WPML_ST_Translations_File_Scan
    {
        /**
         * @param WPML_ST_Translations_File_Scan_Db_Charset_Filter_Factory $charset_filter_factory
         */
        public function __construct(\WPML_ST_Translations_File_Scan_Db_Charset_Filter_Factory $charset_filter_factory)
        {
        }
        /**
         * @param string $file
         *
         * @return WPML_ST_Translations_File_Translation[]
         */
        public function load_translations($file)
        {
        }
    }
    class WPML_ST_Translations_File_Registration
    {
        const PATH_PATTERN_SEARCH_MO = '#(-)?([a-z]+)([_A-Z]*)\\.mo$#i';
        const PATH_PATTERN_REPLACE_MO = '${1}%s.mo';
        const PATH_PATTERN_SEARCH_JSON = '#(DOMAIN_PLACEHOLDER)([a-z]+)([_A-Z]*)(-[-_a-z0-9]+)\\.json$#i';
        const PATH_PATTERN_REPLACE_JSON = '${1}%s${4}.json';
        /**
         * @param WPML_ST_Translations_File_Dictionary        $file_dictionary
         * @param WPML_File                                   $wpml_file
         * @param WPML_ST_Translations_File_Component_Details $components_find
         * @param array                                       $active_languages
         */
        public function __construct(\WPML_ST_Translations_File_Dictionary $file_dictionary, \WPML_File $wpml_file, \WPML_ST_Translations_File_Component_Details $components_find, array $active_languages)
        {
        }
        public function add_hooks()
        {
        }
        /**
         * @param bool   $override
         * @param string $domain
         * @param string $mo_file_path
         *
         * @return bool
         */
        public function cached_save_mo_file_info($override, $domain, $mo_file_path)
        {
        }
        /**
         * @param string|false $translations translations in the JED format
         * @param string|false $file
         * @param string       $handle
         * @param string       $original_domain
         *
         * @return string|false
         */
        public function add_json_translations_to_import_queue($translations, $file, $handle, $original_domain)
        {
        }
    }
    interface WPML_ST_Translations_File_Dictionary_Storage
    {
        public function save(\WPML_ST_Translations_File_Entry $file);
        /**
         * @param null|string       $path
         * @param null|string|array $status
         *
         * @return WPML_ST_Translations_File_Entry[]
         */
        public function find($path = \null, $status = \null);
    }
    class WPML_ST_Translations_File_Dictionary_Storage_Table implements \WPML_ST_Translations_File_Dictionary_Storage
    {
        /**
         * @param wpdb $wpdb
         */
        public function __construct(\wpdb $wpdb)
        {
        }
        public function add_hooks()
        {
        }
        public function save(\WPML_ST_Translations_File_Entry $file)
        {
        }
        /**
         * We have to postpone saving of real data because target table may not be created yet by migration process
         */
        public function persist()
        {
        }
        public function find($path = \null, $status = \null)
        {
        }
        public function reset()
        {
        }
    }
    class WPML_ST_Translations_File_Scan_Storage
    {
        /**
         * @param wpdb                        $wpdb
         * @param WPML_ST_Bulk_Strings_Insert $bulk_insert
         */
        public function __construct(\wpdb $wpdb, \WPML_ST_Bulk_Strings_Insert $bulk_insert)
        {
        }
        public function save(array $translations, $domain, $lang)
        {
        }
    }
}
namespace WPML\ST\MO\Scan\UI {
    class InstalledComponents
    {
        /**
         * @param Collection $components Collection of WPML_ST_Translations_File_Entry objects.
         *
         * @return Collection
         */
        public static function filter(\WPML\Collect\Support\Collection $components)
        {
        }
        /**
         * WPML_ST_Translations_File_Entry -> bool
         *
         * @return \Closure
         */
        public static function isPluginMissing()
        {
        }
        /**
         * WPML_ST_Translations_File_Entry -> bool
         *
         * @return \Closure
         */
        public static function isThemeMissing()
        {
        }
    }
    class UI
    {
        public static function add_hooks(callable $getModel, $isSTPage)
        {
        }
        public static function add_admin_notice()
        {
        }
        public static function localize($model)
        {
        }
    }
    class Factory implements \IWPML_Backend_Action_Loader, \IWPML_Deferred_Action_Loader
    {
        const WPML_VERSION_INTRODUCING_ST_MO_FLOW = '4.3.0';
        const OPTION_GROUP = 'ST-MO';
        const IGNORE_WPML_VERSION = 'ignore-wpml-version';
        /**
         * @return callable|null
         * @throws \WPML\Auryn\InjectionException
         */
        public function create()
        {
        }
        public function get_load_action()
        {
        }
        /**
         * @return bool
         * @throws \WPML\Auryn\InjectionException
         */
        public static function isDismissed()
        {
        }
        /**
         * @return int
         * @throws \WPML\Auryn\InjectionException
         */
        public static function getDomainsToPreGenerateCount()
        {
        }
        /**
         * @return bool
         */
        public static function shouldIgnoreWpmlVersion()
        {
        }
        public static function ignoreWpmlVersion()
        {
        }
        public static function clearIgnoreWpmlVersion()
        {
        }
    }
    class Model
    {
        /**
         * @param Collection $files_to_scan
         * @param int        $domains_to_pre_generate_count
         * @param bool       $is_st_page
         * @param bool       $is_network_admin
         *
         * @return \Closure
         */
        public static function provider(\WPML\Collect\Support\Collection $files_to_scan, $domains_to_pre_generate_count, $is_st_page, $is_network_admin)
        {
        }
    }
}
namespace {
    class WPML_ST_Translations_File_Translation
    {
        /**
         * @param string $original
         * @param string $translation
         * @param string $context
         */
        public function __construct($original, $translation, $context = '')
        {
        }
        /**
         * @return string
         */
        public function get_original()
        {
        }
        /**
         * @return string
         */
        public function get_translation()
        {
        }
        /**
         * @return string
         */
        public function get_context()
        {
        }
    }
    class WPML_ST_String_Factory
    {
        /**
         * WPML_ST_String_Factory constructor.
         *
         * @param wpdb $wpdb
         */
        public function __construct(\wpdb $wpdb)
        {
        }
        /**
         * @param int $string_id
         *
         * @return WPML_ST_String
         */
        public function find_by_id($string_id)
        {
        }
        /**
         * @param string $name
         *
         * @return WPML_ST_String
         */
        public function find_by_name($name)
        {
        }
        /**
         * @param string $name
         *
         * @return WPML_ST_Admin_String
         */
        public function find_admin_by_name($name)
        {
        }
        /**
         * @param string         $string
         * @param string|array   $context
         * @param string|false   $name
         *
         * @return mixed
         */
        public function get_string_id($string, $context, $name = \false)
        {
        }
    }
    class WPML_ST_Records
    {
        /** @var wpdb $wpdb */
        public $wpdb;
        /**
         * @param wpdb $wpdb
         */
        public function __construct(\wpdb $wpdb)
        {
        }
        /** @retur wpdb */
        public function get_wpdb()
        {
        }
        /**
         * @param int $string_id
         *
         * @return WPML_ST_ICL_Strings
         */
        public function icl_strings_by_string_id($string_id)
        {
        }
        /**
         * @param int    $string_id
         * @param string $language_code
         *
         * @return WPML_ST_ICL_String_Translations
         */
        public function icl_string_translations_by_string_id_and_language($string_id, $language_code)
        {
        }
    }
    class WPML_ST_ICL_String_Translations extends \WPML_WPDB_User
    {
        /**
         * WPML_ST_ICL_String_Translations constructor.
         *
         * @param wpdb   $wpdb
         * @param int    $string_id
         * @param string $lang_code
         */
        public function __construct(&$wpdb, $string_id, $lang_code)
        {
        }
        /**
         * @return int|string
         */
        public function translator_id()
        {
        }
        /**
         * @return string
         */
        public function value()
        {
        }
        /**
         * @return int
         */
        public function id()
        {
        }
    }
    class WPML_ST_ICL_Strings extends \WPML_WPDB_User
    {
        /**
         * WPML_TM_ICL_Strings constructor.
         *
         * @param wpdb $wpdb
         * @param int  $string_id
         */
        public function __construct(&$wpdb, $string_id)
        {
        }
        /**
         * @param array $args in the same format used by \wpdb::update()
         *
         * @return $this
         */
        public function update($args)
        {
        }
        /**
         * @return string
         */
        public function value()
        {
        }
        /**
         * @return string
         */
        public function language()
        {
        }
        /**
         * @return int
         */
        public function status()
        {
        }
    }
    class WPML_ST_Strings
    {
        const EMPTY_CONTEXT_LABEL = 'empty-context-domain';
        public function __construct($sitepress, $wpdb, $wp_query)
        {
        }
        public function get_string_translations()
        {
        }
        public function get_per_domain_counts($status)
        {
        }
    }
    /**
     * @author OnTheGo Systems
     */
    class WPML_ST_Privacy_Content extends \WPML_Privacy_Content
    {
        /**
         * @return string
         */
        protected function get_plugin_name()
        {
        }
        /**
         * @return string|array
         */
        protected function get_privacy_policy()
        {
        }
    }
    /**
     * @author OnTheGo Systems
     */
    class WPML_ST_Privacy_Content_Factory implements \IWPML_Backend_Action_Loader
    {
        /**
         * @return IWPML_Action
         */
        public function create()
        {
        }
    }
    /**
     * @author OnTheGo Systems
     */
    class WPML_ST_Support_Info_Filter implements \IWPML_Backend_Action, \IWPML_DIC_Action
    {
        function __construct(\WPML_ST_Support_Info $support_info)
        {
        }
        public function add_hooks()
        {
        }
        /**
         * @param array $blocks
         *
         * @return array
         */
        public function filter_blocks(array $blocks)
        {
        }
    }
    /**
     * @author OnTheGo Systems
     */
    class WPML_ST_Support_Info
    {
        public function is_mbstring_extension_loaded()
        {
        }
    }
    class WPML_ST_Translation_Memory implements \IWPML_AJAX_Action, \IWPML_Backend_Action, \IWPML_DIC_Action
    {
        public function __construct(\WPML_ST_Translation_Memory_Records $records)
        {
        }
        public function add_hooks()
        {
        }
        /**
         * @param array $empty_array
         * @param array $args with keys
         *                  - `strings` an array of strings
         *                  - `source_lang`
         *                  - `target_lang`
         *
         * @return stdClass[]
         */
        public function get_translation_memory($empty_array, $args)
        {
        }
    }
}
namespace WPML\ST\Main\Ajax {
    class FetchTranslationMemory implements \WPML\Ajax\IHandler
    {
        public function __construct(\WPML_ST_Translation_Memory_Records $records)
        {
        }
        public function run(\WPML\Collect\Support\Collection $data)
        {
        }
        public function fetchSingleString($data)
        {
        }
    }
}
namespace {
    class WPML_ST_Translation_Memory_Records
    {
        public function __construct(\wpdb $wpdb)
        {
        }
        /**
         * @param array $strings
         * @param string $source_lang
         * @param string $target_lang
         *
         * @return array
         */
        public function get($strings, $source_lang, $target_lang)
        {
        }
    }
    class WPML_ST_String_Tracking_AJAX implements \IWPML_Action
    {
        /**
         * WPML_ST_String_Tracking_AJAX constructor.
         *
         * @param WPML_ST_String_Positions      $string_position
         * @param WPML_Super_Globals_Validation $globals_validation
         * @param string                        $action
         */
        public function __construct(\WPML_ST_String_Positions $string_position, \WPML_Super_Globals_Validation $globals_validation, $action)
        {
        }
        public function add_hooks()
        {
        }
        public function render_string_position()
        {
        }
    }
    class WPML_ST_String_Tracking_AJAX_Factory implements \IWPML_AJAX_Action_Loader
    {
        const ACTION_POSITION_IN_SOURCE = 'view_string_in_source';
        const ACTION_POSITION_IN_PAGE = 'view_string_in_page';
        public function create()
        {
        }
    }
    abstract class WPML_ST_String_Positions
    {
        const TEMPLATE_PATH = '/templates/string-tracking/';
        /**
         * @var WPML_ST_DB_Mappers_String_Positions $string_position_mapper
         */
        protected $string_position_mapper;
        /**
         * @var IWPML_Template_Service $template_service
         */
        protected $template_service;
        public function __construct(\WPML_ST_DB_Mappers_String_Positions $string_position_mapper, \IWPML_Template_Service $template_service)
        {
        }
        /**
         * @param int $string_id
         *
         * @return array
         */
        protected abstract function get_model($string_id);
        /** @return string */
        protected abstract function get_template_name();
        /**
         * @param int $string_id
         */
        public function dialog_render($string_id)
        {
        }
        /**
         * @return WPML_ST_DB_Mappers_String_Positions
         */
        protected function get_mapper()
        {
        }
        /**
         * @return IWPML_Template_Service
         */
        protected function get_template_service()
        {
        }
    }
    /**
     * Class WPML_ST_String_Positions_In_Source
     */
    class WPML_ST_String_Positions_In_Source extends \WPML_ST_String_Positions
    {
        const KIND = \ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_SOURCE;
        const TEMPLATE = 'positions-in-source.twig';
        public function __construct(\SitePress $sitePress, \WPML_ST_DB_Mappers_String_Positions $string_position_mapper, \IWPML_Template_Service $template_service, \WPML_WP_API $wp_api)
        {
        }
        protected function get_model($string_id)
        {
        }
        protected function get_template_name()
        {
        }
    }
    class WPML_ST_String_Positions_In_Page extends \WPML_ST_String_Positions
    {
        const KIND = \ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_PAGE;
        const TEMPLATE = 'positions-in-page.twig';
        public function __construct(\WPML_ST_String_Factory $string_factory, \WPML_ST_DB_Mappers_String_Positions $string_position_mapper, \IWPML_Template_Service $template_service)
        {
        }
        protected function get_model($string_id)
        {
        }
        protected function get_template_name()
        {
        }
    }
}
namespace WPML\ST\Container {
    class Config
    {
        public static function getSharedClasses()
        {
        }
        public static function getAliases()
        {
        }
        public static function getDelegated()
        {
        }
    }
}
namespace {
    class WPML_ST_Reset
    {
        /**
         * @param wpdb             $wpdb
         * @param WPML_ST_Settings $settings
         */
        public function __construct($wpdb, \WPML_ST_Settings $settings = \null)
        {
        }
        public function reset()
        {
        }
        public function remove_db_tables()
        {
        }
    }
    class WPML_ST_Remote_String_Translation_Factory implements \IWPML_Backend_Action_Loader, \IWPML_Action
    {
        public function create()
        {
        }
        public function add_hooks()
        {
        }
        public function on_tm_loaded()
        {
        }
    }
}
namespace WPML\ST {
    class Actions
    {
        public static function get()
        {
        }
    }
}
namespace {
    class WPML_ST_WP_Loaded_Action extends \WPML_SP_User
    {
        public function __construct(&$sitepress, &$st_instance, &$pagenow, $get_page)
        {
        }
        public function run()
        {
        }
    }
    class WPML_PO_Import_Strings_Scripts
    {
        public function __construct()
        {
        }
        public function init()
        {
        }
        public function enqueue_scripts($page_hook)
        {
        }
    }
    class WPML_PO_Import_Strings
    {
        const NONCE_NAME = 'wpml-po-import-strings';
        public function maybe_import_po_add_strings()
        {
        }
        /**
         * @return null|WPML_PO_Import
         */
        public function import_po()
        {
        }
        /**
         * @return string
         */
        public function get_errors()
        {
        }
    }
    /**
     * WPML_ST_Admin_String class
     */
    class WPML_ST_Admin_String extends \WPML_ST_String
    {
        /**
         * @param string $new_value
         */
        public function update_value($new_value)
        {
        }
    }
}
namespace WPML\ST\DB\Mappers {
    class StringsRetrieve
    {
        public function __construct(\wpdb $wpdb, \WPML_DB_Chunk $chunk_retrieve)
        {
        }
        /**
         * @param string $language
         * @param string $domain
         * @param bool   $modified_mo_only
         *
         * @return array
         */
        public function get($language, $domain, $modified_mo_only = false)
        {
        }
    }
    /**
     * Class DomainsRepository
     * @package WPML\ST\DB\Mappers
     *
     * @method static callable|array getByStringIds( ...$stringIds ) - Curried :: int[]->string[]
     *
     */
    class DomainsRepository
    {
        use \WPML\FP\Curryable;
        public static function init()
        {
        }
    }
}
namespace {
    class WPML_ST_Bulk_Update_Strings_Status
    {
        public function __construct(\wpdb $wpdb, array $active_lang_codes)
        {
        }
        /**
         * This bulk process was transposed from PHP code
         *
         * @see WPML_ST_String::update_status
         *
         * Important: The order we call each method is important because it reflects
         * the order of the conditions in WPML_ST_String::update_status. The updated IDs
         * will not be updated anymore in the subsequent calls.
         *
         * @return array updated IDs
         */
        public function run()
        {
        }
    }
    class WPML_ST_Word_Count_Package_Records
    {
        public function __construct(\wpdb $wpdb)
        {
        }
        /** @return array */
        public function get_all_package_ids()
        {
        }
        /** @return array */
        public function get_packages_ids_without_word_count()
        {
        }
        /** @return array */
        public function get_word_counts($post_id)
        {
        }
        /**
         * @param int    $package_id
         * @param string $word_count
         */
        public function set_word_count($package_id, $word_count)
        {
        }
        /**
         * @param int $package_id
         *
         * @return null|string
         */
        public function get_word_count($package_id)
        {
        }
        public function reset_all(array $package_kinds)
        {
        }
        /**
         * @param array $kinds
         *
         * @return array
         */
        public function get_ids_from_kind_slugs(array $kinds)
        {
        }
        /**
         * @param array $post_types
         *
         * @return array
         */
        public function get_ids_from_post_types(array $post_types)
        {
        }
        /**
         * @param string $kind_slug
         *
         * @return int
         */
        public function count_items_by_kind_not_part_of_posts($kind_slug)
        {
        }
        /**
         * @param string $kind_slug
         *
         * @return int
         */
        public function count_word_counts_by_kind($kind_slug)
        {
        }
        /**
         * @param string $kind_slug
         *
         * @return array
         */
        public function get_word_counts_by_kind($kind_slug)
        {
        }
    }
    class WPML_ST_Models_String
    {
        /**
         * @param string $language
         * @param string $domain
         * @param string $context
         * @param string $value
         * @param int $status
         * @param string|null $name
         */
        public function __construct($language, $domain, $context, $value, $status, $name = \null)
        {
        }
        /**
         * @return string
         */
        public function get_language()
        {
        }
        /**
         * @return string
         */
        public function get_domain()
        {
        }
        /**
         * @return string
         */
        public function get_context()
        {
        }
        /**
         * @return string
         */
        public function get_value()
        {
        }
        /**
         * @return int
         */
        public function get_status()
        {
        }
        /**
         * @return string
         */
        public function get_name()
        {
        }
        /**
         * @return string
         */
        public function get_domain_name_context_md5()
        {
        }
    }
}
namespace WPML\ST\DB\Mappers {
    class Hooks implements \IWPML_Action, \IWPML_Backend_Action, \IWPML_Frontend_Action
    {
        public function add_hooks()
        {
        }
    }
    class Update
    {
        /**
         * @param  callable $getStringById
         * @param  int      $stringId
         * @param  string   $domain
         *
         * @return bool
         */
        public static function moveStringToDomain(callable $getStringById, $stringId, $domain)
        {
        }
        /**
         * @param string $oldDomain
         * @param string $newDomain
         */
        public static function moveAllStringsToNewDomain($oldDomain, $newDomain)
        {
        }
    }
}
namespace {
    class WPML_ST_DB_Mappers_String_Positions
    {
        /**
         * @param wpdb $wpdb
         */
        public function __construct(\wpdb $wpdb)
        {
        }
        /**
         * @param int $string_id
         * @param int $kind
         *
         * @return int
         */
        public function get_count_of_positions_by_string_and_kind($string_id, $kind)
        {
        }
        /**
         * @param int $string_id
         * @param int $kind
         *
         * @return array
         */
        public function get_positions_by_string_and_kind($string_id, $kind)
        {
        }
        /**
         * @param int    $string_id
         * @param string $position
         * @param int    $kind
         *
         * @return bool
         */
        public function is_string_tracked($string_id, $position, $kind)
        {
        }
        /**
         * @param int    $string_id
         * @param string $position
         * @param int    $kind
         */
        public function insert($string_id, $position, $kind)
        {
        }
    }
}
namespace WPML\ST\DB\Mappers {
    class StringTranslations
    {
        /**
         * @param  \wpdb  $wpdb
         * @param  int    $stringId
         * @param  string $language
         *
         * @return callable|bool
         */
        public static function hasTranslation($wpdb = null, $stringId = null, $language = null)
        {
        }
    }
}
namespace {
    class WPML_ST_Word_Count_String_Records
    {
        const CACHE_GROUP = __CLASS__;
        public function __construct(\wpdb $wpdb)
        {
        }
        /** @return int */
        public function get_total_words()
        {
        }
        /** @return array */
        public function get_all_values_without_word_count()
        {
        }
        /**
         * @param string      $lang
         * @param null|string $package_id
         *
         * @return int
         */
        public function get_words_to_translate_per_lang($lang, $package_id = \null)
        {
        }
        /**
         * @param int $string_id
         *
         * @return stdClass
         */
        public function get_value_and_language($string_id)
        {
        }
        /**
         * @param int $string_id
         * @param int $word_count
         */
        public function set_word_count($string_id, $word_count)
        {
        }
        /**
         * @param int $string_id
         *
         * @return int
         */
        public function get_word_count($string_id)
        {
        }
        public function reset_all()
        {
        }
        /**
         * @param array $package_ids
         *
         * @return array
         */
        public function get_ids_from_package_ids(array $package_ids)
        {
        }
    }
    class WPML_ST_Bulk_Strings_Insert_Exception extends \Exception
    {
    }
    class WPML_ST_Bulk_Strings_Insert
    {
        /**
         * @param wpdb $wpdb
         */
        public function __construct(\wpdb $wpdb)
        {
        }
        /**
         * @param int $chunk_size
         */
        public function set_chunk_size($chunk_size)
        {
        }
        /**
         * @param WPML_ST_Models_String[] $strings
         */
        public function insert_strings(array $strings)
        {
        }
        /**
         * @param WPML_ST_Models_String_Translation[] $translations
         */
        public function insert_string_translations(array $translations)
        {
        }
    }
    class WPML_ST_DB_Mappers_Strings
    {
        /**
         * @param wpdb $wpdb
         */
        public function __construct(\wpdb $wpdb)
        {
        }
        /**
         * @param string $context
         *
         * @return array
         */
        public function get_all_by_context($context)
        {
        }
        /**
         * Get a single string row by its domain and value
         *
         * @param string $domain
         * @param string $value
         *
         * @return array
         */
        public function getByDomainAndValue($domain, $value)
        {
        }
        /**
         * Get a single string row by its id
         *
         * @param int $id
         *
         * @return array
         */
        public function getById($id)
        {
        }
    }
    class WPML_ST_Models_String_Translation
    {
        /**
         * @param int $string_id
         * @param string $language
         * @param int $status
         * @param string|null $value
         */
        public function __construct($string_id, $language, $status, $value, $mo_string)
        {
        }
        /**
         * @return int
         */
        public function get_string_id()
        {
        }
        /**
         * @return string
         */
        public function get_language()
        {
        }
        /**
         * @return int
         */
        public function get_status()
        {
        }
        /**
         * @return string
         */
        public function get_value()
        {
        }
        /**
         * @return string
         */
        public function get_mo_string()
        {
        }
    }
}
namespace WPML\ST\Gettext {
    class AutoRegisterSettings
    {
        const KEY_EXCLUDED_DOMAINS = 'wpml_st_auto_reg_excluded_contexts';
        const KEY_ENABLED = 'auto_register_enabled';
        const RESET_AUTOLOAD_TIMEOUT = 2 * HOUR_IN_SECONDS;
        /**
         * @var wpdb $wpdb
         */
        protected $wpdb;
        public function __construct(\wpdb $wpdb, \WPML_ST_Settings $settings, \WPML\ST\Package\Domains $package_domains, \WPML_Localization $localization)
        {
        }
        /** @return bool */
        public function isEnabled()
        {
        }
        /**
         * @param bool $isEnabled
         */
        public function setEnabled($isEnabled)
        {
        }
        /**
         * @return int number of seconds before auto-disable
         */
        public function getTimeToAutoDisable()
        {
        }
        /**
         * @return array
         */
        public function getExcludedDomains()
        {
        }
        /**
         * @param string $domain
         *
         * @return bool
         */
        public function isExcludedDomain($domain)
        {
        }
        /**
         * @return array
         * @todo: Remove this method, looks like dead code.
         */
        public function get_included_contexts()
        {
        }
        /**
         * @return array
         */
        public function getAllDomains()
        {
        }
        /**
         * @param string $domain
         *
         * @return bool
         */
        public function isAdminOrPackageDomain($domain)
        {
        }
        /**
         * @return array
         */
        public function getDomainsAndTheirExcludeStatus()
        {
        }
        public function saveExcludedContexts()
        {
        }
        /** @return string */
        public function getFeatureEnabledDescription()
        {
        }
        /** @return string */
        public function getFeatureDisabledDescription()
        {
        }
        public function getDomainsWithStringsTranslationData()
        {
        }
    }
    class HooksFactory implements \IWPML_Backend_Action_Loader, \IWPML_Frontend_Action_Loader
    {
        const TRACK_PARAM_TEXT = 'icl_string_track_value';
        const TRACK_PARAM_DOMAIN = 'icl_string_track_context';
        /**
         * @return \IWPML_Action|Hooks|null
         * @throws \WPML\Auryn\InjectionException
         */
        public function create()
        {
        }
    }
    /**
     * Class WPML\ST\Gettext\Hooks
     */
    class Hooks implements \IWPML_Action
    {
        public function __construct(\SitePress $sitepress)
        {
        }
        /**
         * Init hooks.
         */
        public function add_hooks()
        {
        }
        public function addFilter(\WPML\ST\Gettext\Filters\IFilter $filter)
        {
        }
        public function clearFilters()
        {
        }
        /**
         * @param string $lang
         */
        public function switch_language_hook($lang)
        {
        }
        /**
         * @throws \WPML\Auryn\InjectionException
         * @deprecated since WPML ST 3.0.0
         */
        public function clear_filters()
        {
        }
        /**
         * Init gettext hooks.
         */
        public function init_gettext_hooks()
        {
        }
        /**
         * @param string       $translation
         * @param string       $text
         * @param string|array $domain
         * @param string|false $name Deprecated since WPML ST 3.0.0 (the name should be automatically created as a hash)
         *
         * @return string
         */
        public function gettext_filter($translation, $text, $domain, $name = false)
        {
        }
        /**
         * @param string       $translation
         * @param string       $text
         * @param string|false $context
         * @param string       $domain
         *
         * @return string
         */
        public function gettext_with_context_filter($translation, $text, $context, $domain)
        {
        }
        /**
         * @param string       $translation
         * @param string       $single
         * @param string       $plural
         * @param string       $number
         * @param string       $domain
         * @param string|false $context
         *
         * @return string
         */
        public function ngettext_filter($translation, $single, $plural, $number, $domain, $context = false)
        {
        }
        /**
         * @param string $translation
         * @param string $single
         * @param string $plural
         * @param string $number
         * @param string $context
         * @param string $domain
         *
         * @return string
         */
        public function ngettext_with_context_filter($translation, $single, $plural, $number, $context, $domain)
        {
        }
    }
    class Settings
    {
        public function __construct(\SitePress $sitepress, \WPML\ST\Gettext\AutoRegisterSettings $auto_register_settings)
        {
        }
        /** @return bool */
        public function isTrackStringsEnabled()
        {
        }
        /** @return string */
        public function getTrackStringColor()
        {
        }
        /** @return bool */
        public function isAutoRegistrationEnabled()
        {
        }
        /**
         * @param string|array $domain
         *
         * @return bool
         */
        public function isDomainRegistrationExcluded($domain)
        {
        }
    }
}
namespace WPML\ST\Gettext\Filters {
    interface IFilter
    {
        /**
         * @param string       $translation
         * @param string       $text
         * @param string|array $domain
         * @param string|false $name
         *
         * @return string
         */
        public function filter($translation, $text, $domain, $name = false);
    }
    class StringHighlighting implements \WPML\ST\Gettext\Filters\IFilter
    {
        public function __construct(\WPML\ST\Gettext\Settings $settings)
        {
        }
        /**
         * @param string       $translation
         * @param string       $text
         * @param string|array $domain
         * @param string|false $name
         *
         * @return string
         */
        public function filter($translation, $text, $domain, $name = false)
        {
        }
    }
    class StringTracking implements \WPML\ST\Gettext\Filters\IFilter
    {
        /**
         * @param string       $translation
         * @param string       $text
         * @param string|array $domain
         * @param string|false $name
         *
         * @return string
         */
        public function filter($translation, $text, $domain, $name = false)
        {
        }
        /**
         * @return bool
         */
        public function canTrackStrings()
        {
        }
    }
    class StringTranslation implements \WPML\ST\Gettext\Filters\IFilter
    {
        public function __construct(\WPML\ST\Gettext\Settings $settings)
        {
        }
        /**
         * @param string       $translation
         * @param string       $text
         * @param string|array $domain
         * @param string|false $name
         *
         * @return string
         */
        public function filter($translation, $text, $domain, $name = false)
        {
        }
    }
}
namespace {
    class WPML_ST_String_Translation_AJAX_Hooks_Factory implements \IWPML_Backend_Action_Loader
    {
        public function create()
        {
        }
    }
    class WPML_Strings_Translation_Priority
    {
        /**
         * @param wpdb $wpdb
         */
        public function __construct(\wpdb $wpdb)
        {
        }
        /**
         * @param int[]  $strings
         * @param string $priority
         */
        public function change_translation_priority_of_strings($strings, $priority)
        {
        }
    }
    class WPML_ST_String_Translation_Priority_AJAX implements \IWPML_Action
    {
        /**
         * @param wpdb $wpdb
         */
        public function __construct(\wpdb $wpdb)
        {
        }
        public function add_hooks()
        {
        }
        public function change_string_translation_priority()
        {
        }
    }
}
namespace WPML\ST {
    class TranslateWpmlString
    {
        public function __construct(\WPML\ST\StringsFilter\Provider $filterProvider, \WPML\ST\MO\Hooks\LanguageSwitch $languageSwitch, \WPML_Locale $locale, \WPML\ST\Gettext\Settings $gettextSettings, \WPML\ST\MO\File\Manager $fileManager)
        {
        }
        public function init()
        {
        }
        /**
         * @param string|array $wpmlContext
         * @param string       $name
         * @param bool|string  $value
         * @param bool         $allowEmptyValue
         * @param null|bool    $hasTranslation
         * @param null|string  $targetLang
         *
         * @return bool|string
         */
        public function translate($wpmlContext, $name, $value = false, $allowEmptyValue = false, &$hasTranslation = null, $targetLang = null)
        {
        }
        /**
         * We will allow MO translation only when
         * the original is not empty.
         *
         * We also need to make sure we deal with a
         * WPML registered string (not gettext).
         *
         * If those conditions are not fulfilled,
         * we will translate from the database.
         *
         * @param string|bool $original
         * @param string      $name
         *
         * @return bool
         */
        public static function canTranslateWithMO($original, $name)
        {
        }
        public static function resetCache()
        {
        }
    }
}
namespace WPML\ST\Batch\Translation {
    /**
     * @phpstan-type curried '__CURRIED_PLACEHOLDER__'
     *
     * @method static callable|void installSchema( ...$wpdb ) :: wpdb → void
     * @method static callable|void set( ...$wpdb, ...$batchId, ...$stringId ) :: wpdb → int → int → void
     * @method static callable|int[] findBatches( ...$wpdb, ...$stringId ) :: wpdb → int → int[]
     */
    class Records
    {
        use \WPML\FP\Curryable;
        /** @var string */
        public static $string_batch_sql_prototype = '
	CREATE TABLE IF NOT EXISTS `%sicl_string_batches` (
	  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	  `string_id` bigint(20) unsigned NOT NULL,
	  `batch_id` bigint(20) unsigned NOT NULL,
	  PRIMARY KEY (`id`)
		)
	';
        /**
         * @param \wpdb|null $wpdb
         * @param int|curried $batchId
         * @return int|callable
         *
         * @phpstan-return ($batchId is not null ? int : callable)
         */
        public static function get(\wpdb $wpdb = null, $batchId = null)
        {
        }
    }
    class Hooks
    {
        public static function addHooks(callable $getBatchId, callable $setBatchRecord, callable $getBatchRecord, callable $getString)
        {
        }
        public static function addStringTranslationStatusHooks(callable $updateTranslationStatus, callable $initializeTranslation)
        {
        }
    }
    /**
     * Class StringTranslations
     *
     * @package WPML\ST\Batch\Translation
     *
     * @phpstan-type curried '__CURRIED_PLACEHOLDER__'
     *
     * @method static callable|void save( ...$element_type_prefix, ...$job, ...$decoder ) :: string → object → ( string → string → string ) → void
     * @method static callable|void addExisting( ...$prevTranslations, ...$package, ...$lang ) :: [WPML_TM_Translated_Field] → object → string → [WPML_TM_Translated_Field]
     * @method static callable|bool isTranslated( ...$field ) :: object → bool
     * @method static callable|void markTranslationsAsInProgress( ...$getJobStatus, ...$hasTranslation, ...$addTranslation, ...$post, ...$element) :: callable -> callable -> callable -> WPML_TM_Translation_Batch_Element -> \stdClass -> void
     * @method static callable|void cancelTranslations(...$job) :: \WPML_TM_Job_Entity -> void
     */
    class StringTranslations
    {
        use \WPML\Collect\Support\Traits\Macroable;
        public static function init()
        {
        }
        /**
         * @param string $element_type_prefix
         * @param \stdClass $job
         * @return callable|void
         * @phpstan-return ( $job is not null ? void : callable )
         */
        public static function updateStatus($element_type_prefix = null, $job = null)
        {
        }
        /**
         * @param string $str
         *
         * @return callable|string
         *
         * @phpstan-template A1 of string|curried
         * @phpstan-param ?A1 $str
         * @phpstan-return ($str is not null ? string : callable(string=):string)
         */
        public static function decodeStringId($str = null)
        {
        }
        /**
         * @param string $str
         *
         * @return callable|bool
         *
         * @phpstan-template A1 of string|curried
         * @phpstan-param ?A1 $str
         * @phpstan-return ($str is not null ? bool : callable(string=):bool)
         */
        public static function isBatchId($str = null)
        {
        }
        /**
         * @param string $field
         *
         * @return callable|bool
         *
         * @phpstan-template A1 of string|curried
         * @phpstan-param ?A1 $field
         * @phpstan-return ($field is not null ? bool : callable(string):bool)
         */
        public static function isBatchField($field = null)
        {
        }
    }
    /**
     * Class Strings
     *
     * @package WPML\ST\Batch\Translation
     * @method static callable|object get( ...$getBatchRecord, ...$getString, ...$item, ...$id, ...$type )
     */
    class Strings
    {
        use \WPML\Collect\Support\Traits\Macroable;
        public static function init()
        {
        }
    }
    /**
     * Class Convert
     *
     * @package WPML\ST\Batch\Translation
     * @method static callable|array toBatchElements( ...$getBatchId, ...$setBatchRecord, ...$elements, ...$basketName ) :: ( string → int ) → ( int → int → string ) → [WPML_TM_Translation_Batch_Element] → string → [WPML_TM_Translation_Batch_Element]
     */
    class Convert
    {
        use \WPML\Collect\Support\Traits\Macroable;
        public static function init()
        {
        }
    }
    class Status
    {
        public static function add(array $translations, $languages)
        {
        }
        public static function getStatuses(\wpdb $wpdb, $batches)
        {
        }
        public static function getStatusesOfBatch(\wpdb $wpdb, $batchId)
        {
        }
    }
    /**
     * Class Module
     * @package WPML\ST\Batch\Translation
     *
     * @phpstan-type curried '__CURRIED_PLACEHOLDER__'
     *
     * @method static callable getBatchId() :: ( string → int )
     * @method static callable|void setBatchLanguage( ...$batchId, ...$sourceLang ) :: int → string → void
     */
    class Module
    {
        use \WPML\Collect\Support\Traits\Macroable;
        const EXTERNAL_TYPE = 'st-batch_strings';
        const STRING_ID_PREFIX = 'batch-string-';
        public static function init()
        {
        }
        /**
         * @param int $id
         * @return string|callable
         * @phpstan-return ($id is not null ? string : callable )
         */
        public static function getString($id = null)
        {
        }
        /**
         * @param callable|curried $saveBatch
         * @param int|curried $batchId
         * @param int|curried $stringId
         * @param string|curried $sourceLang
         * @return void|callable
         *
         * @phpstan-param ?callable $saveBatch
         * @phpstan-param ?int $batchId
         * @phpstan-param ?int $stringId
         * @phpstan-param ?string $sourceLang
         *
         * @phpstan-return ( $sourceLang is not null ? void : callable )
         *
         */
        public static function batchStringsStorage(callable $saveBatch = null, $batchId = null, $stringId = null, $sourceLang = null)
        {
        }
    }
}
namespace {
    class WPML_ST_Upgrade_String_Index
    {
        const OPTION_NAME = 'wpml_string_table_ok_for_mo_import';
        /**
         * @param wpdb $wpdb
         */
        public function __construct(\wpdb $wpdb)
        {
        }
        public function is_uc_domain_name_context_index_unique()
        {
        }
    }
    /**
     * Class WPML_ST_Upgrade_Command_Factory
     */
    class WPML_ST_Upgrade_Command_Factory
    {
        /**
         * WPML_ST_Upgrade_Command_Factory constructor.
         *
         * @param wpdb      $wpdb WP db instance.
         * @param SitePress $sitepress SitePress instance.
         */
        public function __construct(\wpdb $wpdb, \SitePress $sitepress)
        {
        }
        /**
         * Create upgrade commands.
         *
         * @param string $class_name Name of upgrade command class.
         *
         * @throws WPML_ST_Upgrade_Command_Not_Found_Exception Exception when command not found.
         * @return IWPML_St_Upgrade_Command
         */
        public function create($class_name)
        {
        }
    }
    class WPML_ST_Repair_Strings_Schema
    {
        const OPTION_HAS_RUN = 'wpml_st_repair_string_schema_has_run';
        public function __construct(\WPML_Notices $notices, array $args, $db_error)
        {
        }
        public function set_command(\IWPML_St_Upgrade_Command $upgrade_command)
        {
        }
        /** @return bool */
        public function run()
        {
        }
    }
    class WPML_ST_Upgrade_Command_Not_Found_Exception extends \InvalidArgumentException
    {
        /**
         * @param string    $class_name
         * @param int       $code
         * @param Exception $previous
         */
        public function __construct($class_name, $code = 0, \Exception $previous = \null)
        {
        }
    }
    interface IWPML_St_Upgrade_Command
    {
        public function run();
        public function run_ajax();
        public function run_frontend();
        public static function get_command_id();
    }
    class WPML_ST_Upgrade_Migrate_Originals implements \IWPML_St_Upgrade_Command
    {
        public function __construct(\wpdb $wpdb, \SitePress $sitepress)
        {
        }
        public static function get_command_id()
        {
        }
        public function run()
        {
        }
        function update_message()
        {
        }
        public function run_ajax()
        {
        }
        public function run_frontend()
        {
        }
    }
    class WPML_ST_Upgrade_Display_Strings_Scan_Notices implements \IWPML_St_Upgrade_Command
    {
        /**
         * WPML_ST_Upgrade_Display_Strings_Scan_Notices constructor.
         *
         * @param WPML_ST_Themes_And_Plugins_Settings $settings
         */
        public function __construct(\WPML_ST_Themes_And_Plugins_Settings $settings)
        {
        }
        public static function get_command_id()
        {
        }
        public function run()
        {
        }
        public function run_ajax()
        {
        }
        public function run_frontend()
        {
        }
    }
    class WPML_ST_Upgrade_MO_Scanning implements \IWPML_St_Upgrade_Command
    {
        /**
         * @param wpdb $wpdb
         */
        public function __construct(\wpdb $wpdb)
        {
        }
        public function run()
        {
        }
        public function run_ajax()
        {
        }
        public function run_frontend()
        {
        }
        public static function get_command_id()
        {
        }
    }
    /**
     * WPML_ST_Upgrade class file.
     *
     * @package wpml-string-translation
     */
    /**
     * Class WPML_ST_Upgrade
     */
    class WPML_ST_Upgrade
    {
        const TRANSIENT_UPGRADE_IN_PROGRESS = 'wpml_st_upgrade_in_progress';
        /**
         * WPML_ST_Upgrade constructor.
         *
         * @param SitePress                            $sitepress SitePress instance.
         * @param WPML_ST_Upgrade_Command_Factory|null $command_factory Upgrade Command Factory instance.
         */
        public function __construct(\SitePress $sitepress, \WPML_ST_Upgrade_Command_Factory $command_factory = \null)
        {
        }
        /**
         * Run upgrade.
         */
        public function run()
        {
        }
        /**
         * Check if command was executed.
         *
         * @param string $class Command class name.
         *
         * @return bool
         */
        public function has_command_been_executed($class)
        {
        }
        /**
         * Mark command as executed.
         *
         * @param string $class Command class name.
         */
        public function mark_command_as_executed($class)
        {
        }
        /**
         * Filter nonce.
         *
         * @return mixed
         */
        protected function filter_nonce_parameter()
        {
        }
    }
    class WPML_ST_Upgrade_DB_Strings_Add_Translation_Priority_Field implements \IWPML_St_Upgrade_Command
    {
        /**
         * @param wpdb $wpdb
         */
        public function __construct(\wpdb $wpdb)
        {
        }
        public function run()
        {
        }
        public function run_ajax()
        {
        }
        public function run_frontend()
        {
        }
        public static function get_command_id()
        {
        }
    }
}
namespace WPML\ST\Upgrade\Command {
    class RegenerateMoFilesWithStringNames implements \IWPML_St_Upgrade_Command
    {
        const WPML_VERSION_FOR_THIS_COMMAND = '4.3.4';
        /**
         * @param Status            $status
         * @param SingleSiteProcess $singleProcess We use run the single site process because
         *                                         the migration command runs once per site.
         */
        public function __construct(\WPML\ST\MO\Generate\Process\Status $status, \WPML\ST\MO\Generate\Process\SingleSiteProcess $singleProcess)
        {
        }
        public function run()
        {
        }
        public function run_ajax()
        {
        }
        public function run_frontend()
        {
        }
        public static function get_command_id()
        {
        }
    }
    class MigrateMultilingualWidgets implements \IWPML_St_Upgrade_Command
    {
        public function run()
        {
        }
        public function run_ajax()
        {
        }
        public function run_frontend()
        {
        }
        public static function get_command_id()
        {
        }
    }
}
namespace {
    class WPML_ST_Upgrade_DB_String_Name_Index implements \IWPML_St_Upgrade_Command
    {
        /**
         * @param wpdb $wpdb
         */
        public function __construct(\wpdb $wpdb)
        {
        }
        public function run()
        {
        }
        public function run_ajax()
        {
        }
        public function run_frontend()
        {
        }
        public static function get_command_id()
        {
        }
    }
    /**
     * Class WPML_ST_Upgrade_DB_String_Packages
     */
    class WPML_ST_Upgrade_DB_String_Packages implements \IWPML_St_Upgrade_Command
    {
        /**
         * WPML_ST_Upgrade_DB_String_Packages constructor.
         *
         * @param wpdb $wpdb
         */
        public function __construct(\wpdb $wpdb)
        {
        }
        public function run()
        {
        }
        public function run_ajax()
        {
        }
        public function run_frontend()
        {
        }
        /**
         * @return string
         */
        public static function get_command_id()
        {
        }
    }
    /**
     * WPML_ST_Upgrade_DB_Longtext_String_Value class file.
     *
     * @package wpml-string-translation
     */
    /**
     * Class WPML_ST_Upgrade_DB_Longtext_String_Value
     */
    class WPML_ST_Upgrade_DB_Longtext_String_Value implements \IWPML_St_Upgrade_Command
    {
        /**
         * WPML_ST_Upgrade_DB_Longtext_String_Value constructor.
         *
         * @param wpdb $wpdb WP db instance.
         */
        public function __construct(\wpdb $wpdb)
        {
        }
        /**
         * Run upgrade.
         *
         * @return bool
         */
        public function run()
        {
        }
        /**
         * Run upgrade in ajax.
         *
         * @return bool
         */
        public function run_ajax()
        {
        }
        /**
         * Run upgrade on frontend.
         *
         * @return bool
         */
        public function run_frontend()
        {
        }
        /**
         * Get command id.
         *
         * @return string
         */
        public static function get_command_id()
        {
        }
    }
    class WPML_ST_Upgrade_DB_String_Packages_Word_Count implements \IWPML_St_Upgrade_Command
    {
        public function __construct(\WPML_Upgrade_Schema $upgrade_schema)
        {
        }
        public function run()
        {
        }
        public function run_ajax()
        {
        }
        public function run_frontend()
        {
        }
        /**
         * @return string
         */
        public static function get_command_id()
        {
        }
    }
}
namespace WPML\ST\Storage {
    interface StoragePerLanguageInterface
    {
        /* Defined value to allow for null/false values to be stored. */
        const NOTHING = '___NOTHING___';
        // Allow to store a value for all languages.
        const GLOBAL_GROUP = '__ALL_LANG__';
        /**
         * @param string $lang
         *
         * @return mixed Returns self::NOTHING if there is nothing stored.
         */
        public function get($lang);
        /**
         * @param string $lang
         * @param mixed  $value
         */
        public function save($lang, $value);
        /**
         * @param string $lang
         */
        public function delete($lang);
    }
    class WpTransientPerLanguage implements \WPML\ST\Storage\StoragePerLanguageInterface
    {
        // 1 day.
        /**
         * @param string $id
         */
        public function __construct($id)
        {
        }
        /**
         * @param string $lang
         * @return mixed Returns StorageInterface::NOTHING if there is no cache.
         */
        public function get($lang)
        {
        }
        /**
         * @param string $lang
         * @param mixed  $value
         */
        public function save($lang, $value)
        {
        }
        public function delete($lang)
        {
        }
        /**
         * Set the lifetime.
         *
         * @param int $lifetime
         */
        public function setLifetime($lifetime)
        {
        }
    }
}
namespace WPML\ST\Troubleshooting {
    class RequestHandle implements \IWPML_Action
    {
        public function __construct($action, $callback)
        {
        }
        public function add_hooks()
        {
        }
        public function handle()
        {
        }
    }
}
namespace WPML\ST\Troubleshooting\Cleanup {
    class Database
    {
        public function __construct(\wpdb $wpdb, \WPML_ST_Translations_File_Dictionary $dictionary)
        {
        }
        public function deleteStringsFromImportedMoFiles()
        {
        }
        public function truncatePagesAndUrls()
        {
        }
    }
}
namespace WPML\ST\Troubleshooting {
    class BackendHooks implements \IWPML_Backend_Action, \IWPML_DIC_Action
    {
        const SCRIPT_HANDLE = 'wpml-st-troubleshooting';
        const NONCE_KEY = 'wpml-st-troubleshooting';
        public function __construct(\WPML\ST\MO\Generate\DomainsAndLanguagesRepository $domainsAndLanguagesRepo)
        {
        }
        public function add_hooks()
        {
        }
        public function displayButtons()
        {
        }
        /**
         * @param string $hook
         */
        public function loadJS($hook)
        {
        }
    }
    class AjaxFactory implements \IWPML_AJAX_Action_Loader
    {
        const ACTION_SHOW_GENERATE_DIALOG = 'wpml_st_mo_generate_show_dialog';
        const ACTION_CLEANUP = 'wpml_st_troubleshooting_cleanup';
        public function create()
        {
        }
        /**
         * @return \WPML\Collect\Support\Collection
         */
        public static function getActions()
        {
        }
        /**
         * @return \Closure
         */
        public static function buildHandler()
        {
        }
        /**
         * @throws \WPML\Auryn\InjectionException
         */
        public static function showGenerateDialog()
        {
        }
        /**
         * @throws \WPML\Auryn\InjectionException
         */
        public static function cleanup()
        {
        }
    }
}
namespace WPML\ST\MO {
    class LoadedMODictionary
    {
        const PATTERN_SEARCH_LOCALE = '#([-]?)([a-z]+[_A-Z]*)(\\.mo)$#i';
        const LOCALE_PLACEHOLDER = '{LOCALE}';
        public function __construct()
        {
        }
        /**
         * @param string $domain
         * @param string $mofile
         */
        public function addFile($domain, $mofile)
        {
        }
        /**
         * @param array $excluded
         *
         * @return array
         */
        public function getDomains(array $excluded = [])
        {
        }
        /**
         * @param string $domain
         * @param string $locale
         *
         * @return Collection
         */
        public function getFiles($domain, $locale)
        {
        }
        /**
         * @return Collection
         */
        public function getEntities()
        {
        }
    }
}
namespace WPML\ST\MO\File {
    class ManagerFactory
    {
        /**
         * @return Manager
         * @throws \WPML\Auryn\InjectionException
         */
        public static function create()
        {
        }
    }
    class FailureHooks implements \IWPML_Backend_Action
    {
        use \WPML\ST\MO\File\makeDir;
        const NOTICE_GROUP = 'mo-failure';
        const NOTICE_ID_MISSING_FOLDER = 'missing-folder';
        public function __construct(\WP_Filesystem_Direct $filesystem, \WPML\ST\MO\Generate\Process\Status $status, \WPML\ST\MO\Generate\Process\SingleSiteProcess $singleProcess)
        {
        }
        public function add_hooks()
        {
        }
        public function checkDirectories()
        {
        }
        /**
         * @param string $dir
         */
        public function displayMissingFolderNotice($dir)
        {
        }
        /**
         * @param string $dir
         *
         * @return string
         */
        public static function missingFolderNoticeContent($dir)
        {
        }
        /**
         * @return string
         */
        public static function getSubdir()
        {
        }
    }
    class FailureHooksFactory implements \IWPML_Backend_Action_Loader
    {
        /**
         * @return FailureHooks|null
         * @throws \WPML\Auryn\InjectionException
         */
        public function create()
        {
        }
    }
    class Manager extends \WPML\ST\TranslationFile\Manager
    {
        public function __construct(\WPML\ST\TranslationFile\StringsRetrieve $strings, \WPML\ST\MO\File\Builder $builder, \WP_Filesystem_Direct $filesystem, \WPML_Language_Records $language_records, \WPML\ST\TranslationFile\Domains $domains)
        {
        }
        /**
         * @return string
         */
        protected function getFileExtension()
        {
        }
        /**
         * @return bool
         */
        public function isPartialFile()
        {
        }
        /**
         * @return Collection
         */
        protected function getDomains()
        {
        }
        /**
         * @return bool
         */
        public static function hasFiles()
        {
        }
    }
    class Generator
    {
        public function __construct(\WPML\ST\MO\File\MOFactory $moFactory)
        {
        }
        /**
         * @param StringEntity[] $entries
         *
         * @return string
         */
        public function getContent(array $entries)
        {
        }
        /**
         * @param Collection   $carry
         * @param StringEntity $entry
         *
         * @return Collection
         */
        public function createMOFormatEntities($carry, \WPML\ST\TranslationFile\StringEntity $entry)
        {
        }
    }
    class MOFactory
    {
        /**
         * @return \MO
         */
        public function createNewInstance()
        {
        }
    }
    class Builder extends \WPML\ST\TranslationFile\Builder
    {
        public function __construct(\WPML\ST\MO\File\Generator $generator)
        {
        }
        /**
         * @param StringEntity[] $strings
         * @return string
         */
        public function get_content(array $strings)
        {
        }
    }
}
namespace WPML\ST\MO {
    class WPLocaleProxy
    {
        /**
         * @param string $method
         * @param array  $args
         *
         * @return mixed|null
         */
        public function __call($method, array $args)
        {
        }
        /**
         * @param string $property
         *
         * @return bool
         */
        public function __isset($property)
        {
        }
        /**
         * @param string $property
         *
         * @return mixed|null
         */
        public function __get($property)
        {
        }
    }
}
namespace WPML\ST\MO\Generate\MultiSite {
    class Executor
    {
        const MAIN_SITE_ID = 1;
        /**
         * @param callable $callback
         *
         * @return \WPML\Collect\Support\Collection
         */
        public function withEach($callback)
        {
        }
        /**
         * @return \WPML\Collect\Support\Collection
         */
        public function getSiteIds()
        {
        }
        /**
         * @param int      $siteId
         * @param callable $callback
         *
         * @return mixed
         */
        public function executeWith($siteId, callable $callback)
        {
        }
    }
    class Condition
    {
        /**
         * @return bool
         */
        public function shouldRunWithAllSites()
        {
        }
    }
}
namespace WPML\ST\MO\Generate {
    class DomainsAndLanguagesRepository
    {
        /**
         * @param wpdb        $wpdb
         * @param Domains     $domains
         * @param WPML_Locale $wp_locale
         */
        public function __construct(\wpdb $wpdb, \WPML\ST\TranslationFile\Domains $domains, \WPML_Locale $wp_locale)
        {
        }
        /**
         * @return Collection
         */
        public function get()
        {
        }
        /**
         * @return bool
         */
        public static function hasTranslationFilesTable()
        {
        }
    }
    class MissingMOFile
    {
        use \WPML\ST\MO\File\makeDir;
        const OPTION_GROUP = 'ST-MO';
        const OPTION_NAME = 'missing-mo-processed';
        public function __construct(\WP_Filesystem_Direct $filesystem, \WPML\ST\MO\File\Builder $builder, \WPML\ST\MO\Generate\StringsRetrieveMOOriginals $stringsRetrieve, \WPML_Language_Records $languageRecords, \WPML\WP\OptionManager $optionManager)
        {
        }
        /**
         * @param string $generateMoPath
         * @param string $domain
         */
        public function run($generateMoPath, $domain)
        {
        }
        public function isNotProcessed($generateMoPath)
        {
        }
        public static function getSubdir()
        {
        }
    }
}
namespace WPML\ST\MO\Generate\Process {
    interface Process
    {
        public function runAll();
        /**
         * @return int Remaining
         */
        public function runPage();
        /**
         * @return int
         */
        public function getPagesCount();
        /**
         * @return bool
         */
        public function isCompleted();
    }
    class SingleSiteProcess implements \WPML\ST\MO\Generate\Process\Process
    {
        const TIMEOUT = 5;
        /**
         * @param DomainsAndLanguagesRepository $domainsAndLanguagesRepository
         * @param Manager                       $manager
         * @param Status                        $status
         * @param Pager                         $pager
         * @param callable                      $migrateAdminTexts
         */
        public function __construct(\WPML\ST\MO\Generate\DomainsAndLanguagesRepository $domainsAndLanguagesRepository, \WPML\ST\MO\File\Manager $manager, \WPML\ST\MO\Generate\Process\Status $status, \WPML\Utils\Pager $pager, callable $migrateAdminTexts)
        {
        }
        public function runAll()
        {
        }
        /**
         * @return int Remaining
         */
        public function runPage()
        {
        }
        public function getPagesCount()
        {
        }
        /**
         * @return bool
         */
        public function isCompleted()
        {
        }
    }
    class SubSiteValidator
    {
        /**
         * @return bool
         */
        public function isValid()
        {
        }
    }
    class MultiSiteProcess implements \WPML\ST\MO\Generate\Process\Process
    {
        /**
         * @param Executor          $multiSiteExecutor
         * @param SingleSiteProcess $singleSiteProcess
         * @param Status            $status
         * @param Pager             $pager
         * @param SubSiteValidator  $subSiteValidator
         */
        public function __construct(\WPML\ST\MO\Generate\MultiSite\Executor $multiSiteExecutor, \WPML\ST\MO\Generate\Process\SingleSiteProcess $singleSiteProcess, \WPML\ST\MO\Generate\Process\Status $status, \WPML\Utils\Pager $pager, \WPML\ST\MO\Generate\Process\SubSiteValidator $subSiteValidator)
        {
        }
        public function runAll()
        {
        }
        /**
         * @return int Is completed
         */
        public function runPage()
        {
        }
        /**
         * @return int
         */
        public function getPagesCount()
        {
        }
        /**
         * @return bool
         */
        public function isCompleted()
        {
        }
    }
    class Status
    {
        /**
         * @param \SitePress  $sitepress
         * @param string|null $optionPrefix
         */
        public function __construct(\SitePress $sitepress, $optionPrefix = null)
        {
        }
        /**
         * @param bool $allSites
         */
        public function markComplete($allSites = false)
        {
        }
        /**
         * @param bool $allSites
         */
        public function markIncomplete($allSites = false)
        {
        }
        public function markIncompleteForAll()
        {
        }
        /**
         * @return bool
         */
        public function isComplete()
        {
        }
        /**
         * @return bool
         */
        public function isCompleteForAllSites()
        {
        }
    }
    class ProcessFactory
    {
        const FILES_PAGER = 'wpml-st-mo-generate-files-pager';
        const FILES_PAGE_SIZE = 20;
        const SITES_PAGER = 'wpml-st-mo-generate-sites-pager';
        /**
         * @param Condition $multiSiteCondition
         */
        public function __construct(\WPML\ST\MO\Generate\MultiSite\Condition $multiSiteCondition = null)
        {
        }
        /**
         * @return Process
         * @throws \WPML\Auryn\InjectionException
         */
        public function create()
        {
        }
        /**
         * @param bool $isBackgroundProcess
         *
         * @return SingleSiteProcess
         * @throws \WPML\Auryn\InjectionException
         */
        public static function createSingle($isBackgroundProcess = false)
        {
        }
        /**
         * @param bool $isBackgroundProcess
         *
         * @return mixed|\Mockery\MockInterface|Status
         * @throws \WPML\Auryn\InjectionException
         */
        public static function createStatus($isBackgroundProcess = false)
        {
        }
    }
}
namespace WPML\ST\MO\Generate {
    class StringsRetrieveMOOriginals extends \WPML\ST\TranslationFile\StringsRetrieve
    {
        /**
         * @param array $row_data
         *
         * @return string|null
         */
        public static function parseTranslation(array $row_data)
        {
        }
    }
}
namespace WPML\ST\MO\Hooks {
    class LanguageSwitch implements \IWPML_Action
    {
        public function __construct(\WPML\ST\Utils\LanguageResolution $language_resolution, \WPML\ST\MO\JustInTime\MOFactory $jit_mo_factory)
        {
        }
        public function add_hooks()
        {
        }
        /** @return string */
        public function getCurrentLocale()
        {
        }
        public function languageHasSwitched()
        {
        }
        public function initCurrentLocale()
        {
        }
        /**
         * This method will act as the WP Core function `switch_to_locale`,
         * but in a more efficient way. It will avoid to instantly load
         * the domains loaded in the previous locale. Instead, it will let
         * the domains be loaded via the "just in time" function.
         *
         * @param string $new_locale
         */
        public function switchToLocale($new_locale)
        {
        }
        /**
         * @param string|null $locale
         */
        public static function resetCache($locale = null)
        {
        }
        /**
         * @param string $locale
         *
         * @return string
         */
        public function filterLocale($locale)
        {
        }
    }
    class LoadTextDomain implements \IWPML_Action
    {
        const PRIORITY_OVERRIDE = 10;
        public function __construct(\WPML\ST\MO\File\Manager $file_manager, \WPML_ST_Translations_File_Locale $file_locale, \WPML\ST\MO\LoadedMODictionary $loaded_mo_dictionary)
        {
        }
        public function add_hooks()
        {
        }
        /**
         * When a MO file is loaded, we override the process to load
         * the custom MO file before.
         *
         * That way, the custom MO file will be merged into the subsequent
         * native MO files and the custom MO translations will always
         * overwrite the native ones.
         *
         * This gives us the ability to build partial custom MO files
         * with only the modified translations.
         *
         * @param bool   $override Whether to override the .mo file loading. Default false.
         * @param string $domain   Text domain. Unique identifier for retrieving translated strings.
         * @param string $mofile   Path to the MO file.
         *
         * @return bool
         */
        public function overrideLoadTextDomain($override, $domain, $mofile)
        {
        }
        /**
         * @param bool $override
         * @param string $domain
         *
         * @return bool
         */
        public function overrideUnloadTextDomain($override, $domain)
        {
        }
        public function languageHasSwitched()
        {
        }
    }
    class Sync implements \IWPML_Frontend_Action, \IWPML_Backend_Action, \IWPML_DIC_Action
    {
        public function __construct(\WPML\ST\TranslationFile\Sync\FileSync $fileSync, callable $useFileSynchronization)
        {
        }
        public function add_hooks()
        {
        }
        public function syncFile($domain, $moFile)
        {
        }
        /**
         * @param  bool  $override
         * @param  string  $domain
         * @param  string  $moFile
         *
         * @return bool
         */
        public function syncCustomMoFileOnLoadTextDomain($override, $domain, $moFile)
        {
        }
    }
    class DetectPrematurelyTranslatedStrings implements \IWPML_Action
    {
        /**
         * @param \SitePress $sitepress
         */
        public function __construct(\SitePress $sitepress, \WPML\ST\Gettext\Settings $settings)
        {
        }
        /**
         * Init gettext hooks.
         */
        public function add_hooks()
        {
        }
        /**
         * @param string       $translation
         * @param string       $text
         * @param string|array $domain
         *
         * @return string
         */
        public function gettext_filter($translation, $text, $domain)
        {
        }
        /**
         * @param string $translation
         * @param string $text
         * @param string $context
         * @param string $domain
         *
         * @return string
         */
        public function gettext_with_context_filter($translation, $text, $context, $domain)
        {
        }
        /**
         * @param string       $translation
         * @param string       $single
         * @param string       $plural
         * @param string       $number
         * @param string|array $domain
         *
         * @return string
         */
        public function ngettext_filter($translation, $single, $plural, $number, $domain)
        {
        }
        /**
         * @param string $translation
         * @param string $single
         * @param string $plural
         * @param string $number
         * @param string $context
         * @param string $domain
         *
         * @return string
         *
         */
        public function ngettext_with_context_filter($translation, $single, $plural, $number, $context, $domain)
        {
        }
        public function registerDomainToPreloading($plugin_override, $domain)
        {
        }
    }
    class LoadMissingMOFiles implements \IWPML_Action
    {
        const MISSING_MO_FILES_DIR = '/wpml/missing/';
        const OPTION_GROUP = 'ST-MO';
        const MISSING_MO_OPTION = 'missing-mo';
        const TIMEOUT = 10;
        const WPML_VERSION_INTRODUCING_ST_MO_FLOW = '4.3.0';
        public function __construct(\WPML\ST\MO\Generate\MissingMOFile $generateMissingMoFile, \WPML\WP\OptionManager $optionManager, \WPML_ST_Translations_File_Dictionary_Storage_Table $moFilesDictionary)
        {
        }
        public function add_hooks()
        {
        }
        /**
         * @param string $mofile
         * @param string $domain
         *
         * @return string
         */
        public function recordMissing($mofile, $domain)
        {
        }
        public function generateMissing()
        {
        }
        public static function isReadable($mofile)
        {
        }
        public static function getTimeout()
        {
        }
    }
    class CustomTextDomains implements \IWPML_Action
    {
        const CACHE_ID = 'wpml-st-custom-mo-files';
        public function __construct(\WPML\ST\MO\File\Manager $file_manager, \WPML\ST\TranslationFile\Domains $domains, \WPML\ST\MO\LoadedMODictionary $loadedDictionary, \WPML\ST\Storage\StoragePerLanguageInterface $cache, \WPML_Locale $locale, callable $syncMissingFile = null)
        {
        }
        public function clear_cache($filepath, $domain, $locale)
        {
        }
        public function add_hooks()
        {
        }
        public function init_custom_text_domains($locale = null)
        {
        }
    }
    class Factory implements \IWPML_Backend_Action_Loader, \IWPML_Frontend_Action_Loader
    {
        /**
         * Create hooks.
         *
         * @return IWPML_Action[]
         * @throws \WPML\Auryn\InjectionException Auryn Exception.
         */
        public function create()
        {
        }
    }
    class PreloadThemeMoFile implements \IWPML_Action
    {
        const SETTING_KEY = 'theme_localization_load_textdomain';
        const SETTING_DISABLED = 0;
        const SETTING_ENABLED = 1;
        const SETTING_ENABLED_FOR_LOAD_TEXT_DOMAIN = 2;
        public function __construct(\SitePress $sitepress, \wpdb $wpdb)
        {
        }
        public function add_hooks()
        {
        }
    }
    class StringsLanguageChanged implements \IWPML_Action
    {
        /**
         * @param DomainsAndLanguagesRepository $domainsAndLanguageRepository
         * @param Manager                       $manager
         * @param callable                      $getDomainsByStringIds
         */
        public function __construct(\WPML\ST\MO\Generate\DomainsAndLanguagesRepository $domainsAndLanguageRepository, \WPML\ST\MO\File\Manager $manager, callable $getDomainsByStringIds)
        {
        }
        public function add_hooks()
        {
        }
        public function regenerateMOFiles(array $strings)
        {
        }
    }
}
namespace WPML\ST\MO {
    class Plural implements \IWPML_Backend_Action, \IWPML_Frontend_Action
    {
        public function add_hooks()
        {
        }
        /**
         * @param string $translation Translated text.
         * @param string $single      The text to be used if the number is singular.
         * @param string $plural      The text to be used if the number is plural.
         * @param string $number      The number to compare against to use either the singular or plural form.
         * @param string $domain      Text domain. Unique identifier for retrieving translated strings.
         *
         * @return string
         */
        public function handle_plural($translation, $single, $plural, $number, $domain)
        {
        }
        /**
         * @param string $translation Translated text.
         * @param string $single      The text to be used if the number is singular.
         * @param string $plural      The text to be used if the number is plural.
         * @param string $number      The number to compare against to use either the singular or plural form.
         * @param string $context     Context information for the translators.
         * @param string $domain      Text domain. Unique identifier for retrieving translated strings.
         *
         * @return string
         */
        public function handle_plural_with_context($translation, $single, $plural, $number, $context, $domain)
        {
        }
    }
}
namespace WPML\ST\MO\Notice {
    class RegenerationInProgressNotice extends \WPML_Notice
    {
        const ID = 'mo-files-regeneration';
        const GROUP = 'mo-files';
        public function __construct()
        {
        }
    }
}
namespace WPML\ST\MO\JustInTime {
    class MO extends \MO
    {
        /** @var string $locale */
        protected $locale;
        /**
         * @param LoadedMODictionary $loaded_mo_dictionary
         * @param string             $locale
         * @param string             $domain
         */
        public function __construct(\WPML\ST\MO\LoadedMODictionary $loaded_mo_dictionary, $locale, $domain)
        {
        }
        /**
         * @param string $singular
         * @param string $context
         *
         * @return string
         */
        public function translate($singular, $context = null)
        {
        }
        /**
         * @param string $singular
         * @param string $plural
         * @param int    $count
         * @param string $context
         *
         * @return string
         */
        public function translate_plural($singular, $plural, $count, $context = null)
        {
        }
        protected function loadTextDomain()
        {
        }
    }
    class MOFactory
    {
        public function __construct(\WPML\ST\MO\LoadedMODictionary $loaded_mo_dictionary)
        {
        }
        /**
         * We need to rely on the loaded dictionary rather than `$GLOBALS['l10n]`
         * because a domain could have been loaded in a language that
         * does not have a MO file and so it won't be added to the `$GLOBALS['l10n]`.
         *
         * @param string $locale
         * @param array  $excluded_domains
         * @param array  $cachedMoObjects
         *
         * @return array
         */
        public function get($locale, array $excluded_domains, array $cachedMoObjects)
        {
        }
    }
    class DefaultMO extends \WPML\ST\MO\JustInTime\MO
    {
        public function __construct(\WPML\ST\MO\LoadedMODictionary $loaded_mo_dictionary, $locale)
        {
        }
        protected function loadTextDomain()
        {
        }
    }
}
namespace {
    class WPML_ST_Theme_Plugin_Localization_Options_UI
    {
        /**
         * WPML_ST_Theme_Plugin_Localization_Options_UI constructor.
         *
         * @param array $st_settings
         */
        public function __construct($st_settings)
        {
        }
        public function add_hooks()
        {
        }
        /**
         * @param array $model
         *
         * @return array
         */
        public function add_st_options($model)
        {
        }
    }
    class WPML_ST_Theme_Plugin_Localization_Resources
    {
        public function add_hooks()
        {
        }
        public function enqueue_scripts()
        {
        }
    }
    class WPML_ST_Plugin_Localization_UI implements \IWPML_Theme_Plugin_Localization_UI_Strategy
    {
        /**
         * WPML_ST_Plugin_Localization_UI constructor.
         *
         * @param WPML_Localization                 $localization
         * @param WPML_ST_Plugin_Localization_Utils $utils
         */
        public function __construct(\WPML_Localization $localization, \WPML_ST_Plugin_Localization_Utils $utils)
        {
        }
        /**
         * @return array
         */
        public function get_model()
        {
        }
        /** @return string */
        public function get_template()
        {
        }
    }
    class WPML_ST_Theme_Localization_UI implements \IWPML_Theme_Plugin_Localization_UI_Strategy
    {
        /**
         * WPML_ST_Theme_Localization_UI constructor.
         *
         * @param \WPML_Localization                $localization
         * @param \WPML_ST_Theme_Localization_Utils $utils
         * @param string                            $template_path
         */
        public function __construct(\WPML_Localization $localization, \WPML_ST_Theme_Localization_Utils $utils, $template_path)
        {
        }
        /** @return array */
        public function get_model()
        {
        }
        /** @return string */
        public function get_template()
        {
        }
    }
    class WPML_ST_Plugin_Localization_Utils
    {
        /** @return array */
        public function get_plugins()
        {
        }
        /**
         * @param string $plugin_file
         *
         * @return bool
         */
        public function is_plugin_active($plugin_file)
        {
        }
        public function get_plugins_by_status($active)
        {
        }
    }
    class WPML_ST_Theme_Localization_Utils
    {
        /** @return array */
        public function get_theme_data()
        {
        }
    }
    class WPML_ST_Theme_Localization_UI_Factory
    {
        const TEMPLATE_PATH = '/templates/theme-plugin-localization/';
        /**
         * @return WPML_ST_Theme_Localization_UI
         */
        public function create()
        {
        }
    }
    class WPML_ST_Plugin_Localization_UI_Factory
    {
        /**
         * @return WPML_ST_Plugin_Localization_UI
         */
        public function create()
        {
        }
    }
    class WPML_ST_Theme_Plugin_Localization_Options_Settings_Factory implements \IWPML_Backend_Action_Loader
    {
        public function create()
        {
        }
    }
    class WPML_ST_Theme_Plugin_Localization_Options_UI_Factory implements \IWPML_Backend_Action_Loader, \IWPML_Deferred_Action_Loader
    {
        /** @return WPML_ST_Theme_Plugin_Localization_Options_UI */
        public function create()
        {
        }
        /** @return string */
        public function get_load_action()
        {
        }
    }
    class WPML_ST_Theme_Plugin_Localization_Resources_Factory implements \IWPML_Backend_Action_Loader
    {
        /** @return WPML_ST_Theme_Plugin_Localization_Resources */
        public function create()
        {
        }
    }
    class WPML_ST_Theme_Plugin_Localization_Options_Settings implements \IWPML_Action
    {
        public function add_hooks()
        {
        }
        /**
         * @param array $settings
         *
         * @return array
         */
        public function add_st_settings($settings)
        {
        }
    }
    class WPML_Admin_Notifier
    {
        public function display_instant_message($message, $type = 'information', $class = \false, $return = \false, $fadeout = \false)
        {
        }
    }
    class WPML_ST_Themes_And_Plugins_Settings
    {
        const OPTION_NAME = 'wpml_st_display_strings_scan_notices';
        const NOTICES_GROUP = 'wpml-st-string-scan';
        public function init_hooks()
        {
        }
        public function get_notices_group()
        {
        }
        public function must_display_notices()
        {
        }
        public function set_strings_scan_notices($value)
        {
        }
        public function hide_strings_scan_notices()
        {
        }
        public function display_notices_setting_is_missing()
        {
        }
        public function create_display_notices_setting()
        {
        }
        public function enqueue_scripts()
        {
        }
    }
    /**
     * @author OnTheGo Systems
     */
    class WPML_ST_Themes_And_Plugins_Updates
    {
        const WPML_WP_UPDATED_MO_FILES = 'wpml_wp_updated_mo_files';
        const WPML_ST_ITEMS_TO_SCAN = 'wpml_items_to_scan';
        const WPML_ST_SCAN_NOTICE_ID = 'wpml_st_scan_items';
        const WPML_ST_FASTER_SETTINGS_NOTICE_ID = 'wpml_st_faster_settings';
        const WPML_ST_SCAN_ACTIVE_ITEMS_NOTICE_ID = 'wpml_st_scan_active_items';
        /**
         * WPML_ST_Admin_Notices constructor.
         *
         * @param WPML_Notices                        $admin_notices
         * @param WPML_ST_Themes_And_Plugins_Settings $settings
         */
        public function __construct(\WPML_Notices $admin_notices, \WPML_ST_Themes_And_Plugins_Settings $settings)
        {
        }
        public function init_hooks()
        {
        }
        public function data_is_valid($thing)
        {
        }
        public function notices_count()
        {
        }
        public function remove_notice($id)
        {
        }
        /**
         * @param \WP_Upgrader                              $upgrader
         * @param array<string,string|array<string,string>> $language_translations
         */
        public function store_mo_file_update(\WP_Upgrader $upgrader, $language_translations)
        {
        }
    }
    class WPML_File_Name_Converter
    {
        /**
         * @param string $file
         *
         * @return string
         */
        public function transform_realpath_to_reference($file)
        {
        }
        /**
         * @param string $file
         *
         * @return string
         */
        public function transform_reference_to_realpath($file)
        {
        }
    }
    class WPML_ST_Update_File_Hash_Ajax implements \IWPML_Action
    {
        /**
         * WPML_ST_Update_File_Hash_Ajax constructor.
         *
         * @param WPML_ST_File_Hashing $file_hashing
         */
        public function __construct(\WPML_ST_File_Hashing $file_hashing)
        {
        }
        public function add_hooks()
        {
        }
    }
    class WPML_ST_Strings_Stats
    {
        public function __construct(\wpdb $wpdb, \SitePress $sitepress)
        {
        }
        /**
         * @param string $component_name
         * @param string $type
         * @param string $domain
         */
        public function update($component_name, $type, $domain)
        {
        }
    }
    class WPML_ST_File_Hashing
    {
        const OPTION_NAME = 'wpml-scanning-files-hashing';
        public function __construct()
        {
        }
        /**
         * @param string $file
         *
         * @return bool
         */
        public function hash_changed($file)
        {
        }
        public function save_hash()
        {
        }
        public function clean_hashes()
        {
        }
    }
    class WPML_ST_Theme_Plugin_Scan_Files_Ajax implements \IWPML_Action
    {
        /**
         * WPML_ST_Theme_Scan_Files_Ajax constructor.
         *
         * @param IWPML_ST_String_Scanner $string_scanner
         */
        public function __construct(\IWPML_ST_String_Scanner $string_scanner)
        {
        }
        public function add_hooks()
        {
        }
        public function scan()
        {
        }
        public function clear_items_needs_scan_buffer()
        {
        }
    }
    class WPML_ST_Theme_Plugin_Scan_Dir_Ajax
    {
        /**
         * WPML_ST_Theme_Plugin_Scan_Dir_Ajax constructor.
         *
         * @param WPML_ST_Scan_Dir     $scan_dir
         * @param WPML_ST_File_Hashing $file_hashing
         */
        public function __construct(\WPML_ST_Scan_Dir $scan_dir, \WPML_ST_File_Hashing $file_hashing)
        {
        }
        public function add_hooks()
        {
        }
        public function get_files()
        {
        }
    }
    class WPML_ST_Theme_Plugin_Scan_Dir_Ajax_Factory extends \WPML_AJAX_Base_Factory implements \IWPML_Backend_Action_Loader
    {
        const AJAX_ACTION = 'wpml_get_files_to_scan';
        const NONCE = 'wpml-get-files-to-scan-nonce';
        /** @return null|WPML_ST_Theme_Plugin_Scan_Dir_Ajax */
        public function create()
        {
        }
    }
    class WPML_ST_Theme_String_Scanner_Factory
    {
        /** @return WPML_Theme_String_Scanner */
        public function create()
        {
        }
    }
    class WPML_ST_Plugin_String_Scanner_Factory
    {
        /** @return WPML_Plugin_String_Scanner */
        public function create()
        {
        }
    }
    class WPML_ST_Theme_Plugin_Hooks_Factory implements \IWPML_Backend_Action_Loader
    {
        /**
         * @return WPML_ST_Theme_Plugin_Hooks
         */
        public function create()
        {
        }
    }
    class WPML_ST_Update_File_Hash_Ajax_Factory extends \WPML_AJAX_Base_Factory implements \IWPML_Backend_Action_Loader
    {
        const AJAX_ACTION = 'update_file_hash';
        const NONCE = 'wpml-update-file-hash-nonce';
        /** @return null|WPML_ST_Update_File_Hash_Ajax */
        public function create()
        {
        }
    }
    class WPML_ST_Theme_Plugin_Scan_Files_Ajax_Factory extends \WPML_AJAX_Base_Factory implements \IWPML_Backend_Action_Loader
    {
        const AJAX_ACTION = 'wpml_st_scan_chunk';
        const NONCE = 'wpml-scan-files-nonce';
        /** @return null|WPML_ST_Theme_Plugin_Scan_Files_Ajax */
        public function create()
        {
        }
    }
    class WPML_ST_Theme_Plugin_Hooks
    {
        public function __construct(\WPML_ST_File_Hashing $file_hashing)
        {
        }
        public function add_hooks()
        {
        }
    }
    class WPML_Change_String_Domain_Language_Dialog extends \WPML_WPDB_And_SP_User
    {
        public function __construct(&$wpdb, &$sitepress, &$string_factory)
        {
        }
        public function render($domains)
        {
        }
        public function change_language_of_strings($domain, $langs, $to_lang, $set_as_default)
        {
        }
    }
}
namespace WPML\ST\Main {
    class UI implements \IWPML_Backend_Action_Loader
    {
        /**
         * @return callable|null
         */
        public function create()
        {
        }
        public static function localize()
        {
        }
    }
}
namespace {
    class WPML_String_Translation_Table
    {
        /**
         * WPML_String_Translation_Table constructor.
         *
         * @param array<string> $strings
         */
        public function __construct($strings)
        {
        }
        public function render()
        {
        }
        public function render_string_row($string_id, $icl_string)
        {
        }
        /**
         * @param array<string,string|int> $string
         */
        public function updateColumnsForString($string)
        {
        }
    }
    /**
     * Created by OnTheGoSystems
     */
    class WPML_Translation_Priority_Select extends \WPML_Templates_Factory
    {
        const NONCE = 'wpml_change_string_translation_priority_nonce';
        public function get_model()
        {
        }
        public function init_template_base_dir()
        {
        }
        public function get_template()
        {
        }
    }
    class WPML_Change_String_Language_Select
    {
        /**
         * @param wpdb      $wpdb
         * @param SitePress $sitepress
         */
        public function __construct(\wpdb $wpdb, \SitePress $sitepress)
        {
        }
        public function show()
        {
        }
        /**
         * @param int[]  $strings
         * @param string $lang
         *
         * @return array
         */
        public function change_language_of_strings($strings, $lang)
        {
        }
    }
}
namespace WPML\ST\Main\Ajax {
    class SaveTranslation implements \WPML\Ajax\IHandler
    {
        public function run(\WPML\Collect\Support\Collection $data)
        {
        }
    }
    class FetchCompletedStrings implements \WPML\Ajax\IHandler
    {
        public function run(\WPML\Collect\Support\Collection $data)
        {
        }
    }
}
namespace {
    /**
     * Class WPML_ST_Verify_Dependencies
     *
     * Checks that the WPML Core plugin is installed and satisfies certain version
     * requirements
     */
    class WPML_ST_Verify_Dependencies
    {
        /**
         * @param string|false $wpml_core_version
         */
        function verify_wpml($wpml_core_version)
        {
        }
        function notice_no_wpml()
        {
        }
        function wpml_is_outdated()
        {
        }
    }
    class WPML_ST_Initialize
    {
        public function load()
        {
        }
        public function run()
        {
        }
    }
}
namespace WPML\ST\StringsFilter {
    class QueryBuilder
    {
        public function __construct(\wpdb $wpdb)
        {
        }
        /**
         * @param string $language
         *
         * @return $this
         */
        public function setLanguage($language)
        {
        }
        /**
         * @param array $domains
         *
         * @return $this
         */
        public function filterByDomains(array $domains)
        {
        }
        /**
         * @param StringEntity $string
         *
         * @return $this
         */
        public function filterByString(\WPML\ST\StringsFilter\StringEntity $string)
        {
        }
        /**
         * @return string
         */
        public function build()
        {
        }
    }
    class StringEntity
    {
        /**
         * @param string|bool $value
         * @param string      $name
         * @param string      $domain
         * @param string      $context
         */
        public function __construct($value, $name, $domain, $context = '')
        {
        }
        /**
         * @return string|bool
         */
        public function getValue()
        {
        }
        /**
         * @return string
         */
        public function getName()
        {
        }
        /**
         * @return string
         */
        public function getDomain()
        {
        }
        /**
         * @return string
         */
        public function getContext()
        {
        }
        /**
         * @param array $data
         *
         * @return StringEntity
         */
        public static function fromArray(array $data)
        {
        }
    }
    /**
     * This storage in used internally in "Translations" class. Unfortunately, I cannot use anonymous classes due to PHP Version limitation.
     */
    class TranslationsObjectStorage extends \SplObjectStorage
    {
        /**
         * @param StringEntity $o
         *
         * @return string
         */
        #[\ReturnTypeWillChange]
        public function getHash($o)
        {
        }
    }
    class Translations
    {
        public function __construct()
        {
        }
        /**
         * @param StringEntity      $string
         * @param TranslationEntity $translation
         */
        public function add(\WPML\ST\StringsFilter\StringEntity $string, \WPML\ST\StringsFilter\TranslationEntity $translation)
        {
        }
        /**
         * @param StringEntity $string
         *
         * @return TranslationEntity|null
         */
        public function get(\WPML\ST\StringsFilter\StringEntity $string)
        {
        }
    }
    class Translator
    {
        /**
         * @param string              $language
         * @param TranslationReceiver $translationReceiver
         */
        public function __construct($language, \WPML\ST\StringsFilter\TranslationReceiver $translationReceiver)
        {
        }
        /**
         * @param StringEntity $string
         *
         * @return TranslationEntity
         */
        public function translate(\WPML\ST\StringsFilter\StringEntity $string)
        {
        }
    }
    class Provider
    {
        public function __construct(\WPML_String_Translation $string_translation)
        {
        }
        /**
         * Get filter.
         *
         * @param string|null      $lang Language.
         * @param string|null|bool $name Language name.
         *
         * @return WPML_Displayed_String_Filter|WPML_Register_String_Filter|null
         */
        public function getFilter($lang = null, $name = null)
        {
        }
        public function clearFilters()
        {
        }
    }
    class TranslationReceiver
    {
        public function __construct(\wpdb $wpdb, \WPML\ST\StringsFilter\QueryBuilder $query_builder)
        {
        }
        /**
         * @param StringEntity $string
         * @param string       $language
         *
         * @return TranslationEntity
         */
        public function get(\WPML\ST\StringsFilter\StringEntity $string, $language)
        {
        }
    }
}
namespace {
    /**
     * Class WPML_Displayed_String_Filter
     *
     * Handles all string translating when rendering translated strings to the user, unless auto-registering is
     * active for strings.
     */
    class WPML_Displayed_String_Filter
    {
        /** @var Translator */
        protected $translator;
        /**
         * @param Translator $translator
         */
        public function __construct(\WPML\ST\StringsFilter\Translator $translator)
        {
        }
        /**
         * Translate by name and context.
         *
         * @param string|bool   $untranslated_text Untranslated text.
         * @param string       $name Name of the string.
         * @param string|array $context Context.
         * @param null|boolean $has_translation If string has translation.
         *
         * @return string
         */
        public function translate_by_name_and_context($untranslated_text, $name, $context = '', &$has_translation = \null)
        {
        }
        /**
         * Transform translation parameters.
         *
         * @param string|bool  $name Name of the string.
         * @param string|array $context Context.
         *
         * @return array
         */
        protected function transform_parameters($name, $context)
        {
        }
        /**
         * Truncates a string to the maximum string table column width.
         *
         * @param string $string String to translate.
         *
         * @return string
         */
        public static function truncate_long_string($string)
        {
        }
        /**
         * Get translation of the string.
         *
         * @param string|bool  $untranslated_text Untranslated text.
         * @param string|bool  $name Name of the string.
         * @param string|array $context Context.
         *
         * @return TranslationEntity
         */
        protected function get_translation($untranslated_text, $name, $context)
        {
        }
    }
}
namespace WPML\ST\StringsFilter {
    class TranslationEntity
    {
        /**
         * @param bool|string $value
         * @param bool        $hasTranslation
         * @param bool        $stringRegistered
         */
        public function __construct($value, $hasTranslation, $stringRegistered = true)
        {
        }
        /**
         * @return string
         */
        public function getValue()
        {
        }
        /**
         * @return bool
         */
        public function isStringRegistered()
        {
        }
        /**
         * @return bool
         */
        public function hasTranslation()
        {
        }
    }
}
namespace {
    /**
     * Class WPML_Register_String_Filter
     */
    class WPML_Register_String_Filter extends \WPML_Displayed_String_Filter
    {
        /**
         * WP DB instance.
         *
         * @var wpdb
         */
        protected $wpdb;
        /** @var SitePress */
        protected $sitepress;
        // Current string data.
        protected $name;
        protected $domain;
        protected $gettext_context;
        protected $name_and_gettext_context;
        protected $key;
        /**
         * @param wpdb                                $wpdb
         * @param SitePress                           $sitepress
         * @param WPML_ST_String_Factory              $string_factory
         * @param Translator                          $translator
         * @param array                               $excluded_contexts
         * @param WPML_Autoregister_Save_Strings|null $save_strings
         */
        public function __construct($wpdb, \SitePress $sitepress, &$string_factory, \WPML\ST\StringsFilter\Translator $translator, array $excluded_contexts = array(), \WPML_Autoregister_Save_Strings $save_strings = \null)
        {
        }
        public function translate_by_name_and_context($untranslated_text, $name, $context = '', &$has_translation = \null)
        {
        }
        public function force_saving_of_autoregistered_strings()
        {
        }
        public function register_string($context, $name, $value, $allow_empty_value = \false, $source_lang = '')
        {
        }
        /**
         * @param string          $name
         * @param string|string[] $context
         */
        protected function initialize_current_string($name, $context)
        {
        }
        /**
         * @param string          $name
         * @param string|string[] $context
         *
         * @return array
         */
        protected function truncate_name_and_context($name, $context)
        {
        }
        protected function key_by_name_and_context($name, $context)
        {
        }
    }
    class WPML_ST_Taxonomy_Labels_Translation implements \IWPML_Action
    {
        const NONCE_TAXONOMY_TRANSLATION = 'wpml_taxonomy_translation_nonce';
        const PRIORITY_GET_LABEL = 10;
        public function __construct(\WPML_ST_Taxonomy_Strings $taxonomy_strings, \WPML_ST_Tax_Slug_Translation_Settings $slug_translation_settings, \WPML_Super_Globals_Validation $super_globals, array $active_languages)
        {
        }
        public function add_hooks()
        {
        }
        /**
         * @param string $translation
         * @param string $text
         * @param string $gettext_context
         * @param string $domain
         *
         * @return mixed
         */
        public function block_translation_and_init_strings($translation, $text, $gettext_context, $domain)
        {
        }
        /**
         * @param false  $false
         * @param string $taxonomy
         *
         * @return array|null
         */
        public function get_label_translations($false, $taxonomy)
        {
        }
        public function save_label_translations()
        {
        }
        public function change_taxonomy_strings_language()
        {
        }
    }
    class WPML_TM_Filters
    {
        /**
         * WPML_TM_Filters constructor.
         *
         * @param wpdb      $wpdb
         * @param SitePress $sitepress
         */
        public function __construct(\wpdb $wpdb, \SitePress $sitepress)
        {
        }
        /**
         * Filters the active languages to include all languages in which strings exist.
         *
         * @param WPML_Language_Collection $source_langs
         *
         * @return array[]
         */
        public function filter_tm_source_langs(\WPML_Language_Collection $source_langs)
        {
        }
        /**
         * This filters the check whether or not a job is assigned to a specific translator for local string jobs.
         * It is to be used after assigning a job, as it will update the assignment for local string jobs itself.
         *
         * @param bool       $assigned_correctly
         * @param string|int $string_translation_id
         * @param int        $translator_id
         * @param string|int $service
         *
         * @return bool
         */
        public function job_assigned_to_filter($assigned_correctly, $string_translation_id, $translator_id, $service)
        {
        }
    }
    class WPML_ST_Taxonomy_Labels_Translation_Factory implements \IWPML_Backend_Action_Loader, \IWPML_AJAX_Action_Loader
    {
        const AJAX_ACTION_BUILD = 'wpml_get_terms_and_labels_for_taxonomy_table';
        const AJAX_ACTION_SAVE = 'wpml_tt_save_labels_translation';
        const AJAX_ACTION_CHANGE_LANG = 'wpml_tt_change_tax_strings_language';
        const AJAX_ACTION_SET_SLUG_TRANSLATION_ENABLE = 'wpml_tt_set_slug_translation_enabled';
        public function create()
        {
        }
    }
    class WPML_ST_Blog_Name_And_Description_Hooks implements \IWPML_Action
    {
        const STRING_DOMAIN = 'WP';
        const STRING_NAME_BLOGNAME = 'Blog Title';
        const STRING_NAME_BLOGDESCRIPTION = 'Tagline';
        public function add_hooks()
        {
        }
        /**
         * @param string $blogname
         *
         * @return string
         */
        public function option_blogname_filter($blogname)
        {
        }
        /**
         * @param string $blogdescription
         *
         * @return string
         */
        public function option_blogdescription_filter($blogdescription)
        {
        }
        /**
         * As the translation depends on `WPML_String_Translation::get_current_string_language`,
         * we added this clear cache callback on `wpml_language_has_switched` as done
         * in `WPML_String_Translation::wpml_language_has_switched`.
         */
        public function clear_cache()
        {
        }
        public function switch_blog_action()
        {
        }
        /**
         * @param string $string_name
         *
         * Checks whether a given string is to be translated in the Admin back-end.
         * Currently only tagline and title of a site are to be translated.
         * All other admin strings are to always be displayed in the user admin language.
         *
         * @return bool
         */
        public static function is_string($string_name)
        {
        }
    }
    class WPML_ST_WCML_Taxonomy_Labels_Translation implements \IWPML_Action
    {
        public function add_hooks()
        {
        }
        /**
         * @param array  $data
         * @param string $taxonomy
         *
         * @return array
         */
        public function alter_slug_translation_display($data, $taxonomy)
        {
        }
    }
    class WPML_Autoregister_Save_Strings
    {
        const INSERT_CHUNK_SIZE = 200;
        /**
         * @param wpdb                    $wpdb
         * @param SitePress               $sitepress
         * @param WPML_Language_Of_Domain $language_of_domain
         */
        public function __construct(\wpdb $wpdb, \SitePress $sitepress, \WPML_Language_Of_Domain $language_of_domain = \null)
        {
        }
        /**
         * @param string|bool $value
         * @param string      $name
         * @param string      $domain
         * @param string      $gettext_context
         */
        public function save($value, $name, $domain, $gettext_context = '')
        {
        }
        /**
         * @param string $name
         * @param string $domain
         *
         * @return string
         */
        public function get_source_lang($name, $domain)
        {
        }
        public function shutdown()
        {
        }
    }
    class WPML_ST_Taxonomy_Strings
    {
        const CONTEXT_GENERAL = 'taxonomy general name';
        const CONTEXT_SINGULAR = 'taxonomy singular name';
        const LEGACY_NAME_PREFIX_GENERAL = 'taxonomy general name: ';
        const LEGACY_NAME_PREFIX_SINGULAR = 'taxonomy singular name: ';
        const LEGACY_STRING_DOMAIN = 'WordPress';
        public function __construct(\WPML_Tax_Slug_Translation_Records $slug_translation_records, \WPML_ST_String_Factory $string_factory)
        {
        }
        /**
         * @param string $text
         * @param string $domain
         */
        public function add_to_translated_with_gettext_context($text, $domain)
        {
        }
        /**
         * @param string       $text
         * @param string       $gettext_context
         * @param string       $domain
         * @param false|string $name
         *
         * @return int
         */
        public function create_string_if_not_exist($text, $gettext_context = '', $domain = '', $name = \false)
        {
        }
        /**
         * @param string $taxonomy_name
         *
         * @return WPML_ST_String[]
         */
        public function get_taxonomy_strings($taxonomy_name)
        {
        }
    }
}
namespace {
    \define('WPML_ST_VERSION', '3.2.10');
    // Do not uncomment the following line!
    // If you need to use this constant, use it in the wp-config.php file
    // define( 'WPML_PT_VERSION_DEV', '2.2.3-dev' );
    \define('WPML_ST_PATH', \dirname(__FILE__));
    function wpml_st_verify_wpml()
    {
    }
    /**
     * WPML ST Core loaded hook.
     *
     * @throws \WPML\Auryn\InjectionException Auryn Exception.
     */
    function wpml_st_core_loaded()
    {
    }
    /**
     * @throws \WPML\Auryn\InjectionException
     */
    function load_wpml_st_basics()
    {
    }
    function context_array($contexts)
    {
    }
    function _icl_string_translation_rtl_div($language)
    {
    }
    function _icl_string_translation_rtl_textarea($language)
    {
    }
    \define('LINE', 0);
    \define('AREA', 1);
    \define('VISUAL', 2);
    \define('WPML_PACKAGE_TRANSLATION', '0.0.2');
    \define('WPML_PACKAGE_TRANSLATION_PATH', \dirname(__FILE__));
    \define('WPML_PACKAGE_TRANSLATION_URL', \WPML_ST_URL . '/inc/' . \basename(\WPML_PACKAGE_TRANSLATION_PATH));
    /**
     * @package wpml-core
     */
    // $Id: potx.inc,v 1.1.2.17.2.7.2.19.4.1 2009/07/19 12:54:42 goba Exp $
    /**
     * @file
     *   Extraction API used by the web and command line interface.
     *
     *   This include file implements the default string and file version
     *   storage as well as formatting of POT files for web download or
     *   file system level creation. The strings, versions and file contents
     *   are handled with global variables to reduce the possible memory overhead
     *   and API clutter of passing them around. Custom string and version saving
     *   functions can be implemented to use the functionality provided here as an
     *   API for Drupal code to translatable string conversion.
     *
     *   The potx-cli.php script can be used with this include file as
     *   a command line interface to string extraction. The potx.module
     *   can be used as a web interface for manual extraction.
     *
     *   For a module using potx as an extraction API, but providing more
     *   sophisticated functionality on top of it, look into the
     *   'Localization server' module: http://drupal.org/project/l10n_server
     */
    /**
     * Silence status reports.
     */
    \define('POTX_STATUS_SILENT', 0);
    /**
     * Drupal message based status reports.
     */
    \define('POTX_STATUS_MESSAGE', 1);
    /**
     * Command line status reporting.
     *
     * Status goes to standard output, errors to standard error.
     */
    \define('POTX_STATUS_CLI', 2);
    /**
     * Structured array status logging.
     *
     * Useful for coder review status reporting.
     */
    \define('POTX_STATUS_STRUCTURED', 3);
    /**
     * Core parsing mode:
     *  - .info files folded into general.pot
     *  - separate files generated for modules
     */
    \define('POTX_BUILD_CORE', 0);
    /**
     * Multiple files mode:
     *  - .info files folded into their module pot files
     *  - separate files generated for modules
     */
    \define('POTX_BUILD_MULTIPLE', 1);
    /**
     * Single file mode:
     *  - all files folded into one pot file
     */
    \define('POTX_BUILD_SINGLE', 2);
    /**
     * Save string to both installer and runtime collection.
     */
    \define('POTX_STRING_BOTH', 0);
    /**
     * Save string to installer collection only.
     */
    \define('POTX_STRING_INSTALLER', 1);
    /**
     * Save string to runtime collection only.
     */
    \define('POTX_STRING_RUNTIME', 2);
    /**
     * Parse source files in Drupal 5.x format.
     */
    \define('POTX_API_5', 5);
    /**
     * Parse source files in Drupal 6.x format.
     *
     * Changes since 5.x documented at http://drupal.org/node/114774
     */
    \define('POTX_API_6', 6);
    /**
     * Parse source files in Drupal 7.x format.
     *
     * Changes since 6.x documented at http://drupal.org/node/224333
     */
    \define('POTX_API_7', 7);
    /**
     * When no context is used. Makes it easy to look these up.
     */
    \define('POTX_CONTEXT_NONE', \NULL);
    /**
     * When there was a context identification error.
     */
    \define('POTX_CONTEXT_ERROR', \FALSE);
    /**
     * Process a file and put extracted information to the given parameters.
     *
     * @param string          $file_path        Complete path to file to process.
     * @param int             $strip_prefix     An integer denoting the number of chars to strip from filepath for output.
     * @param callable	      $save_callback    Callback function to use to save the collected strings.
     * @param callable	      $version_callback Callback function to use to save collected version numbers.
     * @param string          $default_domain   Default domain to be used if one can't be found.
     */
    function _potx_process_file($file_path, $strip_prefix = 0, $save_callback = '_potx_save_string', $version_callback = '_potx_save_version', $default_domain = '')
    {
    }
    /**
     * Escape quotes in a strings depending on the surrounding
     * quote type used.
     *
     * @param string $str The strings to escape
     */
    function _potx_format_quoted_string($str)
    {
    }
    /**
     * @param string $string
     * 
     * @return string
     */
    function wpml_potx_unquote_context_or_domain($string)
    {
    }
    /**
     * Output a marker error with an extract of where the error was found.
     *
     * @param string $file     Name of file
     * @param int    $line     Line number of error
     * @param string $marker   Function name with which the error was identified
     * @param int    $ti       Index on the token array
     * @param string $error    Helpful error message for users.
     * @param string $docs_url Documentation reference.
     */
    function _potx_marker_error($file, $line, $marker, $ti, $error, $docs_url = \NULL)
    {
    }
    /**
     * Status notification function.
     *
     * @param string $op       Operation to perform or type of message text.
     *                         - set:    sets the reporting mode to $value
     *                         use one of the POTX_STATUS_* constants as $value
     *                         - get:    returns the list of error messages recorded
     *                         if $value is true, it also clears the internal message cache
     *                         - error:  sends an error message in $value with optional $file and $line
     *                         - status: sends a status message in $value
     * @param string $value    Value depending on $op.
     * @param string $file     Name of file the error message is related to.
     * @param int    $line     Number of line the error message is related to.
     * @param string $excerpt  Excerpt of the code in question, if available.
     * @param string $docs_url URL to the guidelines to follow to fix the problem.
     */
    function potx_status($op, $value = \NULL, $file = \NULL, $line = \NULL, $excerpt = \NULL, $docs_url = \NULL)
    {
    }
    /**
     * Detect all occurances of t()-like calls.
     *
     * These sequences are searched for:
     *   T_STRING("$function_name") + "(" + T_CONSTANT_ENCAPSED_STRING + ")"
     *   T_STRING("$function_name") + "(" + T_CONSTANT_ENCAPSED_STRING + ","
     *
     * @param string   $file          Name of file parsed.
     * @param callable $save_callback Callback function used to save strings.
     * @param string   $function_name The name of the function to look for (could be 't', '$t', 'st'
     *                                or any other t-like function).
     * @param int      $string_mode   String mode to use: POTX_STRING_INSTALLER, POTX_STRING_RUNTIME or
     *                                POTX_STRING_BOTH.
     */
    function _potx_find_t_calls($file, $save_callback, $function_name = 't', $string_mode = \POTX_STRING_RUNTIME)
    {
    }
    /**
     * Detect all occurances of t()-like calls from Drupal 7 (with context).
     *
     * These sequences are searched for:
     *   T_STRING("$function_name") + "(" + T_CONSTANT_ENCAPSED_STRING + ")"
     *   T_STRING("$function_name") + "(" + T_CONSTANT_ENCAPSED_STRING + ","
     *   and then an optional value for the replacements and an optional array
     *   for the options with an optional context key.
     *
     * @param string   $file          Name of file parsed.
     * @param callable $save_callback Callback function used to save strings.
     * @param string   $function_name
     * @param string   $default_domain
     * @param int      $string_mode   String mode to use: POTX_STRING_INSTALLER, POTX_STRING_RUNTIME or
     *                                POTX_STRING_BOTH.
     *
     * @internal param $function_name The name of the function to look for (could be 't', '$t', 'st'*   The name of the function to look for (could be 't', '$t', 'st'
     *   or any other t-like function). Drupal 7 only supports context on t().
     */
    function _potx_find_t_calls_with_context($file, $save_callback, $function_name = '_e', $default_domain = '', $string_mode = \POTX_STRING_RUNTIME)
    {
    }
    /**
     * Helper function to look up the token closing the current function.
     *
     * @param string $here The token at the function name
     */
    function _potx_find_end_of_function($here, $open = '{', $close = '}')
    {
    }
    /**
     * Helper to move past potx_t() and format_plural() arguments in search of context.
     *
     * @param int $here The token index before the start of the arguments
     */
    function _potx_skip_args($here)
    {
    }
    /**
     * Helper to find the value for 'context' on t() and format_plural().
     *
     * @param int    $tf            Start position of the original function.
     * @param int    $ti            Start position where we should search from.
     * @param string $file          Full path name of file parsed.
     * @param string $function_name The name of the function to look for. Either 'format_plural' or 't'
     *                              given that Drupal 7 only supports context on these.
     */
    function _potx_find_context($tf, $ti, $file, $function_name)
    {
    }
    /**
     * Get the exact CVS version number from the file, so we can
     * push that into the generated output.
     *
     * @param string|false   $code             Complete source code of the file parsed.
     * @param string   		 $file             Name of the file parsed.
     * @param callable 		 $version_callback Callback used to save the version information.
     */
    function _potx_find_version_number($code, $file, $version_callback)
    {
    }
    /**
     * Default $version_callback used by the potx system. Saves values
     * to a global array to reduce memory consumption problems when
     * passing around big chunks of values.
     *
     * @param string $value The version number value of $file. If NULL, the collected
     *                      values are returned.
     * @param string $file  Name of file where the version information was found.
     */
    function _potx_save_version($value = \NULL, $file = \NULL)
    {
    }
    /**
     * Default $save_callback used by the potx system. Saves values
     * to global arrays to reduce memory consumption problems when
     * passing around big chunks of values.
     *
     * @param string $value       The string value. If NULL, the array of collected values
     *                            are returned for the given $string_mode.
     * @param string $context     From Drupal 7, separate contexts are supported. POTX_CONTEXT_NONE is
     *                            the default, if the code does not specify a context otherwise.
     * @param string $file        Name of file where the string was found.
     * @param int    $line        Line number where the string was found.
     * @param int    $string_mode String mode: POTX_STRING_INSTALLER, POTX_STRING_RUNTIME
     *                            or POTX_STRING_BOTH.
     */
    function _potx_save_string($value = \NULL, $context = \NULL, $file = \NULL, $line = 0, $string_mode = \POTX_STRING_RUNTIME)
    {
    }
    function potx_t($string, $args = array())
    {
    }
    function wpml_st_pos_scan_store_results($string, $domain, $context, $file, $line)
    {
    }
    function wpml_st_parse_config($file_or_object)
    {
    }
    /**
     * Action run on the wp_loaded hook that registers widget titles,
     * tagline and bloginfo as well as the current theme's strings when
     * String translation is first activated
     */
    function wpml_st_initialize_basic_strings()
    {
    }
    /**
     * @param string $old
     * @param string $new
     */
    function icl_st_update_blogname_actions($old, $new)
    {
    }
    /**
     * @param string $old
     * @param string $new
     */
    function icl_st_update_blogdescription_actions($old, $new)
    {
    }
    \define('WPML_ST_FOLDER', \basename(\WPML_ST_PATH));
    \define('WPML_ST_URL', \plugins_url('', \dirname(__FILE__)));
    // Old ST status constants, kept for backward compatibility with plugins that use them, like WCML
    \define('ICL_STRING_TRANSLATION_PARTIAL', 2);
    \define('ICL_STRING_TRANSLATION_COMPLETE', 10);
    \define('ICL_STRING_TRANSLATION_NEEDS_UPDATE', 3);
    \define('ICL_STRING_TRANSLATION_NOT_TRANSLATED', 0);
    \define('ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR', 1);
    \define('ICL_STRING_TRANSLATION_TEMPLATE_DIRECTORY', \get_template_directory());
    \define('ICL_STRING_TRANSLATION_STYLESHEET_DIRECTORY', \get_stylesheet_directory());
    \define('ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_SOURCE', 0);
    \define('ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_PAGE', 1);
    \define('ICL_STRING_TRANSLATION_STRING_TRACKING_THRESHOLD', 5);
    \define('ICL_STRING_TRANSLATION_AUTO_REGISTER_THRESHOLD', 500);
    \define('ICL_STRING_TRANSLATION_DYNAMIC_CONTEXT', 'wpml_string');
    \define('WPML_ST_DEFAULT_STRINGS_PER_PAGE', 10);
    \define('WPML_ST_WIDGET_STRING_DOMAIN', 'Widgets');
    function icl_st_init()
    {
    }
    function wpml_st_init_register_widget_titles()
    {
    }
    function wpml_get_default_widget_title($id)
    {
    }
    /**
     * Registers a string for translation
     *
     * @param string|array $context           The context for the string
     * @param string       $name              A name to help the translator understand what’s being translated
     * @param string|array $value             The string or array value
     * @param bool         $allow_empty_value This param is not being used
     * @param string       $source_lang       The language of the registered string. Defaults to 'en'
     *
     * @return int|false|null string_id of the just registered string or the id found in the database corresponding to the
     *             input parameters
     * @throws \WPML\Auryn\InjectionException
     */
    function icl_register_string($context, $name, $value, $allow_empty_value = \false, $source_lang = '')
    {
    }
    /**
     * Registers a string for translation
     *
     * @api
     *
     * @param string $context The context for the string
     * @param string $name A name to help the translator understand what’s being translated
     * @param string $value The string value
     * @param bool   $allow_empty_value This param is not being used
     * @param string $source_lang_code
     */
    function wpml_register_single_string_action($context, $name, $value, $allow_empty_value = \false, $source_lang_code = '')
    {
    }
    /**
     * @param string|array $context
     * @param string       $name
     * @param bool|string  $value
     * @param bool         $allow_empty_value
     * @param null|bool    $has_translation
     * @param null|string  $target_lang
     *
     * @return bool|string
     */
    function icl_translate($context, $name, $value = \false, $allow_empty_value = \false, &$has_translation = \null, $target_lang = \null)
    {
    }
    /**
     * @return bool
     */
    function wpml_st_is_requested_blog()
    {
    }
    /**
     * @param string $value
     * @param mixed  $context
     * @param string $name
     *
     * @return string
     */
    function wpml_get_string_current_translation($value, $context, $name)
    {
    }
    function icl_st_is_registered_string($context, $name)
    {
    }
    function icl_st_string_has_translations($context, $name)
    {
    }
    function icl_update_string_status($string_id)
    {
    }
    function icl_update_string_status_all()
    {
    }
    /**
     * @param string $context
     * @param string $name
     */
    function icl_unregister_string($context, $name)
    {
    }
    /**
     * @param array $string_ids
     */
    function wpml_unregister_string_multi(array $string_ids)
    {
    }
    /**
     * @since      unknown
     * @deprecated 3.2 use 'wpml_translate_string' filter instead.
     */
    function translate_string_filter($original_value, $context, $name, $has_translation = \null, $disable_auto_register = \false, $language_code = \null)
    {
    }
    /**
     * Retrieve a string translation
     * Looks for a string with matching $context and $name.
     * If it finds it, it looks for translation in the current language or the language specified
     * If a translation exists, it will return it. Otherwise, it will return the original string.
     *
     * @api
     *
     * @param string|bool $original_value           The string's original value
     * @param string      $context                  The string's registered context
     * @param string      $name                     The string's registered name
     * @param null|string $language_code            Return the translation in this language
     *                                              Default is NULL which returns the current language
     * @param bool|null   $has_translation          Currently unused. Defaults to NULL
     *
     * @return string
     */
    function wpml_translate_single_string_filter($original_value, $context, $name, $language_code = \null, $has_translation = \null)
    {
    }
    /**
     * Retrieve a string translation
     * Looks for a string with matching $context and $name.
     * If it finds it, it looks for translation in the current language or the language specified
     * If a translation exists, it will return it. Otherwise, it will return the original string.
     *
     * @param string|bool $original_value           The string's original value
     * @param string      $context                  The string's registered context
     * @param string      $name                     The string's registered name
     * @param bool|null   $has_translation          Currently unused. Defaults to NULL
     * @param bool        $disable_auto_register    Currently unused. Set to false in calling icl_translate
     * @param null|string $language_code            Return the translation in this language
     *                                              Default is NULL which returns the current language
     *
     * @return string
     */
    function icl_t($context, $name, $original_value = \false, &$has_translation = \null, $disable_auto_register = \false, $language_code = \null)
    {
    }
    /**
     * @deprecated since WPML ST 3.0.0
     *
     * @param string $name
     *
     * @return bool
     */
    function is_translated_admin_string($name)
    {
    }
    /**
     * Helper function for icl_t()
     *
     * @param array  $result
     * @param string $original_value
     * @return boolean
     */
    function _icl_is_string_change($result, $original_value)
    {
    }
    function icl_add_string_translation($string_id, $language, $value = \null, $status = \false, $translator_id = \null, $translation_service = \null, $batch_id = \null)
    {
    }
    /**
     * Updates the string translation for an admin option
     *
     * @global SitePress               $sitepress
     * @global WPML_String_Translation $WPML_String_Translation
     *
     * @param string   $option_name
     * @param string   $language
     * @param string   $new_value
     * @param int|bool $status
     * @param int      $translator_id
     *
     * @return boolean|mixed
     */
    function icl_update_string_translation($option_name, $language, $new_value = \null, $status = \false, $translator_id = \null)
    {
    }
    /**
     * @param string       $string
     * @param string       $context
     * @param string|false $name
     *
     * @return int
     * @throws \WPML\Auryn\InjectionException
     */
    function icl_get_string_id($string, $context, $name = \false)
    {
    }
    function icl_get_string_translations()
    {
    }
    /** *
     *
     * @param int          $string_id     ID of string in icl_strings DB table
     * @param string|false $language_code false, or language code
     *
     * @return string|false
     */
    function icl_get_string_by_id($string_id, $language_code = \false)
    {
    }
    function icl_get_string_translations_by_id($string_id)
    {
    }
    /**
     * @param array<string,mixed> $string_translations
     *
     * @return string[]
     */
    function icl_get_strings_tracked_in_pages($string_translations)
    {
    }
    function icl_sw_filters_widget_title($val)
    {
    }
    function icl_sw_filters_widget_text($val)
    {
    }
    /**
     * @param string       $translation String This parameter is not important to the filter since we filter before other filters.
     * @param string       $text
     * @param string|array $domain
     * @param bool|string  $name
     *
     * @return string
     * @throws \WPML\Auryn\InjectionException
     * @deprecated since WPML ST 3.0.0
     *
     */
    function icl_sw_filters_gettext($translation, $text, $domain, $name = \false)
    {
    }
    /**
     * @return bool
     * @throws \WPML\Auryn\InjectionException
     * @deprecated since WPML ST 3.0.0
     *
     */
    function icl_sw_must_track_strings()
    {
    }
    function icl_st_track_string($text, $domain, $kind = \ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_PAGE)
    {
    }
    /**
     * @param string $translation
     * @param string $text
     * @param string $_gettext_context
     * @param string $domain
     *
     * @return string
     * @throws \WPML\Auryn\InjectionException
     * @deprecated since WPML ST 3.0.0
     *
     */
    function icl_sw_filters_gettext_with_context($translation, $text, $_gettext_context, $domain)
    {
    }
    /**
     * @param string       $translation
     * @param string       $single
     * @param string       $plural
     * @param string|int   $number
     * @param string       $domain
     * @param string       $_gettext_context
     *
     * @return string
     * @throws \WPML\Auryn\InjectionException
     * @deprecated since WPML ST 3.0.0
     *
     */
    function icl_sw_filters_ngettext($translation, $single, $plural, $number, $domain, $_gettext_context)
    {
    }
    /**
     * @param string $translation
     * @param string $single
     * @param string $plural
     * @param string $number
     * @param string $_gettext_context
     * @param string $domain
     *
     * @return string
     * @throws \WPML\Auryn\InjectionException
     * @deprecated since WPML ST 3.0.0
     *
     */
    function icl_sw_filters_nxgettext($translation, $single, $plural, $number, $_gettext_context, $domain)
    {
    }
    /**
     * @return array Translated User IDs
     */
    function icl_st_register_user_strings_all()
    {
    }
    function icl_st_update_string_actions($context, $name, $old_value, $new_value, $force_complete = \false)
    {
    }
    /**
     * @param string $name
     * @param array<string,mixed> $old
     * @param array<string,mixed> $new
     *
     * @throws \WPML\Auryn\InjectionException
     */
    function icl_st_update_widget_title_actions($name, $old, $new)
    {
    }
    /**
     * @param array<string,mixed> $old_options
     * @param array<string,mixed> $new_options
     *
     * @throws \WPML\Auryn\InjectionException
     */
    function icl_st_update_text_widgets_actions($old_options, $new_options)
    {
    }
    function icl_st_get_contexts($status)
    {
    }
    function icl_st_admin_notices()
    {
    }
    function icl_st_generate_po_file($strings)
    {
    }
    function _icl_st_get_options_writes($path)
    {
    }
    function array_unique_recursive($array)
    {
    }
    function _icl_st_filter_empty_options_out($array)
    {
    }
    function wpml_register_admin_strings($serialized_array)
    {
    }
    function icl_is_string_translation($translation)
    {
    }
    function icl_translation_add_string_translation($rid, $translation, $lang_code)
    {
    }
    function icl_st_admin_notices_string_updated()
    {
    }
    /**
     * @param string $path
     *
     * @return bool
     */
    function wpml_st_file_path_is_valid($path)
    {
    }
    /**
     * @param string|array $context
     *
     * @return array
     */
    function wpml_st_extract_context_parameters($context)
    {
    }
    /**
     * @param array $source_languages
     *
     * @return array[]
     */
    function filter_tm_source_langs($source_languages)
    {
    }
    /**
     *
     * @param bool       $assigned_correctly
     * @param string     $string_translation_id in the format used by
     *                                          TM functionality as
     *                                          "string|{$string_translation_id}"
     * @param int        $translator_id
     * @param int|string $service
     *
     * @return bool
     */
    function wpml_st_filter_job_assignment($assigned_correctly, $string_translation_id, $translator_id, $service)
    {
    }
    /**
     * @deprecated since WPML ST 3.0.0
     *
     * @param string $val
     *
     * @return string
     * @throws \WPML\Auryn\InjectionException
     */
    function wpml_st_blog_title_filter($val)
    {
    }
    /**
     * @deprecated since WPML ST 3.0.0
     *
     * @param string $val
     *
     * @return string
     * @throws \WPML\Auryn\InjectionException
     */
    function wpml_st_blog_description_filter($val)
    {
    }
    /**
     * @return WPML_Admin_Texts
     */
    function wpml_st_load_admin_texts()
    {
    }
}