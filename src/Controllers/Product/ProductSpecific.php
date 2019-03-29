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
    // <editor-fold defaultstate="collapsed" desc="Pull">
    public function pullData(
        ProductModel $model,
        \WC_Product $product,
        \WC_Product_Attribute $attribute,
        $slug
    ) {
        $name = $attribute->get_name();
        $productAttribute = $product->get_attribute($name);
        $results = [];
        $values = array_map('trim', explode(',', $productAttribute));
        
        foreach ($values as $value) {
            if (empty($value)) {
                continue;
            }
            $results[] = $this->buildProductSpecific($slug, $value, $model);
        }
        
        return $results;
    }
    
    // </editor-fold>
    
    // <editor-fold defaultstate="collapsed" desc="Push">
    
    // </editor-fold>
    
    // <editor-fold defaultstate="collapsed" desc="Methods">
    /**
     * Returns Id for taxonomy
     *
     * @param $slug
     * @return string
     */
    public function getSpecificId($slug)
    {
        $name = substr($slug, 3);
        $val = $this->database->query(SqlHelper::getSpecificId($name));
        
        return isset($val[0]['attribute_id']) ? $val[0]['attribute_id'] : '';
    }
    
    private function buildProductSpecific($slug, $value, ProductModel $result)
    {
        $parent = (new ProductVariationSpecificAttribute);
        $valueId = $parent->getSpecificValueId($slug, $value);
        $specificId = (new Identity)->setEndpoint($this->getSpecificId($slug));
        
        $specific = (new ProductSpecificModel)
            ->setId($specificId)
            ->setProductId($result->getId())
            ->setSpecificValueId($valueId);
        
        return $specific;
    }
    // </editor-fold>
}
