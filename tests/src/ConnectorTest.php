<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests;

use Jtl\Connector\Core\Application\Application;
use Jtl\Connector\Core\Application\Request;
use Jtl\Connector\Core\Application\Response;
use Jtl\UnitTest\TestCase;
use JtlWooCommerceConnector\Connector;
use JtlWooCommerceConnector\Event\CanHandleEvent;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\InvalidArgumentException;
use PHPUnit\Framework\MockObject\CannotUseOnlyMethodsException;
use PHPUnit\Framework\MockObject\ClassAlreadyExistsException;
use PHPUnit\Framework\MockObject\ClassIsFinalException;
use PHPUnit\Framework\MockObject\ClassIsReadonlyException;
use PHPUnit\Framework\MockObject\DuplicateMethodException;
use PHPUnit\Framework\MockObject\IncompatibleReturnValueException;
use PHPUnit\Framework\MockObject\InvalidMethodNameException;
use PHPUnit\Framework\MockObject\MethodCannotBeConfiguredException;
use PHPUnit\Framework\MockObject\MethodNameAlreadyConfiguredException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\OriginalConstructorInvocationRequiredException;
use PHPUnit\Framework\MockObject\ReflectionException;
use PHPUnit\Framework\MockObject\RuntimeException;
use PHPUnit\Framework\MockObject\UnknownTypeException;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ConnectorTest extends TestCase
{
    /**
     * @return void
     * @throws MethodCannotBeConfiguredException
     * @throws RuntimeException
     * @throws ClassIsFinalException
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws DuplicateMethodException
     * @throws ClassIsReadonlyException
     * @throws Exception
     * @throws ClassAlreadyExistsException
     * @throws InvalidMethodNameException
     * @throws CannotUseOnlyMethodsException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws MethodNameAlreadyConfiguredException
     * @throws ReflectionException
     * @throws UnknownTypeException
     * @throws IncompatibleReturnValueException
     */
    public function testHandleByApplication(): void
    {
        $connector = $this->createConnectorMock();

        $application = $this->createApplicationMock(['getEventDispatcher', 'handleRequest']);
        $application->expects($this->once())->method('handleRequest')->willReturn(new Response('foo'));

        $request = $this->createRequestMock();

        $result = $connector->handle($application, $request);

        $this->assertInstanceOf(Response::class, $result);
    }

    /**
     * @return void
     * @throws MethodCannotBeConfiguredException
     * @throws RuntimeException
     * @throws ClassIsFinalException
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     * @throws DuplicateMethodException
     * @throws ClassIsReadonlyException
     * @throws Exception
     * @throws ClassAlreadyExistsException
     * @throws InvalidMethodNameException
     * @throws CannotUseOnlyMethodsException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws MethodNameAlreadyConfiguredException
     * @throws ReflectionException
     * @throws UnknownTypeException
     * @throws IncompatibleReturnValueException
     */
    public function testHandleByPlugin(): void
    {
        $connector = $this->createConnectorMock(['handleCallByPlugin']);
        $connector->expects($this->once())->method('handleCallByPlugin')->willReturn(new Response('foo'));

        $application = $this->createApplicationMock(['getEventDispatcher']);
        $application->method('getEventDispatcher')->willReturn($dispatcher = new EventDispatcher());

        $dispatcher->addListener(CanHandleEvent::EVENT_NAME, function (CanHandleEvent $event): void {
            $event->setCanHandle(true);
        });

        $request = $this->createRequestMock();

        $result = $connector->handle($application, $request);

        $this->assertInstanceOf(Response::class, $result);
    }

    /**
     * @return void
     * @throws InvalidMethodNameException
     * @throws RuntimeException
     * @throws CannotUseOnlyMethodsException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws ClassIsFinalException
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     * @throws DuplicateMethodException
     * @throws ClassIsReadonlyException
     * @throws ReflectionException
     * @throws UnknownTypeException
     * @throws \ReflectionException
     * @throws Exception
     * @throws ClassAlreadyExistsException
     */
    public function testsHandleCallByPlugin(): void
    {
        $connector = $this->createConnectorMock();

        $eventDispatcher = new EventDispatcher();
        $request         = new Request('', '', []);

        $result = $this->invokeMethod($connector, 'handleCallByPlugin', $eventDispatcher, $request);

        $this->assertInstanceOf(Response::class, $result);
    }

    /**
     * @return void
     * @throws InvalidMethodNameException
     * @throws RuntimeException
     * @throws CannotUseOnlyMethodsException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws ClassIsFinalException
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     * @throws DuplicateMethodException
     * @throws ClassIsReadonlyException
     * @throws ReflectionException
     * @throws UnknownTypeException
     * @throws ClassAlreadyExistsException
     */
    public function testGetControllerNamespace(): void
    {
        $connector = $this->createConnectorMock();
        $this->assertEquals('JtlWooCommerceConnector\Controllers', $connector->getControllerNamespace());
    }

    /**
     * @return void
     * @throws InvalidMethodNameException
     * @throws RuntimeException
     * @throws CannotUseOnlyMethodsException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws ClassIsFinalException
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws DuplicateMethodException
     * @throws ClassIsReadonlyException
     * @throws ReflectionException
     * @throws UnknownTypeException
     * @throws ClassAlreadyExistsException
     */
    public function testGetPlatformName(): void
    {
        $connector = $this->createConnectorMock();
        $this->assertEquals('WooCommerce', $connector->getPlatformName());
    }

    /**
     * @return void
     * @throws InvalidMethodNameException
     * @throws RuntimeException
     * @throws CannotUseOnlyMethodsException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws ClassIsFinalException
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     * @throws DuplicateMethodException
     * @throws ClassIsReadonlyException
     * @throws ReflectionException
     * @throws UnknownTypeException
     * @throws ClassAlreadyExistsException
     */
    public function testGetPlatformVersion(): void
    {
        $connector = $this->createConnectorMock();
        $this->assertEquals('', $connector->getPlatformVersion());
    }

    /**
     * @return MockObject
     * @throws InvalidMethodNameException
     * @throws ClassIsFinalException
     * @throws InvalidArgumentException
     * @throws DuplicateMethodException
     * @throws ClassIsReadonlyException
     * @throws RuntimeException
     * @throws ReflectionException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws UnknownTypeException
     * @throws ClassAlreadyExistsException
     */
    protected function createRequestMock(): MockObject
    {
        return $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param string[] $onlyMethods
     * @return MockObject
     * @throws InvalidMethodNameException
     * @throws ClassIsFinalException
     * @throws InvalidArgumentException
     * @throws DuplicateMethodException
     * @throws RuntimeException
     * @throws ClassIsReadonlyException
     * @throws CannotUseOnlyMethodsException
     * @throws ReflectionException
     * @throws UnknownTypeException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws ClassAlreadyExistsException
     */
    protected function createApplicationMock(array $onlyMethods = []): MockObject
    {
        return $this->getMockBuilder(Application::class)
            ->onlyMethods($onlyMethods)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param string[] $onlyMethods
     * @return MockObject
     * @throws InvalidMethodNameException
     * @throws ClassIsFinalException
     * @throws InvalidArgumentException
     * @throws DuplicateMethodException
     * @throws RuntimeException
     * @throws ClassIsReadonlyException
     * @throws CannotUseOnlyMethodsException
     * @throws ReflectionException
     * @throws UnknownTypeException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws ClassAlreadyExistsException
     */
    protected function createConnectorMock(array $onlyMethods = []): MockObject
    {
        return $this->getMockBuilder(Connector::class)
            ->onlyMethods($onlyMethods)
            ->getMock();
    }
}
