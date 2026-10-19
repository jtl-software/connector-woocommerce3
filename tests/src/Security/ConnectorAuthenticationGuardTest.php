<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Security;

use Jtl\Connector\Core\Exception\SessionException;
use Jtl\UnitTest\TestCase;
use JtlWooCommerceConnector\Authentication\TokenValidator;
use JtlWooCommerceConnector\Connector;

class ConnectorAuthenticationGuardTest extends TestCase
{
    /**
     * @return void
     * @covers \JtlWooCommerceConnector\Connector::assertAuthenticated
     */
    public function testAuthActionIsAlwaysAllowed(): void
    {
        Connector::assertAuthenticated('Connector', 'auth', true, []);
        $this->addToAssertionCount(1);
    }

    /**
     * @return void
     * @covers \JtlWooCommerceConnector\Connector::assertAuthenticated
     */
    public function testAuthenticatedSessionIsAllowed(): void
    {
        Connector::assertAuthenticated('product', 'push', true, [TokenValidator::AUTH_SESSION_KEY => true]);
        $this->addToAssertionCount(1);
    }

    /**
     * @return void
     * @covers \JtlWooCommerceConnector\Connector::assertAuthenticated
     */
    public function testFreshSessionIsRejected(): void
    {
        $this->expectException(SessionException::class);
        Connector::assertAuthenticated('product', 'push', true, []);
    }

    /**
     * @return void
     * @covers \JtlWooCommerceConnector\Connector::assertAuthenticated
     */
    public function testInactiveSessionIsRejected(): void
    {
        $this->expectException(SessionException::class);
        Connector::assertAuthenticated('category', 'push', false, [TokenValidator::AUTH_SESSION_KEY => true]);
    }

    /**
     * @return void
     * @covers \JtlWooCommerceConnector\Connector::assertAuthenticated
     */
    public function testNonBooleanMarkerIsRejected(): void
    {
        $this->expectException(SessionException::class);
        Connector::assertAuthenticated('product', 'push', true, [TokenValidator::AUTH_SESSION_KEY => 'true']);
    }

    /**
     * @return void
     * @covers \JtlWooCommerceConnector\Connector::assertAuthenticated
     */
    public function testCoreActionsOtherThanAuthAreRejected(): void
    {
        $this->expectException(SessionException::class);
        Connector::assertAuthenticated('Connector', 'finish', true, []);
    }
}
