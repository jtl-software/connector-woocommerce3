<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Telemetry\Event;

/**
 * Telemetry event describing a single order (Transaction, order level).
 *
 * Maps 1:1 to the "Transaction" entity of docs/telemetry/CO-3395-data-model.md. The denormalised
 * {@see Context} block is embedded into the serialized output. Timestamps are strings in the
 * ClickHouse DateTime64(3) format (e.g. `2026-04-30 09:12:00.000`).
 *
 * @package JtlWooCommerceConnector\Telemetry\Event
 */
final readonly class TransactionEvent implements \JsonSerializable
{
    /**
     * @param string            $transactionId
     * @param string            $orderId
     * @param string            $orderNumber
     * @param ConnectorType     $sourceSystem
     * @param TransactionStatus $status
     * @param float             $totalAmount
     * @param string            $currency
     * @param int               $itemCount
     * @param int               $totalQuantity
     * @param string            $createdAt
     * @param string            $syncedAt
     * @param Context           $context
     */
    public function __construct(
        public string $transactionId,
        public string $orderId,
        public string $orderNumber,
        public ConnectorType $sourceSystem,
        public TransactionStatus $status,
        public float $totalAmount,
        public string $currency,
        public int $itemCount,
        public int $totalQuantity,
        public string $createdAt,
        public string $syncedAt,
        public Context $context,
    ) {
    }

    /**
     * @return array<string, scalar>
     */
    public function toArray(): array
    {
        return [
            'transaction_id' => $this->transactionId,
            'order_id'       => $this->orderId,
            'order_number'   => $this->orderNumber,
            'source_system'  => $this->sourceSystem->value,
            'status'         => $this->status->value,
            'total_amount'   => $this->totalAmount,
            'currency'       => $this->currency,
            'item_count'     => $this->itemCount,
            'total_quantity' => $this->totalQuantity,
            'created_at'     => $this->createdAt,
            'synced_at'      => $this->syncedAt,
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
