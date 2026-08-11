<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Telemetry\Event;

/**
 * Closed value set for the `status` field of a TransactionItem.
 *
 * @package JtlWooCommerceConnector\Telemetry\Event
 */
enum TransactionItemStatus: string
{
    case Synced  = 'synced';
    case Failed  = 'failed';
    case Skipped = 'skipped';
}
