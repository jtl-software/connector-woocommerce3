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

        if ($isValid && $this->isSessionActive()) {
            $_SESSION[self::AUTH_SESSION_KEY] = true;
        }

        return $isValid;
    }

    /**
     * Seam over the global session state. Extracted so the authenticated-marker
     * side effect can be verified without bootstrapping a real PHP session
     * (which is not possible once the test runner has produced output).
     *
     * @return bool
     */
    protected function isSessionActive(): bool
    {
        return \session_status() === \PHP_SESSION_ACTIVE;
    }
}
