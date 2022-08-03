<?php

namespace JtlWooCommerceConnector\Tests;

use Jtl\Connector\Core\Application\Application;
use Jtl\Connector\Core\Application\Request;
use Jtl\Connector\Core\Application\Response;
use Jtl\UnitTest\TestCase;
use JtlWooCommerceConnector\Connector;
use JtlWooCommerceConnector\Event\CanHandleEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ConnectorTest extends TestCase
{
    public function testHandleByApplication(): void
    {
        $connector = $this->createConnectorMock();

        $application = $this->createApplicationMock(['getEventDispatcher', 'handleRequest']);
        $application->expects($this->once())->method('handleRequest')->willReturn(new Response('foo'));

        $request = $this->createRequestMock();

        $result = $connector->handle($application, $request);

        $this->assertInstanceOf(Response::class, $result);
    }

    public function testHandleByPlugin(): void
    {
        $connector = $this->createConnectorMock(['handleCallByPlugin']);
        $connector->expects($this->once())->method('handleCallByPlugin')->willReturn(new Response('foo'));

        $application = $this->createApplicationMock(['getEventDispatcher']);
        $application->method('getEventDispatcher')->willReturn($dispatcher = new EventDispatcher());

        $dispatcher->addListener(CanHandleEvent::EVENT_NAME, function (CanHandleEvent $event) {
            $event->setCanHandle(true);
        });

        $request = $this->createRequestMock();

        $result = $connector->handle($application, $request);

        $this->assertInstanceOf(Response::class, $result);
    }

    public function testsHandleCallByPlugin(): void
    {
        $connector = $this->createConnectorMock();

        $eventDispatcher = new EventDispatcher();
        $request = new Request('', '', []);

        $result = $this->invokeMethod($connector, 'handleCallByPlugin', $eventDispatcher, $request);

        $this->assertInstanceOf(Response::class, $result);
    }

    public function testGetControllerNamespace(): void
    {
        $connector = $this->createConnectorMock();
        $this->assertEquals('JtlWooCommerceConnector\Controllers', $connector->getControllerNamespace());
    }

    public function testGetPlatformName(): void
    {
        $connector = $this->createConnectorMock();
        $this->assertEquals('WooCommerce', $connector->getPlatformName());
    }

    public function testGetPlatformVersion(): void
    {
        $connector = $this->createConnectorMock();
        $this->assertEquals('', $connector->getPlatformVersion());
    }

    protected function createRequestMock(): MockObject
    {
        return $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createApplicationMock(array $onlyMethods = []): MockObject
    {
        return $this->getMockBuilder(Application::class)
            ->onlyMethods($onlyMethods)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createConnectorMock(array $onlyMethods = []): MockObject
    {
        return $this->getMockBuilder(Connector::class)
            ->onlyMethods($onlyMethods)
            ->getMock();
    }
}