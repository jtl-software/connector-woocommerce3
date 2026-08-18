<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Telemetry\Event;

/**
 * Telemetry event describing a single object processed within a SyncRun (SyncItem).
 *
 * Maps 1:1 to the "SyncItem" entity of docs/telemetry/CO-3395-data-model.md. The optional
 * `transaction_id`, `error_code` and `error_message` default to empty strings to match the model's
 * "no Nullable" convention.
 *
 * @package JtlWooCommerceConnector\Telemetry\Event
 */
final readonly class SyncItemEvent implements \JsonSerializable
{
    /**
     * @param string         $syncItemId
     * @param string         $syncRunId
     * @param string         $objectRef
     * @param SyncItemResult $result
     * @param int            $durationMs
     * @param string         $transactionId
     * @param string         $errorCode
     * @param string         $errorMessage
     */
    public function __construct(
        public string $syncItemId,
        public string $syncRunId,
        public string $objectRef,
        public SyncItemResult $result,
        public int $durationMs,
        public string $transactionId = '',
        public string $errorCode = '',
        public string $errorMessage = '',
    ) {
    }

    /**
     * @return array<string, scalar>
     */
    public function toArray(): array
    {
        return [
            'sync_item_id'   => $this->syncItemId,
            'sync_run_id'    => $this->syncRunId,
            'transaction_id' => $this->transactionId,
            'object_ref'     => $this->objectRef,
            'result'         => $this->result->value,
            'error_code'     => $this->errorCode,
            'error_message'  => $this->errorMessage,
            'duration_ms'    => $this->durationMs,
        ];
    }

    /**
     * @return array<string, scalar>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
