<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller;

use jtl\Connector\Core\Controller\Controller;
use jtl\Connector\Core\Model\QueryFilter;
use jtl\Connector\Model\ConnectorIdentification;
use jtl\Connector\Model\ConnectorServerInfo;
use jtl\Connector\Result\Action;
use jtl\Connector\WooCommerce\Connector as WooConnector;
use jtl\Connector\WooCommerce\Event\CanHandleEvent;
use jtl\Connector\WooCommerce\Event\HandleStatsEvent;
use jtl\Connector\WooCommerce\Traits\BaseControllerTrait;
use jtl\Connector\WooCommerce\Utility\Category as CategoryUtil;
use jtl\Connector\WooCommerce\Utility\Util;
use jtl\Connector\WooCommerce\Utility\Germanized;

class Connector extends Controller
{
    use BaseControllerTrait;

    public function identify()
    {
        $action = new Action();
        $action->setHandled(true);

        $returnMegaBytes = function ($value) {
            $value = trim($value);
            $unit = strtolower($value[strlen($value) - 1]);
            switch ($unit) {
                case 'g':
                    $value *= 1024;
            }

            return (int)$value;
        };

        $serverInfo = new ConnectorServerInfo();
        $serverInfo->setMemoryLimit($returnMegaBytes(ini_get('memory_limit')))
            ->setExecutionTime((int)ini_get('max_execution_time'))
            ->setPostMaxSize($returnMegaBytes(ini_get('post_max_size')))
            ->setUploadMaxFilesize($returnMegaBytes(ini_get('upload_max_filesize')));

        $identification = new ConnectorIdentification();
        $identification->setPlatformName('WooCommerce')
            ->setPlatformVersion(\WC()->version)
            ->setEndpointVersion(CONNECTOR_VERSION)
            ->setProtocolVersion(Application()->getProtocolVersion())
            ->setServerInfo($serverInfo);

        $action->setResult($identification);

        return $action;
    }

    public function finish()
    {
        $action = new Action();
        $action->setHandled(true);

        try {
            if (\get_option(CategoryUtil::OPTION_CATEGORY_HAS_CHANGED, 'no') === 'yes') {
                CategoryUtil::saveCategoryLevelsAsPreOrder();
                \update_option(CategoryUtil::OPTION_CATEGORY_HAS_CHANGED, 'no');
            }

            Util::getInstance()->countCategories();
            Util::getInstance()->countProductTags();
            Util::getInstance()->syncMasterProducts();
        } catch (\Exception $exc) {
            $this->handleException($exc, $action);
        }

        $action->setResult(true);

        return $action;
    }

    public function statistic(QueryFilter $queryFilter)
    {
        $action = new Action();
        $action->setHandled(true);

        $results = [];

        $mainControllers = [
            'Category',
            'Customer',
            'CustomerOrder',
            'CrossSelling',
            'Image',
            'Product',
            'Payment',
            'Manufacturer',
            'DeliveryNote',
        ];

        foreach ($mainControllers as $mainController) {
            $event = new CanHandleEvent($mainController, 'statistic');
            WooConnector::getInstance()->getEventDispatcher()->dispatch(CanHandleEvent::EVENT_NAME, $event);

            if ($event->isCanHandle()) {
                $event = new HandleStatsEvent($mainController);
                WooConnector::getInstance()->getEventDispatcher()->dispatch(HandleStatsEvent::EVENT_NAME, $event);
                $results[] = $event->getResult();
            } else {
                $className = Util::getInstance()->getControllerNamespace($mainController);
                $className = Germanized::getInstance()->getController($mainController, $className);

                if (class_exists($className)) {
                    try {
                        $controllerObj = new $className();

                        if (method_exists($controllerObj, 'statistic')) {
                            $result = $controllerObj->statistic($queryFilter);
                            if ($result instanceof Action && $result->isHandled() && !$result->isError()) {
                                $results[] = $result->getResult();
                            }
                        }
                    } catch (\Exception $exc) {
                        $this->handleException($exc, $action);
                    }
                }
            }
        }

        $action->setResult($results);

        return $action;
    }
}
