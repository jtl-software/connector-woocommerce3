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
     * @param $prevValue
     * @return bool|int
     */
    public function updatePostMeta($postId, $metaKey, $metaValue, $prevValue = '')
    {
        return update_post_meta($postId, $metaKey, $metaValue, $prevValue);
    }

    /**
     * @param $postId
     * @param $metaKey
     * @param $single
     * @return mixed
     */
    public function getPostMeta($postId, $metaKey = '', $single = false)
    {
        return get_post_meta($postId, $metaKey, $single);
    }

    /**
     * @param $postId
     * @param $metaKey
     * @param string $metaValue
     * @return bool
     */
    public function deletePostMeta($postId, $metaKey, $metaValue = '')
    {
        return delete_post_meta($postId, $metaKey, $metaValue);
    }
}
