<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Session;

use Jtl\UnitTest\TestCase;
use JtlWooCommerceConnector\Authentication\TokenValidator;
use JtlWooCommerceConnector\Session\AuthenticatedSqliteSessionHandler;

class AuthenticatedSqliteSessionHandlerTest extends TestCase
{
    private string $databaseDir;

    private ?AuthenticatedSqliteSessionHandler $handler = null;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->databaseDir = \sprintf(
            '%s/jtlwcc-session-test-%s',
            \sys_get_temp_dir(),
            \uniqid('', true)
        );
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        // Release the SQLite file lock before removing the temporary directory.
        $this->handler = null;
        \gc_collect_cycles();

        if (\is_dir($this->databaseDir)) {
            foreach ((array)\glob($this->databaseDir . '/*') as $file) {
                if (\is_string($file) && \is_file($file)) {
                    @\unlink($file);
                }
            }
            @\rmdir($this->databaseDir);
        }

        parent::tearDown();
    }

    /**
     * @param string $databaseDir
     * @return AuthenticatedSqliteSessionHandler
     * @throws \Throwable
     */
    private function createHandler(string $databaseDir): AuthenticatedSqliteSessionHandler
    {
        $this->handler = new AuthenticatedSqliteSessionHandler($databaseDir);

        return $this->handler;
    }

    /**
     * A session whose persisted data carries the authenticated marker
     * (php serialize handler) must be accepted.
     *
     * @return void
     * @throws \Throwable
     * @covers \JtlWooCommerceConnector\Session\AuthenticatedSqliteSessionHandler::validateId
     */
    public function testValidateIdAcceptsAuthenticatedSessionPhpHandler(): void
    {
        $handler   = $this->createHandler($this->databaseDir);
        $sessionId = 'authenticated-session-php';

        $handler->write($sessionId, TokenValidator::AUTH_SESSION_KEY . '|b:1;');

        $this->assertTrue($handler->validateId($sessionId));
    }

    /**
     * A session whose persisted data carries the authenticated marker
     * (php_serialize handler) must be accepted.
     *
     * @return void
     * @throws \Throwable
     * @covers \JtlWooCommerceConnector\Session\AuthenticatedSqliteSessionHandler::validateId
     */
    public function testValidateIdAcceptsAuthenticatedSessionPhpSerializeHandler(): void
    {
        $handler   = $this->createHandler($this->databaseDir);
        $sessionId = 'authenticated-session-serialize';

        $handler->write(
            $sessionId,
            \sprintf(
                'a:1:{s:%d:"%s";b:1;}',
                \strlen(TokenValidator::AUTH_SESSION_KEY),
                TokenValidator::AUTH_SESSION_KEY
            )
        );

        $this->assertTrue($handler->validateId($sessionId));
    }

    /**
     * A session minted without a successful authentication (no marker) must be
     * rejected, closing the auth-bypass replay.
     *
     * @return void
     * @throws \Throwable
     * @covers \JtlWooCommerceConnector\Session\AuthenticatedSqliteSessionHandler::validateId
     */
    public function testValidateIdRejectsUnauthenticatedSession(): void
    {
        $handler   = $this->createHandler($this->databaseDir);
        $sessionId = 'unauthenticated-session';

        $handler->write($sessionId, 'some_other_key|s:3:"abc";');

        $this->assertFalse($handler->validateId($sessionId));
    }

    /**
     * A session that was never persisted at all must be rejected.
     *
     * @return void
     * @throws \Throwable
     * @covers \JtlWooCommerceConnector\Session\AuthenticatedSqliteSessionHandler::validateId
     */
    public function testValidateIdRejectsUnknownSession(): void
    {
        $handler = $this->createHandler($this->databaseDir);

        $this->assertFalse($handler->validateId('never-written-session'));
    }

    /**
     * A session whose persisted data carries the authenticated marker
     * (php_binary handler) must be accepted.
     *
     * @return void
     * @throws \Throwable
     * @covers \JtlWooCommerceConnector\Session\AuthenticatedSqliteSessionHandler::validateId
     */
    public function testValidateIdAcceptsAuthenticatedSessionPhpBinaryHandler(): void
    {
        $handler   = $this->createHandler($this->databaseDir);
        $sessionId = 'authenticated-session-binary';

        $handler->write(
            $sessionId,
            \chr(\strlen(TokenValidator::AUTH_SESSION_KEY)) . TokenValidator::AUTH_SESSION_KEY . 'b:1;'
        );

        $this->assertTrue($handler->validateId($sessionId));
    }

    /**
     * A different key that merely shares the marker as a prefix must not be
     * mistaken for the authenticated marker.
     *
     * @return void
     * @throws \Throwable
     * @covers \JtlWooCommerceConnector\Session\AuthenticatedSqliteSessionHandler::validateId
     */
    public function testValidateIdRejectsSimilarButDifferentKey(): void
    {
        $handler   = $this->createHandler($this->databaseDir);
        $sessionId = 'similar-key-session';

        $handler->write($sessionId, TokenValidator::AUTH_SESSION_KEY . '_backup|s:3:"abc";');

        $this->assertFalse($handler->validateId($sessionId));
    }

    /**
     * A marker with a falsy value must not be accepted.
     *
     * @return void
     * @throws \Throwable
     * @covers \JtlWooCommerceConnector\Session\AuthenticatedSqliteSessionHandler::validateId
     */
    public function testValidateIdRejectsFalsyMarker(): void
    {
        $handler   = $this->createHandler($this->databaseDir);
        $sessionId = 'falsy-marker-session';

        $handler->write($sessionId, TokenValidator::AUTH_SESSION_KEY . '|b:0;');

        $this->assertFalse($handler->validateId($sessionId));
    }
}
