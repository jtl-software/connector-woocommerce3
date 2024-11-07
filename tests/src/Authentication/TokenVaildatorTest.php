<?php

namespace JtlWooCommerceConnector\Tests\Authentication;

use Jtl\UnitTest\TestCase;
use JtlWooCommerceConnector\Authentication\TokenValidator;

class TokenValidatorTest extends TestCase
{
    /**
     * @throws \Exception
     */
    public function testValidate(): void
    {
        $tokenValidator = new TokenValidator('foo');
        $this->assertTrue($tokenValidator->validate('foo'));
    }

    /**
     * @throws \Exception
     */
    public function testValidateFailure(): void
    {
        $tokenValidator = new TokenValidator('foo1');
        $this->assertFalse($tokenValidator->validate('foo'));
    }
}
