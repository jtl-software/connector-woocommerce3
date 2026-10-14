<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Security;

use Jtl\Connector\Core\Mapper\PrimaryKeyMapperInterface;
use JtlWooCommerceConnector\Controllers\ImageController;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\Util;
use PHPUnit\Framework\TestCase;
use WP_Mock;

/**
 * Regression tests for the unauthenticated arbitrary file upload via the
 * image.push method (saveImage). Only genuine image files may be written to the
 * publicly accessible uploads directory; executable files such as PHP web shells
 * must be rejected.
 *
 * @covers \JtlWooCommerceConnector\Controllers\ImageController
 */
class ImageUploadValidationTest extends TestCase
{
    /** @var array<int, string> */
    private array $tempFiles = [];

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        WP_Mock::setUp();

        WP_Mock::userFunction('wp_check_filetype')->andReturnUsing(
            static function (string $filename): array {
                $ext      = \strtolower((string)\pathinfo($filename, \PATHINFO_EXTENSION));
                $imageExt = ['jpg', 'jpeg', 'jpe', 'png', 'gif', 'bmp', 'webp', 'tif', 'tiff', 'ico', 'avif'];

                return \in_array($ext, $imageExt, true)
                    ? ['ext' => $ext, 'type' => 'image/' . $ext]
                    : ['ext' => false, 'type' => false];
            }
        );
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        WP_Mock::tearDown();
        parent::tearDown();

        foreach ($this->tempFiles as $file) {
            if (\is_file($file)) {
                \unlink($file);
            }
        }
        $this->tempFiles = [];
    }

    /**
     * @param string $extension
     * @param string $contents
     * @return string
     */
    private function createTempFile(string $extension, string $contents): string
    {
        $path              = \sprintf('%s/jtl-upload-test-%s.%s', \sys_get_temp_dir(), \uniqid('', true), $extension);
        $this->tempFiles[] = $path;
        \file_put_contents($path, $contents);

        return $path;
    }

    /**
     * @return string
     */
    private function pngBytes(): string
    {
        return (string)\base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='
        );
    }

    /**
     * @return ImageController
     * @throws \Exception
     */
    private function createController(): ImageController
    {
        $db               = $this->getMockBuilder(Db::class)->disableOriginalConstructor()->getMock();
        $util             = $this->getMockBuilder(Util::class)->disableOriginalConstructor()->getMock();
        $primaryKeyMapper = $this->getMockBuilder(PrimaryKeyMapperInterface::class)->getMock();

        return new ImageController($db, $util, $primaryKeyMapper);
    }

    /**
     * @param ImageController $controller
     * @param string          $filePath
     * @param string          $extension
     * @return bool
     * @throws \ReflectionException
     */
    private function invokeValidator(ImageController $controller, string $filePath, string $extension): bool
    {
        $method = (new \ReflectionClass($controller))->getMethod('isAllowedImageFile');
        $method->setAccessible(true);

        return (bool)$method->invoke($controller, $filePath, $extension);
    }

    /**
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function testGenuineImageIsAllowed(): void
    {
        $controller = $this->createController();
        $file       = $this->createTempFile('png', $this->pngBytes());

        $this->assertTrue($this->invokeValidator($controller, $file, 'png'));
    }

    /**
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function testUppercaseExtensionIsAllowed(): void
    {
        $controller = $this->createController();
        $file       = $this->createTempFile('PNG', $this->pngBytes());

        $this->assertTrue($this->invokeValidator($controller, $file, 'PNG'));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function disallowedExtensionProvider(): array
    {
        return [
            'php'    => ['php'],
            'phtml'  => ['phtml'],
            'php5'   => ['php5'],
            'phar'   => ['phar'],
            'html'   => ['html'],
            'js'     => ['js'],
            'svg'    => ['svg'],
            'no ext' => [''],
        ];
    }

    /**
     * @dataProvider disallowedExtensionProvider
     * @param string $extension
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function testExecutableExtensionsAreRejected(string $extension): void
    {
        $controller = $this->createController();
        $file       = $this->createTempFile($extension !== '' ? $extension : 'bin', "<?php echo 'pwned'; ?>");

        $this->assertFalse($this->invokeValidator($controller, $file, $extension));
    }

    /**
     * A file with an image extension but non-image content (e.g. a PHP payload
     * disguised as shell.png) must still be rejected.
     *
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function testImageExtensionWithNonImageContentIsRejected(): void
    {
        $controller = $this->createController();
        $file       = $this->createTempFile('png', "<?php system(\$_GET['c']); ?>");

        $this->assertFalse($this->invokeValidator($controller, $file, 'png'));
    }

    /**
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function testMissingFileIsRejected(): void
    {
        $controller = $this->createController();

        $this->assertFalse(
            $this->invokeValidator($controller, \sys_get_temp_dir() . '/does-not-exist.png', 'png')
        );
    }
}
