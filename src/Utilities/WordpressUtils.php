<?php

namespace JtlWooCommerceConnector\Utilities;

/**
 * Class WordpressUtils
 * @package JtlWooCommerceConnector\Utilities
 */
abstract class WordpressUtils
{
    /**
     * @var Db
     */
    protected Db $db;

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    /**
     * @param $postId
     * @param $metaKey
     * @param $metaValue
     * @param string $prevValue
     * @return bool|int
     */
    public function updatePostMeta($postId, $metaKey, $metaValue, string $prevValue = ''): bool|int
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
