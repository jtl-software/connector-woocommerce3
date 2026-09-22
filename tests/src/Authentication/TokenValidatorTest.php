<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Authentication;

use Jtl\UnitTest\TestCase;
use JtlWooCommerceConnector\Authentication\TokenValidator;

class TokenValidatorTest extends TestCase
{
    /**
     * @return void
     * @throws \Exception
     * @covers TokenValidator::validate
     */
    public function testValidate(): void
    {
        $tokenValidator = new TokenValidator('foo');
        $this->assertTrue($tokenValidator->validate('foo'));
    }

    /**
     * @return void
     * @throws \Exception
     * @covers TokenValidator::validate
     */
    public function testValidateFailure(): void
    {
        $tokenValidator = new TokenValidator('foo1');
        $this->assertFalse($tokenValidator->validate('foo'));
    }

    /**
     * A successful validation must flag the active session as authenticated so
     * that AuthenticatedSqliteSessionHandler can later distinguish it from a
     * session id minted on a failed attempt.
     *
     * @return void
     * @throws \Exception
     * @covers \JtlWooCommerceConnector\Authentication\TokenValidator::validate
     */
    public function testValidateSetsAuthenticatedMarkerOnSuccess(): void
    {
        $_SESSION  = [];
        $validator = $this->createValidatorWithSessionState('secret-token', true);

        $this->assertTrue($validator->validate('secret-token'));
        $this->assertArrayHasKey(TokenValidator::AUTH_SESSION_KEY, $_SESSION);
        $this->assertTrue($_SESSION[TokenValidator::AUTH_SESSION_KEY]);
    }

    /**
     * A failed validation must never flag the active session as authenticated.
     *
     * @return void
     * @throws \Exception
     * @covers \JtlWooCommerceConnector\Authentication\TokenValidator::validate
     */
    public function testValidateDoesNotSetMarkerOnFailure(): void
    {
        $_SESSION  = [];
        $validator = $this->createValidatorWithSessionState('secret-token', true);

        $this->assertFalse($validator->validate('wrong-token'));
        $this->assertArrayNotHasKey(TokenValidator::AUTH_SESSION_KEY, $_SESSION);
    }

    /**
     * Without an active session a successful validation must not touch the
     * session marker.
     *
     * @return void
     * @throws \Exception
     * @covers \JtlWooCommerceConnector\Authentication\TokenValidator::validate
     */
    public function testValidateDoesNotSetMarkerWhenSessionInactive(): void
    {
        $_SESSION  = [];
        $validator = $this->createValidatorWithSessionState('secret-token', false);

        $this->assertTrue($validator->validate('secret-token'));
        $this->assertArrayNotHasKey(TokenValidator::AUTH_SESSION_KEY, $_SESSION);
    }

    /**
     * @param string $endpointToken
     * @param bool   $sessionActive
     * @return TokenValidator
     */
    private function createValidatorWithSessionState(string $endpointToken, bool $sessionActive): TokenValidator
    {
        return new class ($endpointToken, $sessionActive) extends TokenValidator {
            private bool $sessionActive;

            /**
             * @param string $endpointToken
             * @param bool   $sessionActive
             */
            public function __construct(string $endpointToken, bool $sessionActive)
            {
                parent::__construct($endpointToken);
                $this->sessionActive = $sessionActive;
            }

            /**
             * @return bool
             */
            protected function isSessionActive(): bool
            {
                return $this->sessionActive;
            }
        };
    }
}
