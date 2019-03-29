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
use jtl\Connector\Model\ProductVariationI18n as ProductVariationI18nModel;
use jtl\Connector\Model\ProductVariationValue as ProductVariationValueModel;
use jtl\Connector\Model\ProductVariationValueI18n as ProductVariationValueI18nModel;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\SupportedPlugins as SupportedPluginsAlias;
use JtlWooCommerceConnector\Utilities\Util;

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
            $finishedAttr = (new ProductAttr)->pushData(
                $productId,
                $pushedAttributes,
                $attributesFilteredVariationsAndSpecifics
            );
            $this->mergeAttributes($newProductAttributes, $finishedAttr);
            // handleVarSpecifics
            $finishedSpecifics = (new ProductSpecific)->pushData(
                $productId, $curAttributes, $specificData, $pushedSpecifics
            );
            $this->mergeAttributes($newProductAttributes, $finishedSpecifics);
            // handleVarSpecifics
            $finishedVarSpecifics = (new ProductVariation)->pushMasterData(
                $productId,
                $variationSpecificData,
                $attributesFilteredVariationSpecifics
            );
            $this->mergeAttributes($newProductAttributes, $finishedVarSpecifics);
            $old = \get_post_meta($productId, '_product_attributes', true);
            \update_post_meta($productId, '_product_attributes', $newProductAttributes, $old);
            
        } else {
            (new ProductVariation)->pushChildData(
                $productId,
                $pushedVariations
            );
        }
        // remove the transient to renew the cache
        delete_transient('wc_attribute_taxonomies');
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
    
}