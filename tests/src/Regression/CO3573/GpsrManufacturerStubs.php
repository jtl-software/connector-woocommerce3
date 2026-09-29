<?php

/**
 * Lightweight WordPress term/meta stubs for the CO-3573 regression test. The stubs record their
 * calls in the $GLOBALS key __co3573_calls so the test can assert how the manufacturer term meta
 * (formatted_address / formatted_eu_address) is written or deleted. get_term_by returns the object
 * stored in $GLOBALS['__co3573_existing_term'] so no new term is inserted.
 */

declare(strict_types=1);

if (!class_exists('WP_Error')) {
    /**
     * Minimal WP_Error stand-in used to signal "no linked terms" to TaxonomyOverride. The name and
     * global namespace are dictated by WordPress, so the naming/namespace sniffs are disabled here.
     *
     * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
     */
    class WP_Error
    {
    }
    // phpcs:enable PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
}

if (!function_exists('get_term_by')) {
    /**
     * @param string     $field
     * @param string|int $value
     * @param string     $taxonomy
     * @return object|false
     */
    function get_term_by(string $field, string|int $value, string $taxonomy): object|false
    {
        $GLOBALS['__co3573_calls']['get_term_by'][] = [$field, $value, $taxonomy];

        return $GLOBALS['__co3573_existing_term'] ?? false;
    }
}

if (!function_exists('wp_insert_term')) {
    /**
     * @param string               $term
     * @param string               $taxonomy
     * @param array<string, mixed> $args
     * @return array<string, int>
     */
    function wp_insert_term(string $term, string $taxonomy, array $args = []): array
    {
        return ['term_id' => 999];
    }
}

if (!function_exists('update_term_meta')) {
    /**
     * @param int    $termId
     * @param string $metaKey
     * @param mixed  $metaValue
     * @param mixed  $prevValue
     * @return bool
     */
    function update_term_meta(int $termId, string $metaKey, mixed $metaValue, mixed $prevValue = ''): bool
    {
        $GLOBALS['__co3573_calls']['update_term_meta'][] = [$termId, $metaKey, $metaValue];

        return true;
    }
}

if (!function_exists('delete_term_meta')) {
    /**
     * @param int    $termId
     * @param string $metaKey
     * @param mixed  $metaValue
     * @return bool
     */
    function delete_term_meta(int $termId, string $metaKey, mixed $metaValue = ''): bool
    {
        $GLOBALS['__co3573_calls']['delete_term_meta'][] = [$termId, $metaKey];

        return true;
    }
}

if (!function_exists('wp_set_object_terms')) {
    /**
     * @param int              $objectId
     * @param int|int[]|string $terms
     * @param string           $taxonomy
     * @param bool             $append
     * @return array<int, int>
     */
    function wp_set_object_terms(int $objectId, int|array|string $terms, string $taxonomy, bool $append = false): array
    {
        $GLOBALS['__co3573_calls']['wp_set_object_terms'][] = [$objectId, $terms, $taxonomy];

        return [];
    }
}

if (!function_exists('update_post_meta')) {
    /**
     * @param int    $postId
     * @param string $metaKey
     * @param mixed  $metaValue
     * @param mixed  $prevValue
     * @return bool
     */
    function update_post_meta(int $postId, string $metaKey, mixed $metaValue, mixed $prevValue = ''): bool
    {
        return true;
    }
}

if (!function_exists('wp_get_object_terms')) {
    /**
     * @param int|int[]            $objectIds
     * @param string|string[]      $taxonomies
     * @param array<string, mixed> $args
     * @return \WP_Error
     */
    function wp_get_object_terms(int|array $objectIds, string|array $taxonomies, array $args = []): \WP_Error
    {
        return new \WP_Error();
    }
}

if (!function_exists('wp_remove_object_terms')) {
    /**
     * @param int              $objectId
     * @param int|int[]|string $terms
     * @param string           $taxonomy
     * @return bool
     */
    function wp_remove_object_terms(int $objectId, int|array|string $terms, string $taxonomy): bool
    {
        return true;
    }
}
