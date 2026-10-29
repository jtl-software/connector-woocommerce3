<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Security;

use Jtl\Connector\Core\Definition\Event;
use Jtl\Connector\Core\Event\RpcEvent;
use Jtl\UnitTest\TestCase;
use JtlWooCommerceConnector\Connector;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ConnectorErrorSanitizerTest extends TestCase
{
    /**
     * @return void
     * @covers \JtlWooCommerceConnector\Connector::registerErrorSanitizer
     */
    public function testListenerSanitizesRpcAfterEvent(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $dispatcher = new EventDispatcher();
        Connector::registerErrorSanitizer($dispatcher, $logger);

        $event = new RpcEvent(['code' => 1, 'message' => 'x /tmp/a/b', 'data' => 'secret'], 'product', 'push');
        $dispatcher->dispatch($event, Event::createRpcEventName(Event::AFTER));

        $data = $event->getData();
        $this->assertIsArray($data);
        $this->assertSame(1, $data['code']);
        $this->assertNull($data['data']);
        $this->assertStringStartsWith('Internal error (reference: ', $data['message']);
    }

    /**
     * @return void
     * @covers \JtlWooCommerceConnector\Connector::registerErrorSanitizer
     */
    public function testListenerRunsAfterAllOtherAfterListeners(): void
    {
        $dispatcher = new EventDispatcher();
        Connector::registerErrorSanitizer($dispatcher, $this->createMock(LoggerInterface::class));

        $listeners = $dispatcher->getListeners(Event::createRpcEventName(Event::AFTER));

        $this->assertCount(1, $listeners);
        $eventName = Event::createRpcEventName(Event::AFTER);
        $this->assertSame(-100, $dispatcher->getListenerPriority($eventName, $listeners[0]));
    }
}
