<?php

namespace JtlWooCommerceConnector\Controllers;

use jtl\Connector\Core\Controller\Controller;
use jtl\Connector\Core\Model\DataModel;
use jtl\Connector\Core\Model\QueryFilter;
use jtl\Connector\Model\ProductAttrI18n as ProductAttrI18nModel;
use jtl\Connector\Model\Statistic;
use jtl\Connector\Result\Action;
use JtlWooCommerceConnector\Traits\BaseControllerTrait;
use JtlWooCommerceConnector\Utilities\Db;
use ReflectionClass;

abstract class BaseController extends Controller
{
    use BaseControllerTrait;

    /**
     * @var Db
     */
    protected $database;
    /**
     * @var string
     */
    protected $controllerName;

    /**
     * BaseController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->database = Db::getInstance();
        try {
            $reflect              = new ReflectionClass($this);
            $shortName            = $reflect->getShortName();
            $this->controllerName = $shortName;
        } catch (\ReflectionException $exception) {
            //
        }
    }

    /**
     * Method called on a pull request.
     *
     * @param QueryFilter $query Filter data like the limit.
     * @return Action The action which is handled by the core.
     */
    public function pull(QueryFilter $query): Action
    {
        $action = new Action();
        $action->setHandled(true);

        try {
            $result = null;

            if (\method_exists($this, 'pullData')) {
                $result = $this->pullData($query->getLimit());
            }

            $action->setResult($result);
        } catch (\Exception $exc) {
            $this->handleException($exc, $action);
        }

        return $action;
    }

    /**
     * Method called on a push request.
     *
     * @param DataModel $data The data of the object which should be saved.
     * @return Action The action which will be handled by the core.
     */
    public function push(DataModel $data): Action
    {
        $action = new Action();
        $action->setHandled(true);

        try {
            $result = null;

            if (\method_exists($this, 'pushData')) {
                $result = $this->pushData($data);
            }

            $action->setResult($result);
        } catch (\Exception $exc) {
            $this->handleException($exc, $action);
        }

        return $action;
    }

    /**
     * Method called on a delete request.
     *
     * @param DataModel $data The data of the object which should be deleted.
     * @return Action The action which will be handled by the core.
     */
    public function delete(DataModel $data): Action
    {
        $action = new Action();
        $action->setHandled(true);

        try {
            $result = null;

            if (\method_exists($this, 'deleteData')) {
                $action->setResult($this->deleteData($data));
            }
        } catch (\Exception $exc) {
            $this->handleException($exc, $action);
        }

        return $action;
    }

    /**
     * Method called on a statistic request.
     *
     * @param QueryFilter $query Filter data like the limit.
     * @return Action The action which will be handled by the core.
     */
    public function statistic(QueryFilter $query): Action
    {
        $action = new Action();
        $action->setHandled(true);

        try {
            $statModel = new Statistic();

            if (\method_exists($this, 'getStats')) {
                $statModel->setAvailable((int)$this->getStats());
            }

            $statModel->setControllerName(\lcfirst($this->controllerName));
            $action->setResult($statModel);
        } catch (\Exception $exc) {
            $this->handleException($exc, $action);
        }

        return $action;
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
        if ($taxonomyName instanceof DataModel && \method_exists($taxonomyName, 'getName')) {
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
