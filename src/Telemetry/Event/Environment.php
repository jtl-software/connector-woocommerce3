<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Telemetry\Event;

/**
 * Closed value set for the `environment` Context field of the telemetry data model.
 *
 * @package JtlWooCommerceConnector\Telemetry\Event
 */
enum Environment: string
{
    case Production  = 'production';
    case Staging     = 'staging';
    case Development = 'development';
}
