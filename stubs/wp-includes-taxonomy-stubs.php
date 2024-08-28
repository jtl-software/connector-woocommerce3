<?php

/**
 * Core Taxonomy API
 *
 * @package WordPress
 * @subpackage Taxonomy
 */
//
// Taxonomy registration.
//
/**
 * Creates the initial taxonomies.
 *
 * This function fires twice: in wp-settings.php before plugins are loaded (for
 * backward compatibility reasons), and again on the {@see 'init'} action. We must
 * avoid registering rewrite rules before the {@see 'init'} action.
 *
 * @since 2.8.0
 * @since 5.9.0 Added `'wp_template_part_area'` taxonomy.
 *
 * @global WP_Rewrite $wp_rewrite WordPress rewrite component.
 */
function create_initial_taxonomies()
{
}
/**
 * Retrieves a list of registered taxonomy names or objects.
 *
 * @since 3.0.0
 *
 * @global WP_Taxonomy[] $wp_taxonomies The registered taxonomies.
 *
 * @param array  $args     Optional. An array of `key => value` arguments to match against the taxonomy objects.
 *                         Default empty array.
 * @param string $output   Optional. The type of output to return in the array. Either 'names'
 *                         or 'objects'. Default 'names'.
 * @param string $operator Optional. The logical operation to perform. Accepts 'and' or 'or'. 'or' means only
 *                         one element from the array needs to match; 'and' means all elements must match.
 *                         Default 'and'.
 * @return string[]|WP_Taxonomy[] An array of taxonomy names or objects.
 */
function get_taxonomies($args = array(), $output = 'names', $operator = 'and')
{
}
/**
 * Returns the names or objects of the taxonomies which are registered for the requested object or object type,
 * such as a post object or post type name.
 *
 * Example:
 *
 *     $taxonomies = get_object_taxonomies( 'post' );
 *
 * This results in:
 *
 *     Array( 'category', 'post_tag' )
 *
 * @since 2.3.0
 *
 * @global WP_Taxonomy[] $wp_taxonomies The registered taxonomies.
 *
 * @param string|string[]|WP_Post $object_type Name of the type of taxonomy object, or an object (row from posts).
 * @param string                  $output      Optional. The type of output to return in the array. Accepts either
 *                                             'names' or 'objects'. Default 'names'.
 * @return string[]|WP_Taxonomy[] The names or objects of all taxonomies of `$object_type`.
 */
function get_object_taxonomies($object_type, $output = 'names')
{
}
/**
 * Retrieves the taxonomy object of $taxonomy.
 *
 * The get_taxonomy function will first check that the parameter string given
 * is a taxonomy object and if it is, it will return it.
 *
 * @since 2.3.0
 *
 * @global WP_Taxonomy[] $wp_taxonomies The registered taxonomies.
 *
 * @param string $taxonomy Name of taxonomy object to return.
 * @return WP_Taxonomy|false The taxonomy object or false if $taxonomy doesn't exist.
 */
function get_taxonomy($taxonomy)
{
}
/**
 * Determines whether the taxonomy name exists.
 *
 * Formerly is_taxonomy(), introduced in 2.3.0.
 *
 * For more information on this and similar theme functions, check out
 * the {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} article in the Theme Developer Handbook.
 *
 * @since 3.0.0
 *
 * @global WP_Taxonomy[] $wp_taxonomies The registered taxonomies.
 *
 * @param string $taxonomy Name of taxonomy object.
 * @return bool Whether the taxonomy exists.
 */
function taxonomy_exists($taxonomy)
{
}
/**
 * Determines whether the taxonomy object is hierarchical.
 *
 * Checks to make sure that the taxonomy is an object first. Then Gets the
 * object, and finally returns the hierarchical value in the object.
 *
 * A false return value might also mean that the taxonomy does not exist.
 *
 * For more information on this and similar theme functions, check out
 * the {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} article in the Theme Developer Handbook.
 *
 * @since 2.3.0
 *
 * @param string $taxonomy Name of taxonomy object.
 * @return bool Whether the taxonomy is hierarchical.
 */
function is_taxonomy_hierarchical($taxonomy)
{
}
/**
 * Creates or modifies a taxonomy object.
 *
 * Note: Do not use before the {@see 'init'} hook.
 *
 * A simple function for creating or modifying a taxonomy object based on
 * the parameters given. If modifying an existing taxonomy object, note
 * that the `$object_type` value from the original registration will be
 * overwritten.
 *
 * @since 2.3.0
 * @since 4.2.0 Introduced `show_in_quick_edit` argument.
 * @since 4.4.0 The `show_ui` argument is now enforced on the term editing screen.
 * @since 4.4.0 The `public` argument now controls whether the taxonomy can be queried on the front end.
 * @since 4.5.0 Introduced `publicly_queryable` argument.
 * @since 4.7.0 Introduced `show_in_rest`, 'rest_base' and 'rest_controller_class'
 *              arguments to register the taxonomy in REST API.
 * @since 5.1.0 Introduced `meta_box_sanitize_cb` argument.
 * @since 5.4.0 Added the registered taxonomy object as a return value.
 * @since 5.5.0 Introduced `default_term` argument.
 * @since 5.9.0 Introduced `rest_namespace` argument.
 *
 * @global WP_Taxonomy[] $wp_taxonomies Registered taxonomies.
 *
 * @param string       $taxonomy    Taxonomy key. Must not exceed 32 characters and may only contain
 *                                  lowercase alphanumeric characters, dashes, and underscores. See sanitize_key().
 * @param array|string $object_type Object type or array of object types with which the taxonomy should be associated.
 * @param array|string $args        {
 *     Optional. Array or query string of arguments for registering a taxonomy.
 *
 *     @type string[]      $labels                An array of labels for this taxonomy. By default, Tag labels are
 *                                                used for non-hierarchical taxonomies, and Category labels are used
 *                                                for hierarchical taxonomies. See accepted values in
 *                                                get_taxonomy_labels(). Default empty array.
 *     @type string        $description           A short descriptive summary of what the taxonomy is for. Default empty.
 *     @type bool          $public                Whether a taxonomy is intended for use publicly either via
 *                                                the admin interface or by front-end users. The default settings
 *                                                of `$publicly_queryable`, `$show_ui`, and `$show_in_nav_menus`
 *                                                are inherited from `$public`.
 *     @type bool          $publicly_queryable    Whether the taxonomy is publicly queryable.
 *                                                If not set, the default is inherited from `$public`
 *     @type bool          $hierarchical          Whether the taxonomy is hierarchical. Default false.
 *     @type bool          $show_ui               Whether to generate and allow a UI for managing terms in this taxonomy in
 *                                                the admin. If not set, the default is inherited from `$public`
 *                                                (default true).
 *     @type bool          $show_in_menu          Whether to show the taxonomy in the admin menu. If true, the taxonomy is
 *                                                shown as a submenu of the object type menu. If false, no menu is shown.
 *                                                `$show_ui` must be true. If not set, default is inherited from `$show_ui`
 *                                                (default true).
 *     @type bool          $show_in_nav_menus     Makes this taxonomy available for selection in navigation menus. If not
 *                                                set, the default is inherited from `$public` (default true).
 *     @type bool          $show_in_rest          Whether to include the taxonomy in the REST API. Set this to true
 *                                                for the taxonomy to be available in the block editor.
 *     @type string        $rest_base             To change the base url of REST API route. Default is $taxonomy.
 *     @type string        $rest_namespace        To change the namespace URL of REST API route. Default is wp/v2.
 *     @type string        $rest_controller_class REST API Controller class name. Default is 'WP_REST_Terms_Controller'.
 *     @type bool          $show_tagcloud         Whether to list the taxonomy in the Tag Cloud Widget controls. If not set,
 *                                                the default is inherited from `$show_ui` (default true).
 *     @type bool          $show_in_quick_edit    Whether to show the taxonomy in the quick/bulk edit panel. It not set,
 *                                                the default is inherited from `$show_ui` (default true).
 *     @type bool          $show_admin_column     Whether to display a column for the taxonomy on its post type listing
 *                                                screens. Default false.
 *     @type bool|callable $meta_box_cb           Provide a callback function for the meta box display. If not set,
 *                                                post_categories_meta_box() is used for hierarchical taxonomies, and
 *                                                post_tags_meta_box() is used for non-hierarchical. If false, no meta
 *                                                box is shown.
 *     @type callable      $meta_box_sanitize_cb  Callback function for sanitizing taxonomy data saved from a meta
 *                                                box. If no callback is defined, an appropriate one is determined
 *                                                based on the value of `$meta_box_cb`.
 *     @type string[]      $capabilities {
 *         Array of capabilities for this taxonomy.
 *
 *         @type string $manage_terms Default 'manage_categories'.
 *         @type string $edit_terms   Default 'manage_categories'.
 *         @type string $delete_terms Default 'manage_categories'.
 *         @type string $assign_terms Default 'edit_posts'.
 *     }
 *     @type bool|array    $rewrite {
 *         Triggers the handling of rewrites for this taxonomy. Default true, using $taxonomy as slug. To prevent
 *         rewrite, set to false. To specify rewrite rules, an array can be passed with any of these keys:
 *
 *         @type string $slug         Customize the permastruct slug. Default `$taxonomy` key.
 *         @type bool   $with_front   Should the permastruct be prepended with WP_Rewrite::$front. Default true.
 *         @type bool   $hierarchical Either hierarchical rewrite tag or not. Default false.
 *         @type int    $ep_mask      Assign an endpoint mask. Default `EP_NONE`.
 *     }
 *     @type string|bool   $query_var             Sets the query var key for this taxonomy. Default `$taxonomy` key. If
 *                                                false, a taxonomy cannot be loaded at `?{query_var}={term_slug}`. If a
 *                                                string, the query `?{query_var}={term_slug}` will be valid.
 *     @type callable      $update_count_callback Works much like a hook, in that it will be called when the count is
 *                                                updated. Default _update_post_term_count() for taxonomies attached
 *                                                to post types, which confirms that the objects are published before
 *                                                counting them. Default _update_generic_term_count() for taxonomies
 *                                                attached to other object types, such as users.
 *     @type string|array  $default_term {
 *         Default term to be used for the taxonomy.
 *
 *         @type string $name         Name of default term.
 *         @type string $slug         Slug for default term. Default empty.
 *         @type string $description  Description for default term. Default empty.
 *     }
 *     @type bool          $sort                  Whether terms in this taxonomy should be sorted in the order they are
 *                                                provided to `wp_set_object_terms()`. Default null which equates to false.
 *     @type array         $args                  Array of arguments to automatically use inside `wp_get_object_terms()`
 *                                                for this taxonomy.
 *     @type bool          $_builtin              This taxonomy is a "built-in" taxonomy. INTERNAL USE ONLY!
 *                                                Default false.
 * }
 * @return WP_Taxonomy|WP_Error The registered taxonomy object on success, WP_Error object on failure.
 */
