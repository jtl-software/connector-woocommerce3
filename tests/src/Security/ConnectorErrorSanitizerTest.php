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
     * @return array<string, array{0: string, 1: string}>
     */
    public function pathMessageProvider(): array
    {
        return [
            'unix: space in directory name' => [
                'Cannot open /var/www/Shop Name/private/config.php',
                'Cannot open [path]',
            ],
            'unix: several spaces in directory names' => [
                'Cannot open /home/shop/Local Sites/My Shop/app/public/a.php',
                'Cannot open [path]',
            ],
            'unix: root level file' => [
                'Cannot open /app.php',
                'Cannot open [path]',
            ],
            'unix: non-ASCII characters' => [
                'Cannot open /var/www/Müller/Größe/config.php',
                'Cannot open [path]',
            ],
            'unix: non-ASCII character at segment start' => [
                'Cannot open /var/www/Ärzte/config.php',
                'Cannot open [path]',
            ],
            'unix: duplicate separators' => [
                'Cannot open /var/www//html/a.php',
                'Cannot open [path]',
            ],
            'unix: space in file name before line number' => [
                'Uncaught Exception: boom in /var/www/Shop Name/My File.php:12',
                'Uncaught Exception: boom in [path]:12',
            ],
            'unix: space in file name before "on line"' => [
                'thrown in /var/www/Shop Name/My File.php on line 12',
                'thrown in [path] on line 12',
            ],
            'unix: space in file name inside parentheses' => [
                'copy(/tmp/jtl/Mein Bild.jpg): Failed to open stream',
                'copy([path]): Failed to open stream',
            ],
            'unix: space in quoted directory name' => [
                "Directory '/home/Max Mustermann' is not writable",
                "Directory '[path]' is not writable",
            ],
            'unix: space in file name at the end of the message' => [
                'Cannot read /var/www/Shop Name/my file.json.',
                'Cannot read [path].',
            ],
            'unix: text after the path is kept' => [
                'Directory /var/www/uploads is not writable',
                'Directory [path] is not writable',
            ],
            'unix: text after a file name is kept even if it contains a slash' => [
                'Cannot copy /tmp/a.jpg for product 4711/XL',
                'Cannot copy [path] for product 4711/XL',
            ],
            'unix: URLs and relative paths are kept' => [
                'Download of https://shop.example/img/a.jpg to wp-content/uploads/a.jpg failed',
                'Download of https://shop.example/img/a.jpg to wp-content/uploads/a.jpg failed',
            ],
            'windows: space in directory name' => [
                'Cannot open C:\\Program Files\\Shop\\config.php',
                'Cannot open [path]',
            ],
            'windows: parentheses in directory name' => [
                'Cannot open C:\\Program Files (x86)\\Shop\\config.php',
                'Cannot open [path]',
            ],
            'windows: mixed separators and line number' => [
                'boom in C:\\Users\\Max Mustermann\\Local Sites\\shop\\app\\public/wp-content/plugins/a.php:12',
                'boom in [path]:12',
            ],
            'windows: forward slashes and non-ASCII characters' => [
                'Cannot open C:/xampp/htdocs/Müller/config.php',
                'Cannot open [path]',
            ],
            'windows: root level file' => [
                'Cannot open D:\\app.php',
                'Cannot open [path]',
            ],
            'windows: space in file name before "on line"' => [
                'thrown in C:\\Shop Name\\My File.php on line 3',
                'thrown in [path] on line 3',
            ],
            'windows: UNC path' => [
                'Cannot open \\\\fileserver\\Shop Name\\config.php',
                'Cannot open [path]',
            ],
        ];
    }

    /**
     * @dataProvider pathMessageProvider
     * @param string $message
     * @param string $expected
     * @return void
     * @covers \JtlWooCommerceConnector\Connector::sanitizeErrorData
     */
    public function testCompletePathsAreMasked(string $message, string $expected): void
    {
        $result = Connector::sanitizeErrorData(['code' => 1, 'message' => $message, 'data' => null]);

        $this->assertSame($expected, $result['message']);
    }

    /**
     * @return void
     * @covers \JtlWooCommerceConnector\Connector::sanitizeErrorData
     */
    public function testPathsAreMaskedInMessageWithInvalidUtf8(): void
    {
        $result = Connector::sanitizeErrorData([
            'code'    => 1,
            'message' => "Cannot open /var/www/Shop Name/a.php \xFF",
            'data'    => null,
        ]);

        $this->assertIsString($result['message']);
        $this->assertStringStartsWith('Cannot open [path] ', $result['message']);
        $this->assertStringNotContainsString('Name', $result['message']);
        $this->assertTrue(\mb_check_encoding($result['message'], 'UTF-8'));
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
