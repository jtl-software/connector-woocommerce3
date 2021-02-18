<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers;

use jtl\Connector\Core\Controller\Controller;
use jtl\Connector\Core\Model\QueryFilter;
use jtl\Connector\Model\ConnectorIdentification;
use jtl\Connector\Model\ConnectorServerInfo;
use jtl\Connector\Result\Action;
use JtlWooCommerceConnector\Connector as WooConnector;
use JtlWooCommerceConnector\Event\CanHandleEvent;
use JtlWooCommerceConnector\Event\HandleStatsEvent;
use JtlWooCommerceConnector\Traits\BaseControllerTrait;
use JtlWooCommerceConnector\Utilities\Category as CategoryUtil;
use JtlWooCommerceConnector\Utilities\Config;
use JtlWooCommerceConnector\Utilities\Util;
use Symfony\Component\Yaml\Yaml;

class Connector extends Controller
{
    use BaseControllerTrait;

    public function identify()
    {
        $action = new Action();
        $action->setHandled(true);

        $returnMegaBytes = function ($value) {
            $value = trim($value);
            $res = (int)substr($value,0, -1);
            $unit = strtolower($value[strlen($value) - 1]);
            switch ($unit) {
                case 'g':
                    $res *= 1024;
            }

            return (int)$res;
        };
        
        $serverInfo = new ConnectorServerInfo();
        $serverInfo->setMemoryLimit((int)$returnMegaBytes(ini_get('memory_limit')))
            ->setExecutionTime((int)ini_get('max_execution_time'))
            ->setPostMaxSize((int)$returnMegaBytes(ini_get('post_max_size')))
            ->setUploadMaxFilesize((int)$returnMegaBytes(ini_get('upload_max_filesize')));

        $identification = new ConnectorIdentification();
        $identification->setPlatformName('WooCommerce')
            ->setEndpointVersion(trim(Yaml::parseFile( JTLWCC_CONNECTOR_DIR . '/build-config.yaml')['version']))
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
            if (Config::get(CategoryUtil::OPTION_CATEGORY_HAS_CHANGED, 'no') === 'yes') {
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