function register_taxonomy($taxonomy, $object_type, $args = array())
{
}
/**
 * Unregisters a taxonomy.
 *
 * Can not be used to unregister built-in taxonomies.
 *
 * @since 4.5.0
 *
 * @global WP_Taxonomy[] $wp_taxonomies List of taxonomies.
 *
 * @param string $taxonomy Taxonomy name.
 * @return true|WP_Error True on success, WP_Error on failure or if the taxonomy doesn't exist.
 */
function unregister_taxonomy($taxonomy)
{
}
/**
 * Builds an object with all taxonomy labels out of a taxonomy object.
 *
 * @since 3.0.0
 * @since 4.3.0 Added the `no_terms` label.
 * @since 4.4.0 Added the `items_list_navigation` and `items_list` labels.
 * @since 4.9.0 Added the `most_used` and `back_to_items` labels.
 * @since 5.7.0 Added the `filter_by_item` label.
 * @since 5.8.0 Added the `item_link` and `item_link_description` labels.
 * @since 5.9.0 Added the `name_field_description`, `slug_field_description`,
 *              `parent_field_description`, and `desc_field_description` labels.
 * @since 6.6.0 Added the `template_name` label.
 *
 * @param WP_Taxonomy $tax Taxonomy object.
 * @return object {
 *     Taxonomy labels object. The first default value is for non-hierarchical taxonomies
 *     (like tags) and the second one is for hierarchical taxonomies (like categories).
 *
 *     @type string $name                       General name for the taxonomy, usually plural. The same
 *                                              as and overridden by `$tax->label`. Default 'Tags'/'Categories'.
 *     @type string $singular_name              Name for one object of this taxonomy. Default 'Tag'/'Category'.
 *     @type string $search_items               Default 'Search Tags'/'Search Categories'.
 *     @type string $popular_items              This label is only used for non-hierarchical taxonomies.
 *                                              Default 'Popular Tags'.
 *     @type string $all_items                  Default 'All Tags'/'All Categories'.
 *     @type string $parent_item                This label is only used for hierarchical taxonomies. Default
 *                                              'Parent Category'.
 *     @type string $parent_item_colon          The same as `parent_item`, but with colon `:` in the end.
 *     @type string $name_field_description     Description for the Name field on Edit Tags screen.
 *                                              Default 'The name is how it appears on your site'.
 *     @type string $slug_field_description     Description for the Slug field on Edit Tags screen.
 *                                              Default 'The &#8220;slug&#8221; is the URL-friendly version
 *                                              of the name. It is usually all lowercase and contains
 *                                              only letters, numbers, and hyphens'.
 *     @type string $parent_field_description   Description for the Parent field on Edit Tags screen.
 *                                              Default 'Assign a parent term to create a hierarchy.
 *                                              The term Jazz, for example, would be the parent
 *                                              of Bebop and Big Band'.
 *     @type string $desc_field_description     Description for the Description field on Edit Tags screen.
 *                                              Default 'The description is not prominent by default;
 *                                              however, some themes may show it'.
 *     @type string $edit_item                  Default 'Edit Tag'/'Edit Category'.
 *     @type string $view_item                  Default 'View Tag'/'View Category'.
 *     @type string $update_item                Default 'Update Tag'/'Update Category'.
 *     @type string $add_new_item               Default 'Add New Tag'/'Add New Category'.
 *     @type string $new_item_name              Default 'New Tag Name'/'New Category Name'.
 *     @type string $template_name              Default 'Tag Archives'/'Category Archives'.
 *     @type string $separate_items_with_commas This label is only used for non-hierarchical taxonomies. Default
 *                                              'Separate tags with commas', used in the meta box.
 *     @type string $add_or_remove_items        This label is only used for non-hierarchical taxonomies. Default
 *                                              'Add or remove tags', used in the meta box when JavaScript
 *                                              is disabled.
 *     @type string $choose_from_most_used      This label is only used on non-hierarchical taxonomies. Default
 *                                              'Choose from the most used tags', used in the meta box.
 *     @type string $not_found                  Default 'No tags found'/'No categories found', used in
 *                                              the meta box and taxonomy list table.
 *     @type string $no_terms                   Default 'No tags'/'No categories', used in the posts and media
 *                                              list tables.
 *     @type string $filter_by_item             This label is only used for hierarchical taxonomies. Default
 *                                              'Filter by category', used in the posts list table.
 *     @type string $items_list_navigation      Label for the table pagination hidden heading.
 *     @type string $items_list                 Label for the table hidden heading.
 *     @type string $most_used                  Title for the Most Used tab. Default 'Most Used'.
 *     @type string $back_to_items              Label displayed after a term has been updated.
 *     @type string $item_link                  Used in the block editor. Title for a navigation link block variation.
 *                                              Default 'Tag Link'/'Category Link'.
 *     @type string $item_link_description      Used in the block editor. Description for a navigation link block
 *                                              variation. Default 'A link to a tag'/'A link to a category'.
 * }
 */
