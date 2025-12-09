<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Utilities;

class Date
{
    public const HOURS_PER_DAY      = 24;
    public const MINUTES_PER_HOUR   = 60;
    public const SECONDS_PER_MINUTE = 60;
    public const LAST_HOUR          = 23;
    public const LAST_MINUTE        = 59;
    public const LAST_SECOND        = 59;

    /**
     * @param string $date
     * @return bool
     */
    public static function isOpenDate(string $date): bool
    {
        $date = \preg_replace("/[^1-9]/", "", $date);
        return empty($date);
    }
}
