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
     * @return bool|int
     */
    protected function updatePostMeta($postId, $metaKey, $value): bool|int
    {
        return \update_post_meta($postId, $metaKey, $value, \get_post_meta($postId, $metaKey, true));
    }

    /**
     * @param $postId
     * @param $metaKey
     * @param $value
     * @return bool|int
     */
    protected function addPostMeta($postId, $metaKey, $value): bool|int
    {
        return \add_post_meta($postId, $metaKey, $value, true);
    }

    /**
     * @param $objectId
     * @param $terms
     * @param $taxonomy
     * @param bool $append
     * @return array|\WP_Error|int|bool|string|null
     */
    protected function wpSetObjectTerms(
        $objectId,
        $terms,
        $taxonomy,
        bool $append = false
    ): array|\WP_Error|int|bool|string|null {
        return \wp_set_object_terms($objectId, $terms, $taxonomy, $append);
    }

    /**
     * @param $objectId
     * @param $terms
     * @param $taxonomy
     * @return array|\WP_Error|bool|int|string|null
     */
    protected function wpRemoveObjectTerms($objectId, $terms, $taxonomy): array|\WP_Error|bool|int|string|null
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
     * @return \WP_Term|\WP_Error|bool|array|null
     */
    protected function getTermBy(
        $field,
        $value,
        string $taxonomy = '',
        string $output = \OBJECT,
        string $filter = 'raw'
    ): \WP_Term|\WP_Error|bool|array|null {
        return \get_term_by($field, $value, $taxonomy, $output = \OBJECT, $filter = 'raw');
    }

    /**
     * @param $variable
     * @return array|string
     */
    protected function wcClean($variable): array|string
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
     * @return int|\WP_Error
     * @throws \Exception
     */
    protected function wpUpdatePost($postData): \WP_Error|int
    {
        return \wp_update_post($postData);
    }
}
