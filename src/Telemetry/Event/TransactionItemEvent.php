<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Telemetry\Event;

/**
 * Telemetry event describing a single order line (TransactionItem, position level).
 *
 * Maps 1:1 to the "TransactionItem" entity of docs/telemetry/CO-3395-data-model.md. The optional
 * `error_message` defaults to an empty string to match the model's "no Nullable" convention.
 *
 * @package JtlWooCommerceConnector\Telemetry\Event
 */
final readonly class TransactionItemEvent implements \JsonSerializable
{
    /**
     * @param string                $itemId
     * @param string                $transactionId
     * @param string                $sku
     * @param string                $productName
     * @param int                   $quantity
     * @param float                 $unitPrice
     * @param float                 $totalPrice
     * @param TransactionItemStatus $status
     * @param string                $errorMessage
     */
    public function __construct(
        public string $itemId,
        public string $transactionId,
        public string $sku,
        public string $productName,
        public int $quantity,
        public float $unitPrice,
        public float $totalPrice,
        public TransactionItemStatus $status,
        public string $errorMessage = '',
    ) {
    }

    /**
     * @return array<string, scalar>
     */
    public function toArray(): array
    {
        return [
            'item_id'        => $this->itemId,
            'transaction_id' => $this->transactionId,
            'sku'            => $this->sku,
            'product_name'   => $this->productName,
            'quantity'       => $this->quantity,
            'unit_price'     => $this->unitPrice,
            'total_price'    => $this->totalPrice,
            'status'         => $this->status->value,
            'error_message'  => $this->errorMessage,
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
