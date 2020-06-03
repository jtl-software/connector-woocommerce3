<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2018 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Product;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\Model\ProductSpecific as ProductSpecificModel;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Utilities\SqlHelper;

class ProductSpecific extends BaseController
{
    /**
     * @param $productId
     * @param $curAttributes
     * @param array $specificData
     * @param array $pushedSpecifics
     * @return array
     */
    public function pushData($productId, $curAttributes, $specificData = [], $pushedSpecifics = [])
    {
        $newSpecifics = [];
        
        /** @var ProductSpecificModel $specific */
        foreach ($pushedSpecifics as $specific) {
            $specificData[(int)$specific->getId()->getEndpoint()]['options'][] =
                (int)$specific->getSpecificValueId()->getEndpoint();
        }
        
        /**
         * FILTER Attributes & UPDATE EXISTING
         *
         * @var \WC_Product_Attribute $productSpecific
         */
        foreach ($curAttributes as $slug => $productSpecific) {
            if (!preg_match('/^pa_/', $slug)) {
                $newSpecifics[$slug] = [
                    'name'         => $productSpecific->get_name(),
                    'value'        => implode(' ' . WC_DELIMITER . ' ', $productSpecific->get_options()),
                    'position'     => $productSpecific->get_position(),
                    'is_visible'   => $productSpecific->get_visible(),
                    'is_variation' => $productSpecific->get_variation(),
                    'is_taxonomy'  => $productSpecific->get_taxonomy(),
                ];
            } elseif (
                preg_match('/^pa_/', $slug)
                && array_key_exists($productSpecific->get_id(), $specificData)
            ) {
                // $cOptions    = $specificData[$productSpecific->get_id()]['options'];
                $cOldOptions = $productSpecific->get_options();
                unset($specificData[$slug]);
                
                $newSpecifics[$slug] = [
                    'name'         => $productSpecific->get_name(),
                    'value'        => '',
                    'position'     => $productSpecific->get_position(),
                    'is_visible'   => $productSpecific->get_visible(),
                    'is_variation' => $productSpecific->get_variation(),
                    'is_taxonomy'  => $productSpecific->get_taxonomy(),
                ];
                
                foreach ($cOldOptions as $value) {
                    if ($productSpecific->get_variation()) {
                        continue;
                    }
                    wp_remove_object_terms($productId, $value, $slug);
                }
            }
        }
        
        foreach ($specificData as $key => $specific) {
            
            $slug = wc_attribute_taxonomy_name_by_id($key);
            $newSpecifics[$slug] = [
                'name'         => $slug,
                'value'        => '',
                'position'     => null,
                'is_visible'   => 1,
                'is_variation' => 0,
                'is_taxonomy'  => $slug,
            ];
            $values = [];
            
            if (isset($specific) && count($specific['options']) > 0) {
                foreach ($specific['options'] as $valId) {
                    $term = get_term_by('id', $valId, $slug);
                    if ($term !== null && $term instanceof \WP_Term) {
                        $values[] = $term->slug;
                    }
                }
            }
            
            wp_set_object_terms($productId, $values, $slug, true);
        }
        
        return $newSpecifics;
    }
}