function get_taxonomy_labels($tax)
{
}
/**
 * Adds an already registered taxonomy to an object type.
 *
 * @since 3.0.0
 *
 * @global WP_Taxonomy[] $wp_taxonomies The registered taxonomies.
 *
 * @param string $taxonomy    Name of taxonomy object.
 * @param string $object_type Name of the object type.
 * @return bool True if successful, false if not.
 */
function register_taxonomy_for_object_type($taxonomy, $object_type)
{
}
/**
 * Removes an already registered taxonomy from an object type.
 *
 * @since 3.7.0
 *
 * @global WP_Taxonomy[] $wp_taxonomies The registered taxonomies.
 *
 * @param string $taxonomy    Name of taxonomy object.
 * @param string $object_type Name of the object type.
 * @return bool True if successful, false if not.
 */
function unregister_taxonomy_for_object_type($taxonomy, $object_type)
{
}
//
// Term API.
//
/**
 * Retrieves object IDs of valid taxonomy and term.
 *
 * The strings of `$taxonomies` must exist before this function will continue.
 * On failure of finding a valid taxonomy, it will return a WP_Error.
 *
 * The `$terms` aren't checked the same as `$taxonomies`, but still need to exist
 * for object IDs to be returned.
 *
 * It is possible to change the order that object IDs are returned by using `$args`
 * with either ASC or DESC array. The value should be in the key named 'order'.
 *
 * @since 2.3.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int|int[]       $term_ids   Term ID or array of term IDs of terms that will be used.
 * @param string|string[] $taxonomies String of taxonomy name or Array of string values of taxonomy names.
 * @param array|string    $args       {
 *     Change the order of the object IDs.
 *
 *     @type string $order Order to retrieve terms. Accepts 'ASC' or 'DESC'. Default 'ASC'.
 * }
 * @return string[]|WP_Error An array of object IDs as numeric strings on success,
 *                           WP_Error if the taxonomy does not exist.
 */
function get_objects_in_term($term_ids, $taxonomies, $args = array())
{
}
/**
 * Given a taxonomy query, generates SQL to be appended to a main query.
 *
 * @since 3.1.0
 *
 * @see WP_Tax_Query
 *
 * @param array  $tax_query         A compact tax query
 * @param string $primary_table
 * @param string $primary_id_column
 * @return string[]
 */
function get_tax_sql($tax_query, $primary_table, $primary_id_column)
{
}
/**
 * Gets all term data from database by term ID.
 *
 * The usage of the get_term function is to apply filters to a term object. It
 * is possible to get a term object from the database before applying the
 * filters.
 *
 * $term ID must be part of $taxonomy, to get from the database. Failure, might
 * be able to be captured by the hooks. Failure would be the same value as $wpdb
 * returns for the get_row method.
 *
 * There are two hooks, one is specifically for each term, named 'get_term', and
 * the second is for the taxonomy name, 'term_$taxonomy'. Both hooks gets the
 * term object, and the taxonomy name as parameters. Both hooks are expected to
 * return a term object.
 *
 * {@see 'get_term'} hook - Takes two parameters the term Object and the taxonomy name.
 * Must return term object. Used in get_term() as a catch-all filter for every
 * $term.
 *
 * {@see 'get_$taxonomy'} hook - Takes two parameters the term Object and the taxonomy
 * name. Must return term object. $taxonomy will be the taxonomy name, so for
 * example, if 'category', it would be 'get_category' as the filter name. Useful
 * for custom taxonomies or plugging into default taxonomies.
 *
 * @todo Better formatting for DocBlock
 *
 * @since 2.3.0
 * @since 4.4.0 Converted to return a WP_Term object if `$output` is `OBJECT`.
 *              The `$taxonomy` parameter was made optional.
 *
 * @see sanitize_term_field() The $context param lists the available values for get_term_by() $filter param.
 *
 * @param int|WP_Term|object $term     If integer, term data will be fetched from the database,
 *                                     or from the cache if available.
 *                                     If stdClass object (as in the results of a database query),
 *                                     will apply filters and return a `WP_Term` object with the `$term` data.
 *                                     If `WP_Term`, will return `$term`.
 * @param string             $taxonomy Optional. Taxonomy name that `$term` is part of.
 * @param string             $output   Optional. The required return type. One of OBJECT, ARRAY_A, or ARRAY_N, which
 *                                     correspond to a WP_Term object, an associative array, or a numeric array,
 *                                     respectively. Default OBJECT.
 * @param string             $filter   Optional. How to sanitize term fields. Default 'raw'.
 * @return WP_Term|array|WP_Error|null WP_Term instance (or array) on success, depending on the `$output` value.
 *                                     WP_Error if `$taxonomy` does not exist. Null for miscellaneous failure.
 */
function get_term($term, $taxonomy = '', $output = \OBJECT, $filter = 'raw')
{
}
/**
 * Gets all term data from database by term field and data.
 *
 * Warning: $value is not escaped for 'name' $field. You must do it yourself, if
 * required.
 *
 * The default $field is 'id', therefore it is possible to also use null for
 * field, but not recommended that you do so.
 *
 * If $value does not exist, the return value will be false. If $taxonomy exists
 * and $field and $value combinations exist, the term will be returned.
 *
 * This function will always return the first term that matches the `$field`-
 * `$value`-`$taxonomy` combination specified in the parameters. If your query
 * is likely to match more than one term (as is likely to be the case when
 * `$field` is 'name', for example), consider using get_terms() instead; that
 * way, you will get all matching terms, and can provide your own logic for
 * deciding which one was intended.
 *
 * @todo Better formatting for DocBlock.
 *
 * @since 2.3.0
 * @since 4.4.0 `$taxonomy` is optional if `$field` is 'term_taxonomy_id'. Converted to return
 *              a WP_Term object if `$output` is `OBJECT`.
 * @since 5.5.0 Added 'ID' as an alias of 'id' for the `$field` parameter.
 *
 * @see sanitize_term_field() The $context param lists the available values for get_term_by() $filter param.
 *
 * @param string     $field    Either 'slug', 'name', 'term_id' (or 'id', 'ID'), or 'term_taxonomy_id'.
 * @param string|int $value    Search for this term value.
 * @param string     $taxonomy Taxonomy name. Optional, if `$field` is 'term_taxonomy_id'.
 * @param string     $output   Optional. The required return type. One of OBJECT, ARRAY_A, or ARRAY_N, which
 *                             correspond to a WP_Term object, an associative array, or a numeric array,
 *                             respectively. Default OBJECT.
 * @param string     $filter   Optional. How to sanitize term fields. Default 'raw'.
 * @return WP_Term|array|false WP_Term instance (or array) on success, depending on the `$output` value.
 *                             False if `$taxonomy` does not exist or `$term` was not found.
 */
function get_term_by($field, $value, $taxonomy = '', $output = \OBJECT, $filter = 'raw')
{
}
/**
 * Merges all term children into a single array of their IDs.
 *
 * This recursive function will merge all of the children of $term into the same
 * array of term IDs. Only useful for taxonomies which are hierarchical.
 *
 * Will return an empty array if $term does not exist in $taxonomy.
 *
 * @since 2.3.0
 *
 * @param int    $term_id  ID of term to get children.
 * @param string $taxonomy Taxonomy name.
 * @return array|WP_Error List of term IDs. WP_Error returned if `$taxonomy` does not exist.
 */
