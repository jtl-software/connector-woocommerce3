<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Telemetry\Event;

/**
 * Closed value set for the `direction` field of a SyncRun.
 *
 * @package JtlWooCommerceConnector\Telemetry\Event
 */
enum SyncDirection: string
{
    case Inbound  = 'inbound';
    case Outbound = 'outbound';
}
