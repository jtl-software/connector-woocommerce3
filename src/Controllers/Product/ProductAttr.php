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
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;

class ProductAttr extends BaseController
{
    const DELIVERY_TIME_ATTR = 'wc_dt_offset';
    const DOWNLOADABLE_ATTR = 'wc_downloadable';
    const FACEBOOK_VISIBILITY_ATTR = 'wc_fb_visibility';
    const FACEBOOK_SYNC_STATUS_ATTR = 'wc_fb_sync_status';
    const PAYABLE_ATTR = 'wc_payable';
    const NOSEARCH_ATTR = 'wc_nosearch';
    const VIRTUAL_ATTR = 'wc_virtual';
    
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
                    ->setProductAttrId(new Identity(self::PAYABLE_ATTR))
                    ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage())
                    ->setName(self::PAYABLE_ATTR)
                    ->setValue('false');
                
                $productAttributes[] = (new ProductAttrModel())
                    ->setId(new Identity(self::PAYABLE_ATTR))
                    ->setIsCustomProperty(true)
                    ->addI18n($attrI18n);
            }
        }
    }
    
    private function setProductFunctionAttributes(\WC_Product $product, $productAttributes)
    {
        $functionAttributes = [
            $this->getDeliveryTimeFunctionAttribute($product),
            $this->getDownloadableFunctionAttribute($product),
            $this->getPayableFunctionAttribute($product),
            $this->getNoSearchFunctionAttribute($product),
            $this->getVirtualFunctionAttribute($product),
        ];
        
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_FB_FOR_WOO)) {
            /*  $functionAttributes[] = $this->getFacebookVisibilityFunctionAttribute($product);*/
            $functionAttributes[] = $this->getFacebookSyncStatusFunctionAttribute($product);
        }
        
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
    
    private function getDeliveryTimeFunctionAttribute(\WC_Product $product)
    {
        $i18n = (new ProductAttrI18nModel())
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
    
    private function getPayableFunctionAttribute(\WC_Product $product)
    {
        $value = strcmp(get_post_status($product->get_id()), 'private') !== 0 ? 'true' : 'false';
        
        $i18n = (new ProductAttrI18nModel())
            ->setProductAttrId(new Identity($product->get_id() . '_' . self::PAYABLE_ATTR))
            ->setName(self::PAYABLE_ATTR)
            ->setValue((string)$value)
            ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage());
        
        $attribute = (new ProductAttrModel())
            ->setId($i18n->getProductAttrId())
            ->setProductId(new Identity($product->get_id()))
            ->setIsCustomProperty(false)
            ->addI18n($i18n);
        
        return $attribute;
    }
    
    private function getNoSearchFunctionAttribute(\WC_Product $product)
    {
        $visibility = get_post_meta($product->get_id(), '_visibility');
        
        if (count($visibility) > 0 && strcmp($visibility[0], 'catalog') === 0) {
            $value = 'true';
        } else {
            $value = 'false';
        }
        
        $i18n = (new ProductAttrI18nModel())
            ->setProductAttrId(new Identity($product->get_id() . '_' . self::NOSEARCH_ATTR))
            ->setName(self::NOSEARCH_ATTR)
            ->setValue((string)$value)
            ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage());
        
        $attribute = (new ProductAttrModel())
            ->setId($i18n->getProductAttrId())
            ->setProductId(new Identity($product->get_id()))
            ->setIsCustomProperty(false)
            ->addI18n($i18n);
        
        return $attribute;
    }
    
    /* private function getFacebookVisibilityFunctionAttribute(\WC_Product $product)
     {
         $value = 'false';
         $visibility = get_post_meta($product->get_id(), 'fb_visibility');
         
         if (count($visibility) > 0 && strcmp($visibility[0], '1') === 0) {
             $value = 'true';
         }
         
         $i18n = (new ProductAttrI18nModel())
             ->setProductAttrId(new Identity($product->get_id() . '_' . self::FACEBOOK_VISIBILITY_ATTR))
             ->setName(self::FACEBOOK_VISIBILITY_ATTR)
             ->setValue((string)$value)
             ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage());
         
         $attribute = (new ProductAttrModel())
             ->setId($i18n->getProductAttrId())
             ->setProductId(new Identity($product->get_id()))
             ->setIsCustomProperty(false)
             ->addI18n($i18n);
         
         return $attribute;
     }*/
    
    private function getFacebookSyncStatusFunctionAttribute(\WC_Product $product)
    {
        $value  = 'false';
        $status = get_post_meta($product->get_id(), 'fb_sync_status');
        
        if (count($status) > 0 && strcmp($status[0], '1') === 0) {
            $value = 'true';
        }
        
        $i18n = (new ProductAttrI18nModel())
            ->setProductAttrId(new Identity($product->get_id() . '_' . self::FACEBOOK_SYNC_STATUS_ATTR))
            ->setName(self::FACEBOOK_SYNC_STATUS_ATTR)
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
        $payable      = false;
        $nosearch     = false;
        $fbStatusCode = false;
        /* $fbVisibility = false;*/
        
        $productId = $product->getId()->getEndpoint();
        
        foreach ($pushedAttributes as $key => $pushedAttribute) {
            foreach ($pushedAttribute->getI18ns() as $i18n) {
                if ( ! Util::getInstance()->isWooCommerceLanguage($i18n->getLanguageISO())) {
                    continue;
                }
                
                $attrName = strtolower(trim($i18n->getName()));
                
                if (preg_match('/^(wc_)[a-zA-Z\_]+$/', $attrName)
                    || in_array($attrName, ['nosearch', 'payable'])
                ) {
                    if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_FB_FOR_WOO)) {
                        if (strcmp($attrName, self::FACEBOOK_SYNC_STATUS_ATTR) === 0) {
                            $value = strcmp(trim($i18n->getValue()), 'true') === 0;
                            $value = $value ? '1' : '';
                            
                            if ( ! add_post_meta(
                                $productId,
                                substr($attrName, 3),
                                $value,
                                true
                            )) {
                                update_post_meta(
                                    $productId,
                                    substr($attrName, 3),
                                    $value
                                );
                            }
                            $fbStatusCode = true;
                        }
                        
                        /* if (strcmp($attrName, self::FACEBOOK_VISIBILITY_ATTR) === 0) {
                             $value = strcmp(trim($i18n->getValue()), 'true') === 0;
                             $value = $value ? '1' : '0';
                             
                             if (!add_post_meta(
                                 $productId,
                                 substr($attrName, 3),
                                 $value,
                                 true
                             )) {
                                 update_post_meta(
                                     $productId,
                                     substr($attrName, 3),
                                     $value
                                 );
                             }
                             $fbVisibility = true;
                         }*/
                    }
                    
                    if (strcmp($attrName, self::DOWNLOADABLE_ATTR) === 0) {
                        $value = strcmp(trim($i18n->getValue()), 'true') === 0;
                        $value = $value ? 'yes' : 'no';
                        
                        if ( ! add_post_meta(
                            $productId,
                            substr($attrName, 2),
                            $value,
                            true
                        )) {
                            update_post_meta(
                                $productId,
                                substr($attrName, 2),
                                $value
                            );
                        }
                        $downloadable = true;
                    }
                    
                    if (strcmp($attrName, self::VIRTUAL_ATTR) === 0) {
                        $value = strcmp(trim($i18n->getValue()), 'true') === 0;
                        $value = $value ? 'yes' : 'no';
                        
                        if ( ! add_post_meta(
                            $productId,
                            substr($attrName, 2),
                            $value,
                            true
                        )) {
                            update_post_meta(
                                $productId,
                                substr($attrName, 2),
                                $value
                            );
                        }
                        
                        $virtual = true;
                    }
                    
                    if ($attrName === self::PAYABLE_ATTR || $attrName === 'payable') {
                        if (strcmp(trim($i18n->getValue()), 'false') === 0) {
                            \wp_update_post(['ID' => $productId, 'post_status' => 'private']);
                            $payable = true;
                        }
                    }
                    
                    if ($attrName === self::NOSEARCH_ATTR || $attrName === 'nosearch') {
                        if (strcmp(trim($i18n->getValue()), 'true') === 0) {
                            \update_post_meta($productId, '_visibility', 'catalog');
                            
                            /*
                            "   exclude-from-catalog"
                            "   exclude-from-search"
                            "   featured"
                            "   outofstock"
                            */
                            wp_set_object_terms($productId, ['exclude-from-search'], 'product_visibility', true);
                            $nosearch = true;
                        }
                    }
                    
                    unset($pushedAttributes[$key]);
                }
            }
        }
        
        //Revert
        if ( ! $virtual) {
            if ( ! add_post_meta(
                $productId,
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
                $productId,
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
        
        if ( ! $nosearch) {
            
            if ( ! add_post_meta(
                $productId,
                '_visibility',
                'visible',
                true
            )) {
                update_post_meta(
                    $productId,
                    '_visibility',
                    'visible'
                );
            }
            wp_remove_object_terms($productId, ['exclude-from-search'], 'product_visibility');
        }
        
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_FB_FOR_WOO)) {
            if ( ! $fbStatusCode) {
                if ( ! add_post_meta(
                    $productId,
                    substr(self::FACEBOOK_SYNC_STATUS_ATTR, 3),
                    '',
                    true
                )) {
                    update_post_meta(
                        $productId,
                        substr(self::FACEBOOK_SYNC_STATUS_ATTR, 3),
                        ''
                    );
                }
            }
            
            /*if (!$fbVisibility) {
                if (!add_post_meta(
                    $productId,
                    substr(self::FACEBOOK_VISIBILITY_ATTR, 3),
                    '1',
                    true
                )) {
                    update_post_meta(
                        $productId,
                        substr(self::FACEBOOK_VISIBILITY_ATTR, 3),
                        '1'
                    );
                }
            }*/
        }
        
        if ( ! $payable) {
            \wp_update_post(['ID' => $productId, 'post_status' => 'publish']);
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
                
                $this->saveAttribute($attribute, $i18n, $attributes);
                break;
            }
        }
        
        
        if ( ! empty($attributes)) {
            \update_post_meta($productId, '_product_attributes', $attributes);
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
     * @param array                $attributes The product attributes.
     */
    private function saveAttribute(
        ProductAttrModel $attribute,
        ProductAttrI18nModel $i18n,
        array &$attributes
    ) {
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
