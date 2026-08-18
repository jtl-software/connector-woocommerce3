<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Telemetry\Event;

/**
 * Closed value set for the `status` field of a Transaction.
 *
 * @package JtlWooCommerceConnector\Telemetry\Event
 */
enum TransactionStatus: string
{
    case Pending   = 'pending';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Error     = 'error';
}
