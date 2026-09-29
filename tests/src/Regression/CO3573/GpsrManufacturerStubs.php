<?php

// phpcs:ignoreFile

declare(strict_types=1);

/**
 * Lightweight WordPress term/meta stubs for the CO-3573 regression test. The stubs record their
 * calls in the $GLOBALS key __co3573_calls so the test can assert how the manufacturer term meta
 * (formatted_address / formatted_eu_address) is written or deleted. get_term_by returns the object
 * stored in $GLOBALS['__co3573_existing_term'] so no new term is inserted.
 */

if (!class_exists('WP_Error')) {
    class WP_Error
    {
    }
}

if (!function_exists('get_term_by')) {
    function get_term_by($field, $value, $taxonomy)
    {
        $GLOBALS['__co3573_calls']['get_term_by'][] = [$field, $value, $taxonomy];

        return $GLOBALS['__co3573_existing_term'] ?? false;
    }
}

if (!function_exists('wp_insert_term')) {
    function wp_insert_term($term, $taxonomy, $args = [])
    {
        return ['term_id' => 999];
    }
}

if (!function_exists('update_term_meta')) {
    function update_term_meta($term_id, $meta_key, $meta_value, $prev_value = '')
    {
        $GLOBALS['__co3573_calls']['update_term_meta'][] = [$term_id, $meta_key, $meta_value];

        return true;
    }
}

if (!function_exists('delete_term_meta')) {
    function delete_term_meta($term_id, $meta_key, $meta_value = '')
    {
        $GLOBALS['__co3573_calls']['delete_term_meta'][] = [$term_id, $meta_key];

        return true;
    }
}

if (!function_exists('wp_set_object_terms')) {
    function wp_set_object_terms($object_id, $terms, $taxonomy, $append = false)
    {
        $GLOBALS['__co3573_calls']['wp_set_object_terms'][] = [$object_id, $terms, $taxonomy];

        return [];
    }
}

if (!function_exists('update_post_meta')) {
    function update_post_meta($post_id, $meta_key, $meta_value, $prev_value = '')
    {
        return true;
    }
}

if (!function_exists('wp_get_object_terms')) {
    function wp_get_object_terms($object_ids, $taxonomies, $args = [])
    {
        return new \WP_Error();
    }
}

if (!function_exists('wp_remove_object_terms')) {
    function wp_remove_object_terms($object_id, $terms, $taxonomy)
    {
        return true;
    }
}
