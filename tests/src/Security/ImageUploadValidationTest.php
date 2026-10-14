<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Security;

use Jtl\Connector\Core\Mapper\PrimaryKeyMapperInterface;
use Jtl\Connector\Core\Model\CategoryImage;
use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\ManufacturerImage;
use Jtl\Connector\Core\Model\ProductImage;
use JtlWooCommerceConnector\Controllers\ImageController;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\Util;
use phpmock\MockBuilder;
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

    /**
     * @param ImageController $controller
     * @param string          $fileName
     * @param string          $fallback
     * @param string          $extension
     * @param string          $uploadPath
     * @return string
     * @throws \ReflectionException
     */
    private function invokeEnsureSafe(
        ImageController $controller,
        string $fileName,
        string $fallback,
        string $extension,
        string $uploadPath
    ): string {
        $method = (new \ReflectionClass($controller))->getMethod('ensureSafeDestinationFilename');
        $method->setAccessible(true);

        return (string)$method->invoke($controller, $fileName, $fallback, $extension, $uploadPath);
    }

    /**
     * A legacy attachment created through the original vulnerability can carry an
     * executable destination name (e.g. shell.php). Updating it via image.push
     * must not copy the pushed file onto that executable name; the destination is
     * regenerated with the already-validated image extension instead.
     *
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function testExistingAttachmentWithExecutableExtensionIsRegenerated(): void
    {
        $controller = $this->createController();

        $result = $this->invokeEnsureSafe(
            $controller,
            'legacy-shell.php',
            'pushed-image',
            'png',
            \sys_get_temp_dir()
        );

        $this->assertStringEndsWith('.png', $result);
        $this->assertStringNotContainsString('.php', $result);
    }

    /**
     * A destination name that already carries a permitted image extension must be
     * left untouched so in-place attachment updates keep overwriting the same file.
     *
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function testExistingAttachmentWithImageExtensionIsKept(): void
    {
        $controller = $this->createController();

        $result = $this->invokeEnsureSafe(
            $controller,
            'photo.jpg',
            'pushed-image',
            'jpg',
            \sys_get_temp_dir()
        );

        $this->assertSame('photo.jpg', $result);
    }

    /**
     * Updating a legacy attachment with an executable filename must use a safe
     * destination even when the pushed file is a genuine image.
     *
     * @return void
     * @throws \phpmock\MockEnabledException
     * @throws \Exception
     */
    public function testSaveImageUsesSafeDestinationForLegacyAttachment(): void
    {
        $db               = $this->getMockBuilder(Db::class)->disableOriginalConstructor()->getMock();
        $util             = $this->getMockBuilder(Util::class)->disableOriginalConstructor()->getMock();
        $primaryKeyMapper = $this->getMockBuilder(PrimaryKeyMapperInterface::class)->getMock();
        $controller       = new class ($db, $util, $primaryKeyMapper) extends ImageController {
            protected function isAttachmentUsedInOtherPlaces(int $attachmentId): bool
            {
                return false;
            }
        };
        $source = $this->createTempFile('png', $this->pngBytes());
        $image  = (new ProductImage())
            ->setId(new Identity('42', 0))
            ->setForeignKey(new Identity('123', 0))
            ->setName('updated image')
            ->setFilename($source);

        WP_Mock::userFunction('get_term')->andReturn(null);
        WP_Mock::userFunction('wp_upload_dir')->andReturn([
            'path' => \sys_get_temp_dir(),
            'url'  => 'https://example.test/uploads',
        ]);
        WP_Mock::userFunction('get_post')->with(42, \ARRAY_A)->andReturn(['ID' => 42]);
        WP_Mock::userFunction('get_attached_file')->with(42)->andReturn('/uploads/legacy-shell.php');

        $destination = null;
        $copyMock    = (new MockBuilder())
            ->setNamespace('JtlWooCommerceConnector\Controllers')
            ->setName('copy')
            ->setFunction(static function (string $source, string $target) use (&$destination): bool {
                $destination = $target;

                return false;
            })->build();
        $copyMock->enable();

        try {
            $controller->saveImage($image);
        } finally {
            $copyMock->disable();
        }

        $this->assertIsString($destination);
        $this->assertStringEndsWith('.png', $destination);
        $this->assertStringNotContainsString('.php', $destination);
    }

    /**
     * saveImage()-level regression test guarding the real security boundary: a
     * pushed source that fails validation must be rejected (return null) before
     * the file is copied or any attachment API is touched. Unlike the helper
     * tests this fails if the enforcement call in saveImage() is removed.
     *
     * @return void
     * @throws \phpmock\MockEnabledException
     * @throws \Exception
     */
    public function testSaveImageRejectsInvalidUploadWithoutCopying(): void
    {
        $controller = $this->createController();
        $source     = $this->createTempFile('php', "<?php system(\$_GET['c']); ?>");

        $image = (new ProductImage())
            ->setId(new Identity('', 0))
            ->setForeignKey(new Identity('123', 0))
            ->setName('')
            ->setFilename($source);

        WP_Mock::userFunction('get_term')->andReturn(null);
        WP_Mock::userFunction('wp_upload_dir')->never();
        WP_Mock::userFunction('wp_insert_attachment')->never();
        WP_Mock::userFunction('wp_generate_attachment_metadata')->never();

        $copyCalled = false;
        $copyMock   = (new MockBuilder())
            ->setNamespace('JtlWooCommerceConnector\Controllers')
            ->setName('copy')
            ->setFunction(function () use (&$copyCalled) {
                $copyCalled = true;

                return true;
            })->build();
        $copyMock->enable();

        try {
            $result = $controller->saveImage($image);
        } finally {
            $copyMock->disable();
        }

        $this->assertNull($result);
        $this->assertFalse($copyCalled, 'copy() must not be called for a rejected upload.');
    }

    /**
     * @return string
     */
    private function jpegBytes(): string
    {
        return (string)\base64_decode(
            '/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5' .
            'ID0gOTAK/9sAQwADAgIDAgIDAwMDBAMDBAUIBQUEBAUKBwcGCAwKDAwLCgsLDQ4SEA0OEQ4LCxAWEBETFBUVFQwPFxgWFBgSFBUU' .
            '/9sAQwEDBAQFBAUJBQUJFA0LDRQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQU/8AA' .
            'EQgAAgACAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQ' .
            'RBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGl' .
            'qc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP' .
            '09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQd' .
            'hcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl' .
            '6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9o' .
            'ADAMBAAIRAxEAPwD8qqKKKAP/2Q=='
        );
    }

    /**
     * A PNG renamed to .jpg must be rejected: the declared extension and the
     * actual image content do not match.
     *
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function testImageContentExtensionMismatchIsRejected(): void
    {
        $controller = $this->createController();
        $file       = $this->createTempFile('jpg', $this->pngBytes());

        $this->assertFalse($this->invokeValidator($controller, $file, 'jpg'));
    }

    /**
     * A genuine JPEG with a matching .jpg extension must be accepted.
     *
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function testMatchingJpegExtensionIsAllowed(): void
    {
        $controller = $this->createController();
        $file       = $this->createTempFile('jpg', $this->jpegBytes());

        $this->assertTrue($this->invokeValidator($controller, $file, 'jpg'));
    }

    /**
     * push() must not delete the currently linked image before the pushed file
     * has been validated. A rejected upload must leave the existing image (and
     * the model endpoint) untouched.
     *
     * @return void
     * @throws \Exception
     */
    public function testPushDoesNotDeleteExistingImageWhenUploadRejected(): void
    {
        $db   = $this->getMockBuilder(Db::class)->disableOriginalConstructor()->getMock();
        $util = $this->getMockBuilder(Util::class)->disableOriginalConstructor()->getMock();
        $pkm  = $this->getMockBuilder(PrimaryKeyMapperInterface::class)->getMock();

        $controller = $this->getMockBuilder(ImageController::class)
            ->setConstructorArgs([$db, $util, $pkm])
            ->onlyMethods(['delete'])
            ->getMock();
        $controller->expects($this->never())->method('delete');

        $source = $this->createTempFile('php', "<?php echo 'x'; ?>");
        $image  = (new ProductImage())
            ->setId(new Identity('p_1_2', 0))
            ->setForeignKey(new Identity('2', 0))
            ->setName('')
            ->setFilename($source);

        $result = $controller->push($image);

        $this->assertSame('p_1_2', $result[0]->getId()->getEndpoint());
    }

    /**
     * A rejected category image push must not overwrite the existing thumbnail
     * meta with attachment id 0, and must return an empty endpoint.
     *
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function testPushCategoryImageRejectionPreservesThumbnail(): void
    {
        $db   = $this->getMockBuilder(Db::class)->disableOriginalConstructor()->getMock();
        $util = $this->getMockBuilder(Util::class)->disableOriginalConstructor()->getMock();
        $pkm  = $this->getMockBuilder(PrimaryKeyMapperInterface::class)->getMock();

        $controller = $this->getMockBuilder(ImageController::class)
            ->setConstructorArgs([$db, $util, $pkm])
            ->onlyMethods(['saveImage'])
            ->getMock();
        $controller->method('saveImage')->willReturn(null);

        WP_Mock::userFunction('term_exists')->andReturn(1);
        WP_Mock::userFunction('update_term_meta')->never();

        $image = (new CategoryImage())
            ->setId(new Identity('', 0))
            ->setForeignKey(new Identity('7', 0))
            ->setFilename('whatever.png');

        $method = (new \ReflectionClass(ImageController::class))->getMethod('pushCategoryImage');
        $method->setAccessible(true);

        $this->assertSame('', $method->invoke($controller, $image));
    }

    /**
     * A rejected manufacturer image push must not overwrite the existing image
     * meta with attachment id 0, and must return an empty endpoint.
     *
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function testPushManufacturerImageRejectionPreservesImage(): void
    {
        $db   = $this->getMockBuilder(Db::class)->disableOriginalConstructor()->getMock();
        $util = $this->getMockBuilder(Util::class)->disableOriginalConstructor()->getMock();
        $pkm  = $this->getMockBuilder(PrimaryKeyMapperInterface::class)->getMock();

        $controller = $this->getMockBuilder(ImageController::class)
            ->setConstructorArgs([$db, $util, $pkm])
            ->onlyMethods(['saveImage'])
            ->getMock();
        $controller->method('saveImage')->willReturn(null);

        WP_Mock::userFunction('term_exists')->andReturn(1);
        WP_Mock::userFunction('update_term_meta')->never();

        $image = (new ManufacturerImage())
            ->setId(new Identity('', 0))
            ->setForeignKey(new Identity('7', 0))
            ->setFilename('whatever.png');

        $method = (new \ReflectionClass(ImageController::class))->getMethod('pushManufacturerImage');
        $method->setAccessible(true);

        $this->assertSame('', $method->invoke($controller, $image));
    }
}
