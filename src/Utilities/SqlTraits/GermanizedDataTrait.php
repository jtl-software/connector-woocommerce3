<?php

/**
 * Created by PhpStorm.
 * User: Jan Weskamp <jan.weskamp@jtl-software.com>
 * Date: 07.11.2018
 * Time: 10:56
 */

declare(strict_types=1);

namespace JtlWooCommerceConnector\Utilities\SqlTraits;

trait GermanizedDataTrait
{
    /**
     * @return string
     */
    public static function globalDataGermanizedMeasurementUnitPull(): string
    {
        global $wpdb;

        return "
            SELECT tt.term_id as id, t.slug as code
            FROM {$wpdb->term_taxonomy} tt
            LEFT JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
            WHERE tt.taxonomy = 'product_unit'";
    }

    /**
     * @param string $status
     * @return string
     */
    public static function deliveryStatusByText(string $status): string
    {
        global $wpdb;

        return "
            SELECT tt.term_id
            FROM {$wpdb->terms} t
            LEFT JOIN {$wpdb->term_taxonomy} tt
            ON tt.term_id = t.term_id
            WHERE tt.taxonomy = 'product_delivery_time'
            AND t.name = '{$status}'";
    }
}
