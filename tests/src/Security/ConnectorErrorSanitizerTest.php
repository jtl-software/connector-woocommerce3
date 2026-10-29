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
     * @param string $message
     * @param string $expected
     * @return void
     * @dataProvider pathMessageProvider
     * @covers \JtlWooCommerceConnector\Connector::sanitizeErrorData
     */
    public function testPathsAreMaskedCompletely(string $message, string $expected): void
    {
        $result = Connector::sanitizeErrorData(['code' => 1, 'message' => $message, 'data' => null]);

        $this->assertSame($expected, $result['message']);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function pathMessageProvider(): array
    {
        return [
            'unix path with spaces'              => ['/var/www/Shop Name/private/config.php', '[path]'],
            'unix path with spaces in message'   => [
                'Cannot read /var/www/Shop Name/private/config.php:3',
                'Cannot read [path]:3',
            ],
            'unix root level file'               => ['Error in /app.php:3', 'Error in [path]:3'],
            'unix path with non-ascii'           => ['/var/www/Müller/config.php', '[path]'],
            'unix path in quotes with spaces'    => [
                "Failed opening '/var/www/a b/c.php' for inclusion",
                "Failed opening '[path]' for inclusion",
            ],
            'windows path with spaces'           => ['C:\\Users\\Max Muster\\shop\\a.php', '[path]'],
            'windows root level file'            => ['Error in C:\\a.php:3', 'Error in [path]:3'],
            'windows path with non-ascii'        => ['C:\\Users\\Müller\\a.php', '[path]'],
            'plain unix path'                    => ['/var/www/html/wp-content/a.php', '[path]'],
            'no path: relative slash'            => ['for product/push and/or 1/2', 'for product/push and/or 1/2'],
        ];
    }

    /**
     * @return void
     * @covers \JtlWooCommerceConnector\Connector::sanitizeErrorData
     */
    public function testMessageWithInvalidUtf8DoesNotLeakPath(): void
    {
        $result = Connector::sanitizeErrorData([
            'code'    => 1,
            'message' => "bad \xFF byte in /var/www/Shop Name/a.php",
            'data'    => null,
        ]);

        $this->assertStringNotContainsString('/var/www', $result['message']);
        $this->assertStringNotContainsString('Shop', $result['message']);
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
