<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Telemetry\Event;

/**
 * Closed value set for the `result` field of a SyncRun (batch-level outcome).
 *
 * @package JtlWooCommerceConnector\Telemetry\Event
 */
enum SyncResult: string
{
    case Success = 'success';
    case Partial = 'partial';
    case Failed  = 'failed';
}
