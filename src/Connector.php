<?php

/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector;

use jtl\Connector\Base\Connector as BaseConnector;
use jtl\Connector\Core\Controller\Controller as CoreController;
use jtl\Connector\Core\Rpc\Method;
use jtl\Connector\Core\Rpc\RequestPacket;
use jtl\Connector\Core\Utilities\RpcMethod;
use jtl\Connector\Core\Utilities\Singleton;
use jtl\Connector\Event\Rpc\RpcBeforeEvent;
use jtl\Connector\Result\Action;
use JtlWooCommerceConnector\Authentication\TokenLoader;
use JtlWooCommerceConnector\Checksum\ChecksumLoader;
use JtlWooCommerceConnector\Event\CanHandleEvent;
use JtlWooCommerceConnector\Event\HandleDeleteEvent;
use JtlWooCommerceConnector\Event\HandlePullEvent;
use JtlWooCommerceConnector\Event\HandlePushEvent;
use JtlWooCommerceConnector\Event\HandleStatsEvent;
use JtlWooCommerceConnector\Mapper\PrimaryKeyMapper;
use JtlWooCommerceConnector\Utilities\B2BMarket;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;

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

    /**
     * @return void
     */
    public function initialize(): void
    {
        $this->setPrimaryKeyMapper(new PrimaryKeyMapper())
             ->setTokenLoader(new TokenLoader())
             ->setChecksumLoader(new ChecksumLoader());


        $this->getEventDispatcher()
            ->addListener(RpcBeforeEvent::EVENT_NAME, static function (RpcBeforeEvent $event) {
                if ($event->getController() === 'connector' && $event->getAction() === 'auth') {
                    \JtlConnectorAdmin::loadFeaturesJson();
                }
            });
    }

    /**
     * @return bool
     */
    public function canHandle(): bool
    {
        $controllerName  = RpcMethod::buildController($this->getMethod()->getController());
        $controllerClass = Util::getInstance()->getControllerNamespace($controllerName);

        if (\class_exists($controllerClass) && \method_exists($controllerClass, 'getInstance')) {
            $this->controller = $controllerClass::getInstance();
            $this->action     = RpcMethod::buildAction($this->getMethod()->getAction());

            return \is_callable([ $this->controller, $this->action ]);
        }

        $event = new CanHandleEvent($this->getMethod()->getController(), $this->getMethod()->getAction());
        $this->eventDispatcher->dispatch(CanHandleEvent::EVENT_NAME, $event);

        return $event->isCanHandle();
    }

    /**
     * @param RequestPacket $requestPacket
     * @return Action
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function handle(RequestPacket $requestPacket): Action
    {
        $event = new CanHandleEvent($this->getMethod()->getController(), $this->getMethod()->getAction());

        $this->eventDispatcher->dispatch(CanHandleEvent::EVENT_NAME, $event);

        if ($event->isCanHandle()) {
            return $this->handleCallByPlugin($requestPacket);
        }

        $this->controller->setMethod($this->getMethod());

        if ($this->action === Method::ACTION_PUSH || $this->action === Method::ACTION_DELETE) {
            if (! \is_array($requestPacket->getParams())) {
                throw new \Exception("Expecting request array, invalid data given");
            }

            $this->disableGermanMarketActions();

            $results  = [];
            $action   = new Action();
            $entities = $requestPacket->getParams();

            foreach ($entities as $entity) {
                $result = $this->controller->{$this->action}($entity);

                if ($result instanceof Action && $result->getResult() !== null) {
                    $results[] = $result->getResult();
                }

                $action
                    ->setHandled(true)
                    ->setResult($results)
                    ->setError($result->getError());
            }


            if (\in_array($controllerName = $this->getMethod()->getController(), ['product', 'category'])) {
                (new B2BMarket())->handleCustomerGroupsBlacklists($controllerName, ...$entities);
            }

            return $action;
        }

        return $this->controller->{$this->action}($requestPacket->getParams());
    }

    /**
     * @return void
     */
    protected function disableGermanMarketActions(): void
    {
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_GERMAN_MARKET)) {
            if ($this->getMethod()->getController() === 'product') {
                \remove_action('save_post', ['WGM_Product', 'save_product_digital_type']);
            }
        }
    }

    /**
     * @return CoreController
     */
    public function getController(): CoreController
    {
        return $this->controller;
    }

    /**
     * @param CoreController $controller
     * @return Connector
     */
    public function setController(CoreController $controller): Connector
    {
        $this->controller = $controller;

        return $this;
    }

    /**
     * @return Action
     */
    public function getAction(): Action
    {
        return $this->action;
    }

    /**
     * @param $action
     * @return Connector
     */
    public function setAction($action): Connector
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
    private function handleCallByPlugin(RequestPacket $requestPacket): Action
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
     * @return Singleton
     */
    public static function getInstance(): Singleton
    {
        return parent::getInstance();
    }
}
