<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Product;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\Model\ProductAttr as ProductAttrModel;
use jtl\Connector\Model\ProductAttrI18n as ProductAttrI18nModel;
use jtl\Connector\Model\ProductSpecific as ProductSpecificModel;
use jtl\Connector\Model\ProductVariation as ProductVariationModel;
use jtl\Connector\Model\ProductVariationI18n as ProductVariationI18nModel;
use jtl\Connector\Model\ProductVariationValue as ProductVariationValueModel;
use jtl\Connector\Model\ProductVariationValueI18n as ProductVariationValueI18nModel;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Logger\WpErrorLogger;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\SupportedPlugins as SupportedPluginsAlias;
use JtlWooCommerceConnector\Utilities\Util;
use WP_Error;

if (defined('WC_DELIMITER')) {
    define('WC_DELIMITER', '|');
}

class ProductVariationSpecificAttribute extends BaseController
{
    const DELIVERY_TIME_ATTR = 'wc_dt_offset';
    const DOWNLOADABLE_ATTR = 'wc_downloadable';
    const FACEBOOK_VISIBILITY_ATTR = 'wc_fb_visibility';
    const FACEBOOK_SYNC_STATUS_ATTR = 'wc_fb_sync_status';
    const PAYABLE_ATTR = 'wc_payable';
    const NOSEARCH_ATTR = 'wc_nosearch';
    const VIRTUAL_ATTR = 'wc_virtual';
    
    private $productData = [
        'productVariation'  => [],
        'productAttributes' => [],
        'productSpecifics'  => [],
    ];
    
    private $values = [];
    
    public function pullData(\WC_Product $product, ProductModel $model)
    {
        $globCurrentAttr = $product->get_attributes();
        $isProductVariation = $product instanceof \WC_Product_Variation;
        $isProductVariationParent = $product instanceof \WC_Product_Variable;
        $languageIso = Util::getInstance()->getWooCommerceLanguage();
        
        if (!$isProductVariation) {
            /**
             * @var string $slug
             * @var \WC_Product_Attribute $attribute
             */
            foreach ($globCurrentAttr as $slug => $attribute) {
                
                $isVariation = $attribute->get_variation();
                $taxonomyExistsCurrentAttr = taxonomy_exists($slug);
                
                // <editor-fold defaultstate="collapsed" desc="Handling ATTR Pull">
                if (!$isVariation && !$taxonomyExistsCurrentAttr) {
                    $this->productData['productAttributes'][] = (new ProductAttr)
                        ->pullData(
                            $product,
                            $attribute,
                            $slug,
                            $languageIso
                        );
                }
                // </editor-fold>
                // <editor-fold defaultstate="collapsed" desc="Handling Specific Pull">
                if (!$isVariation && $taxonomyExistsCurrentAttr) {
                    $tmp = (new ProductSpecific)
                        ->pullData(
                            $model,
                            $product,
                            $attribute,
                            $slug
                        );
                    if (is_null($tmp)) {
                        continue;
                    }
                    foreach ($tmp as $productSpecific) {
                        $this->productData['productSpecifics'][] = $productSpecific;
                    }
                }
                // </editor-fold>
                // <editor-fold defaultstate="collapsed" desc="Handling Variation Parent Pull">
                
                if ($isVariation && $isProductVariationParent) {
                    $tmp = (new ProductVariation)
                        ->pullDataParent(
                            $model,
                            $attribute,
                            $languageIso
                        );
                    if (is_null($tmp)) {
                        continue;
                    }
                    $this->productData['productVariation'][] = $tmp;
                }
                
                // </editor-fold>
            }
        } else {
            // <editor-fold defaultstate="collapsed" desc="Handling Variation Child Pull">
            $tmp = (new ProductVariation)
                ->pullDataChild(
                    $product,
                    $model,
                    $languageIso
                );
            if (!is_null($tmp)) {
                $this->productData['productVariation'][] = $tmp;
            }
            // </editor-fold>
        }
        
        // <editor-fold defaultstate="collapsed" desc="FUNC ATTR Pull">
        $this->handleCustomPropertyAttributes($product, $languageIso);
        $this->setProductFunctionAttributes($product, $languageIso);
        
        // </editor-fold>
        
        return $this->productData;
    }
    
