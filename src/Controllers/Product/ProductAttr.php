<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2018 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Product;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\Model\ProductAttr as ProductAttrModel;
use jtl\Connector\Model\ProductAttrI18n as ProductAttrI18nModel;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\Util;

class ProductAttr extends BaseController
{
    const PAYABLE = 'payable';
    const NOSEARCH = 'nosearch';
    
    // <editor-fold defaultstate="collapsed" desc="Pull">
    public function pullData(\WC_Product $product)
    {
        $productAttributes = [];
        
        $attributes = $product->get_attributes();
        
        /**
         * @var string                $slug
         * @var \WC_Product_Attribute $attribute
         */
        foreach ($attributes as $slug => $attribute) {
            
            $var  = $attribute->get_variation();
            $taxe = taxonomy_exists($slug);
            
            // No variations and no specifics
            if ($var || $taxe) {
                continue;
            }
            
            $productAttributes[] = $this->buildAttribute($product, $attribute, $slug);
        }
        
        $this->handleCustomPropertyAttributes($product, $productAttributes);
        $productAttributes = $this->setProductFunctionAttributes($product, $productAttributes);
        
        return $productAttributes;
    }
    
    private function buildAttribute(\WC_Product $product, \WC_Product_Attribute $attribute, $slug)
    {
        $productAttribute = $product->get_attribute($attribute->get_name());
        
        // Divided by |
        $values = explode(WC_DELIMITER, $productAttribute);
        
        $i18n = (new ProductAttrI18nModel())
            ->setProductAttrId(new Identity($slug))
            ->setName($attribute->get_name())
            ->setValue(implode(', ', $values))
            ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage());
        
