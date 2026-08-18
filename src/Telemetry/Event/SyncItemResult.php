<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Telemetry\Event;

/**
 * Closed value set for the `result` field of a SyncItem (single-object outcome).
 *
 * @package JtlWooCommerceConnector\Telemetry\Event
 */
enum SyncItemResult: string
{
    case Success = 'success';
    case Failed  = 'failed';
    case Skipped = 'skipped';
}
