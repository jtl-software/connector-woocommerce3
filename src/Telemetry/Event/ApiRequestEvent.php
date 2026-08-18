<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Telemetry\Event;

/**
 * Telemetry event describing a single outbound API call against a target system (ApiRequest).
 *
 * Maps 1:1 to the "ApiRequest" entity of docs/telemetry/CO-3395-data-model.md. The denormalised
 * {@see Context} block is embedded into the serialized output.
 *
 * All optional string/int fields default to `''`/`0` and optional bools to `false` to match the
 * model's "no Nullable" convention. The single exception is the optional enum field
 * `error_category`: as there is no "none" case in {@see ApiErrorCategory}, it is modelled as a
 * nullable enum that serializes to the empty string `''` when absent — preserving both strong
 * enum typing and the no-Nullable wire format.
 *
 * @package JtlWooCommerceConnector\Telemetry\Event
 */
final readonly class ApiRequestEvent implements \JsonSerializable
{
    /**
     * @param string                $requestId
     * @param string                $timestamp
     * @param TargetSystem          $targetSystem
     * @param HttpMethod            $httpMethod
     * @param string                $endpoint
     * @param int                   $httpStatus
     * @param int                   $durationMs
     * @param bool                  $isError
     * @param bool                  $isRetry
     * @param int                   $retryAttempt
     * @param bool                  $isRateLimited
     * @param Context               $context
     * @param string                $syncRunId
     * @param string                $syncItemId
     * @param int                   $requestSizeBytes
     * @param int                   $responseSizeBytes
     * @param ApiErrorCategory|null $errorCategory
     * @param string                $errorMessage
     * @param string                $retryOfRequestId
     * @param int                   $rateLimitWaitMs
     */
    public function __construct(
        public string $requestId,
        public string $timestamp,
        public TargetSystem $targetSystem,
        public HttpMethod $httpMethod,
        public string $endpoint,
        public int $httpStatus,
        public int $durationMs,
        public bool $isError,
        public bool $isRetry,
        public int $retryAttempt,
        public bool $isRateLimited,
        public Context $context,
        public string $syncRunId = '',
        public string $syncItemId = '',
        public int $requestSizeBytes = 0,
        public int $responseSizeBytes = 0,
        public ?ApiErrorCategory $errorCategory = null,
        public string $errorMessage = '',
        public string $retryOfRequestId = '',
        public int $rateLimitWaitMs = 0,
    ) {
    }

    /**
     * @return array<string, scalar>
     */
    public function toArray(): array
    {
        return [
            'request_id'          => $this->requestId,
            'sync_run_id'         => $this->syncRunId,
            'sync_item_id'        => $this->syncItemId,
            'timestamp'           => $this->timestamp,
            'target_system'       => $this->targetSystem->value,
            'http_method'         => $this->httpMethod->value,
            'endpoint'            => $this->endpoint,
            'http_status'         => $this->httpStatus,
            'duration_ms'         => $this->durationMs,
            'request_size_bytes'  => $this->requestSizeBytes,
            'response_size_bytes' => $this->responseSizeBytes,
            'is_error'            => $this->isError,
            'error_category'      => $this->errorCategory?->value ?? '',
            'error_message'       => $this->errorMessage,
            'is_retry'            => $this->isRetry,
            'retry_attempt'       => $this->retryAttempt,
            'retry_of_request_id' => $this->retryOfRequestId,
            'is_rate_limited'     => $this->isRateLimited,
            'rate_limit_wait_ms'  => $this->rateLimitWaitMs,
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
