<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Error;

use Psr\Log\LoggerInterface;

/**
 * Keeps server internals (file paths, stack traces, exception details) out of RPC error responses.
 *
 * Every error response is logged in full together with a reference id. The response itself only keeps the
 * message if it cannot contain a file path, otherwise it carries a generic message with that reference id,
 * so a support request can be matched to the log entry.
 */
final class ErrorResponseSanitizer
{
    private const string PUBLIC_MESSAGE = 'Internal error (reference: %s). Details are available in the connector log.';

    /**
     * A message containing one of these is not passed on: every file path contains a directory separator.
     * This also replaces harmless messages such as "product/push" - the original is in the log.
     */
    private const array UNSAFE_MARKERS = ['/', '\\', 'Stack trace:'];

    private const int REFERENCE_BYTES = 4;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    /**
     * Handles a serialized RPC error, i.e. an array that consists of exactly "code", "message" and "data".
     * Any other payload is returned unchanged.
     *
     * @param array<array-key, mixed> $data
     * @return array<array-key, mixed>
     * @throws \Random\RandomException
     */
    public function sanitize(array $data): array
    {
        if (!$this->isError($data)) {
            return $data;
        }

        $reference = \bin2hex(\random_bytes(self::REFERENCE_BYTES));

        $this->log($reference, $data);

        if (!$this->isSafeMessage($data['message'])) {
            $data['message'] = \sprintf(self::PUBLIC_MESSAGE, $reference);
        }
        $data['data'] = null;

        return $data;
    }

    /**
     * Logs the original error. A failing logger (full disk, unwritable log directory) must not keep the
     * response from being sanitized, so the failure is reported to the PHP error log instead.
     *
     * @param string                  $reference
     * @param array<array-key, mixed> $error
     * @return void
     */
    private function log(string $reference, array $error): void
    {
        $context = [
            'reference' => $reference,
            'code'      => $error['code'],
            'message'   => $error['message'],
            'data'      => $error['data'],
        ];

        try {
            $this->logger->error('RPC error response', $context);
        } catch (\Throwable $exception) {
            \error_log(\sprintf(
                'JTL connector: writing the RPC error log failed (%s): %s',
                $exception->getMessage(),
                (string)\json_encode($context, \JSON_PARTIAL_OUTPUT_ON_ERROR)
            ));
        }
    }

    /**
     * @param array<array-key, mixed> $data
     * @return bool
     */
    private function isError(array $data): bool
    {
        $keys = \array_keys($data);
        \sort($keys);

        return $keys === ['code', 'data', 'message'];
    }

    /**
     * @param mixed $message
     * @return bool
     */
    private function isSafeMessage(mixed $message): bool
    {
        if (!\is_string($message)) {
            return false;
        }

        foreach (self::UNSAFE_MARKERS as $marker) {
            if (\str_contains($message, $marker)) {
                return false;
            }
        }

        return true;
    }
}
