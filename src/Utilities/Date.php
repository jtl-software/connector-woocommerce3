<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Utilities;

class Date
{
    public static function isOpenDate($date)
    {
        $date = preg_replace("/[^1-9]/", "", $date);
        return empty($date);
    }
}
