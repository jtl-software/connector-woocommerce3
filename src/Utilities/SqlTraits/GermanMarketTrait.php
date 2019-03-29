<?php
/**
 * Created by PhpStorm.
 * User: Jan Weskamp <jan.weskamp@jtl-software.com>
 * Date: 07.11.2018
 * Time: 10:56
 */

namespace JtlWooCommerceConnector\Utilities\SqlTraits;


use JtlWooCommerceConnector\Utilities\Util;

trait GermanMarketTrait
{
    public static function globalDataGMMUPullSpecific()
    {
        global $wpdb;
        $defaultValues = \WGM_Defaults::get_default_product_attributes();
        $notExists = false;
        $slug = '';
        
        if (count($defaultValues) > 0) {
            $slug = $defaultValues[0]['attribute_name'];
            if (strcmp($slug, 'measuring-unit')===0) {
                $exId = Util::getAttributeTaxonomyIdByName($slug);
                $notExists = $exId === 0;
            }
        }
        
        if ($notExists) {
            \WGM_Installation::install_default_attributes();
        }
    
        $wat  = $wpdb->prefix . 'woocommerce_attribute_taxonomies';
    
        return "SELECT wat.attribute_id, wat.attribute_name, wat.attribute_label, wat.attribute_type
                FROM {$wat} wat
               WHERE wat.attribute_name = '{$slug}'";
    }
}