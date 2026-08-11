<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Telemetry\Event;

/**
 * Closed value set for the `error_category` field of an ApiRequest.
 *
 * @package JtlWooCommerceConnector\Telemetry\Event
 */
enum ApiErrorCategory: string
{
    case Timeout     = 'timeout';
    case RateLimit   = 'rate_limit';
    case Auth        = 'auth';
    case ServerError = 'server_error';
    case ClientError = 'client_error';
}