        return (new ProductAttrModel())
            ->setId($i18n->getProductAttrId())
            ->setProductId(new Identity($product->get_id()))
            ->setIsCustomProperty($attribute->is_taxonomy())
            ->addI18n($i18n);
    }
    
    private function handleCustomPropertyAttributes(\WC_Product $product, array &$productAttributes)
    {
        if ( ! $product->is_purchasable()) {
            $isPurchasable = false;
            
            if ($product->has_child()) {
                $isPurchasable = true;
                
                foreach ($product->get_children() as $childId) {
                    $child         = \wc_get_product($childId);
                    $isPurchasable = $isPurchasable & $child->is_purchasable();
                }
            }
            
            if ( ! $isPurchasable) {
                $attrI18n = (new ProductAttrI18nModel())
                    ->setProductAttrId(new Identity(self::PAYABLE))
                    ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage())
                    ->setName(self::PAYABLE)
                    ->setValue('false');
                
                $productAttributes[] = (new ProductAttrModel())
                    ->setId(new Identity(self::PAYABLE))
                    ->setIsCustomProperty(true)
                    ->addI18n($attrI18n);
            }
        }
    }
    
    private function setProductFunctionAttributes(\WC_Product $product, $productAttributes)
    {
        
        $functionAttributes = [
            $this->getVirtualFunctionAttribute($product),
            $this->getDownloadableFunctionAttribute($product),
            $this->getDeliveryTimeFunctionAttribute($product),
        ];
        
        foreach ($functionAttributes as $functionAttribute) {
            array_push($productAttributes, $functionAttribute);
        }
        
        return $productAttributes;
    }
    
    private function getVirtualFunctionAttribute(\WC_Product $product)
    {
        $value = $product->is_virtual() ? 'true' : 'false';
        $i18n  = (new ProductAttrI18nModel())
            ->setProductAttrId(new Identity($product->get_id() . '_wc_virtual'))
            ->setName('wc_virtual')
            ->setValue((string)$value)
            ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage());
        
        $attribute = (new ProductAttrModel())
            ->setId($i18n->getProductAttrId())
            ->setProductId(new Identity($product->get_id()))
            ->setIsCustomProperty(false)
            ->addI18n($i18n);
        
        return $attribute;
    }

    private function  getDeliveryTimeFunctionAttribute(\WC_Product $product)
    {
        $i18n  = (new ProductAttrI18nModel())
            ->setProductAttrId(new Identity($product->get_id() . '_wc_dt_offset'))
            ->setName('wc_dt_offset')
            ->setValue((string)0)
            ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage());
        
        $attribute = (new ProductAttrModel())
            ->setId($i18n->getProductAttrId())
            ->setProductId(new Identity($product->get_id()))
            ->setIsCustomProperty(false)
            ->addI18n($i18n);
        
        return $attribute;
    }
    
    private function getDownloadableFunctionAttribute(\WC_Product $product)
    {
        $value = $product->is_downloadable() ? 'true' : 'false';
        $i18n  = (new ProductAttrI18nModel())
            ->setProductAttrId(new Identity($product->get_id() . '_wc_downloadable'))
            ->setName('wc_downloadable')
            ->setValue((string)$value)
            ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage());
        
        $attribute = (new ProductAttrModel())
            ->setId($i18n->getProductAttrId())
            ->setProductId(new Identity($product->get_id()))
            ->setIsCustomProperty(false)
            ->addI18n($i18n);
        
        return $attribute;
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
        
        $attributes       = $this->getVariationAndSpecificAttributes($wcProduct);
        $pushedAttributes = $product->getAttributes();
        
        //FUNCTION ATTRIBUTES BY JTL
        $virtual      = false;
        $downloadable = false;
        
        foreach ($pushedAttributes as $key => $pushedAttribute) {
            foreach ($pushedAttribute->getI18ns() as $i18n) {
                if ( ! Util::getInstance()->isWooCommerceLanguage($i18n->getLanguageISO())) {
                    continue;
                }
                
                if (preg_match('/^(wc_)[a-zA-Z\_]+$/', trim($i18n->getName()))) {
                    
                    if (strcmp(trim($i18n->getName()), 'wc_virtual') === 0
                        || strcmp(trim($i18n->getName()), 'wc_downloadable') === 0
                    ) {
                        
                        if (strcmp(trim($i18n->getName()), 'wc_virtual') === 0) {
                            $virtual = true;
                        }
                        
                        if (strcmp(trim($i18n->getName()), 'wc_downloadable') === 0) {
                            $downloadable = true;
                        }
                        
                        $value = strcmp(trim($i18n->getValue()), 'true') === 0;
                        $value = $value ? 'yes' : 'no';
                        
                        if ( ! add_post_meta(
                            $product->getId()->getEndpoint(),
                            substr(trim($i18n->getName()), 2),
                            $value,
                            true
                        )) {
                            update_post_meta(
                                $product->getId()->getEndpoint(),
                                substr(trim($i18n->getName()), 2),
                                $value
                            );
                        }
                    }
                    
                    unset($pushedAttributes[$key]);
                }
            }
        }
        
        if ( ! $virtual) {
            if ( ! add_post_meta(
                $product->getId()->getEndpoint(),
                '_virtual',
                'no',
                true
            )) {
                update_post_meta(
                    $product->getId()->getEndpoint(),
                    '_virtual',
                    'no'
                );
            }
        }
        
        if ( ! $downloadable) {
            if ( ! add_post_meta(
                $product->getId()->getEndpoint(),
                '_downloadable',
                'no',
                true
            )) {
                update_post_meta(
                    $product->getId()->getEndpoint(),
                    '_downloadable',
                    'no'
                );
            }
        }
        
        foreach ($attributes as $key => $attr) {
            if ($attr['is_variation'] === true || $attr['is_variation'] === false && $attr['value'] === '') {
                continue;
            }
            $tmp = false;
            
            foreach ($pushedAttributes as $pushedAttribute) {
                if ($attr->id == $pushedAttribute->getId()->getEndpoint()) {
                    $tmp = true;
                }
            }
            
            if ($tmp) {
                unset($attributes[$key]);
            }
        }
        
        foreach ($pushedAttributes as $attribute) {
            if ( ! Util::sendCustomPropertiesEnabled() && $attribute->getIsCustomProperty() === true) {
                continue;
            }
            
            foreach ($attribute->getI18ns() as $i18n) {
                if ( ! Util::getInstance()->isWooCommerceLanguage($i18n->getLanguageISO())) {
                    continue;
                }
                
                $this->saveAttribute($attribute, $i18n, $wcProduct->get_id(), $attributes);
                break;
            }
        }
        
        
        if ( ! empty($attributes)) {
            \update_post_meta($wcProduct->get_id(), '_product_attributes', $attributes);
        }
    }
    
    /**
     * Get variation attributes as they will be overwritten if they are not added again.
     *
     * @param \WC_Product $product The product.
     *
     * @return array The variation attributes.
     */
    private function getVariationAndSpecificAttributes(\WC_Product $product)
    {
        $attributes = [];
        
        $currentAttributes = $product->get_attributes();
        
        /**
         * @var string                $slug The attributes unique slug.
         * @var \WC_Product_Attribute $attribute The attribute.
         */
        foreach ($currentAttributes as $slug => $attribute) {
            if ($attribute->get_variation()) {
                $attributes[$slug] = [
                    'id'           => $attribute->get_id(),
                    'name'         => $attribute->get_name(),
                    'value'        => implode(' ' . WC_DELIMITER . ' ', $attribute->get_options()),
                    'position'     => $attribute->get_position(),
                    'is_visible'   => $attribute->get_visible(),
                    'is_variation' => $attribute->get_variation(),
                    'is_taxonomy'  => $attribute->get_taxonomy(),
                ];
            } elseif (taxonomy_exists($slug)) {
                $attributes[$slug] =
                    [
                        'id'           => $attribute->get_id(),
                        'name'         => $attribute->get_name(),
                        'value'        => '',
                        'position'     => $attribute->get_position(),
                        'is_visible'   => $attribute->get_visible(),
                        'is_variation' => $attribute->get_variation(),
                        'is_taxonomy'  => $attribute->get_taxonomy(),
                    ];
            }
        }
        
        return $attributes;
    }
    
    /**
     * Check if the attribute is a custom property or a simple attribute and save it regarding to that fact.
     *
     * @param ProductAttrModel     $attribute The attribute.
     * @param ProductAttrI18nModel $i18n The used language attribute.
     * @param string               $productId The product id.
     * @param array                $attributes The product attributes.
     */
    private function saveAttribute(
        ProductAttrModel $attribute,
        ProductAttrI18nModel $i18n,
        $productId,
        array &$attributes
    ) {
        if (strtolower($i18n->getName()) === strtolower(self::PAYABLE)) {
            \wp_update_post(['ID' => $productId, 'post_status' => 'private']);
            
            return;
        } elseif (strtolower($i18n->getName()) === strtolower(self::NOSEARCH)) {
            \update_post_meta($productId, '_visibility', 'catalog');
            
            return;
        }
        
        $this->addNewAttributeOrEditExisting($i18n, [
            'name'             => \wc_clean($i18n->getName()),
            'value'            => \wc_clean($i18n->getValue()),
            'isCustomProperty' => $attribute->getIsCustomProperty(),
        ], $attributes);
    }
    
    private function addNewAttributeOrEditExisting(ProductAttrI18nModel $i18n, array $data, array &$attributes)
    {
        $slug = \wc_sanitize_taxonomy_name($i18n->getName());
        
        if (isset($attributes[$slug])) {
            $this->editAttribute($slug, $i18n->getValue(), $attributes);
        } else {
            $this->addAttribute($slug, $data, $attributes);
        }
    }
    
    private function editAttribute($slug, $value, array &$attributes)
    {
        $values                     = explode(',', $attributes[$slug]['value']);
        $values[]                   = \wc_clean($value);
        $attributes[$slug]['value'] = implode(' | ', $values);
    }
    
    private function addAttribute($slug, array $data, array &$attributes)
    {
        $attributes[$slug] = [
            'name'         => $data['name'],
            'value'        => $data['value'],
            'position'     => 0,
            'is_visible'   => 1,
            'is_variation' => 0,
            'is_taxonomy'  => 0,
        ];
    }
    // </editor-fold>
}
