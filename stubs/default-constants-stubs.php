<?php

/**
 * Defines constants and global variables that can be overridden, generally in wp-config.php.
 *
 * @package WordPress
 */
/**
 * Defines initial WordPress constants.
 *
 * @see wp_debug_mode()
 *
 * @since 3.0.0
 *
 * @global int    $blog_id    The current site ID.
 * @global string $wp_version The WordPress version string.
 */
function wp_initial_constants()
{
}
/**
 * Defines plugin directory WordPress constants.
 *
 * Defines must-use plugin directory constants, which may be overridden in the sunrise.php drop-in.
 *
 * @since 3.0.0
 */
function wp_plugin_directory_constants()
{
}
/**
 * Defines cookie-related WordPress constants.
 *
 * Defines constants after multisite is loaded.
 *
 * @since 3.0.0
 */
function wp_cookie_constants()
{
}
/**
 * Defines SSL-related WordPress constants.
 *
 * @since 3.0.0
 */
function wp_ssl_constants()
{
}
/**
 * Defines functionality-related WordPress constants.
 *
 * @since 3.0.0
 */
function wp_functionality_constants()
{
}
/**
 * Defines templating-related WordPress constants.
 *
 * @since 3.0.0
 */
function wp_templating_constants()
{
}