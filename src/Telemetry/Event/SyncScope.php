<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Telemetry\Event;

/**
 * Closed value set for the `scope` field of a SyncRun.
 *
 * @package JtlWooCommerceConnector\Telemetry\Event
 */
enum SyncScope: string
{
    case Full  = 'full';
    case Delta = 'delta';
}
