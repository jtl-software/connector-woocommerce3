<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @author    Daniel Hoffmann <daniel.hoffmann@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

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
            $reflect = new ReflectionClass($this);
            $shortName = $reflect->getShortName();
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
    public function pull(QueryFilter $query)
    {
        $action = new Action();
        $action->setHandled(true);

        try {
            $result = null;

            if (method_exists($this, 'pullData')) {
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
    public function push(DataModel $data)
    {
        $action = new Action();
        $action->setHandled(true);

        try {
            $result = null;

            if (method_exists($this, 'pushData')) {
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
    public function delete(DataModel $data)
    {
        $action = new Action();
        $action->setHandled(true);

        try {
            $result = null;

            if (method_exists($this, 'deleteData')) {
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
    public function statistic(QueryFilter $query)
    {
        $action = new Action();
        $action->setHandled(true);

        try {
            $statModel = new Statistic();

            if (method_exists($this, 'getStats')) {
                $statModel->setAvailable((int)$this->getStats());
            }

            $statModel->setControllerName(lcfirst($this->controllerName));
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
