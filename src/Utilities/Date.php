<?php

namespace JtlWooCommerceConnector\Utilities;

class Date
{
    /**
     * @param $date
     * @return bool
     */
    public static function isOpenDate($date): bool
    {
        $date = \preg_replace("/[^1-9]/", "", $date);
        return empty($date);
    }
}