function get_term_children($term_id, $taxonomy)
{
}
/**
 * Gets sanitized term field.
 *
 * The function is for contextual reasons and for simplicity of usage.
 *
 * @since 2.3.0
 * @since 4.4.0 The `$taxonomy` parameter was made optional. `$term` can also now accept a WP_Term object.
 *
 * @see sanitize_term_field()
 *
 * @param string      $field    Term field to fetch.
 * @param int|WP_Term $term     Term ID or object.
 * @param string      $taxonomy Optional. Taxonomy name. Default empty.
 * @param string      $context  Optional. How to sanitize term fields. Look at sanitize_term_field() for available options.
 *                              Default 'display'.
 * @return string|int|null|WP_Error Will return an empty string if $term is not an object or if $field is not set in $term.
 */
function get_term_field($field, $term, $taxonomy = '', $context = 'display')
{
}
/**
 * Sanitizes term for editing.
 *
 * Return value is sanitize_term() and usage is for sanitizing the term for
 * editing. Function is for contextual and simplicity.
 *
 * @since 2.3.0
 *
 * @param int|object $id       Term ID or object.
 * @param string     $taxonomy Taxonomy name.
 * @return string|int|null|WP_Error Will return empty string if $term is not an object.
 */
function get_term_to_edit($id, $taxonomy)
{
}
/**
 * Retrieves the terms in a given taxonomy or list of taxonomies.
 *
 * You can fully inject any customizations to the query before it is sent, as
 * well as control the output with a filter.
 *
 * The return type varies depending on the value passed to `$args['fields']`. See
 * WP_Term_Query::get_terms() for details. In all cases, a `WP_Error` object will
 * be returned if an invalid taxonomy is requested.
 *
 * The {@see 'get_terms'} filter will be called when the cache has the term and will
 * pass the found term along with the array of $taxonomies and array of $args.
 * This filter is also called before the array of terms is passed and will pass
 * the array of terms, along with the $taxonomies and $args.
 *
 * The {@see 'list_terms_exclusions'} filter passes the compiled exclusions along with
 * the $args.
 *
 * The {@see 'get_terms_orderby'} filter passes the `ORDER BY` clause for the query
 * along with the $args array.
 *
 * Taxonomy or an array of taxonomies should be passed via the 'taxonomy' argument
 * in the `$args` array:
 *
 *     $terms = get_terms( array(
 *         'taxonomy'   => 'post_tag',
 *         'hide_empty' => false,
 *     ) );
 *
 * Prior to 4.5.0, taxonomy was passed as the first parameter of `get_terms()`.
 *
 * @since 2.3.0
 * @since 4.2.0 Introduced 'name' and 'childless' parameters.
 * @since 4.4.0 Introduced the ability to pass 'term_id' as an alias of 'id' for the `orderby` parameter.
 *              Introduced the 'meta_query' and 'update_term_meta_cache' parameters. Converted to return
 *              a list of WP_Term objects.
 * @since 4.5.0 Changed the function signature so that the `$args` array can be provided as the first parameter.
 *              Introduced 'meta_key' and 'meta_value' parameters. Introduced the ability to order results by metadata.
 * @since 4.8.0 Introduced 'suppress_filter' parameter.
 *
 * @internal The `$deprecated` parameter is parsed for backward compatibility only.
 *
 * @param array|string $args       Optional. Array or string of arguments. See WP_Term_Query::__construct()
 *                                 for information on accepted arguments. Default empty array.
 * @param array|string $deprecated Optional. Argument array, when using the legacy function parameter format.
 *                                 If present, this parameter will be interpreted as `$args`, and the first
 *                                 function parameter will be parsed as a taxonomy or array of taxonomies.
 *                                 Default empty.
 * @return WP_Term[]|int[]|string[]|string|WP_Error Array of terms, a count thereof as a numeric string,
 *                                                  or WP_Error if any of the taxonomies do not exist.
 *                                                  See the function description for more information.
 */
function get_terms($args = array(), $deprecated = '')
{
}
/**
 * Adds metadata to a term.
 *
 * @since 4.4.0
 *
 * @param int    $term_id    Term ID.
 * @param string $meta_key   Metadata name.
 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
 * @param bool   $unique     Optional. Whether the same key should not be added.
 *                           Default false.
 * @return int|false|WP_Error Meta ID on success, false on failure.
 *                            WP_Error when term_id is ambiguous between taxonomies.
 */
function add_term_meta($term_id, $meta_key, $meta_value, $unique = \false)
{
}
/**
 * Removes metadata matching criteria from a term.
 *
 * @since 4.4.0
 *
 * @param int    $term_id    Term ID.
 * @param string $meta_key   Metadata name.
 * @param mixed  $meta_value Optional. Metadata value. If provided,
 *                           rows will only be removed that match the value.
 *                           Must be serializable if non-scalar. Default empty.
 * @return bool True on success, false on failure.
 */
function delete_term_meta($term_id, $meta_key, $meta_value = '')
{
}
/**
 * Retrieves metadata for a term.
 *
 * @since 4.4.0
 *
 * @param int    $term_id Term ID.
 * @param string $key     Optional. The meta key to retrieve. By default,
 *                        returns data for all keys. Default empty.
 * @param bool   $single  Optional. Whether to return a single value.
 *                        This parameter has no effect if `$key` is not specified.
 *                        Default false.
 * @return mixed An array of values if `$single` is false.
 *               The value of the meta field if `$single` is true.
 *               False for an invalid `$term_id` (non-numeric, zero, or negative value).
 *               An empty string if a valid but non-existing term ID is passed.
 */
function get_term_meta($term_id, $key = '', $single = \false)
{
}
/**
 * Updates term metadata.
 *
 * Use the `$prev_value` parameter to differentiate between meta fields with the same key and term ID.
 *
 * If the meta field for the term does not exist, it will be added.
 *
 * @since 4.4.0
 *
 * @param int    $term_id    Term ID.
 * @param string $meta_key   Metadata key.
 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
 * @param mixed  $prev_value Optional. Previous value to check before updating.
 *                           If specified, only update existing metadata entries with
 *                           this value. Otherwise, update all entries. Default empty.
 * @return int|bool|WP_Error Meta ID if the key didn't exist. true on successful update,
 *                           false on failure or if the value passed to the function
 *                           is the same as the one that is already in the database.
 *                           WP_Error when term_id is ambiguous between taxonomies.
 */
function update_term_meta($term_id, $meta_key, $meta_value, $prev_value = '')
{
}
/**
 * Updates metadata cache for list of term IDs.
 *
 * Performs SQL query to retrieve all metadata for the terms matching `$term_ids` and stores them in the cache.
 * Subsequent calls to `get_term_meta()` will not need to query the database.
 *
 * @since 4.4.0
 *
 * @param array $term_ids List of term IDs.
 * @return array|false An array of metadata on success, false if there is nothing to update.
 */
function update_termmeta_cache($term_ids)
{
}
/**
 * Queue term meta for lazy-loading.
 *
 * @since 6.3.0
 *
 * @param array $term_ids List of term IDs.
 */
function wp_lazyload_term_meta(array $term_ids)
{
}
/**
 * Gets all meta data, including meta IDs, for the given term ID.
 *
 * @since 4.9.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int $term_id Term ID.
 * @return array|false Array with meta data, or false when the meta table is not installed.
 */
function has_term_meta($term_id)
{
}
/**
 * Registers a meta key for terms.
 *
 * @since 4.9.8
 *
 * @param string $taxonomy Taxonomy to register a meta key for. Pass an empty string
 *                         to register the meta key across all existing taxonomies.
 * @param string $meta_key The meta key to register.
 * @param array  $args     Data used to describe the meta key when registered. See
 *                         {@see register_meta()} for a list of supported arguments.
 * @return bool True if the meta key was successfully registered, false if not.
 */
function register_term_meta($taxonomy, $meta_key, array $args)
{
}
/**
 * Unregisters a meta key for terms.
 *
 * @since 4.9.8
 *
 * @param string $taxonomy Taxonomy the meta key is currently registered for. Pass
 *                         an empty string if the meta key is registered across all
 *                         existing taxonomies.
 * @param string $meta_key The meta key to unregister.
 * @return bool True on success, false if the meta key was not previously registered.
 */
