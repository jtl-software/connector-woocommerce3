<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @author    Daniel Hoffmann <daniel.hoffmann@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller;

use jtl\Connector\Core\Controller\Controller;
use jtl\Connector\Core\Model\DataModel;
use jtl\Connector\Core\Model\QueryFilter;
use jtl\Connector\Model\Statistic;
use jtl\Connector\Result\Action;
use jtl\Connector\WooCommerce\Mapper\BaseMapper;
use jtl\Connector\WooCommerce\Traits\BaseControllerTrait;
use jtl\Connector\WooCommerce\Utility\Db;
use jtl\Connector\WooCommerce\Utility\WooCommerceDatabase;

abstract class BaseController extends Controller
{
    use BaseControllerTrait;

    /**
     * @var BaseMapper
     */
    protected $mapper;
    /**
     * @var WooCommerceDatabase
     */
    protected $database;
    /**
     * @var string
     */
    protected $controllerName;

    public function __construct()
    {
        $this->setDatabase();

        $reflect = new \ReflectionClass($this);
        $shortName = $reflect->getShortName();
        $this->controllerName = $shortName;

        $mapperClass = str_replace('Controller', 'Mapper', $reflect->getNamespaceName()) . '\\' . $shortName;

        if (class_exists($mapperClass)) {
            $this->mapper = new $mapperClass();
        }
    }

    /**
     * Extracted method which can be overwritten by tests to use another database or manipulate the WooCommerce helper.
     *
     * @param WooCommerceDatabase $database The implementation of the WooCommerceDatabase.
     */
    protected function setDatabase(WooCommerceDatabase $database = null)
    {
        if (is_null($database)) {
            $this->database = Db::getInstance();
        }
    }

    /**
     * Method called on a pull request.
     *
     * @param QueryFilter $query Filter data like the limit.
     *
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
     *
     * @return Action The action which will be handled by the core.
     */
    public function push(DataModel $data)
    {
        $action = new Action();
        $action->setHandled(true);

        try {
            $result = null;

            if (method_exists($this, 'pushData')) {
                $result = $this->pushData($data, null);
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
     *
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
     *
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
}
