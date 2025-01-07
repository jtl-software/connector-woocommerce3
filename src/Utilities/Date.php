<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Utilities;

class Date
{
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
