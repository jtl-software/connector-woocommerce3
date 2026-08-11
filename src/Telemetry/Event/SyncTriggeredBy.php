<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Telemetry\Event;

/**
 * Closed value set for the `triggered_by` field of a SyncRun.
 *
 * @package JtlWooCommerceConnector\Telemetry\Event
 */
enum SyncTriggeredBy: string
{
    case WawiScheduled = 'wawi_scheduled';
    case WawiManual    = 'wawi_manual';
    case Webhook       = 'webhook';
}
