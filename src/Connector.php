<?php //phpcs:ignore

namespace JtlWooCommerceConnector;

\ini_set('display_errors', 'off');

use DI\Container;
use Jtl\Connector\Core\Application\Application;
use Jtl\Connector\Core\Application\Request;
use Jtl\Connector\Core\Application\Response;
use Jtl\Connector\Core\Checksum\ChecksumLoaderInterface;
use Jtl\Connector\Core\Config\ConfigSchema;
use Jtl\Connector\Core\Connector\ConnectorInterface;
use Jtl\Connector\Core\Connector\HandleRequestInterface;
use Jtl\Connector\Core\Connector\UseChecksumInterface;
use Jtl\Connector\Core\Definition\Action;
use Jtl\Connector\Core\Definition\Controller;
use Jtl\Connector\Core\Definition\Event;
use Jtl\Connector\Core\Authentication\TokenValidatorInterface;
use Jtl\Connector\Core\Event\BoolEvent;
use Jtl\Connector\Core\Event\RpcEvent;
use Jtl\Connector\Core\Logger\LoggerService;
use JtlWooCommerceConnector\Authentication\TokenValidator;
use JtlWooCommerceConnector\Checksum\ChecksumLoader;
use JtlWooCommerceConnector\Event\CanHandleEvent;
use JtlWooCommerceConnector\Event\HandleDeleteEvent;
use JtlWooCommerceConnector\Event\HandlePullEvent;
use JtlWooCommerceConnector\Event\HandlePushEvent;
use JtlWooCommerceConnector\Event\HandleStatsEvent;
use JtlWooCommerceConnector\Mapper\PrimaryKeyMapper;
use JtlWooCommerceConnector\Utilities\B2BMarket;
use JtlWooCommerceConnector\Utilities\Category as CategoryUtil;
use JtlWooCommerceConnector\Utilities\Config;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;
use Noodlehaus\ConfigInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Yaml\Exception\ParseException;

class Connector implements ConnectorInterface, UseChecksumInterface, HandleRequestInterface
{
    /**
     * @var Db
     */
    protected Db $db;

    /**
     * @var LoggerService
     */
    protected LoggerService $loggerService;

    /**
     * @var SqlHelper
     */
    protected SqlHelper $sqlHelper;

    /**
     * @return void
     */
    public function initialize(ConfigInterface $config, Container $container, EventDispatcher $dispatcher): void
    {
        global $wpdb;

        $db   = new Db($wpdb);
        $util = new Util($db);

        $this->db            = $db;
        $this->sqlHelper     = new SqlHelper($this->db);
        $this->loggerService = $container->get(LoggerService::class);
        $container->set(Db::class, $db);
        $container->set(Util::class, $util);

        $dispatcher->addListener(Event::createRpcEventName(
            Event::BEFORE
        ), static function (RpcEvent $event) use ($config) {
            if ($event->getController() === 'Connector' && $event->getAction() === 'auth') {
                \JtlConnectorAdmin::loadFeaturesJson($config->get(ConfigSchema::FEATURES_PATH));
            }
        });


        $dispatcher->addListener(Event::createCoreEventName(
            Controller::CONNECTOR,
            Action::FINISH,
            Event::AFTER
        ), static function (BoolEvent $event) use ($db, $util) {
            if (Config::get(CategoryUtil::OPTION_CATEGORY_HAS_CHANGED, 'no') === 'yes') {
                (new CategoryUtil($db))->saveCategoryLevelsAsPreOrder();
                \update_option(CategoryUtil::OPTION_CATEGORY_HAS_CHANGED, 'no');
            }

            $util->countCategories();
            $util->countProductTags();
            $util->syncMasterProducts();
        });
    }

    /**
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function handle(Application $application, Request $request): Response
    {
        $event = new CanHandleEvent($request->getController(), $request->getAction());

        $application->getEventDispatcher()->dispatch($event, CanHandleEvent::EVENT_NAME);

        if ($event->isCanHandle()) {
            $result = $this->handleCallByPlugin($application->getEventDispatcher(), $request);
        } else {
            if (
                $request->getController() === 'product'
                && \in_array($request->getAction(), [Action::PUSH, Action::DELETE], true)
            ) {
                $this->disableGermanMarketActions();
            }

            $result = $application->handleRequest($this, $request);

            if (\in_array($controllerName = $request->getController(), ['product', 'category'])) {
                $database = $application->getContainer()->get(Db::class);
                $util     = $application->getContainer()->get(Util::class);

                (new B2BMarket($database, $util))
                    ->handleCustomerGroupsBlacklists($controllerName, ...$request->getParams());
            }
        }

        return $result;
    }

    /**
     * @return void
     */
    protected function disableGermanMarketActions(): void
    {
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_GERMAN_MARKET)) {
            \remove_action('save_post', ['WGM_Product', 'save_product_digital_type']);
        }
    }

    /**
     * This method allows main entities to be added by plugins.
     *
     * @param EventDispatcher $eventDispatcher
     * @param Request $request
     * @return Response
     */
    public function handleCallByPlugin(EventDispatcher $eventDispatcher, Request $request): Response
    {
        if ($request->getAction() === 'pull') {
            $event = new HandlePullEvent($request->getController(), $request->getParams());
            $eventDispatcher->dispatch($event, HandlePullEvent::EVENT_NAME);
        } elseif ($request->getAction() === 'statistic') {
            $event = new HandleStatsEvent($request->getController());
            $eventDispatcher->dispatch($event, HandleStatsEvent::EVENT_NAME);
        } elseif ($request->getAction() === 'push') {
            $event = new HandlePushEvent($request->getController(), $request->getParams());
            $eventDispatcher->dispatch($event, HandlePushEvent::EVENT_NAME);
        } else {
            $event = new HandleDeleteEvent($request->getController(), $request->getParams());
            $eventDispatcher->dispatch($event, HandleDeleteEvent::EVENT_NAME);
        }

        return new Response($event->getResult());
    }

    public function getChecksumLoader(): ChecksumLoaderInterface
    {
        $checksumLoader = new ChecksumLoader($this->getDb());
        $checksumLoader->setLogger($this->getLoggerService()->get(LoggerService::CHANNEL_CHECKSUM));

        return $checksumLoader;
    }

    public function getPrimaryKeyMapper(): \Jtl\Connector\Core\Mapper\PrimaryKeyMapperInterface
    {
        $primaryKeyMapper = new PrimaryKeyMapper($this->getDb(), $this->getSqlHelper());
        $primaryKeyMapper->setLogger($this->getLoggerService()->get(LoggerService::CHANNEL_LINKER));

        return $primaryKeyMapper;
    }

    public function getTokenValidator(): TokenValidatorInterface
    {
        return new TokenValidator(Config::get(Config::OPTIONS_TOKEN, ''));
    }

    public function getControllerNamespace(): string
    {
        return 'JtlWooCommerceConnector\Controllers';
    }

    /**
     * @throws ParseException
     */
    public function getEndpointVersion(): string
    {
        return Config::getBuildVersion();
    }

    public function getPlatformVersion(): string
    {
        return '';
    }

    public function getPlatformName(): string
    {
        return 'WooCommerce';
    }

    /**
     * @return Db
     */
    public function getDb(): Db
    {
        return $this->db;
    }

    /**
     * @return LoggerService
     */
    public function getLoggerService(): LoggerService
    {
        return $this->loggerService;
    }

    /**
     * @return SqlHelper
     */
    public function getSqlHelper(): SqlHelper
    {
        return $this->sqlHelper;
    }
}