function unregister_term_meta($taxonomy, $meta_key)
{
}
/**
 * Determines whether a taxonomy term exists.
 *
 * Formerly is_term(), introduced in 2.3.0.
 *
 * For more information on this and similar theme functions, check out
 * the {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} article in the Theme Developer Handbook.
 *
 * @since 3.0.0
 * @since 6.0.0 Converted to use `get_terms()`.
 *
 * @global bool $_wp_suspend_cache_invalidation
 *
 * @param int|string $term        The term to check. Accepts term ID, slug, or name.
 * @param string     $taxonomy    Optional. The taxonomy name to use.
 * @param int        $parent_term Optional. ID of parent term under which to confine the exists search.
 * @return mixed Returns null if the term does not exist.
 *               Returns the term ID if no taxonomy is specified and the term ID exists.
 *               Returns an array of the term ID and the term taxonomy ID if the taxonomy is specified and the pairing exists.
 *               Returns 0 if term ID 0 is passed to the function.
 */
function term_exists($term, $taxonomy = '', $parent_term = \null)
{
}
/**
 * Checks if a term is an ancestor of another term.
 *
 * You can use either an ID or the term object for both parameters.
 *
 * @since 3.4.0
 *
 * @param int|object $term1    ID or object to check if this is the parent term.
 * @param int|object $term2    The child term.
 * @param string     $taxonomy Taxonomy name that $term1 and `$term2` belong to.
 * @return bool Whether `$term2` is a child of `$term1`.
 */
function term_is_ancestor_of($term1, $term2, $taxonomy)
{
}
/**
 * Sanitizes all term fields.
 *
 * Relies on sanitize_term_field() to sanitize the term. The difference is that
 * this function will sanitize **all** fields. The context is based
 * on sanitize_term_field().
 *
 * The `$term` is expected to be either an array or an object.
 *
 * @since 2.3.0
 *
 * @param array|object $term     The term to check.
 * @param string       $taxonomy The taxonomy name to use.
 * @param string       $context  Optional. Context in which to sanitize the term.
 *                               Accepts 'raw', 'edit', 'db', 'display', 'rss',
 *                               'attribute', or 'js'. Default 'display'.
 * @return array|object Term with all fields sanitized.
 */
function sanitize_term($term, $taxonomy, $context = 'display')
{
}
/**
 * Sanitizes the field value in the term based on the context.
 *
 * Passing a term field value through the function should be assumed to have
 * cleansed the value for whatever context the term field is going to be used.
 *
 * If no context or an unsupported context is given, then default filters will
 * be applied.
 *
 * There are enough filters for each context to support a custom filtering
 * without creating your own filter function. Simply create a function that
 * hooks into the filter you need.
 *
 * @since 2.3.0
 *
 * @param string $field    Term field to sanitize.
 * @param string $value    Search for this term value.
 * @param int    $term_id  Term ID.
 * @param string $taxonomy Taxonomy name.
 * @param string $context  Context in which to sanitize the term field.
 *                         Accepts 'raw', 'edit', 'db', 'display', 'rss',
 *                         'attribute', or 'js'. Default 'display'.
 * @return mixed Sanitized field.
 */
function sanitize_term_field($field, $value, $term_id, $taxonomy, $context)
{
}
/**
 * Counts how many terms are in taxonomy.
 *
 * Default $args is 'hide_empty' which can be 'hide_empty=true' or array('hide_empty' => true).
 *
 * @since 2.3.0
 * @since 5.6.0 Changed the function signature so that the `$args` array can be provided as the first parameter.
 *
 * @internal The `$deprecated` parameter is parsed for backward compatibility only.
 *
 * @param array|string $args       Optional. Array or string of arguments. See WP_Term_Query::__construct()
 *                                 for information on accepted arguments. Default empty array.
 * @param array|string $deprecated Optional. Argument array, when using the legacy function parameter format.
 *                                 If present, this parameter will be interpreted as `$args`, and the first
 *                                 function parameter will be parsed as a taxonomy or array of taxonomies.
 *                                 Default empty.
 * @return string|WP_Error Numeric string containing the number of terms in that
 *                         taxonomy or WP_Error if the taxonomy does not exist.
 */
function wp_count_terms($args = array(), $deprecated = '')
{
}
/**
 * Unlinks the object from the taxonomy or taxonomies.
 *
 * Will remove all relationships between the object and any terms in
 * a particular taxonomy or taxonomies. Does not remove the term or
 * taxonomy itself.
 *
 * @since 2.3.0
 *
 * @param int          $object_id  The term object ID that refers to the term.
 * @param string|array $taxonomies List of taxonomy names or single taxonomy name.
 */
function wp_delete_object_term_relationships($object_id, $taxonomies)
{
}
/**
 * Removes a term from the database.
 *
 * If the term is a parent of other terms, then the children will be updated to
 * that term's parent.
 *
 * Metadata associated with the term will be deleted.
 *
 * @since 2.3.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int          $term     Term ID.
 * @param string       $taxonomy Taxonomy name.
 * @param array|string $args {
 *     Optional. Array of arguments to override the default term ID. Default empty array.
 *
 *     @type int  $default       The term ID to make the default term. This will only override
 *                               the terms found if there is only one term found. Any other and
 *                               the found terms are used.
 *     @type bool $force_default Optional. Whether to force the supplied term as default to be
 *                               assigned even if the object was not going to be term-less.
 *                               Default false.
 * }
 * @return bool|int|WP_Error True on success, false if term does not exist. Zero on attempted
 *                           deletion of default Category. WP_Error if the taxonomy does not exist.
 */
function wp_delete_term($term, $taxonomy, $args = array())
{
}
/**
 * Deletes one existing category.
 *
 * @since 2.0.0
 *
 * @param int $cat_id Category term ID.
 * @return bool|int|WP_Error Returns true if completes delete action; false if term doesn't exist;
 *                           Zero on attempted deletion of default Category; WP_Error object is
 *                           also a possibility.
 */
function wp_delete_category($cat_id)
{
}
/**
 * Retrieves the terms associated with the given object(s), in the supplied taxonomies.
 *
 * @since 2.3.0
 * @since 4.2.0 Added support for 'taxonomy', 'parent', and 'term_taxonomy_id' values of `$orderby`.
 *              Introduced `$parent` argument.
 * @since 4.4.0 Introduced `$meta_query` and `$update_term_meta_cache` arguments. When `$fields` is 'all' or
 *              'all_with_object_id', an array of `WP_Term` objects will be returned.
 * @since 4.7.0 Refactored to use WP_Term_Query, and to support any WP_Term_Query arguments.
 * @since 6.3.0 Passing `update_term_meta_cache` argument value false by default resulting in get_terms() to not
 *              prime the term meta cache.
 *
 * @param int|int[]       $object_ids The ID(s) of the object(s) to retrieve.
 * @param string|string[] $taxonomies The taxonomy names to retrieve terms from.
 * @param array|string    $args       See WP_Term_Query::__construct() for supported arguments.
 * @return WP_Term[]|int[]|string[]|string|WP_Error Array of terms, a count thereof as a numeric string,
 *                                                  or WP_Error if any of the taxonomies do not exist.
 *                                                  See WP_Term_Query::get_terms() for more information.
 */
