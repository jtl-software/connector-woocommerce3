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
            'unix directory with trailing slash' => ['/var/www/Shop Name/', '[path]'],
            'unix path then controller/action'   => [
                'Cannot open /var/www/a.php for product/push',
                'Cannot open [path] for product/push',
            ],
            'two unix paths'                     => [
                'Cannot open /var/www/a.php and /var/www/b.php',
                'Cannot open [path] and [path]',
            ],
            'unix decomposed unicode'            => ["/var/www/Mu\u{0308}ller/private/config.php", '[path]'],
            'quoted unix path, several spaces'   => [
                "Failed opening '/var/www/My Great Shop/c.php' for product/push",
                "Failed opening '[path]' for product/push",
            ],
            'double-quoted unix path with apostrophe' => [
                "Failed opening \"/var/www/O'Reilly/private/config.php\" for product/push",
                'Failed opening "[path]" for product/push',
            ],
            'single-quoted unix path with double quote' => [
                "Failed opening '/var/www/My \"Shop\"/c.php' for product/push",
                "Failed opening '[path]' for product/push",
            ],
            'double-quoted windows path with apostrophe' => [
                'Failed opening "C:\\Users\\O\'Reilly\\a.php" for product/push',
                'Failed opening "[path]" for product/push',
            ],
            'single-quoted windows path with double quote' => [
                "Failed opening 'C:\\Users\\My \"Shop\"\\a.php' for product/push",
                "Failed opening '[path]' for product/push",
            ],
            'two quoted unix paths'              => [
                "Copy '/a b/c.php' to '/d e/f.php'",
                "Copy '[path]' to '[path]'",
            ],
            'quoted windows path, forward slashes' => [
                'Failed opening "C:/var/www/My Great Shop/private/config.php" for product/push',
                'Failed opening "[path]" for product/push',
            ],
            'windows path, forward slashes'      => ['C:/Users/Max Muster/shop/a.php', '[path]'],
            'windows path, mixed separators'     => ['C:\\Users/Max Muster\\a.php', '[path]'],
            'windows root level file, slash'     => ['Error in C:/a.php:3', 'Error in [path]:3'],
            'quoted windows then quoted unix path, inner quote' => [
                "Copy \"C:/Users/Customers'/a.php\" to '/var/www/My Great Shop/private/config.php'",
                "Copy \"[path]\" to '[path]'",
            ],
            'quoted unix then quoted windows path, inner quote' => [
                "Copy '/var/www/My \"Shop\"/a.php' to \"C:\\Users\\My Great Shop\\b.php\"",
                "Copy '[path]' to \"[path]\"",
            ],
            'windows path after localized text'  => ['无法读取C:\\Users\\Mueller\\private\\config.php', '无法读取[path]'],
            'unix path after localized text'     => ['无法读取/var/www/html/a.php', '无法读取[path]'],
            'unix path after cyrillic text'      => ['Ошибка/var/www/html/a.php', 'Ошибка[path]'],
            'php include diagnostic, apostrophe'  => [
                "include(): Failed opening '/var/www/O'Reilly/private/config.php' for inclusion",
                "include(): Failed opening '[path]' for inclusion",
            ],
            'php include diagnostic, windows apostrophe' => [
                "include(): Failed opening 'C:\\Users\\O'Reilly\\a.php' for inclusion",
                "include(): Failed opening '[path]' for inclusion",
            ],
            'unquoted unix path, apostrophe'     => ["/var/www/O'Reilly/private/config.php", '[path]'],
            'unquoted windows path, apostrophe'  => ["C:\\Users\\O'Reilly\\a.php", '[path]'],
            'apostrophe in text before path'     => ["it's /var/www/a.php", "it's [path]"],
            'unix path with symbols'             => ['/var/www/a&b/c#1/d=e.php', '[path]'],
            'unix path with tilde'               => ['/home/user/~backup/a.php', '[path]'],
            'unix path with emoji and dash'      => ['/var/www/😀/Shop—Ñ/a.php', '[path]'],
            'windows path with symbols'          => ['C:\\Users\\a&b\\c#1\\d.php', '[path]'],
            'path followed by line number'       => ['in /var/www/a.php(12): x', 'in [path](12): x'],
            'path in parentheses'                => ['(see /var/www/a.php)', '(see [path])'],
            'two paths separated by comma'       => ['/var/www/a.php,/var/www/b.php', '[path],[path]'],
            'windows dir with parentheses'       => ['C:\\inetpub\\shop(backup)\\private\\config.php', '[path]'],
            'windows dir with brackets'          => ['C:\\inetpub\\shop[1]\\private\\config.php', '[path]'],
            'windows file followed by line no.'  => ['in C:\\inetpub\\a.php(12): x', 'in [path](12): x'],
            'unix dir with parentheses'          => ['/var/www/shop(backup)/private/config.php', '[path]'],
            'unix dir with space and parentheses' => ['/var/www/Shop (old)/a.php', '[path]'],
            'unix dir with brackets'             => ['/var/www/[x]/{y}/a.php', '[path]'],
            'path in parentheses, then path'     => ['(see /var/www/a.php) and /b/c.php', '(see [path]) and [path]'],
            'unc path'                           => [
                'Cannot open \\\\server\\share\\dir\\a.php for product/push',
                'Cannot open [path] for product/push',
            ],
            'quoted unc path with spaces'        => [
                'Failed opening "\\\\server\\My Share\\a b.php"',
                'Failed opening "[path]"',
            ],
            'file url'                           => [
                'Cannot read file:///var/www/html/a.php',
                'Cannot read file://[path]',
            ],
            'phar url with spaces'               => ['phar:///var/www/My Shop/a.phar/b.php', 'phar://[path]'],
            'file url with windows drive'        => ['file:///C:/Users/a.php', 'file:///[path]'],
            'quoted phar url with spaces'        => [
                "Failed opening 'phar:///var/www/My Great Shop/a.phar/b.php' for inclusion",
                "Failed opening '[path]' for inclusion",
            ],
            'quoted file url with spaces'        => [
                'Cannot read "file:///var/www/My Great Shop/a.php"',
                'Cannot read "[path]"',
            ],
            'quoted file url with windows drive' => [
                'Cannot read "file:///C:/My Great Shop/a.php"',
                'Cannot read "[path]"',
            ],
            'quoted url is untouched'            => [
                "Cannot reach 'https://example.com/My Shop/a' now",
                "Cannot reach 'https://example.com/My Shop/a' now",
            ],
            'no path: url'                       => [
                'Cannot reach https://example.com/api/product for product/push',
                'Cannot reach https://example.com/api/product for product/push',
            ],
            'quoted windows path with spaces'    => [
                'Failed opening "C:\\Program Files\\My Shop\\c.php" for product/push',
                'Failed opening "[path]" for product/push',
            ],
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
