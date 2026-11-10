<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Security;

use Jtl\Connector\Core\Definition\ErrorCode;
use Jtl\UnitTest\TestCase;
use JtlWooCommerceConnector\Authentication\RequestAuthenticator;

class RequestAuthenticatorTest extends TestCase
{
    private const string AUTH_RPC = '{"jtlrpc":"2.0","method":"core.connector.auth","params":{"token":"x"},"id":"1"}';
    private const string PUSH_RPC = '{"jtlrpc":"2.0","method":"category.push","params":[],"id":7}';

    /**
     * @return void
     * @covers \JtlWooCommerceConnector\Authentication\RequestAuthenticator::check
     */
    public function testAuthMethodPassesWithoutSession(): void
    {
        $this->assertNull(RequestAuthenticator::check(self::AUTH_RPC, null, $this->sessionLookupMustNotHappen()));
    }

    /**
     * @return void
     * @covers \JtlWooCommerceConnector\Authentication\RequestAuthenticator::check
     */
    public function testValidSessionPasses(): void
    {
        $this->assertNull(RequestAuthenticator::check(self::PUSH_RPC, 'abc', static fn (string $id): bool => true));
    }

    /**
     * @return void
     * @covers \JtlWooCommerceConnector\Authentication\RequestAuthenticator::check
     */
    public function testInvalidSessionIsRejected(): void
    {
        $this->assertSame(
            ErrorCode::INVALID_SESSION,
            RequestAuthenticator::check(self::PUSH_RPC, 'abc', static fn (string $id): bool => false)
        );
    }

    /**
     * @return void
     * @covers \JtlWooCommerceConnector\Authentication\RequestAuthenticator::check
     */
    public function testMissingEmptyAndArraySessionAreRejectedWithoutLookup(): void
    {
        foreach ([null, '', ['x'], 123] as $jtlauth) {
            $this->assertSame(
                ErrorCode::NO_SESSION,
                RequestAuthenticator::check(self::PUSH_RPC, $jtlauth, $this->sessionLookupMustNotHappen())
            );
        }
    }

    /**
     * @return void
     * @covers \JtlWooCommerceConnector\Authentication\RequestAuthenticator::check
     */
    public function testUnparsableOrUnknownRequestsNeedASession(): void
    {
        $requests = ['', 'not json', '{"method":["core.connector.auth"]}', '{"method":"connector.identification"}'];
        foreach ($requests as $rpc) {
            $this->assertSame(
                ErrorCode::NO_SESSION,
                RequestAuthenticator::check($rpc, '', $this->sessionLookupMustNotHappen())
            );
        }
    }

    /**
     * @return void
     * @covers \JtlWooCommerceConnector\Authentication\RequestAuthenticator::createErrorResponse
     */
    public function testErrorResponseContainsNoInternals(): void
    {
        $this->assertSame(
            [
                'jtlrpc' => '2.0',
                'error'  => ['code' => ErrorCode::NO_SESSION, 'message' => 'No session', 'data' => null],
                'id'     => 7,
            ],
            RequestAuthenticator::createErrorResponse(self::PUSH_RPC, ErrorCode::NO_SESSION)
        );
        $this->assertNull(RequestAuthenticator::createErrorResponse('garbage', ErrorCode::INVALID_SESSION)['id']);
    }

    /**
     * @return callable
     */
    private function sessionLookupMustNotHappen(): callable
    {
        return static function (string $id): bool {
            throw new \LogicException('Session must not be looked up');
        };
    }
}
