<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Authentication;

use Jtl\Connector\Core\Authentication\TokenValidatorInterface;

class TokenValidator implements TokenValidatorInterface
{
    /**
     * Session key set once a connector token has been validated successfully.
     * Consumed by AuthenticatedSqliteSessionHandler::validateId() to reject the
     * replay of session ids that were minted on a failed authentication attempt.
     */
    public const string AUTH_SESSION_KEY = 'jtl_authenticated';

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
        $isValid = \hash_equals($this->endpointToken, $token);

        if ($isValid && \session_status() === \PHP_SESSION_ACTIVE) {
            $_SESSION[self::AUTH_SESSION_KEY] = true;
        }

        return $isValid;
    }
}
