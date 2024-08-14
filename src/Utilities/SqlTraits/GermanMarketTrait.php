<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Utilities\SqlTraits;

use JtlWooCommerceConnector\Utilities\Util;

trait GermanMarketTrait
{
    /**
     * @return string
     */
    public static function globalDataGMMUPullSpecific(): string
    {
        global $wpdb;
        $defaultValues = \WGM_Defaults::get_default_product_attributes();
        $notExists     = false;
        $slug          = '';

        if (\count($defaultValues) > 0) {
            $slug = $defaultValues[0]['attribute_name'];
            if (\strcmp($slug, 'measuring-unit') === 0) {
                $exId      = Util::getAttributeTaxonomyIdByName($slug);
                $notExists = $exId === 0;
            }
        }

        if ($notExists) {
            \WGM_Installation::install_default_attributes();
        }

        $wat = $wpdb->prefix . 'woocommerce_attribute_taxonomies';

        return "SELECT wat.attribute_id, wat.attribute_name, wat.attribute_label, wat.attribute_type
                FROM {$wat} wat
               WHERE wat.attribute_name = '{$slug}'";
    }
}
