<?php

/**
 * Created by PhpStorm.
 * User: Jan Weskamp <jan.weskamp@jtl-software.com>
 * Date: 07.11.2018
 * Time: 09:44
 */

namespace JtlWooCommerceConnector\Utilities\SqlTraits;

trait GlobalDataTrait
{
    /**
     * @return string
     */
    public static function taxRatePull(): string
    {
        global $wpdb;

        return "SELECT tax_rate_id, tax_rate
				FROM {$wpdb->prefix}woocommerce_tax_rates";
    }
}
