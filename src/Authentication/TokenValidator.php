<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Authentication;

use Jtl\Connector\Core\Authentication\TokenValidatorInterface;

class TokenValidator implements TokenValidatorInterface
{
    protected string $endpointToken;

    /**
     * @param string $endpointToken
     */
    public function __construct(string $endpointToken = '')
    {
        $this->endpointToken = $endpointToken;
    }

    /**
     * @param string $token
     * @return bool
     */
    public function validate(string $token): bool
    {
        return $token === $this->endpointToken;
    }
}
