<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Error;

use JtlWooCommerceConnector\Error\ErrorResponseSanitizer;
use JtlWooCommerceConnector\Tests\AbstractTestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \JtlWooCommerceConnector\Error\ErrorResponseSanitizer
 */
class ErrorResponseSanitizerTest extends AbstractTestCase
{
    private const string PUBLIC_MESSAGE_PATTERN =
        '/^Internal error \(reference: ([0-9a-f]{8})\)\. Details are available in the connector log\.$/';

    /**
     * @return void
     */
    public function testMessageWithoutPathIsKept(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $result = (new ErrorResponseSanitizer($logger))->sanitize([
            'code'    => 789,
            'message' => 'No session',
            'data'    => 'Jtl\Connector\Core\Exception\SessionException (Code: 789) in .../Application.php:441',
        ]);

        $this->assertSame(['code' => 789, 'message' => 'No session', 'data' => null], $result);
    }

    /**
     * @param string $message
     * @return void
     * @dataProvider unsafeMessageProvider
     */
    public function testMessageWithPathOrStackTraceIsReplaced(string $message): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $result = (new ErrorResponseSanitizer($logger))->sanitize(['code' => 1, 'message' => $message, 'data' => 'x']);

        $this->assertSame(1, $result['code']);
        $this->assertNull($result['data']);
        $this->assertMatchesRegularExpression(self::PUBLIC_MESSAGE_PATTERN, $result['message']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unsafeMessageProvider(): array
    {
        return [
            'unix path'                => ['Cannot open /var/www/html/a.txt'],
            'unix path with spaces'    => ['Cannot read /var/www/Shop Name/private/config.php:3'],
            'quoted path, apostrophe'  => ["Failed opening '/var/www/O'Reilly/config.php' for inclusion"],
            'unix path after cjk text' => ['无法读取/var/www/html/a.php'],
            'windows path'             => ['Cannot open C:\\inetpub\\wwwroot\\b.txt'],
            'windows path, slashes'    => ['Cannot open C:/Users/Max Muster/a.php'],
            'unc path'                 => ['Cannot open \\\\server\\share\\a.php'],
            'file url'                 => ['Cannot read file:///var/www/a.php'],
            'phar url'                 => ['phar:///var/www/My Shop/a.phar/b.php'],
            'root level file'          => ['Error in /app.php:3'],
            'stack trace'              => ["Uncaught Exception: boom\nStack trace:\n#0 {main}"],
            'relative slash text'      => ['Unknown controller product/push'],
        ];
    }

    /**
     * @return void
     */
    public function testOriginalErrorIsLoggedWithTheReferenceFromTheResponse(): void
    {
        $logged = null;
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->willReturnCallback(static function (string $message, array $context) use (&$logged): void {
                $logged = [$message, $context];
            });

        $result = (new ErrorResponseSanitizer($logger))->sanitize([
            'code'    => 12,
            'message' => 'Cannot open /var/www/html/a.txt',
            'data'    => 'trace in /var/www/html/B.php:7',
        ]);

        $this->assertIsArray($logged);
        $this->assertSame('RPC error response', $logged[0]);
        $this->assertSame(12, $logged[1]['code']);
        $this->assertSame('Cannot open /var/www/html/a.txt', $logged[1]['message']);
        $this->assertSame('trace in /var/www/html/B.php:7', $logged[1]['data']);
        $this->assertSame(1, \preg_match(self::PUBLIC_MESSAGE_PATTERN, $result['message'], $matches));
        $this->assertSame($matches[1], $logged[1]['reference']);
    }

    /**
     * @return void
     */
    public function testEachErrorGetsItsOwnReference(): void
    {
        $sanitizer = new ErrorResponseSanitizer($this->createMock(LoggerInterface::class));
        $error     = ['code' => 1, 'message' => 'Cannot open /var/www/a', 'data' => null];

        $this->assertNotSame($sanitizer->sanitize($error)['message'], $sanitizer->sanitize($error)['message']);
    }

    /**
     * @return void
     */
    public function testPayloadThatIsNoErrorIsUntouchedAndNotLogged(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');
        $sanitizer = new ErrorResponseSanitizer($logger);

        $list = [['id' => 1, 'name' => '/var/www/x']];
        $this->assertSame($list, $sanitizer->sanitize($list));

        $extraKey = ['code' => 1, 'message' => '/var/www/x', 'data' => 'y', 'other' => true];
        $this->assertSame($extraKey, $sanitizer->sanitize($extraKey));
    }

    /**
     * @return void
     */
    public function testNonStringMessageIsReplaced(): void
    {
        $result = (new ErrorResponseSanitizer($this->createMock(LoggerInterface::class)))
            ->sanitize(['code' => 1, 'message' => ['/var/www/x'], 'data' => null]);

        $this->assertMatchesRegularExpression(self::PUBLIC_MESSAGE_PATTERN, $result['message']);
    }
}