function wp_get_object_terms($object_ids, $taxonomies, $args = array())
{
}
/**
 * Adds a new term to the database.
 *
 * A non-existent term is inserted in the following sequence:
 * 1. The term is added to the term table, then related to the taxonomy.
 * 2. If everything is correct, several actions are fired.
 * 3. The 'term_id_filter' is evaluated.
 * 4. The term cache is cleaned.
 * 5. Several more actions are fired.
 * 6. An array is returned containing the `term_id` and `term_taxonomy_id`.
 *
 * If the 'slug' argument is not empty, then it is checked to see if the term
 * is invalid. If it is not a valid, existing term, it is added and the term_id
 * is given.
 *
 * If the taxonomy is hierarchical, and the 'parent' argument is not empty,
 * the term is inserted and the term_id will be given.
 *
 * Error handling:
 * If `$taxonomy` does not exist or `$term` is empty,
 * a WP_Error object will be returned.
 *
 * If the term already exists on the same hierarchical level,
 * or the term slug and name are not unique, a WP_Error object will be returned.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @since 2.3.0
 *
 * @param string       $term     The term name to add.
 * @param string       $taxonomy The taxonomy to which to add the term.
 * @param array|string $args {
 *     Optional. Array or query string of arguments for inserting a term.
 *
 *     @type string $alias_of    Slug of the term to make this term an alias of.
 *                               Default empty string. Accepts a term slug.
 *     @type string $description The term description. Default empty string.
 *     @type int    $parent      The id of the parent term. Default 0.
 *     @type string $slug        The term slug to use. Default empty string.
 * }
 * @return array|WP_Error {
 *     An array of the new term data, WP_Error otherwise.
 *
 *     @type int        $term_id          The new term ID.
 *     @type int|string $term_taxonomy_id The new term taxonomy ID. Can be a numeric string.
 * }
 */
function wp_insert_term($term, $taxonomy, $args = array())
{
}
/**
 * Creates term and taxonomy relationships.
 *
 * Relates an object (post, link, etc.) to a term and taxonomy type. Creates the
 * term and taxonomy relationship if it doesn't already exist. Creates a term if
 * it doesn't exist (using the slug).
 *
 * A relationship means that the term is grouped in or belongs to the taxonomy.
 * A term has no meaning until it is given context by defining which taxonomy it
 * exists under.
 *
 * @since 2.3.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int              $object_id The object to relate to.
 * @param string|int|array $terms     A single term slug, single term ID, or array of either term slugs or IDs.
 *                                    Will replace all existing related terms in this taxonomy. Passing an
 *                                    empty array will remove all related terms.
 * @param string           $taxonomy  The context in which to relate the term to the object.
 * @param bool             $append    Optional. If false will delete difference of terms. Default false.
 * @return array|WP_Error Term taxonomy IDs of the affected terms or WP_Error on failure.
 */
function wp_set_object_terms($object_id, $terms, $taxonomy, $append = \false)
{
}
/**
 * Adds term(s) associated with a given object.
 *
 * @since 3.6.0
 *
 * @param int              $object_id The ID of the object to which the terms will be added.
 * @param string|int|array $terms     The slug(s) or ID(s) of the term(s) to add.
 * @param array|string     $taxonomy  Taxonomy name.
 * @return array|WP_Error Term taxonomy IDs of the affected terms.
 */
function wp_add_object_terms($object_id, $terms, $taxonomy)
{
}
/**
 * Removes term(s) associated with a given object.
 *
 * @since 3.6.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int              $object_id The ID of the object from which the terms will be removed.
 * @param string|int|array $terms     The slug(s) or ID(s) of the term(s) to remove.
 * @param string           $taxonomy  Taxonomy name.
 * @return bool|WP_Error True on success, false or WP_Error on failure.
 */
function wp_remove_object_terms($object_id, $terms, $taxonomy)
{
}
/**
 * Makes term slug unique, if it isn't already.
 *
 * The `$slug` has to be unique global to every taxonomy, meaning that one
 * taxonomy term can't have a matching slug with another taxonomy term. Each
 * slug has to be globally unique for every taxonomy.
 *
 * The way this works is that if the taxonomy that the term belongs to is
 * hierarchical and has a parent, it will append that parent to the $slug.
 *
 * If that still doesn't return a unique slug, then it tries to append a number
 * until it finds a number that is truly unique.
 *
 * The only purpose for `$term` is for appending a parent, if one exists.
 *
 * @since 2.3.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string $slug The string that will be tried for a unique slug.
 * @param object $term The term object that the `$slug` will belong to.
 * @return string Will return a true unique slug.
 */
function wp_unique_term_slug($slug, $term)
{
}
/**
 * Updates term based on arguments provided.
 *
 * The `$args` will indiscriminately override all values with the same field name.
 * Care must be taken to not override important information need to update or
 * update will fail (or perhaps create a new term, neither would be acceptable).
 *
 * Defaults will set 'alias_of', 'description', 'parent', and 'slug' if not
 * defined in `$args` already.
 *
 * 'alias_of' will create a term group, if it doesn't already exist, and
 * update it for the `$term`.
 *
 * If the 'slug' argument in `$args` is missing, then the 'name' will be used.
 * If you set 'slug' and it isn't unique, then a WP_Error is returned.
 * If you don't pass any slug, then a unique one will be created.
 *
 * @since 2.3.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int          $term_id  The ID of the term.
 * @param string       $taxonomy The taxonomy of the term.
 * @param array        $args {
 *     Optional. Array of arguments for updating a term.
 *
 *     @type string $alias_of    Slug of the term to make this term an alias of.
 *                               Default empty string. Accepts a term slug.
 *     @type string $description The term description. Default empty string.
 *     @type int    $parent      The id of the parent term. Default 0.
 *     @type string $slug        The term slug to use. Default empty string.
 * }
 * @return array|WP_Error An array containing the `term_id` and `term_taxonomy_id`,
 *                        WP_Error otherwise.
 */
function wp_update_term($term_id, $taxonomy, $args = array())
{
}
/**
 * Enables or disables term counting.
 *
 * @since 2.5.0
 *
 * @param bool $defer Optional. Enable if true, disable if false.
 * @return bool Whether term counting is enabled or disabled.
 */
function wp_defer_term_counting($defer = \null)
{
}
/**
 * Updates the amount of terms in taxonomy.
 *
 * If there is a taxonomy callback applied, then it will be called for updating
 * the count.
 *
 * The default action is to count what the amount of terms have the relationship
 * of term ID. Once that is done, then update the database.
 *
 * @since 2.3.0
 *
 * @param int|array $terms       The term_taxonomy_id of the terms.
 * @param string    $taxonomy    The context of the term.
 * @param bool      $do_deferred Whether to flush the deferred term counts too. Default false.
 * @return bool If no terms will return false, and if successful will return true.
 */
function wp_update_term_count($terms, $taxonomy, $do_deferred = \false)
{
}
/**
 * Performs term count update immediately.
 *
 * @since 2.5.0
 *
 * @param array  $terms    The term_taxonomy_id of terms to update.
 * @param string $taxonomy The context of the term.
 * @return true Always true when complete.
 */
function wp_update_term_count_now($terms, $taxonomy)
{
}
//
// Cache.
//
/**
 * Removes the taxonomy relationship to terms from the cache.
 *
 * Will remove the entire taxonomy relationship containing term `$object_id`. The
 * term IDs have to exist within the taxonomy `$object_type` for the deletion to
 * take place.
 *
 * @since 2.3.0
 *
 * @global bool $_wp_suspend_cache_invalidation
 *
 * @see get_object_taxonomies() for more on $object_type.
 *
 * @param int|array    $object_ids  Single or list of term object ID(s).
 * @param array|string $object_type The taxonomy object type.
 */
function clean_object_term_cache($object_ids, $object_type)
{
}
/**
 * Removes all of the term IDs from the cache.
 *
 * @since 2.3.0
 *
 * @global wpdb $wpdb                           WordPress database abstraction object.
 * @global bool $_wp_suspend_cache_invalidation
 *
 * @param int|int[] $ids            Single or array of term IDs.
 * @param string    $taxonomy       Optional. Taxonomy slug. Can be empty, in which case the taxonomies of the passed
 *                                  term IDs will be used. Default empty.
 * @param bool      $clean_taxonomy Optional. Whether to clean taxonomy wide caches (true), or just individual
 *                                  term object caches (false). Default true.
 */
