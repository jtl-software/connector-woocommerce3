<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Utility;

final class SQLsGermanized
{
    public static function globalDataMeasurementUnitPull()
    {
        global $wpdb;

        return "
            SELECT tt.term_id as id, t.slug as code
            FROM {$wpdb->term_taxonomy} tt
            LEFT JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
            WHERE tt.taxonomy = 'product_unit'";
    }

    public static function deliveryStatusByText($status)
    {
        global $wpdb;

        return "
            SELECT tt.term_id
            FROM {$wpdb->terms} t
            LEFT JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id
            WHERE tt.taxonomy = 'product_delivery_time' AND t.name = '{$status}'";
    }
}
