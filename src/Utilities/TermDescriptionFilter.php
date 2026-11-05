<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Utilities;

/**
 * Connector requests run without a logged-in user, so WordPress filters term descriptions with its strict
 * `pre_term_description` allowlist (no <p>, <img>, <table>). Descriptions from the Wawi are HTML, therefore the
 * allowlist of post content is used instead. Scripts and event handlers are still removed.
 */
class TermDescriptionFilter
{
    /**
     * @return void
     */
    public static function register(): void
    {
        \remove_filter('pre_term_description', 'wp_filter_kses');
        \add_filter('pre_term_description', 'wp_filter_post_kses');
    }
}
