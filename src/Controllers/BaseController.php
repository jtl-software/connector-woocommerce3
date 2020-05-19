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
use jtl\Connector\Model\Statistic;
use jtl\Connector\Result\Action;
use JtlWooCommerceConnector\Integrations\IntegrationsManager;
use JtlWooCommerceConnector\Integrations\Plugins\PluginInterface;
use JtlWooCommerceConnector\Integrations\Plugins\PluginsManager;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\Wpml;
use JtlWooCommerceConnector\Traits\BaseControllerTrait;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
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
     * @var PluginsManager
     */
    protected $pluginsManager;

    /**
     * @var Wpml
     */
    protected $wpml;

    /**
     * BaseController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->database = Db::getInstance();

        $integrationsManager = new IntegrationsManager($this->database);
        $this->pluginsManager = $integrationsManager->getPluginsManager();
        $this->wpml = $this->pluginsManager->get(Wpml::class);

        try {
            $reflect = new ReflectionClass($this);
            $shortName = $reflect->getShortName();
            $this->controllerName = $shortName;
        } catch (\ReflectionException $exception) {
            //
        }
    }

    /**
     * @return PluginsManager
     */
    protected function getPluginsManager(): PluginsManager
    {
        return $this->pluginsManager;
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
}
