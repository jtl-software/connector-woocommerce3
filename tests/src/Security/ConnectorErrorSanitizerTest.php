<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Security;

use Jtl\Connector\Core\Definition\Event;
use Jtl\Connector\Core\Event\RpcEvent;
use Jtl\UnitTest\TestCase;
use JtlWooCommerceConnector\Connector;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ConnectorErrorSanitizerTest extends TestCase
{
    /**
     * @return void
     * @covers \JtlWooCommerceConnector\Connector::sanitizeErrorData
     */
    public function testExceptionDetailsAreRemoved(): void
    {
        $result = Connector::sanitizeErrorData([
            'code'    => 789,
            'message' => 'No session',
            'data'    => 'Jtl\Connector\Core\Exception\SessionException (Code: 789) in .../Application.php:441',
        ]);

        $this->assertSame(['code' => 789, 'message' => 'No session', 'data' => null], $result);
    }

    /**
     * @return void
     * @covers \JtlWooCommerceConnector\Connector::sanitizeErrorData
     */
    public function testStackTraceAndPathsAreRemovedFromMessage(): void
    {
        $message = "Uncaught Exception: boom in /var/www/html/wp-content/plugins/woo-jtl/src/A.php:12\n"
            . "Stack trace:\n#0 /var/www/html/index.php(3): A->run()\n#1 {main}\n"
            . "  thrown in /var/www/html/A.php on line 12";

        $result = Connector::sanitizeErrorData(['code' => 1, 'message' => $message, 'data' => 'x']);

        $this->assertSame('Uncaught Exception: boom in [path]:12', $result['message']);
        $this->assertNull($result['data']);
    }

    /**
     * @return void
     * @covers \JtlWooCommerceConnector\Connector::sanitizeErrorData
     */
    public function testPathsInMessageAreMasked(): void
    {
        $result = Connector::sanitizeErrorData([
            'code'    => 1,
            'message' => 'Cannot open /var/www/html/a.txt and C:\\inetpub\\wwwroot\\b.txt for product/push',
            'data'    => null,
        ]);

        $this->assertSame('Cannot open [path] and [path] for product/push', $result['message']);
    }

    /**
     * @return void
     * @covers \JtlWooCommerceConnector\Connector::sanitizeErrorData
     */
    public function testNonErrorPayloadIsUntouched(): void
    {
        $payload = [['id' => 1, 'name' => '/var/www/x']];
        $this->assertSame($payload, Connector::sanitizeErrorData($payload));

        $withExtraKey = ['code' => 1, 'message' => '/var/www/x', 'data' => 'y', 'other' => true];
        $this->assertSame($withExtraKey, Connector::sanitizeErrorData($withExtraKey));
    }

    /**
     * @return void
     * @covers \JtlWooCommerceConnector\Connector::registerErrorSanitizer
     * @covers \JtlWooCommerceConnector\Connector::sanitizeErrorData
     */
    public function testListenerSanitizesRpcAfterEvent(): void
    {
        $dispatcher = new EventDispatcher();
        Connector::registerErrorSanitizer($dispatcher);

        $event = new RpcEvent(['code' => 1, 'message' => 'x /tmp/a/b', 'data' => 'secret'], 'product', 'push');
        $dispatcher->dispatch($event, Event::createRpcEventName(Event::AFTER));

        $this->assertSame(['code' => 1, 'message' => 'x [path]', 'data' => null], $event->getData());
    }
}
