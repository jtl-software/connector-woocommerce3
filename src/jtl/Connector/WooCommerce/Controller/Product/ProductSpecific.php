<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2018 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller\Product;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\Model\ProductSpecific as ProductSpecificModel;
use jtl\Connector\WooCommerce\Controller\BaseController;
use jtl\Connector\WooCommerce\Utility\SQL;
use jtl\Connector\WooCommerce\Utility\Util;

class ProductSpecific extends BaseController
{
    // <editor-fold defaultstate="collapsed" desc="Pull">
    public function pullData(\WC_Product $product, ProductModel $result)
    {
        $productSpecifics = [];
        
        $attributes = $product->get_attributes();
        
        /**
         * @var string $slug
         * @var \WC_Product_Attribute $attribute
         */
        foreach ($attributes as $slug => $attribute) {
            
            $var = $attribute->get_variation();
            $taxe = taxonomy_exists($slug);
            
            // No variations and specifics
            if (!$var && $taxe) {
                $name = $attribute->get_name();
                $productAttribute = $product->get_attribute($name);
                
                $values = array_map('trim', explode(',', $productAttribute));
                
                foreach ($values as $value) {
                    if(empty($value)) {
                        continue;
                    }
                    $productSpecifics[] = $this->buildProductSpecific($slug, $value, $result);
                }
            } else {
                continue;
            }
        }
        
        return $productSpecifics;
    }
    
    private function buildProductSpecific($slug, $value, ProductModel $result)
    {
        $valueId = $this->getSpecificValueId($slug, $value);
        $specifcId = (new Identity)->setEndpoint($this->getSpecificId($slug));
        
        $specifc = (new ProductSpecificModel)
            ->setId($specifcId)
            ->setProductId($result->getId())
            ->setSpecificValueId($valueId);
        
        return $specifc;
    }
    
    private function getSpecificValueId($slug, $value)
    {
        $val = $this->database->query(SQL::getSpecificValueId($slug, $value));
        $result = isset($val[0]['endpoint_id']) && isset($val[0]['host_id'])
            ? (new Identity)->setEndpoint($val[0]['endpoint_id'])->setHost($val[0]['host_id'])
            : (new Identity)->setEndpoint($val[0]['term_taxonomy_id']);
        
        return $result;
    }
    
    private function getSpecificId($slug)
    {
        $name = substr($slug, 3);
        $val = $this->database->query(SQL::getSpecificId($name));
        
        return isset($val[0]['attribute_id']) ? $val[0]['attribute_id'] : '';
    }
    
    // </editor-fold>
    
    // <editor-fold defaultstate="collapsed" desc="Push">
    public function pushData(ProductModel $product)
    {
        $wcProduct = \wc_get_product($product->getId()->getEndpoint());
        
        if ($wcProduct === false) {
            return;
        }
        
        if ($wcProduct->get_parent_id() !== 0) {
            return;
        }
        
        $specifics = $product->getSpecifics();
        $productSpecifics = $wcProduct->get_attributes();
        
        $current = [];
        
        $attributes = [];
        $specificData = [];
        
        foreach ($specifics as $specific) {
            $specificData[(int)$specific->getId()->getEndpoint()]['options'][] =
                (int)$specific->getSpecificValueId()->getEndpoint();
        }
        
        /**
         * FILTER Attributes & UPDATE EXISTING
         *
         * @var \WC_Product_Attribute $productSpecific
         */
        foreach ($productSpecifics as $slug => $productSpecific) {
            if (!preg_match('/^pa_/', $slug)) {
                $current[$slug] = [
                    'name'         => $productSpecific->get_name(),
                    'value'        => implode(' ' . WC_DELIMITER . ' ', $productSpecific->get_options()),
                    'position'     => $productSpecific->get_position(),
                    'is_visible'   => $productSpecific->get_visible(),
                    'is_variation' => $productSpecific->get_variation(),
                    'is_taxonomy'  => $productSpecific->get_taxonomy(),
                ];
            } elseif (preg_match('/^pa_/', $slug)
                && array_key_exists($productSpecific->get_id(), $specificData)) {
                $cOptions = $specificData[$productSpecific->get_id()]['options'];
                $cOldOptions = $productSpecific->get_options();
                unset($specificData[$slug]);
                $current[$slug] = [
                    'name'         => $productSpecific->get_name(),
                    'value'        => '',
                    'position'     => $productSpecific->get_position(),
                    'is_visible'   => $productSpecific->get_visible(),
                    'is_variation' => $productSpecific->get_variation(),
                    'is_taxonomy'  => $productSpecific->get_taxonomy(),
                ];
                foreach ($cOldOptions as $value) {
                    wp_remove_object_terms($product->getId()->getEndpoint(), $value, $slug);
                }
                
                wp_set_post_terms($product->getId()->getEndpoint(), $cOptions, $slug, true);
            }
        }
        
        foreach ($specificData as $key => $specific) {
            $slug = wc_attribute_taxonomy_name_by_id($key);
            //$attribute = (array)wc_get_attribute($key);
            $current[$slug] = [
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
                    $values[] = get_term_by('id', $valId, $slug)->slug;
                }
            }
            wp_set_object_terms($wcProduct->get_id(), $values, $slug, true);
        }
        
        if (!empty($current)) {
            \update_post_meta($wcProduct->get_id(), '_product_attributes', $current);
        }
    }
    // </editor-fold>
}
