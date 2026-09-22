<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Session;

use Jtl\Connector\Core\Session\SqliteSessionHandler;
use JtlWooCommerceConnector\Authentication\TokenValidator;

/**
 * Hardened session handler that only accepts a session id whose persisted
 * session data was flagged as authenticated by a successful token validation.
 *
 * This closes an authentication-bypass replay: the core framework mints and
 * persists a session id even when the supplied connector token is invalid, and
 * the default validateId() accepts any non-expired session row regardless of
 * whether it was ever authenticated. By additionally requiring the
 * authentication marker set in TokenValidator::validate(), a session id minted
 * on a failed auth attempt can no longer be replayed as the jtlauth parameter.
 */
class AuthenticatedSqliteSessionHandler extends SqliteSessionHandler
{
    /**
     * @param string $sessionId
     * @return bool
     * @throws \Throwable
     */
    public function validateId(string $sessionId): bool
    {
        if (!parent::validateId($sessionId)) {
            return false;
        }

        $sessionData = $this->read($sessionId);

        if (!\is_string($sessionData) || $sessionData === '') {
            return false;
        }

        /*
         * Match the authenticated boolean marker independently of the configured
         * session serialize handler. The separator between the key and its
         * serialized boolean value differs per handler:
         *   php            -> jtl_authenticated|b:1;
         *   php_serialize  -> s:17:"jtl_authenticated";b:1;
         *   php_binary     -> <0x11>jtl_authenticatedb:1;
         * Hence the separator group is optional.
         */

        $pattern = \sprintf(
            '/%s(?:\||";)?b:1;/',
            \preg_quote(TokenValidator::AUTH_SESSION_KEY, '/')
        );

        return \preg_match($pattern, $sessionData) === 1;
    }
}
