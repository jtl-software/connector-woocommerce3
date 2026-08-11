<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Telemetry\Event;

/**
 * Denormalised Context block that is written onto every telemetry event (no own table, no joins).
 *
 * Mirrors the "Context" section of docs/telemetry/CO-3395-data-model.md 1:1. `wawiType` and
 * `wawiVersion` are optional and default to empty strings to match the model's "no Nullable"
 * convention.
 *
 * @package JtlWooCommerceConnector\Telemetry\Event
 */
final readonly class Context implements \JsonSerializable
{
    /**
     * @param string        $tenantId
     * @param string        $tenantName
     * @param ConnectorType $connectorType
     * @param string        $connectorVersion
     * @param Environment   $environment
     * @param string        $wawiType
     * @param string        $wawiVersion
     */
    public function __construct(
        public string $tenantId,
        public string $tenantName,
        public ConnectorType $connectorType,
        public string $connectorVersion,
        public Environment $environment,
        public string $wawiType = '',
        public string $wawiVersion = '',
    ) {
    }

    /**
     * @return array<string, scalar>
     */
    public function toArray(): array
    {
        return [
            'tenant_id'         => $this->tenantId,
            'tenant_name'       => $this->tenantName,
            'connector_type'    => $this->connectorType->value,
            'connector_version' => $this->connectorVersion,
            'environment'       => $this->environment->value,
            'wawi_type'         => $this->wawiType,
            'wawi_version'      => $this->wawiVersion,
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
