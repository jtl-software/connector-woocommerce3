<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Telemetry\Event;

/**
 * Closed value set for the `http_method` field of an ApiRequest.
 *
 * @package JtlWooCommerceConnector\Telemetry\Event
 */
enum HttpMethod: string
{
    case Get    = 'GET';
    case Post   = 'POST';
    case Put    = 'PUT';
    case Delete = 'DELETE';
}
