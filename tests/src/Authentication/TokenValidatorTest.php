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
}
