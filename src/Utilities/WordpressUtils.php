<?php

namespace JtlWooCommerceConnector\Utilities;

use jtl\Connector\Core\Utilities\Singleton;

/**
 * Class WordpressUtils
 * @package JtlWooCommerceConnector\Utilities
 */
abstract class WordpressUtils extends Singleton
{
    /**
     * @param $postId
     * @param $metaKey
     * @param $metaValue
     * @param string $prevValue
     */
    public function updatePostMeta($postId, $metaKey, $metaValue, string $prevValue = '')
    {
        return \update_post_meta($postId, $metaKey, $metaValue, $prevValue);
    }

    /**
     * @param $postId
     * @param string $metaKey
     * @param bool $single
     */
    public function getPostMeta($postId, string $metaKey = '', bool $single = false)
    {
        return \get_post_meta($postId, $metaKey, $single);
    }

    /**
     * @param $postId
     * @param $metaKey
     * @param string $metaValue
     * @return bool
     */
    public function deletePostMeta($postId, $metaKey, string $metaValue = ''): bool
    {
        return \delete_post_meta($postId, $metaKey, $metaValue);
    }
}
