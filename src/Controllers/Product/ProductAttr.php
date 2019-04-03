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
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;

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
    
    /**
     * @param $productId
     * @param $pushedAttributes
     * @param $attributesFilteredVariationsAndSpecifics
     * @return mixed
     */
    public function pushData($productId, $pushedAttributes, $attributesFilteredVariationsAndSpecifics)
    {
        //  $parent = (new ProductVariationSpecificAttribute);
        //FUNCTION ATTRIBUTES BY JTL
        $virtual = false;
        $downloadable = false;
        $digital = false;
        $payable = false;
        $nosearch = false;
        $fbStatusCode = false;
        /* $fbVisibility = false;*/
        
        /** @var  ProductAttrModel $pushedAttribute */
        foreach ($pushedAttributes as $key => $pushedAttribute) {
            foreach ($pushedAttribute->getI18ns() as $i18n) {
                if (!Util::getInstance()->isWooCommerceLanguage($i18n->getLanguageISO())) {
                    continue;
                }
                
                $attrName = strtolower(trim($i18n->getName()));
                
                if (preg_match('/^(wc_)[a-zA-Z\_]+$/', $attrName)
                    || in_array($attrName, ['nosearch', 'payable'])
                ) {
                    if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_FB_FOR_WOO)) {
                        if (strcmp($attrName, ProductVariationSpecificAttribute::FACEBOOK_SYNC_STATUS_ATTR) === 0) {
                            $value = strcmp(trim($i18n->getValue()), 'true') === 0;
                            $value = $value ? '1' : '';
                            
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
                    
                    if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_GERMAN_MARKET)) {
                        if (strcmp($attrName, ProductVariationSpecificAttribute::DIGITAL_GM_ATTR) === 0) {
                            $value = strcmp(trim($i18n->getValue()), 'true') === 0;
                            $value = $value ? 'yes' : 'no';
                            
                            if (!add_post_meta(
                                $productId,
                                substr($attrName, 5),
                                $value,
                                true
                            )) {
                                update_post_meta(
                                    $productId,
                                    substr($attrName, 5),
                                    $value
                                );
                            }
                            $digital = true;
                        }
                    }
                    
                    if (strcmp($attrName, ProductVariationSpecificAttribute::DOWNLOADABLE_ATTR) === 0) {
                        $value = strcmp(trim($i18n->getValue()), 'true') === 0;
                        $value = $value ? 'yes' : 'no';
                        
                        if (!add_post_meta(
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
                    
                    if (strcmp($attrName, ProductVariationSpecificAttribute::VIRTUAL_ATTR) === 0) {
                        $value = strcmp(trim($i18n->getValue()), 'true') === 0;
                        $value = $value ? 'yes' : 'no';
                        
                        if (!add_post_meta(
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
                    
                    if ($attrName === ProductVariationSpecificAttribute::PAYABLE_ATTR || $attrName === 'payable') {
                        if (strcmp(trim($i18n->getValue()), 'false') === 0) {
                            \wp_update_post(['ID' => $productId, 'post_status' => 'private']);
                            $payable = true;
                        }
                    }
                    
                    if ($attrName === ProductVariationSpecificAttribute::NOSEARCH_ATTR || $attrName === 'nosearch') {
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
        if (!$virtual) {
            if (!add_post_meta(
                $productId,
                '_virtual',
                'no',
                true
            )) {
                update_post_meta(
                    $productId,
                    '_virtual',
                    'no'
                );
            }
        }
    
        if (!$downloadable) {
            if (!add_post_meta(
                $productId,
                '_downloadable',
                'no',
                true
            )) {
                update_post_meta(
                    $productId,
                    '_downloadable',
                    'no'
                );
            }
        }
        
        if (!$nosearch) {
            
            if (!add_post_meta(
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
        
        if(SupportedPlugins::isActive(SupportedPlugins::PLUGIN_GERMAN_MARKET)){
            if (!$digital) {
                if (!add_post_meta(
                    $productId,
                    '_digital',
                    'no',
                    true
                )) {
                    update_post_meta(
                        $productId,
                        '_digital',
                        'no'
                    );
                }
            }
        }
        
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_FB_FOR_WOO)) {
            if (!$fbStatusCode) {
                if (!add_post_meta(
                    $productId,
                    substr(ProductVariationSpecificAttribute::FACEBOOK_SYNC_STATUS_ATTR, 3),
                    '',
                    true
                )) {
                    update_post_meta(
                        $productId,
                        substr(ProductVariationSpecificAttribute::FACEBOOK_SYNC_STATUS_ATTR, 3),
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
        
        if (!$payable) {
            \wp_update_post(['ID' => $productId, 'post_status' => 'publish']);
        }
        
        foreach ($attributesFilteredVariationsAndSpecifics as $key => $attr) {
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
                unset($attributesFilteredVariationsAndSpecifics[$key]);
            }
        }
        
        /** @var ProductAttrModel $attribute */
        foreach ($pushedAttributes as $attribute) {
            $result = null;
            if (!Util::sendCustomPropertiesEnabled() && $attribute->getIsCustomProperty() === true) {
                continue;
            }
            
            foreach ($attribute->getI18ns() as $i18n) {
                if (!Util::getInstance()->isWooCommerceLanguage($i18n->getLanguageISO())) {
                    continue;
                }
                
                $this->saveAttribute($attribute, $i18n, $attributesFilteredVariationsAndSpecifics);
                break;
            }
        }
        
        return $attributesFilteredVariationsAndSpecifics;
    }
    
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
    
    private function saveAttribute(ProductAttrModel $attribute, ProductAttrI18nModel $i18n, array &$attributes)
    {
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
        $values = explode(',', $attributes[$slug]['value']);
        $values[] = \wc_clean($value);
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
