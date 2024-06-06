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
     * @param string $postId
     * @param string $metaKey
     * @param string|string[] $metaValue
     * @param string $prevValue
     * @return bool|int
     */
    public function updatePostMeta(
        string $postId,
        string $metaKey,
        array|string $metaValue,
        string $prevValue = ''
    ): bool|int {
        return \update_post_meta((int)$postId, $metaKey, $metaValue, $prevValue);
    }

    /**
     * @param string|int $postId
     * @param string $metaKey
     * @param bool $single
     * @return mixed
     */
    public function getPostMeta(string|int $postId, string $metaKey = '', bool $single = false): mixed //TODO:check das
    {
        return \get_post_meta((int)$postId, $metaKey, $single);
    }

    /**
     * @param string $postId
     * @param string $metaKey
     * @param string $metaValue
     * @return bool
     */
    public function deletePostMeta(string $postId, string $metaKey, string $metaValue = ''): bool
    {
        return \delete_post_meta((int)$postId, $metaKey, $metaValue);
    }
}
