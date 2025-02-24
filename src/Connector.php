<?php //phpcs:ignore

namespace JtlWooCommerceConnector;

\ini_set('display_errors', 'off');

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Jawira\CaseConverter\CaseConverterException;
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
use Jtl\Connector\Core\Exception\ApplicationException;
use Jtl\Connector\Core\Exception\DefinitionException;
use Jtl\Connector\Core\Exception\MustNotBeNullException;
use Jtl\Connector\Core\Logger\LoggerService;
use Jtl\Connector\Core\Model\Category;
use Jtl\Connector\Core\Model\Product;
use Jtl\Connector\Core\Model\QueryFilter;
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
    protected Db $db;

    protected LoggerService $loggerService;

    protected SqlHelper $sqlHelper;

    /**
     * @param ConfigInterface $config
     * @param Container       $container
     * @param EventDispatcher $dispatcher
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     * @throws \InvalidArgumentException
     * @throws CaseConverterException
     * @throws DefinitionException
     * @throws \LogicException
     * @throws \RuntimeException
     */
    public function initialize(ConfigInterface $config, Container $container, EventDispatcher $dispatcher): void
    {
        global $wpdb;

        $db   = new Db($wpdb);
        $util = new Util($db);

        $this->db        = $db;
        $this->sqlHelper = new SqlHelper();

        $loggerService = $container->get(LoggerService::class);

        if (!$loggerService instanceof LoggerService) {
            throw new \RuntimeException('LoggerService not found');
        }

        $this->loggerService = $loggerService;
        $container->set(Db::class, $db);
        $container->set(Util::class, $util);

        $dispatcher->addListener(Event::createRpcEventName(
            Event::BEFORE
        ), static function (RpcEvent $event) use ($config): void {
            if ($event->getController() === 'Connector' && $event->getAction() === 'auth') {
                $featuresPath = $config->get(ConfigSchema::FEATURES_PATH);

                if (!\is_string($featuresPath)) {
                    throw new \InvalidArgumentException(
                        "Expected featuresPath to be string but got " . \gettype($featuresPath) . " instead"
                    );
                }

                \JtlConnectorAdmin::loadFeaturesJson($featuresPath);
            }
        });


        $dispatcher->addListener(Event::createCoreEventName(
            Controller::CONNECTOR,
            Action::FINISH,
            Event::AFTER
        ), static function (BoolEvent $event) use ($db, $util): void {
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
     * @param Application $application
     * @param Request     $request
     * @return Response
     * @throws DependencyException
     * @throws NotFoundException
     * @throws \InvalidArgumentException
     * @throws ApplicationException
     * @throws MustNotBeNullException
     * @throws \ReflectionException
     * @throws \RuntimeException
     * @throws \Throwable
     * @throws \TypeError
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
                /** @var Db $database */
                $database = $application->getContainer()->get(Db::class);
                /** @var Util $util */
                $util = $application->getContainer()->get(Util::class);

                /** @var Category[]|Product[] $entities */
                $entities = $request->getParams();

                (new B2BMarket($database, $util))
                    ->handleCustomerGroupsBlacklists($controllerName, ...$entities);
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
     * @param Request         $request
     * @return Response
     */
    public function handleCallByPlugin(EventDispatcher $eventDispatcher, Request $request): Response
    {
        if ($request->getAction() === 'pull') {
            /** @var array<QueryFilter> $requestParams */
            $requestParams = $request->getParams();
            $event         = new HandlePullEvent($request->getController(), $requestParams);
            $eventDispatcher->dispatch($event, HandlePullEvent::EVENT_NAME);
        } elseif ($request->getAction() === 'statistic') {
            $event = new HandleStatsEvent($request->getController());
            $eventDispatcher->dispatch($event, HandleStatsEvent::EVENT_NAME);
        } elseif ($request->getAction() === 'push') {
            /** @var array<QueryFilter> $requestParams */
            $requestParams = $request->getParams();
            $event         = new HandlePushEvent($request->getController(), $requestParams);
            $eventDispatcher->dispatch($event, HandlePushEvent::EVENT_NAME);
        } else {
            /** @var array<QueryFilter> $requestParams */
            $requestParams = $request->getParams();
            $event         = new HandleDeleteEvent($request->getController(), $requestParams);
            $eventDispatcher->dispatch($event, HandleDeleteEvent::EVENT_NAME);
        }

        return new Response($event->getResult());
    }

    /**
     * @return ChecksumLoaderInterface
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function getChecksumLoader(): ChecksumLoaderInterface
    {
        $checksumLoader = new ChecksumLoader($this->getDb());
        $checksumLoader->setLogger($this->getLoggerService()->get(LoggerService::CHANNEL_CHECKSUM));

        return $checksumLoader;
    }

    /**
     * @return \Jtl\Connector\Core\Mapper\PrimaryKeyMapperInterface
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function getPrimaryKeyMapper(): \Jtl\Connector\Core\Mapper\PrimaryKeyMapperInterface
    {
        $primaryKeyMapper = new PrimaryKeyMapper($this->getDb(), $this->getSqlHelper());
        $primaryKeyMapper->setLogger($this->getLoggerService()->get(LoggerService::CHANNEL_LINKER));

        return $primaryKeyMapper;
    }

    /**
     * @return TokenValidatorInterface
     */
    public function getTokenValidator(): TokenValidatorInterface
    {
        /** @var string $optionsToken */
        $optionsToken = Config::get(Config::OPTIONS_TOKEN, '');
        return new TokenValidator($optionsToken);
    }

    /**
     * @return string
     */
    public function getControllerNamespace(): string
    {
        return 'JtlWooCommerceConnector\Controllers';
    }

    /**
     * @return string
     * @throws ParseException|\InvalidArgumentException
     */
    public function getEndpointVersion(): string
    {
        return Config::getBuildVersion();
    }

    /**
     * @return string
     */
    public function getPlatformVersion(): string
    {
        return '';
    }

    /**
     * @return string
     */
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
