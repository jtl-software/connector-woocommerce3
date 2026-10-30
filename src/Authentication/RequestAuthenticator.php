<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Authentication;

use Jtl\Connector\Core\Definition\ErrorCode;
use Jtl\Connector\Core\Definition\RpcMethod;

/**
 * Runs before the core framework handles a request: every call except the
 * authentication itself needs a valid, authenticated session id. Nothing else
 * (method validation, session start, controllers) is executed for other requests.
 */
class RequestAuthenticator
{
    /**
     * Returns null if the request may be passed on to the core framework, otherwise
     * the error code the request has to be rejected with.
     *
     * @param string   $jtlrpc           raw jtlrpc request parameter
     * @param mixed    $jtlauth          raw jtlauth request parameter (may be missing, empty or an array)
     * @param callable $isValidSessionId receives the session id, returns true for an authenticated session
     * @return int|null
     */
    public static function check(string $jtlrpc, mixed $jtlauth, callable $isValidSessionId): ?int
    {
        if (self::getMethod($jtlrpc) === RpcMethod::AUTH) {
            return null;
        }

        if (!\is_string($jtlauth) || $jtlauth === '') {
            return ErrorCode::NO_SESSION;
        }

        return $isValidSessionId($jtlauth) === true ? null : ErrorCode::INVALID_SESSION;
    }

    /**
     * Builds the JSON-RPC error response sent for a rejected request.
     *
     * @param string $jtlrpc
     * @param int    $code
     * @return array{jtlrpc: string, error: array{code: int, message: string, data: null}, id: mixed}
     */
    public static function createErrorResponse(string $jtlrpc, int $code): array
    {
        $request = \json_decode($jtlrpc, true);
        $id      = \is_array($request) && (\is_int($request['id'] ?? null) || \is_string($request['id'] ?? null))
            ? $request['id']
            : null;

        return [
            'jtlrpc' => '2.0',
            'error'  => [
                'code'    => $code,
                'message' => $code === ErrorCode::NO_SESSION ? 'No session' : 'Session is invalid',
                'data'    => null,
            ],
            'id'     => $id,
        ];
    }

    /**
     * @param string $jtlrpc
     * @return string
     */
    private static function getMethod(string $jtlrpc): string
    {
        $request = \json_decode($jtlrpc, true);

        return \is_array($request) && \is_string($request['method'] ?? null) ? $request['method'] : '';
    }
}
