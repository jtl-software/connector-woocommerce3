<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce;

use jtl\Connector\Base\Connector as BaseConnector;
use jtl\Connector\Core\Controller\Controller as CoreController;
use jtl\Connector\Core\Rpc\Method;
use jtl\Connector\Core\Rpc\RequestPacket;
use jtl\Connector\Core\Utilities\RpcMethod;
use jtl\Connector\Result\Action;
use jtl\Connector\WooCommerce\Authentication\TokenLoader;
use jtl\Connector\WooCommerce\Checksum\ChecksumLoader;
use jtl\Connector\WooCommerce\Event\CanHandleEvent;
use jtl\Connector\WooCommerce\Event\HandleDeleteEvent;
use jtl\Connector\WooCommerce\Event\HandlePullEvent;
use jtl\Connector\WooCommerce\Event\HandlePushEvent;
use jtl\Connector\WooCommerce\Event\HandleStatsEvent;
use jtl\Connector\WooCommerce\Mapper\PrimaryKeyMapper;
use jtl\Connector\WooCommerce\Utility\Util;
use jtl\Connector\WooCommerce\Utility\UtilGermanized;

class Connector extends BaseConnector
{
    /**
     * @var Action
     */
    protected $action;
    /**
     * @var CoreController
     */
    protected $controller;

    public function __construct()
    {
        $this->useSuperGlobals = false;
    }

    public function initialize()
    {
        $this->setPrimaryKeyMapper(new PrimaryKeyMapper())
            ->setTokenLoader(new TokenLoader())
            ->setChecksumLoader(new ChecksumLoader());
    }

    public function canHandle()
    {
        $controllerName = RpcMethod::buildController($this->getMethod()->getController());
        $controllerClass = Util::getInstance()->getControllerNamespace($controllerName);
        $controllerClass = UtilGermanized::getInstance()->getController($controllerName, $controllerClass);
        if (class_exists($controllerClass) && method_exists($controllerClass, 'getInstance')) {
            $this->controller = $controllerClass::getInstance();
            $this->action = RpcMethod::buildAction($this->getMethod()->getAction());
            return is_callable([$this->controller, $this->action]);
        }
        $event = new CanHandleEvent($this->getMethod()->getController(), $this->getMethod()->getAction());
        $this->eventDispatcher->dispatch(CanHandleEvent::EVENT_NAME, $event);
        return $event->isCanHandle();
    }

    public function handle(RequestPacket $requestPacket)
    {
        $event = new CanHandleEvent($this->getMethod()->getController(), $this->getMethod()->getAction());
        $this->eventDispatcher->dispatch(CanHandleEvent::EVENT_NAME, $event);
        if ($event->isCanHandle()) {
            return $this->handleCallByPlugin($requestPacket);
        }

        $this->controller->setMethod($this->getMethod());
        if ($this->action === Method::ACTION_PUSH || $this->action === Method::ACTION_DELETE) {
            if (!is_array($requestPacket->getParams())) {
                throw new \Exception("Expecting request array, invalid data given");
            }
            $action = new Action();
            $results = [];
            $entities = $requestPacket->getParams();
            foreach ($entities as $entity) {
                $result = $this->controller->{$this->action}($entity);
                if ($result instanceof Action && $result->getResult() !== null) {
                    $results[] = $result->getResult();
                }
                $action->setHandled(true)
                    ->setResult($results)
                    ->setError($result->getError());
            }
            return $action;
        }
        return $this->controller->{$this->action}($requestPacket->getParams());
    }

    public function getController()
    {
        return $this->controller;
    }

    public function setController(CoreController $controller)
    {
        $this->controller = $controller;
        return $this;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * This method allows main entities to be added by plugins.
     *
     * @param RequestPacket $requestPacket
     *
     * @return Action
     */
    private function handleCallByPlugin(RequestPacket $requestPacket)
    {
        $action = new Action();
        $action->setHandled(true);
        if ($this->getMethod()->getAction() === 'pull') {
            $event = new HandlePullEvent($this->getMethod()->getController(), $requestPacket->getParams());
            $this->eventDispatcher->dispatch(HandlePullEvent::EVENT_NAME, $event);
        } elseif ($this->getMethod()->getAction() === 'statistic') {
            $event = new HandleStatsEvent($this->getMethod()->getController());
            $this->eventDispatcher->dispatch(HandleStatsEvent::EVENT_NAME, $event);
        } elseif ($this->getMethod()->getAction() === 'push') {
            $event = new HandlePushEvent($this->getMethod()->getController(), $requestPacket->getParams());
            $this->eventDispatcher->dispatch(HandlePushEvent::EVENT_NAME, $event);
        } else {
            $event = new HandleDeleteEvent($this->getMethod()->getController(), $requestPacket->getParams());
            $this->eventDispatcher->dispatch(HandleDeleteEvent::EVENT_NAME, $event);
        }
        $action->setResult($event->getResult());
        return $action;
    }

    /**
     * @return Connector
     */
    public static function getInstance()
    {
        return parent::getInstance();
    }
}
