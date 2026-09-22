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

        return $this->hasAuthenticatedMarker($sessionData);
    }

    /**
     * Verifies that the authentication marker exists as an actual top-level
     * session key whose value is strictly boolean true.
     *
     * The persisted payload is decoded according to the configured session
     * serialize handler instead of being searched as a raw string: this way the
     * marker bytes appearing inside an unrelated value (e.g. a decoy string or a
     * nested array) can no longer be mistaken for a genuine authentication.
     *
     * @param string $sessionData
     * @return bool
     */
    private function hasAuthenticatedMarker(string $sessionData): bool
    {
        $decoded = $this->decodeSession($sessionData);

        return \array_key_exists(TokenValidator::AUTH_SESSION_KEY, $decoded)
            && $decoded[TokenValidator::AUTH_SESSION_KEY] === true;
    }

    /**
     * Decodes a session payload into its top-level key/value pairs using the
     * configured session.serialize_handler.
     *
     * @param string $sessionData
     * @return array<array-key, mixed>
     */
    private function decodeSession(string $sessionData): array
    {
        return match ($this->getSerializeHandler()) {
            'php_serialize' => $this->decodePhpSerialize($sessionData),
            'php_binary'    => $this->decodePhpBinary($sessionData),
            default         => $this->decodePhp($sessionData),
        };
    }

    /**
     * Resolves the configured session serialize handler. Extracted as a seam so
     * the decoding of the non-default handler formats can be exercised without
     * mutating the global session.serialize_handler ini setting.
     *
     * @return string
     */
    protected function getSerializeHandler(): string
    {
        $handler = \ini_get('session.serialize_handler');

        return \is_string($handler) && $handler !== '' ? $handler : 'php';
    }

    /**
     * Decodes the "php_serialize" handler format (a plain serialize() of the
     * whole $_SESSION array).
     *
     * @param string $sessionData
     * @return array<array-key, mixed>
     */
    private function decodePhpSerialize(string $sessionData): array
    {
        /** @var mixed $decoded */
        $decoded = @\unserialize($sessionData, ['allowed_classes' => false]);

        return \is_array($decoded) ? $decoded : [];
    }

    /**
     * Decodes the default "php" handler format: a sequence of
     * "name|<serialized-value>" pairs, where the variable name is terminated by
     * the first pipe character.
     *
     * @param string $sessionData
     * @return array<array-key, mixed>
     */
    private function decodePhp(string $sessionData): array
    {
        $result = [];
        $offset = 0;
        $length = \strlen($sessionData);

        while ($offset < $length) {
            $separator = \strpos($sessionData, '|', $offset);
            if ($separator === false) {
                break;
            }

            $key    = \substr($sessionData, $offset, $separator - $offset);
            $offset = $separator + 1;

            $parsed = $this->readSerializedValue($sessionData, $offset);
            if ($parsed === null) {
                break;
            }

            $result[$key] = $parsed['value'];
            $offset       = $parsed['offset'];
        }

        return $result;
    }

    /**
     * Decodes the "php_binary" handler format: a sequence of
     * "<key-length-byte><name><serialized-value>" entries. The high bit of the
     * length byte flags an undefined variable and is masked out.
     *
     * @param string $sessionData
     * @return array<array-key, mixed>
     */
    private function decodePhpBinary(string $sessionData): array
    {
        $result = [];
        $offset = 0;
        $length = \strlen($sessionData);

        while ($offset < $length) {
            $keyLength = \ord($sessionData[$offset]) & 0x7F;
            $offset++;

            if ($keyLength === 0 || $offset + $keyLength > $length) {
                break;
            }

            $key     = \substr($sessionData, $offset, $keyLength);
            $offset += $keyLength;

            $parsed = $this->readSerializedValue($sessionData, $offset);
            if ($parsed === null) {
                break;
            }

            $result[$key] = $parsed['value'];
            $offset       = $parsed['offset'];
        }

        return $result;
    }

    /**
     * Reads a single serialized value starting at the given offset and returns
     * both the decoded value and the offset right after it, or null when the
     * payload is malformed or contains an unsupported type.
     *
     * @param string $data
     * @param int    $offset
     * @return array{value: mixed, offset: int}|null
     */
    private function readSerializedValue(string $data, int $offset): ?array
    {
        if ($offset >= \strlen($data)) {
            return null;
        }

        switch ($data[$offset]) {
            case 'N':
                if (\substr($data, $offset, 2) !== 'N;') {
                    return null;
                }

                return ['value' => null, 'offset' => $offset + 2];

            case 'b':
                if (\preg_match('/\Gb:([01]);/', $data, $matches, 0, $offset) !== 1) {
                    return null;
                }

                return ['value' => $matches[1] === '1', 'offset' => $offset + \strlen($matches[0])];

            case 'i':
                if (\preg_match('/\Gi:(-?\d+);/', $data, $matches, 0, $offset) !== 1) {
                    return null;
                }

                return ['value' => (int)$matches[1], 'offset' => $offset + \strlen($matches[0])];

            case 'd':
                if (\preg_match('/\Gd:([^;]+);/', $data, $matches, 0, $offset) !== 1) {
                    return null;
                }

                return ['value' => (float)$matches[1], 'offset' => $offset + \strlen($matches[0])];

            case 's':
                return $this->readSerializedString($data, $offset);

            case 'a':
                return $this->readSerializedArray($data, $offset);

            default:
                return null;
        }
    }

    /**
     * Reads a serialized string value of the form s:<len>:"<bytes>"; honouring
     * the declared length so embedded quotes, pipes or semicolons cannot break
     * out of the value.
     *
     * @param string $data
     * @param int    $offset
     * @return array{value: string, offset: int}|null
     */
    private function readSerializedString(string $data, int $offset): ?array
    {
        if (\preg_match('/\Gs:(\d+):"/', $data, $matches, 0, $offset) !== 1) {
            return null;
        }

        $stringLength = (int)$matches[1];
        $valueStart   = $offset + \strlen($matches[0]);
        $valueEnd     = $valueStart + $stringLength;

        if ($valueEnd + 2 > \strlen($data) || \substr($data, $valueEnd, 2) !== '";') {
            return null;
        }

        return ['value' => \substr($data, $valueStart, $stringLength), 'offset' => $valueEnd + 2];
    }

    /**
     * Reads a serialized array value of the form a:<count>:{<key><value>...}.
     *
     * @param string $data
     * @param int    $offset
     * @return array{value: array<array-key, mixed>, offset: int}|null
     */
    private function readSerializedArray(string $data, int $offset): ?array
    {
        if (\preg_match('/\Ga:(\d+):\{/', $data, $matches, 0, $offset) !== 1) {
            return null;
        }

        $count   = (int)$matches[1];
        $offset += \strlen($matches[0]);
        $array   = [];

        for ($i = 0; $i < $count; $i++) {
            $key = $this->readSerializedValue($data, $offset);
            if ($key === null || !(\is_int($key['value']) || \is_string($key['value']))) {
                return null;
            }
            $offset = $key['offset'];

            $value = $this->readSerializedValue($data, $offset);
            if ($value === null) {
                return null;
            }
            $offset = $value['offset'];

            /** @var array-key $arrayKey */
            $arrayKey         = $key['value'];
            $array[$arrayKey] = $value['value'];
        }

        if ($offset >= \strlen($data) || $data[$offset] !== '}') {
            return null;
        }

        return ['value' => $array, 'offset' => $offset + 1];
    }
}
