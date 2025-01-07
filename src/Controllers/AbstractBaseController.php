<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Controllers;

use Jtl\Connector\Core\Model\AbstractModel;
use Jtl\Connector\Core\Model\QueryFilter;
use JtlWooCommerceConnector\Integrations\IntegrationsManager;
use JtlWooCommerceConnector\Integrations\Plugins\PluginInterface;
use JtlWooCommerceConnector\Integrations\Plugins\PluginsManager;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\Wpml;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\Util;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionClass;

abstract class AbstractBaseController extends AbstractController implements LoggerAwareInterface
{
    protected string $controllerName;

    protected LoggerInterface $logger;

    protected PluginsManager $pluginsManager;

    protected Wpml $wpml;

    /**
     * @param Db   $db
     * @param Util $util
     * @throws \Exception
     */
    public function __construct(Db $db, Util $util)
    {
        parent::__construct($db, $util);

        $integrationsManager  = new IntegrationsManager($this->db);
        $this->pluginsManager = $integrationsManager->getPluginsManager();

        /** @var Wpml $wpml */
        $wpml       = $this->pluginsManager->get(Wpml::class);
        $this->wpml = $wpml;

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

    /**
     * @return PluginsManager
     */
    protected function getPluginsManager(): PluginsManager
    {
        return $this->pluginsManager;
    }

    /**
     * @param QueryFilter $query
     * @return int
     */
    public function statistic(QueryFilter $query): int
    {
        if (\method_exists($this, 'getStats')) {
            return (int)$this->getStats();
        }

        return 0;
    }

    /**
     * @param int                          $postId
     * @param string                       $metaKey
     * @param string|array<string, string> $value
     * @return bool|int
     */
    protected function updatePostMeta(int $postId, string $metaKey, string|array $value): bool|int
    {
        return \update_post_meta($postId, $metaKey, $value, \get_post_meta($postId, $metaKey, true));
    }

    /**
     * @param int    $postId
     * @param string $metaKey
     * @param string $value
     * @return bool|int
     */
    protected function addPostMeta(int $postId, string $metaKey, string $value): bool|int
    {
        return \add_post_meta($postId, $metaKey, $value, true);
    }

    /**
     * @param int      $objectId
     * @param string[] $terms
     * @param string   $taxonomy
     * @param bool     $append
     * @return array<int, int|string>|\WP_Error
     */
    protected function wpSetObjectTerms(
        int $objectId,
        array $terms,
        string $taxonomy,
        bool $append = false
    ): array|\WP_Error {
        return \wp_set_object_terms($objectId, $terms, $taxonomy, $append);
    }

    /**
     * @param int                 $objectId
     * @param int|string|string[] $terms
     * @param string              $taxonomy
     * @return bool|\WP_Error
     */
    protected function wpRemoveObjectTerms(int $objectId, array|int|string $terms, string $taxonomy): bool|\WP_Error
    {
        return \wp_remove_object_terms($objectId, $terms, $taxonomy);
    }

    /**
     * @param string|AbstractModel $taxonomyName
     * @return string
     */
    protected function wcSanitizeTaxonomyName(string|AbstractModel $taxonomyName): string
    {
        if ($taxonomyName instanceof AbstractModel && \method_exists($taxonomyName, 'getName')) {
            $taxonomyName = $taxonomyName->getName();
        }
        return \wc_sanitize_taxonomy_name($taxonomyName);
    }

    /**
     * @param string     $field
     * @param int|string $value
     * @param string     $taxonomy
     * @param string     $output
     * @param string     $filter
     * @return \WP_Term|false
     */
    protected function getTermBy(
        string $field,
        int|string $value,
        string $taxonomy = '',
        string $output = \OBJECT,
        string $filter = 'raw'
    ): \WP_Term|false {
        /** @var \WP_Term|false $getTermBy */
        $getTermBy = \get_term_by($field, $value, $taxonomy, $output = \OBJECT, $filter = 'raw');
        return $getTermBy;
    }

    /**
     * @param string $variable
     * @return string[]|string
     */
    protected function wcClean(string $variable): array|string
    {
        return \wc_clean($variable);
    }

    /**
     * @param int    $postId
     * @param string $metaKey
     * @return bool
     */
    protected function deletePostMeta(int $postId, string $metaKey): bool
    {
        return \delete_post_meta($postId, $metaKey);
    }

    /**
     * @param array<string, int|string> $postData
     * @return int|\WP_Error
     * @throws \Exception
     */
    protected function wpUpdatePost(array $postData): \WP_Error|int
    {
        return \wp_update_post($postData);
    }
}
