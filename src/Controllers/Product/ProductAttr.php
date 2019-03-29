<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2018 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Product;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\ProductAttr as ProductAttrModel;
use jtl\Connector\Model\ProductAttrI18n as ProductAttrI18nModel;
use JtlWooCommerceConnector\Controllers\BaseController;

class ProductAttr extends BaseController
{
    // <editor-fold defaultstate="collapsed" desc="Pull">
    public function pullData(
        \WC_Product $product,
        \WC_Product_Attribute $attribute,
        $slug,
        $languageIso
    ) {
        return $this->buildAttribute(
            $product,
            $attribute,
            $slug,
            $languageIso
        );
    }
    // </editor-fold>
    
    // <editor-fold defaultstate="collapsed" desc="Push">
    
    // </editor-fold>
    
    // <editor-fold defaultstate="collapsed" desc="Methods">
    /**
     * @param \WC_Product $product
     * @param \WC_Product_Attribute $attribute
     * @param $slug
     * @param string $languageIso
     * @return ProductAttrModel
     */
    private function buildAttribute(
        \WC_Product $product,
        \WC_Product_Attribute $attribute,
        $slug,
        $languageIso
    ) {
        $productAttribute = $product->get_attribute($attribute->get_name());
        
        // Divided by |
        $values = explode(WC_DELIMITER, $productAttribute);
        
        $i18n = (new ProductAttrI18nModel)
            ->setProductAttrId(new Identity($slug))
            ->setName($attribute->get_name())
            ->setValue(implode(', ', $values))
            ->setLanguageISO($languageIso);
        
        return (new ProductAttrModel)
            ->setId($i18n->getProductAttrId())
            ->setProductId(new Identity($product->get_id()))
            ->setIsCustomProperty($attribute->is_taxonomy())
            ->addI18n($i18n);
    }
    // </editor-fold>
   
}
