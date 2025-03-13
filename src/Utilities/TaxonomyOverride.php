<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Utilities;

class TaxonomyOverride
{
    // phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    /**
     * Unlinks the object from the taxonomy or taxonomies.
     *
     * Will remove all relationships between the object and any terms in
     * a particular taxonomy or taxonomies. Does not remove the term or
     * taxonomy itself.
     *
     * New and improved so that a invalid taxonomy will not throw an error.
     *
     * @since 2.3.0
     *
     * @param int             $object_id  The term object ID that refers to the term.
     * @param string|string[] $taxonomies List of taxonomy names or single taxonomy name.
     *
     * @return void
     */
    public static function wp_delete_object_term_relationships(int $object_id, string|array $taxonomies): void
    {
        if (!\is_array($taxonomies)) {
            $taxonomies = [$taxonomies];
        }

        foreach ($taxonomies as $taxonomy) {
            $term_ids = \wp_get_object_terms($object_id, $taxonomy, ['fields' => 'ids']);

            if ($term_ids instanceof \WP_Error) {
                continue;
            }

            $term_ids = \array_map('intval', $term_ids);
            \wp_remove_object_terms($object_id, $term_ids, $taxonomy);
        }
    }
    // phpcs:enable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
}
