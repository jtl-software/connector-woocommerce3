<?php

namespace JtlWooCommerceConnector\Controllers;

use Jtl\Connector\Core\Model\AbstractModel;
use Jtl\Connector\Core\Model\QueryFilter;
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

        $reflect              = new ReflectionClass($this);
        $shortName            = $reflect->getShortName();
        $this->controllerName = $shortName;
        $this->logger         = new NullLogger();
    }

    /**
     * @param LoggerInterface $logger
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function statistic(QueryFilter $query): int
    {
        if (\method_exists($this, 'getStats')) {
            return (int)$this->getStats();
        }

        return 0;
    }

    /**
     * @param $postId
     * @param $metaKey
     * @param $value
     */
    protected function updatePostMeta($postId, $metaKey, $value)
    {
        return \update_post_meta($postId, $metaKey, $value, \get_post_meta($postId, $metaKey, true));
    }

    /**
     * @param $postId
     * @param $metaKey
     * @param $value
     */
    protected function addPostMeta($postId, $metaKey, $value)
    {
        return \add_post_meta($postId, $metaKey, $value, true);
    }

    /**
     * @param $objectId
     * @param $terms
     * @param $taxonomy
     * @param bool $append
     */
    protected function wpSetObjectTerms(
        $objectId,
        $terms,
        $taxonomy,
        bool $append = false
    ) {
        return \wp_set_object_terms($objectId, $terms, $taxonomy, $append);
    }

    /**
     * @param $objectId
     * @param $terms
     * @param $taxonomy
     */
    protected function wpRemoveObjectTerms($objectId, $terms, $taxonomy)
    {
        return \wp_remove_object_terms($objectId, $terms, $taxonomy);
    }

    /**
     * @param $taxonomyName
     * @return string
     */
    protected function wcSanitizeTaxonomyName($taxonomyName): string
    {
        if ($taxonomyName instanceof AbstractModel && \method_exists($taxonomyName, 'getName')) {
            $taxonomyName = $taxonomyName->getName();
        }
        return \wc_sanitize_taxonomy_name($taxonomyName);
    }

    /**
     * @param $field
     * @param $value
     * @param string $taxonomy
     * @param string $output
     * @param string $filter
     */
    protected function getTermBy(
        $field,
        $value,
        string $taxonomy = '',
        string $output = \OBJECT,
        string $filter = 'raw'
    ) {
        return \get_term_by($field, $value, $taxonomy, $output = \OBJECT, $filter = 'raw');
    }

    /**
     * @param $variable
     */
    protected function wcClean($variable)
    {
        return \wc_clean($variable);
    }

    /**
     * @param $postId
     * @param $metaKey
     * @return bool
     */
    protected function deletePostMeta($postId, $metaKey): bool
    {
        return \delete_post_meta($postId, $metaKey);
    }

    /**
     * @param $postData
     * @throws \Exception
     */
    protected function wpUpdatePost($postData)
    {
        return \wp_update_post($postData);
    }
}