function clean_term_cache($ids, $taxonomy = '', $clean_taxonomy = \true)
{
}
/**
 * Cleans the caches for a taxonomy.
 *
 * @since 4.9.0
 *
 * @param string $taxonomy Taxonomy slug.
 */
function clean_taxonomy_cache($taxonomy)
{
}
/**
 * Retrieves the cached term objects for the given object ID.
 *
 * Upstream functions (like get_the_terms() and is_object_in_term()) are
 * responsible for populating the object-term relationship cache. The current
 * function only fetches relationship data that is already in the cache.
 *
 * @since 2.3.0
 * @since 4.7.0 Returns a `WP_Error` object if there's an error with
 *              any of the matched terms.
 *
 * @param int    $id       Term object ID, for example a post, comment, or user ID.
 * @param string $taxonomy Taxonomy name.
 * @return bool|WP_Term[]|WP_Error Array of `WP_Term` objects, if cached.
 *                                 False if cache is empty for `$taxonomy` and `$id`.
 *                                 WP_Error if get_term() returns an error object for any term.
 */
function get_object_term_cache($id, $taxonomy)
{
}
/**
 * Updates the cache for the given term object ID(s).
 *
 * Note: Due to performance concerns, great care should be taken to only update
 * term caches when necessary. Processing time can increase exponentially depending
 * on both the number of passed term IDs and the number of taxonomies those terms
 * belong to.
 *
 * Caches will only be updated for terms not already cached.
 *
 * @since 2.3.0
 *
 * @param string|int[]    $object_ids  Comma-separated list or array of term object IDs.
 * @param string|string[] $object_type The taxonomy object type or array of the same.
 * @return void|false Void on success or if the `$object_ids` parameter is empty,
 *                    false if all of the terms in `$object_ids` are already cached.
 */
function update_object_term_cache($object_ids, $object_type)
{
}
/**
 * Updates terms in cache.
 *
 * @since 2.3.0
 *
 * @param WP_Term[] $terms    Array of term objects to change.
 * @param string    $taxonomy Not used.
 */
function update_term_cache($terms, $taxonomy = '')
{
}
//
// Private.
//
/**
 * Retrieves children of taxonomy as term IDs.
 *
 * @access private
 * @since 2.3.0
 *
 * @param string $taxonomy Taxonomy name.
 * @return array Empty if $taxonomy isn't hierarchical or returns children as term IDs.
 */
function _get_term_hierarchy($taxonomy)
{
}
/**
 * Gets the subset of $terms that are descendants of $term_id.
 *
 * If `$terms` is an array of objects, then _get_term_children() returns an array of objects.
 * If `$terms` is an array of IDs, then _get_term_children() returns an array of IDs.
 *
 * @access private
 * @since 2.3.0
 *
 * @param int    $term_id   The ancestor term: all returned terms should be descendants of `$term_id`.
 * @param array  $terms     The set of terms - either an array of term objects or term IDs - from which those that
 *                          are descendants of $term_id will be chosen.
 * @param string $taxonomy  The taxonomy which determines the hierarchy of the terms.
 * @param array  $ancestors Optional. Term ancestors that have already been identified. Passed by reference, to keep
 *                          track of found terms when recursing the hierarchy. The array of located ancestors is used
 *                          to prevent infinite recursion loops. For performance, `term_ids` are used as array keys,
 *                          with 1 as value. Default empty array.
 * @return array|WP_Error The subset of $terms that are descendants of $term_id.
 */
function _get_term_children($term_id, $terms, $taxonomy, &$ancestors = array())
{
}
/**
 * Adds count of children to parent count.
 *
 * Recalculates term counts by including items from child terms. Assumes all
 * relevant children are already in the $terms argument.
 *
 * @access private
 * @since 2.3.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param object[]|WP_Term[] $terms    List of term objects (passed by reference).
 * @param string             $taxonomy Term context.
 */
function _pad_term_counts(&$terms, $taxonomy)
{
}
/**
 * Adds any terms from the given IDs to the cache that do not already exist in cache.
 *
 * @since 4.6.0
 * @since 6.1.0 This function is no longer marked as "private".
 * @since 6.3.0 Use wp_lazyload_term_meta() for lazy-loading of term meta.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param array $term_ids          Array of term IDs.
 * @param bool  $update_meta_cache Optional. Whether to update the meta cache. Default true.
 */
function _prime_term_caches($term_ids, $update_meta_cache = \true)
{
}
//
// Default callbacks.
//
/**
 * Updates term count based on object types of the current taxonomy.
 *
 * Private function for the default callback for post_tag and category
 * taxonomies.
 *
 * @access private
 * @since 2.3.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int[]       $terms    List of term taxonomy IDs.
 * @param WP_Taxonomy $taxonomy Current taxonomy object of terms.
 */
function _update_post_term_count($terms, $taxonomy)
{
}
/**
 * Updates term count based on number of objects.
 *
 * Default callback for the 'link_category' taxonomy.
 *
 * @since 3.3.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int[]       $terms    List of term taxonomy IDs.
 * @param WP_Taxonomy $taxonomy Current taxonomy object of terms.
 */
function _update_generic_term_count($terms, $taxonomy)
{
}
/**
 * Creates a new term for a term_taxonomy item that currently shares its term
 * with another term_taxonomy.
 *
 * @ignore
 * @since 4.2.0
 * @since 4.3.0 Introduced `$record` parameter. Also, `$term_id` and
 *              `$term_taxonomy_id` can now accept objects.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int|object $term_id          ID of the shared term, or the shared term object.
 * @param int|object $term_taxonomy_id ID of the term_taxonomy item to receive a new term, or the term_taxonomy object
 *                                     (corresponding to a row from the term_taxonomy table).
 * @param bool       $record           Whether to record data about the split term in the options table. The recording
 *                                     process has the potential to be resource-intensive, so during batch operations
 *                                     it can be beneficial to skip inline recording and do it just once, after the
 *                                     batch is processed. Only set this to `false` if you know what you are doing.
 *                                     Default: true.
 * @return int|WP_Error When the current term does not need to be split (or cannot be split on the current
 *                      database schema), `$term_id` is returned. When the term is successfully split, the
 *                      new term_id is returned. A WP_Error is returned for miscellaneous errors.
 */
function _split_shared_term($term_id, $term_taxonomy_id, $record = \true)
{
}
/**
 * Splits a batch of shared taxonomy terms.
 *
 * @since 4.3.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 */
function _wp_batch_split_terms()
{
}
/**
 * In order to avoid the _wp_batch_split_terms() job being accidentally removed,
 * checks that it's still scheduled while we haven't finished splitting terms.
 *
 * @ignore
 * @since 4.3.0
 */
function _wp_check_for_scheduled_split_terms()
{
}
/**
 * Checks default categories when a term gets split to see if any of them need to be updated.
 *
 * @ignore
 * @since 4.2.0
 *
 * @param int    $term_id          ID of the formerly shared term.
 * @param int    $new_term_id      ID of the new term created for the $term_taxonomy_id.
 * @param int    $term_taxonomy_id ID for the term_taxonomy row affected by the split.
 * @param string $taxonomy         Taxonomy for the split term.
 */
function _wp_check_split_default_terms($term_id, $new_term_id, $term_taxonomy_id, $taxonomy)
{
}
/**
 * Checks menu items when a term gets split to see if any of them need to be updated.
 *
 * @ignore
 * @since 4.2.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int    $term_id          ID of the formerly shared term.
 * @param int    $new_term_id      ID of the new term created for the $term_taxonomy_id.
 * @param int    $term_taxonomy_id ID for the term_taxonomy row affected by the split.
 * @param string $taxonomy         Taxonomy for the split term.
 */
