<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Security;

use Jtl\Connector\Core\Definition\Event;
use Jtl\Connector\Core\Exception\SessionException;
use Jtl\Connector\Core\Event\RpcEvent;
use Jtl\UnitTest\TestCase;
use JtlWooCommerceConnector\Authentication\TokenValidator;
use JtlWooCommerceConnector\Connector;
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
     * @covers \JtlWooCommerceConnector\Connector::registerAuthenticationGuard
     * @covers \JtlWooCommerceConnector\Connector::assertAuthenticated
     */
    public function testGuardRejectsUnauthenticatedRpcBeforeOtherListeners(): void
    {
        $dispatcher   = new EventDispatcher();
        $handledRpcs  = [];
        $rpcEventName = Event::createRpcEventName(Event::BEFORE);
        $dispatcher->addListener($rpcEventName, static function (RpcEvent $event) use (&$handledRpcs): void {
            $handledRpcs[] = $event->getController() . '.' . $event->getAction();
        });

        (new Connector())->registerAuthenticationGuard($dispatcher);

        try {
            $dispatcher->dispatch(new RpcEvent(null, 'product', 'push'), $rpcEventName);
            $this->fail('Unauthenticated RPC calls must be rejected before handling.');
        } catch (SessionException) {
            $this->assertSame([], $handledRpcs);
        }

        $dispatcher->dispatch(new RpcEvent(null, 'Connector', 'auth'), $rpcEventName);
        $this->assertSame(['Connector.auth'], $handledRpcs);
    }
}