    public function pushDataNew(ProductModel $product, \WC_Product $wcProduct)
    {
        if ($wcProduct === false) {
            return;
        }
        //Identify Master/Child
        $isMaster = $product->getIsMasterProduct();
        
        //New Values
        $pushedAttributes = $product->getAttributes();
        $pushedSpecifics = $product->getSpecifics();
        $pushedVariations = $product->getVariations();
        
        $productId = $product->getId()->getEndpoint();
        
        if ($isMaster) {
            $newProductAttributes = [];
            //Current Values
            $curAttributes = $wcProduct->get_attributes();
            
            //Filtered
            $attributesFilteredVariationsAndSpecifics = $this->getVariationAndSpecificAttributes(
                $curAttributes
            );
            $attributesFilteredVariationSpecifics = $this->getVariationAttributes(
                $curAttributes
            );
            
            //GENERATE DATA ARRAYS
            $variationSpecificData = $this->generateVariationSpecificData($pushedVariations);
            $specificData = $this->generateSpecificData($pushedSpecifics);
            
            //handleAttributes
            $finishedAttr = $this->handleAttributes(
                $productId,
                $pushedAttributes,
                $attributesFilteredVariationsAndSpecifics
            );
            $this->mergeAttributes($newProductAttributes, $finishedAttr);
            // handleVarSpecifics
            $finishedSpecifics = $this->handleSpecifics(
                $productId, $curAttributes, $specificData, $pushedSpecifics
            );
            $this->mergeAttributes($newProductAttributes, $finishedSpecifics);
            // handleVarSpecifics
            $finishedVarSpecifics = $this->handleMasterVariationSpecifics(
                $productId,
                $variationSpecificData,
                $attributesFilteredVariationSpecifics
            );
            $this->mergeAttributes($newProductAttributes, $finishedVarSpecifics);
            $old = \get_post_meta($productId, '_product_attributes', true);
            $debug = \update_post_meta($productId, '_product_attributes', $newProductAttributes, $old);
            
            // remove the transient to renew the cache
            delete_transient('wc_attribute_taxonomies');
        } else {
            $this->handleChildVariation(
                $productId,
                $pushedVariations
            );
        }
    }
 
    
    // <editor-fold defaultstate="collapsed" desc="Filtered Methods">
    private function getVariationAndSpecificAttributes($attributes = [])
    {
        $filteredAttributes = [];
        
        /**
         * @var string $slug The attributes unique slug.
         * @var \WC_Product_Attribute $attribute The attribute.
         */
        foreach ($attributes as $slug => $attribute) {
            if ($attribute->get_variation()) {
                $filteredAttributes[$slug] = [
                    'id'           => $attribute->get_id(),
                    'name'         => $attribute->get_name(),
                    'value'        => implode(' ' . WC_DELIMITER . ' ', $attribute->get_options()),
                    'position'     => $attribute->get_position(),
                    'is_visible'   => $attribute->get_visible(),
                    'is_variation' => $attribute->get_variation(),
                    'is_taxonomy'  => $attribute->get_taxonomy(),
                ];
            } elseif (taxonomy_exists($slug)) {
                $filteredAttributes[$slug] =
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
        
        return $filteredAttributes;
    }
    
    private function getVariationAttributes($curAttributes)
    {
        $filteredAttributes = [];
        
        /**
         * @var string $slug
         * @var \WC_Product_Attribute $curAttributes
         */
        foreach ($curAttributes as $slug => $product_specific) {
            if (!$product_specific->get_variation()) {
                $filteredAttributes[$slug] = [
                    'name'         => $product_specific->get_name(),
                    'value'        => implode(' ' . WC_DELIMITER . ' ', $product_specific->get_options()),
                    'position'     => $product_specific->get_position(),
                    'is_visible'   => $product_specific->get_visible(),
                    'is_variation' => $product_specific->get_variation(),
                    'is_taxonomy'  => $product_specific->get_taxonomy(),
                ];
            }
        }
        
        return $filteredAttributes;
    }
    // </editor-fold>
    
    // <editor-fold defaultstate="collapsed" desc="GenerateData Methods">
    private function generateSpecificData($pushedSpecifics = [])
    {
        $specificData = [];
        foreach ($pushedSpecifics as $specific) {
            $specificData[(int)$specific->getId()->getEndpoint()]['options'][] =
                (int)$specific->getSpecificValueId()->getEndpoint();
        }
        
        return $specificData;
    }
    
    private function generateVariationSpecificData($pushedVariations = [])
    {
        $variationSpecificData = [];
        foreach ($pushedVariations as $variation) {
            /** @var ProductVariationI18nModel $variationI18n */
            foreach ($variation->getI18ns() as $variationI18n) {
                $taxonomyName = \wc_sanitize_taxonomy_name($variationI18n->getName());
                
                if (!Util::getInstance()->isWooCommerceLanguage($variationI18n->getLanguageISO())) {
                    continue;
                }
                
                $values = [];
                
                $this->values = $variation->getValues();
                usort($this->values, [$this, 'sortI18nValues']);
                
                foreach ($this->values as $vv) {
                    /** @var ProductVariationValueI18nModel $valueI18n */
                    foreach ($vv->getI18ns() as $valueI18n) {
                        if (!Util::getInstance()->isWooCommerceLanguage($valueI18n->getLanguageISO())) {
                            continue;
                        }
                        
                        $values[] = $valueI18n->getName();
                    }
                }
                
                $variationSpecificData[$taxonomyName] = [
                    'name'         => $variationI18n->getName(),
                    'value'        => implode(' ' . WC_DELIMITER . ' ', $values),
                    'position'     => $variation->getSort(),
                    'is_visible'   => 0,
                    'is_variation' => 1,
                    'is_taxonomy'  => 0,
                ];
            }
        }
        
        return $variationSpecificData;
    }
    // </editor-fold>
    
    // <editor-fold defaultstate="collapsed" desc="FuncAttr Methods">
    /**
     * @param \WC_Product $product
     * @param string $languageIso
     */
    private function handleCustomPropertyAttributes(\WC_Product $product, $languageIso = '')
    {
        if (!$product->is_purchasable()) {
            $isPurchasable = false;
            
            if ($product->has_child()) {
                $isPurchasable = true;
                
                foreach ($product->get_children() as $childId) {
                    $child = \wc_get_product($childId);
                    $isPurchasable = $isPurchasable & $child->is_purchasable();
                }
            }
            
            if (!$isPurchasable) {
                $attrI18n = (new ProductAttrI18nModel)
                    ->setProductAttrId(new Identity(self::PAYABLE_ATTR))
                    ->setLanguageISO($languageIso)
                    ->setName(self::PAYABLE_ATTR)
                    ->setValue('false');
                
                $this->productData['productAttributes'][] = (new ProductAttrModel)
                    ->setId(new Identity(self::PAYABLE_ATTR))
                    ->setIsCustomProperty(false)
                    ->addI18n($attrI18n);
            }
        }
    }
    
    /**
     * @param \WC_Product $product
     * @param string $languageIso
     */
    private function setProductFunctionAttributes(
        \WC_Product $product,
        $languageIso = ''
    ) {
        $functionAttributes = [
            $this->getDeliveryTimeFunctionAttribute(
                $product,
                $languageIso
            ),
            $this->getDownloadableFunctionAttribute(
                $product,
                $languageIso
            ),
            $this->getPayableFunctionAttribute(
                $product,
                $languageIso
            ),
            $this->getNoSearchFunctionAttribute(
                $product,
                $languageIso
            ),
            $this->getVirtualFunctionAttribute(
                $product,
                $languageIso
            ),
        ];
        
        if (SupportedPluginsAlias::isActive(SupportedPluginsAlias::PLUGIN_FB_FOR_WOO)) {
            /*  $functionAttributes[] = $this->getFacebookVisibilityFunctionAttribute($product);*/
            $functionAttributes[] = $this->getFacebookSyncStatusFunctionAttribute(
                $product,
                $languageIso
            );
        }
        
        foreach ($functionAttributes as $functionAttribute) {
            $this->productData['productAttributes'][] = $functionAttribute;
        }
    }
    
    private function getDeliveryTimeFunctionAttribute(\WC_Product $product, $languageIso = '')
    {
        $i18n = (new ProductAttrI18nModel)
            ->setProductAttrId(new Identity($product->get_id() . '_' . self::DELIVERY_TIME_ATTR))
            ->setName(self::DELIVERY_TIME_ATTR)
            ->setValue((string)0)
            ->setLanguageISO($languageIso);
        
        $attribute = (new ProductAttrModel)
            ->setId($i18n->getProductAttrId())
            ->setProductId(new Identity($product->get_id()))
            ->setIsCustomProperty(false)
            ->addI18n($i18n);
        
        return $attribute;
    }
    
    private function getDownloadableFunctionAttribute(\WC_Product $product, $languageIso = '')
    {
        $value = $product->is_downloadable() ? 'true' : 'false';
        $i18n = (new ProductAttrI18nModel)
            ->setProductAttrId(new Identity($product->get_id() . '_' . self::DOWNLOADABLE_ATTR))
            ->setName(self::DOWNLOADABLE_ATTR)
            ->setValue((string)$value)
            ->setLanguageISO($languageIso);
        
        $attribute = (new ProductAttrModel)
            ->setId($i18n->getProductAttrId())
            ->setProductId(new Identity($product->get_id()))
            ->setIsCustomProperty(false)
            ->addI18n($i18n);
        
        return $attribute;
    }
    
    private function getPayableFunctionAttribute(\WC_Product $product, $languageIso = '')
    {
        $value = strcmp(get_post_status($product->get_id()), 'private') !== 0 ? 'true' : 'false';
        
        $i18n = (new ProductAttrI18nModel)
            ->setProductAttrId(new Identity($product->get_id() . '_' . self::PAYABLE_ATTR))
            ->setName(self::PAYABLE_ATTR)
            ->setValue((string)$value)
            ->setLanguageISO($languageIso);
        
        $attribute = (new ProductAttrModel)
            ->setId($i18n->getProductAttrId())
            ->setProductId(new Identity($product->get_id()))
            ->setIsCustomProperty(false)
            ->addI18n($i18n);
        
        return $attribute;
    }
    
    private function getNoSearchFunctionAttribute(\WC_Product $product, $languageIso = '')
    {
        $visibility = get_post_meta($product->get_id(), '_visibility');
        
        if (count($visibility) > 0 && strcmp($visibility[0], 'catalog') === 0) {
            $value = 'true';
        } else {
            $value = 'false';
        }
        
        $i18n = (new ProductAttrI18nModel)
            ->setProductAttrId(new Identity($product->get_id() . '_' . self::NOSEARCH_ATTR))
            ->setName(self::NOSEARCH_ATTR)
            ->setValue((string)$value)
            ->setLanguageISO($languageIso);
        
        $attribute = (new ProductAttrModel)
            ->setId($i18n->getProductAttrId())
            ->setProductId(new Identity($product->get_id()))
            ->setIsCustomProperty(false)
            ->addI18n($i18n);
        
        return $attribute;
    }
    
    private function getVirtualFunctionAttribute(\WC_Product $product, $languageIso = '')
    {
        $value = $product->is_virtual() ? 'true' : 'false';
        $i18n = (new ProductAttrI18nModel)
            ->setProductAttrId(new Identity($product->get_id() . '_' . self::VIRTUAL_ATTR))
            ->setName(self::VIRTUAL_ATTR)
            ->setValue((string)$value)
            ->setLanguageISO($languageIso);
        
        $attribute = (new ProductAttrModel)
            ->setId($i18n->getProductAttrId())
            ->setProductId(new Identity($product->get_id()))
            ->setIsCustomProperty(false)
            ->addI18n($i18n);
        
        return $attribute;
    }
    
    private function getFacebookSyncStatusFunctionAttribute(\WC_Product $product, $languageIso = '')
    {
        $value = 'false';
        $status = get_post_meta($product->get_id(), 'fb_sync_status');
        
        if (count($status) > 0 && strcmp($status[0], '1') === 0) {
            $value = 'true';
        }
        
        $i18n = (new ProductAttrI18nModel)
            ->setProductAttrId(new Identity($product->get_id() . '_' . self::FACEBOOK_SYNC_STATUS_ATTR))
            ->setName(self::FACEBOOK_SYNC_STATUS_ATTR)
            ->setValue((string)$value)
            ->setLanguageISO($languageIso);
        
        $attribute = (new ProductAttrModel)
            ->setId($i18n->getProductAttrId())
            ->setProductId(new Identity($product->get_id()))
            ->setIsCustomProperty(false)
            ->addI18n($i18n);
        
        return $attribute;
    }
    // </editor-fold>
    
    // <editor-fold defaultstate="collapsed" desc="Specific Methods">
    
    
    /**
     * @param $productId
     * @param $pushedAttributes
     * @param $attributesFilteredVariationsAndSpecifics
     * @return mixed
     */
    private function handleAttributes($productId, $pushedAttributes, $attributesFilteredVariationsAndSpecifics)
    {
        
        //FUNCTION ATTRIBUTES BY JTL
        $virtual = false;
        $downloadable = false;
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
                        if (strcmp($attrName, self::FACEBOOK_SYNC_STATUS_ATTR) === 0) {
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
                    
                    if (strcmp($attrName, self::DOWNLOADABLE_ATTR) === 0) {
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
                    
                    if (strcmp($attrName, self::VIRTUAL_ATTR) === 0) {
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
        
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_FB_FOR_WOO)) {
            if (!$fbStatusCode) {
                if (!add_post_meta(
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
    
    // <editor-fold defaultstate="collapsed" desc="VariationSpecific Methods">
    
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
    
    private function mergeAttributes(array &$newProductAttributes, array $attributes)
    {
        foreach ($attributes as $slug => $attr) {
            if (array_key_exists($slug, $newProductAttributes)) {
                if ($attr['name'] === $slug && $attr['name'] === $newProductAttributes[$slug]['name']) {
                    $isVariation = $attr['is_variation'] || $newProductAttributes[$slug]['is_variation'] ? true : false;
                    $attrValues = explode(' ' . WC_DELIMITER . ' ', $attr['value']);
                    $oldValues = explode(' ' . WC_DELIMITER . ' ', $newProductAttributes[$slug]['value']);
                    
                    $values = array_merge($attrValues, $oldValues);
                    
                    $values = array_map("unserialize", array_unique(array_map("serialize", $values)));
                    $valuesString = implode(' ' . WC_DELIMITER . ' ', $values);
                    $newProductAttributes[$slug]['value'] = $valuesString;
                    $newProductAttributes[$slug]['is_variation'] = $isVariation;
                }
            } else {
                $newProductAttributes[$slug] = $attr;
            }
        }
    }
    
    // </editor-fold>
    
    private function handleSpecifics($productId, $curAttributes, $specificData = [], $pushedSpecifics = [])
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
        //\update_post_meta($productId, '_product_attributes', $newSpecifics);
    }
    
    private function handleMasterVariationSpecifics(
        $productId,
        $variationSpecificData,
        $attributesFilteredVariationSpecifics
    ) {
        $result = null;
        
        foreach ($variationSpecificData as $key => $variationSpecific) {
            $taxonomy = 'pa_' . wc_sanitize_taxonomy_name(substr(trim($key), 0, 27));
            $specificID = $this->database->query(SqlHelper::getSpecificId(sprintf('%s', $key)));
            $specificExists = isset($specificID[0]['attribute_id']) ? true : false;
            $options = [];
            
            if (array_key_exists($taxonomy, $attributesFilteredVariationSpecifics)) {
                $attributesFilteredVariationSpecifics[$taxonomy]['is_variation'] = true;
            }
            
            if ($specificExists) {
                
                //Get existing values
                $pushedValues = explode(' ' . WC_DELIMITER . ' ', $variationSpecific['value']);
                foreach ($pushedValues as $pushedValue) {
                    
                    //check if value did not exists
                    $specificValueId = $this->getSpecificValueId(
                        $taxonomy,
                        trim($pushedValue)
                    );
                    
                    $termId = (int)$specificValueId->getEndpoint();
                    
                    if (!$termId > 0) {
                        //Add values
                        $newTerm = \wp_insert_term(
                            $pushedValue,
                            $taxonomy
                        );
                        
                        if ($newTerm instanceof WP_Error) {
                            //  var_dump($newTerm);
                            // die();
                            WpErrorLogger::getInstance()->logError($newTerm);
                            continue;
                        }
                        
                        $termId = $newTerm['term_id'];
                    }
                    
                    if (array_key_exists($taxonomy, $attributesFilteredVariationSpecifics)) {
                        $attributesFilteredVariationSpecifics[$taxonomy]['is_variation'] = true;
                        
                        $options = explode(
                            ' ' . WC_DELIMITER . ' ',
                            $attributesFilteredVariationSpecifics[$taxonomy]['value']
                        );
                        
                        if ((!in_array($termId, $options))) {
                            array_push($options, $termId);
                        }
                        
                        $attributesFilteredVariationSpecifics[$taxonomy]['value'] = implode(
                            ' ' . WC_DELIMITER . ' ',
                            $options
                        );
                        
                    } else {
                        array_push($options, $termId);
                        $attributesFilteredVariationSpecifics[$taxonomy] = [
                            'name'         => $taxonomy,
                            'value'        => implode(
                                ' ' . WC_DELIMITER . ' ',
                                $options
                            ),
                            'position'     => 0,
                            'is_visible'   => Util::showVariationSpecificsOnProductPageEnabled(),
                            'is_variation' => true,
                            'is_taxonomy'  => $taxonomy,
                        ];
                    }
                    
                    foreach ($options as $key => $value) {
                        $options[$key] = (int)$value;
                    }
                    
                    wp_set_object_terms(
                        $productId,
                        $options,
                        $attributesFilteredVariationSpecifics[$taxonomy]['name'],
                        true
                    );
                }
            } else {
                //Create specific and add values
                $endpoint = [
                    'id'       => '',
                    'name'     => $variationSpecific['name'],
                    'slug'     => $taxonomy,
                    'type'     => 'select',
                    'order_by' => 'menu_order',
                    //'attribute_public'  => 0,
                ];
                
                $options = explode(
                    ' ' . WC_DELIMITER . ' ',
                    $variationSpecific['value']
                );
                
                $attributeId = wc_create_attribute($endpoint);
                
                if ($attributeId instanceof WP_Error) {
                    //var_dump($attributeId);
                    //die();
                    //return $termId->get_error_message();
                    WpErrorLogger::getInstance()->logError($attributeId);
                    
                    return null;
                }
                
                //Register taxonomy for current request
                register_taxonomy($taxonomy, null);
                
                $assignedValueIds = [];
                
                foreach ($options as $optionKey => $optionValue) {
                    $slug = wc_sanitize_taxonomy_name($optionValue);
                    
                    $endpointValue = [
                        'name' => $optionValue,
                        'slug' => $slug,
                    ];
                    
                    $exValId = $this->database->query(
                        SqlHelper::getSpecificValueId(
                            $taxonomy,
                            $endpointValue['name']
                        )
                    );
                    
                    if (count($exValId) >= 1) {
                        if (isset($exValId[0]['term_id'])) {
                            $exValId = $exValId[0]['term_id'];
                        } else {
                            $exValId = null;
                        }
                    } else {
                        $exValId = null;
                    }
                    
                    if (is_null($exValId)) {
                        $newTerm = \wp_insert_term(
                            $endpointValue['name'],
                            $taxonomy
                        );
                        
                        if ($newTerm instanceof WP_Error) {
                            //  var_dump($newTerm);
                            // die();
                            WpErrorLogger::getInstance()->logError($newTerm);
                            continue;
                        }
                        
                        $termId = $newTerm['term_id'];
                        
                        if ($termId instanceof WP_Error) {
                            // var_dump($termId);
                            // die();
                            WpErrorLogger::getInstance()->logError($termId);
                            continue;
                        }
                        
                        $assignedValueIds[] = $termId;
                    }
                }
                
                $attributesFilteredVariationSpecifics[$taxonomy] = [
                    'name'         => $taxonomy,
                    'value'        => implode(
                        ' ' . WC_DELIMITER . ' ',
                        $options
                    ),
                    'position'     => null,
                    'is_visible'   => Util::showVariationSpecificsOnProductPageEnabled(),
                    'is_variation' => true,
                    'is_taxonomy'  => $taxonomy,
                ];
                
                wp_set_object_terms(
                    $productId,
                    $assignedValueIds,
                    $attributesFilteredVariationSpecifics[$taxonomy]['name'],
                    true
                );
            }
            $result = $attributesFilteredVariationSpecifics;
        }
        
        return $result;
    }
    
    //ALL
    public function getSpecificValueId(
        $slug,
        $value
    ) {
        $val = $this->database->query(SqlHelper::getSpecificValueId($slug, $value));
        
        if (count($val) === 0) {
            $result = (new Identity);
        } else {
            $result = isset($val[0]['endpoint_id'])
            && isset($val[0]['host_id'])
            && !is_null($val[0]['endpoint_id'])
            && !is_null($val[0]['host_id'])
                ? (new Identity)->setEndpoint($val[0]['endpoint_id'])->setHost($val[0]['host_id'])
                : (new Identity)->setEndpoint($val[0]['term_taxonomy_id']);
        }
        
        return $result;
    }
    
    private function handleChildVariation(
        $productId,
        $pushedVariations
    ) {
        $updatedAttributeKeys = [];
        
        /** @var ProductVariationModel $variation */
        foreach ($pushedVariations as $variation) {
            foreach ($variation->getValues() as $variationValue) {
                foreach ($variation->getI18ns() as $variationI18n) {
                    if (!Util::getInstance()->isWooCommerceLanguage($variationI18n->getLanguageISO())) {
                        continue;
                    }
                    
                    foreach ($variationValue->getI18ns() as $i18n) {
                        $metaKey =
                            'attribute_pa_' . wc_sanitize_taxonomy_name(
                                substr(
                                    trim(
                                        $variationI18n->getName()
                                    ),
                                    0,
                                    27
                                )
                            );
                        $updatedAttributeKeys[] = $metaKey;
                        
                        \update_post_meta($productId, $metaKey,
                            wc_sanitize_taxonomy_name($i18n->getName()));
                    }
                    break;
                }
            }
        }
        
        /*	$attributesToDelete = $this->database->queryList( SqlHelper::productVariationObsoletes(
            $product->getId()->getEndpoint(),
            $updatedAttributeKeys
        ) );
        
        foreach ( $attributesToDelete as $key ) {
            \delete_post_meta( $product->getId()->getEndpoint(), $key );
        }*/
        
        return $updatedAttributeKeys;
    }
    
    //VARIATIONSPECIFIC && SPECIFIC
    private function sortI18nValues(
        ProductVariationValueModel $a,
        ProductVariationValueModel $b
    ) {
        if ($a->getSort() === $b->getSort()) {
            if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
                return 0;
            } else {
                $indexA = $indexB = 0;
                
                foreach ($this->values as $index => $value) {
                    if ($value->getId() === $a->getId()) {
                        $indexA = $index;
                    } elseif ($value->getId() === $b->getId()) {
                        $indexB = $index;
                    }
                }
                
                return ($indexA < $indexB) ? -1 : 1;
            }
        }
        
        return ($a->getSort() < $b->getSort()) ? -1 : 1;
    }
    
    
}