function _wp_check_split_terms_in_menus($term_id, $new_term_id, $term_taxonomy_id, $taxonomy)
{
}
/**
 * If the term being split is a nav_menu, changes associations.
 *
 * @ignore
 * @since 4.3.0
 *
 * @param int    $term_id          ID of the formerly shared term.
 * @param int    $new_term_id      ID of the new term created for the $term_taxonomy_id.
 * @param int    $term_taxonomy_id ID for the term_taxonomy row affected by the split.
 * @param string $taxonomy         Taxonomy for the split term.
 */
function _wp_check_split_nav_menu_terms($term_id, $new_term_id, $term_taxonomy_id, $taxonomy)
{
}
/**
 * Gets data about terms that previously shared a single term_id, but have since been split.
 *
 * @since 4.2.0
 *
 * @param int $old_term_id Term ID. This is the old, pre-split term ID.
 * @return array Array of new term IDs, keyed by taxonomy.
 */
function wp_get_split_terms($old_term_id)
{
}
/**
 * Gets the new term ID corresponding to a previously split term.
 *
 * @since 4.2.0
 *
 * @param int    $old_term_id Term ID. This is the old, pre-split term ID.
 * @param string $taxonomy    Taxonomy that the term belongs to.
 * @return int|false If a previously split term is found corresponding to the old term_id and taxonomy,
 *                   the new term_id will be returned. If no previously split term is found matching
 *                   the parameters, returns false.
 */
function wp_get_split_term($old_term_id, $taxonomy)
{
}
/**
 * Determines whether a term is shared between multiple taxonomies.
 *
 * Shared taxonomy terms began to be split in 4.3, but failed cron tasks or
 * other delays in upgrade routines may cause shared terms to remain.
 *
 * @since 4.4.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int $term_id Term ID.
 * @return bool Returns false if a term is not shared between multiple taxonomies or
 *              if splitting shared taxonomy terms is finished.
 */
function wp_term_is_shared($term_id)
{
}
/**
 * Generates a permalink for a taxonomy term archive.
 *
 * @since 2.5.0
 *
 * @global WP_Rewrite $wp_rewrite WordPress rewrite component.
 *
 * @param WP_Term|int|string $term     The term object, ID, or slug whose link will be retrieved.
 * @param string             $taxonomy Optional. Taxonomy. Default empty.
 * @return string|WP_Error URL of the taxonomy term archive on success, WP_Error if term does not exist.
 */
function get_term_link($term, $taxonomy = '')
{
}
/**
 * Displays the taxonomies of a post with available options.
 *
 * This function can be used within the loop to display the taxonomies for a
 * post without specifying the Post ID. You can also use it outside the Loop to
 * display the taxonomies for a specific post.
 *
 * @since 2.5.0
 *
 * @param array $args {
 *     Arguments about which post to use and how to format the output. Shares all of the arguments
 *     supported by get_the_taxonomies(), in addition to the following.
 *
 *     @type int|WP_Post $post   Post ID or object to get taxonomies of. Default current post.
 *     @type string      $before Displays before the taxonomies. Default empty string.
 *     @type string      $sep    Separates each taxonomy. Default is a space.
 *     @type string      $after  Displays after the taxonomies. Default empty string.
 * }
 */
function the_taxonomies($args = array())
{
}
/**
 * Retrieves all taxonomies associated with a post.
 *
 * This function can be used within the loop. It will also return an array of
 * the taxonomies with links to the taxonomy and name.
 *
 * @since 2.5.0
 *
 * @param int|WP_Post $post Optional. Post ID or WP_Post object. Default is global $post.
 * @param array       $args {
 *           Optional. Arguments about how to format the list of taxonomies. Default empty array.
 *
 *     @type string $template      Template for displaying a taxonomy label and list of terms.
 *                                 Default is "Label: Terms."
 *     @type string $term_template Template for displaying a single term in the list. Default is the term name
 *                                 linked to its archive.
 * }
 * @return string[] List of taxonomies.
 */
function get_the_taxonomies($post = 0, $args = array())
{
}
/**
 * Retrieves all taxonomy names for the given post.
 *
 * @since 2.5.0
 *
 * @param int|WP_Post $post Optional. Post ID or WP_Post object. Default is global $post.
 * @return string[] An array of all taxonomy names for the given post.
 */
function get_post_taxonomies($post = 0)
{
}
/**
 * Determines if the given object is associated with any of the given terms.
 *
 * The given terms are checked against the object's terms' term_ids, names and slugs.
 * Terms given as integers will only be checked against the object's terms' term_ids.
 * If no terms are given, determines if object is associated with any terms in the given taxonomy.
 *
 * @since 2.7.0
 *
 * @param int                       $object_id ID of the object (post ID, link ID, ...).
 * @param string                    $taxonomy  Single taxonomy name.
 * @param int|string|int[]|string[] $terms     Optional. Term ID, name, slug, or array of such
 *                                             to check against. Default null.
 * @return bool|WP_Error WP_Error on input error.
 */
function is_object_in_term($object_id, $taxonomy, $terms = \null)
{
}
/**
 * Determines if the given object type is associated with the given taxonomy.
 *
 * @since 3.0.0
 *
 * @param string $object_type Object type string.
 * @param string $taxonomy    Single taxonomy name.
 * @return bool True if object is associated with the taxonomy, otherwise false.
 */
function is_object_in_taxonomy($object_type, $taxonomy)
{
}
/**
 * Gets an array of ancestor IDs for a given object.
 *
 * @since 3.1.0
 * @since 4.1.0 Introduced the `$resource_type` argument.
 *
 * @param int    $object_id     Optional. The ID of the object. Default 0.
 * @param string $object_type   Optional. The type of object for which we'll be retrieving
 *                              ancestors. Accepts a post type or a taxonomy name. Default empty.
 * @param string $resource_type Optional. Type of resource $object_type is. Accepts 'post_type'
 *                              or 'taxonomy'. Default empty.
 * @return int[] An array of IDs of ancestors from lowest to highest in the hierarchy.
 */
function get_ancestors($object_id = 0, $object_type = '', $resource_type = '')
{
}
/**
 * Returns the term's parent's term ID.
 *
 * @since 3.1.0
 *
 * @param int    $term_id  Term ID.
 * @param string $taxonomy Taxonomy name.
 * @return int|false Parent term ID on success, false on failure.
 */
function wp_get_term_taxonomy_parent_id($term_id, $taxonomy)
{
}
/**
 * Checks the given subset of the term hierarchy for hierarchy loops.
 * Prevents loops from forming and breaks those that it finds.
 *
 * Attached to the {@see 'wp_update_term_parent'} filter.
 *
 * @since 3.1.0
 *
 * @param int    $parent_term `term_id` of the parent for the term we're checking.
 * @param int    $term_id     The term we're checking.
 * @param string $taxonomy    The taxonomy of the term we're checking.
 * @return int The new parent for the term.
 */
function wp_check_term_hierarchy_for_loops($parent_term, $term_id, $taxonomy)
{
}
/**
 * Determines whether a taxonomy is considered "viewable".
 *
 * @since 5.1.0
 *
 * @param string|WP_Taxonomy $taxonomy Taxonomy name or object.
 * @return bool Whether the taxonomy should be considered viewable.
 */
function is_taxonomy_viewable($taxonomy)
{
}
/**
 * Determines whether a term is publicly viewable.
 *
 * A term is considered publicly viewable if its taxonomy is viewable.
 *
 * @since 6.1.0
 *
 * @param int|WP_Term $term Term ID or term object.
 * @return bool Whether the term is publicly viewable.
 */
function is_term_publicly_viewable($term)
{
}
/**
 * Sets the last changed time for the 'terms' cache group.
 *
 * @since 5.0.0
 */
function wp_cache_set_terms_last_changed()
{
}
/**
 * Aborts calls to term meta if it is not supported.
 *
 * @since 5.0.0
 *
 * @param mixed $check Skip-value for whether to proceed term meta function execution.
 * @return mixed Original value of $check, or false if term meta is not supported.
 */
function wp_check_term_meta_support_prefilter($check)
{
}