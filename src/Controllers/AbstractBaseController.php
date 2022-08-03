<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @author    Daniel Hoffmann <daniel.hoffmann@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers;

use jtl\Connector\Core\Model\QueryFilter;
use Jtl\Connector\Core\Model\Statistic;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\Util;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionClass;

abstract class AbstractBaseController extends AbstractController implements LoggerAwareInterface
{
    /**
     * @var string
     */
    protected $controllerName;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * BaseController constructor.
     */
    public function __construct(Db $db, Util $util)
    {
        parent::__construct($db, $util);

        $reflect = new ReflectionClass($this);
        $shortName = $reflect->getShortName();
        $this->controllerName = $shortName;
        $this->logger = new NullLogger();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param QueryFilter $query
     * @return mixed
     */
    public function statistic(QueryFilter $query): int
    {
        if (method_exists($this, 'getStats')) {
            return (int)$this->getStats();
        }

        return 0;
    }

    /**
     * @param $postId
     * @param $metaKey
     * @param $value
     * @return bool|int|void
     */
    protected function updatePostMeta($postId, $metaKey, $value)
    {
        return \update_post_meta($postId, $metaKey, $value, \get_post_meta($postId, $metaKey, true));
    }

    /**
     * @param $postId
     * @param $metaKey
     * @param $value
     * @return false|int|void
     */
    protected function addPostMeta($postId, $metaKey, $value)
    {
        return add_post_meta($postId, $metaKey, $value, true);
    }

    /**
     * @param $objectId
     * @param $terms
     * @param $taxonomy
     * @param $append
     * @return array|bool|int|int[]|string|string[]|void|\WP_Error|null
     */
    protected function wpSetObjectTerms($objectId, $terms, $taxonomy, $append = false)
    {
        return wp_set_object_terms($objectId, $terms, $taxonomy, $append);
    }

    /**
     * @param $objectId
     * @param $terms
     * @param $taxonomy
     * @return bool|int|string|string[]|void|\WP_Error|null
     */
    protected function wpRemoveObjectTerms($objectId, $terms, $taxonomy)
    {
        return wp_remove_object_terms($objectId, $terms, $taxonomy);
    }

    /**
     * @param $taxonomyName
     * @return string|void
     */
    protected function wcSanitizeTaxonomyName($taxonomyName)
    {
        return \wc_sanitize_taxonomy_name($taxonomyName);
    }

    /**
     * @param $field
     * @param $value
     * @param $taxonomy
     * @param $output
     * @param $filter
     * @return array|false|void|\WP_Error|\WP_Term|null
     */
    protected function getTermBy($field, $value, $taxonomy = '', $output = OBJECT, $filter = 'raw')
    {
        return get_term_by($field, $value, $taxonomy = '', $output = OBJECT, $filter = 'raw');
    }

    /**
     * @param $variable
     * @return array|string|void
     */
    protected function wcClean($variable)
    {
        return \wc_clean($variable);
    }

    /**
     * @param $postId
     * @param $metaKey
     * @return bool|void
     */
    protected function deletePostMeta($postId, $metaKey)
    {
        return \delete_post_meta($postId, $metaKey);
    }

    /**
     * @param $postData
     * @return int|void|\WP_Error
     */
    protected function wpUpdatePost($postData)
    {
        return \wp_update_post($postData);
    }


}
