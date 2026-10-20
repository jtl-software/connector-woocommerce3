<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Security;

use DI\Container;
use Jtl\Connector\Core\Definition\Event;
use Jtl\Connector\Core\Exception\SessionException;
use Jtl\Connector\Core\Event\RpcEvent;
use Jtl\Connector\Core\Logger\LoggerService;
use Jtl\UnitTest\TestCase;
use JtlWooCommerceConnector\Authentication\TokenValidator;
use JtlWooCommerceConnector\Connector;
use Noodlehaus\ConfigInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ConnectorAuthenticationGuardTest extends TestCase
{
    /**
     * @return void
     * @covers \JtlWooCommerceConnector\Connector::assertAuthenticated
     */
    public function testAuthActionIsAlwaysAllowed(): void
    {
        Connector::assertAuthenticated('Connector', 'auth', true, []);
        $this->addToAssertionCount(1);
    }

    /**
     * @return void
     * @covers \JtlWooCommerceConnector\Connector::assertAuthenticated
     */
    public function testAuthenticatedSessionIsAllowed(): void
    {
        Connector::assertAuthenticated('product', 'push', true, [TokenValidator::AUTH_SESSION_KEY => true]);
        $this->addToAssertionCount(1);
    }

    /**
     * @return void
     * @covers \JtlWooCommerceConnector\Connector::assertAuthenticated
     */
    public function testFreshSessionIsRejected(): void
    {
        $this->expectException(SessionException::class);
        Connector::assertAuthenticated('product', 'push', true, []);
    }

    /**
     * @return void
     * @covers \JtlWooCommerceConnector\Connector::assertAuthenticated
     */
    public function testInactiveSessionIsRejected(): void
    {
        $this->expectException(SessionException::class);
        Connector::assertAuthenticated('category', 'push', false, [TokenValidator::AUTH_SESSION_KEY => true]);
    }

    /**
     * @return void
     * @covers \JtlWooCommerceConnector\Connector::assertAuthenticated
     */
    public function testNonBooleanMarkerIsRejected(): void
    {
        $this->expectException(SessionException::class);
        Connector::assertAuthenticated('product', 'push', true, [TokenValidator::AUTH_SESSION_KEY => 'true']);
    }

    /**
     * @return void
     * @covers \JtlWooCommerceConnector\Connector::assertAuthenticated
     */
    public function testCoreActionsOtherThanAuthAreRejected(): void
    {
        $this->expectException(SessionException::class);
        Connector::assertAuthenticated('Connector', 'finish', true, []);
    }

    /**
     * @return void
     * @covers \JtlWooCommerceConnector\Connector::initialize
     * @covers \JtlWooCommerceConnector\Connector::assertAuthenticated
     */
    public function testInitializeRegistersAuthenticationGuardBeforeRpcHandling(): void
    {
        $originalWpdb = $GLOBALS['wpdb'] ?? null;
        $wpdb         = new class {
            public string $prefix = '';
        };

        if (!\class_exists('wpdb', false)) {
            \class_alias($wpdb::class, 'wpdb');
        } elseif (!$wpdb instanceof \wpdb) {
            $wpdb = $this->getMockBuilder(\wpdb::class)
                ->disableOriginalConstructor()
                ->getMock();
        }
        $GLOBALS['wpdb'] = $wpdb;

        $sessionWasActive = \session_status() === \PHP_SESSION_ACTIVE;
        if (!$sessionWasActive) {
            $this->assertTrue(\session_start());
        }
        $originalSession = $_SESSION ?? [];

        try {
            $_SESSION = [];

            \WP_Mock::userFunction('get_option')->andReturn(false);
            \WP_Mock::userFunction('update_option')->andReturn(true);
            \WP_Mock::userFunction('get_locale')->andReturn('en_US');
            require_once __DIR__ . '/../../../includes/JtlConnectorAdmin.php';

            $config = $this->createMock(ConfigInterface::class);
            $config->method('get')->willReturn(__DIR__ . '/../../../config/features.json.example');

            $container = new Container();
            $container->set(
                LoggerService::class,
                $this->getMockBuilder(LoggerService::class)
                    ->disableOriginalConstructor()
                    ->getMock()
            );

            $dispatcher    = new EventDispatcher();
            $handledRpcs   = [];
            $rpcEventName  = Event::createRpcEventName(Event::BEFORE);
            $dispatcher->addListener($rpcEventName, static function (RpcEvent $event) use (&$handledRpcs): void {
                $handledRpcs[] = $event->getController() . '.' . $event->getAction();
            });

            \WP_Mock::userFunction('get_locale')->andReturn('de_DE');
            (new Connector())->initialize($config, $container, $dispatcher);

            try {
                $dispatcher->dispatch(new RpcEvent(null, 'product', 'push'), $rpcEventName);
                $this->fail('Unauthenticated RPC calls must be rejected before handling.');
            } catch (SessionException) {
                $this->assertSame([], $handledRpcs);
            }

            $dispatcher->dispatch(new RpcEvent(null, 'Connector', 'auth'), $rpcEventName);
            $this->assertSame(['Connector.auth'], $handledRpcs);

            $_SESSION[TokenValidator::AUTH_SESSION_KEY] = true;
            $dispatcher->dispatch(new RpcEvent(null, 'product', 'push'), $rpcEventName);
            $this->assertSame(['Connector.auth', 'product.push'], $handledRpcs);
        } finally {
            $_SESSION = $originalSession;
            if (!$sessionWasActive) {
                \session_write_close();
            }

            if ($originalWpdb === null) {
                unset($GLOBALS['wpdb']);
            } else {
                $GLOBALS['wpdb'] = $originalWpdb;
            }
        }
    }
}
