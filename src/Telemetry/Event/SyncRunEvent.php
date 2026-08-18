<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Telemetry\Event;

/**
 * Telemetry event describing a synchronization batch triggered by the WaWi (SyncRun).
 *
 * Maps 1:1 to the "SyncRun" entity of docs/telemetry/CO-3395-data-model.md. The denormalised
 * {@see Context} block is embedded into the serialized output. Timestamps are strings in the
 * ClickHouse DateTime64(3) format.
 *
 * @package JtlWooCommerceConnector\Telemetry\Event
 */
final readonly class SyncRunEvent implements \JsonSerializable
{
    /**
     * @param string          $syncRunId
     * @param SyncTriggeredBy $triggeredBy
     * @param SyncDirection   $direction
     * @param SyncObjectType  $objectType
     * @param SyncScope       $scope
     * @param string          $timestampStart
     * @param string          $timestampEnd
     * @param int             $durationMs
     * @param int             $recordsTotal
     * @param int             $recordsSucceeded
     * @param int             $recordsFailed
     * @param SyncResult      $result
     * @param Context         $context
     */
    public function __construct(
        public string $syncRunId,
        public SyncTriggeredBy $triggeredBy,
        public SyncDirection $direction,
        public SyncObjectType $objectType,
        public SyncScope $scope,
        public string $timestampStart,
        public string $timestampEnd,
        public int $durationMs,
        public int $recordsTotal,
        public int $recordsSucceeded,
        public int $recordsFailed,
        public SyncResult $result,
        public Context $context,
    ) {
    }

    /**
     * @return array<string, scalar>
     */
    public function toArray(): array
    {
        return [
            'sync_run_id'       => $this->syncRunId,
            'triggered_by'      => $this->triggeredBy->value,
            'direction'         => $this->direction->value,
            'object_type'       => $this->objectType->value,
            'scope'             => $this->scope->value,
            'timestamp_start'   => $this->timestampStart,
            'timestamp_end'     => $this->timestampEnd,
            'duration_ms'       => $this->durationMs,
            'records_total'     => $this->recordsTotal,
            'records_succeeded' => $this->recordsSucceeded,
            'records_failed'    => $this->recordsFailed,
            'result'            => $this->result->value,
        ] + $this->context->toArray();
    }

    /**
     * @return array<string, scalar>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
