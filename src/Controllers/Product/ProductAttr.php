<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2018 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Product;

use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\Model\ProductAttr as ProductAttrModel;
use jtl\Connector\Model\ProductAttrI18n as ProductAttrI18nModel;
use JtlConnectorAdmin;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Utilities\Config;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;

class ProductAttr extends BaseController
{
    /**
     * @param              $productId
     * @param              $pushedAttributes
     * @param              $attributesFilteredVariationsAndSpecifics
     * @param ProductModel $product
     *
     * @return mixed
     */
    public function pushData(
        $productId,
        $pushedAttributes,
        $attributesFilteredVariationsAndSpecifics,
        ProductModel $product,
        $wawiLanguageIso
    ) {
        //FUNCTION ATTRIBUTES BY JTL
        $virtual = false;
        $downloadable = false;
        $soldIndividual = false;
        $payable = false;
        $nosearch = false;
        $fbStatusCode = false;
        $purchaseNote = false;
        /* $fbVisibility = false;*/
        //GERMAN MARKET
        $digital = false;
        $altDeliveryNote = false;
        $suppressShippingNotice = false;
        $variationPreselect = [];
        
        /** @var  ProductAttrModel $pushedAttribute */
        foreach ($pushedAttributes as $key => $pushedAttribute) {
            foreach ($pushedAttribute->getI18ns() as $i18n) {
                if ($wawiLanguageIso !== $i18n->getLanguageISO()) {
                    continue;
                }
                
                $attrName = strtolower(trim($i18n->getName()));
                
                if (preg_match('/^(wc_)[a-zA-Z\_]+$/', $attrName)
                    || in_array($attrName, [
                        'nosearch',
                        'payable',
                    ])) {
                    if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_FB_FOR_WOO)) {
                        
                        if (strcmp($attrName, ProductVaSpeAttrHandler::FACEBOOK_SYNC_STATUS_ATTR) === 0) {
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
                                    $value,
                                    \get_post_meta($productId, substr($attrName, 3), true)
                                );
                            }
                            $fbStatusCode = true;
                        }
                    }
                    if(SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)){
                        if($i18n->getName() === ProductVaSpeAttrHandler::GZD_IS_SERVICE) {
                            $value = $i18n->getValue();
                            if (in_array($value, ['yes', 'no'])) {
                                $metaKey = '_service';

                                if (!add_post_meta($productId, $metaKey, $value, true)) {
                                    update_post_meta($productId, $metaKey, $value,
                                        \get_post_meta($productId, $metaKey, true)
                                    );
                                }
                            }
                        }
                    }
                    
                    if (
                        preg_match(
                            '/^(wc_gm_v_preselect_)[a-zA-Z\_]+$/',
                            $attrName
                        )
                        && $product->getMasterProductId()->getHost() === 0
                    ) {
                        $attrName = substr($attrName, 18);
                        
                        $term = \get_term_by(
                            'slug',
                            wc_sanitize_taxonomy_name(substr(trim($i18n->getValue()), 0, 27)),
                            'pa_' . $attrName
                        );
                        
                        if ($term instanceof \WP_Term) {
                            $variationPreselect[$term->taxonomy] = $term->slug;
                        }
                    }
                    
                    if (
                        preg_match(
                            '/^(wc_v_preselect_)[a-zA-Z\_]+$/',
                            $attrName
                        )
                        && $product->getMasterProductId()->getHost() === 0
                    ) {
                        $attrName = substr($attrName, 15);
                        
                        $term = \get_term_by(
                            'slug',
                            wc_sanitize_taxonomy_name(substr(trim($i18n->getValue()), 0, 27)),
                            'pa_' . $attrName
                        );
                        
                        if ($term instanceof \WP_Term) {
                            $variationPreselect[$term->taxonomy] = $term->slug;
                        }
                    }
                    
                    if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_GERMAN_MARKET)) {
                        
                        if (strcmp($attrName, ProductVaSpeAttrHandler::GM_DIGITAL_ATTR) === 0) {
                            $value = strcmp(trim($i18n->getValue()), 'true') === 0;
                            $value = $value ? 'yes' : 'no';
                            $metaKey = substr($attrName, 5);
                            if (!add_post_meta(
                                $productId,
                                $metaKey,
                                $value,
                                true
                            )) {
                                update_post_meta(
                                    $productId,
                                    $metaKey,
                                    $value,
                                    \get_post_meta($productId, $metaKey, true)
                                );
                            }
                            $digital = true;
                        }
                        
                        if (strcmp($attrName, ProductVaSpeAttrHandler::GM_SUPPRESS_SHIPPPING_NOTICE) === 0) {
                            $value = strcmp(trim($i18n->getValue()), 'true') === 0;
                            $value = $value ? 'on' : '';
                            if ($value) {
                                if (!add_post_meta(
                                    $productId,
                                    substr($attrName, 5),
                                    $value,
                                    true
                                )) {
                                    update_post_meta(
                                        $productId,
                                        substr($attrName, 5),
                                        $value,
                                        \get_post_meta($productId, substr($attrName, 5), true)
                                    );
                                }
                            }
                            $suppressShippingNotice = true;
                        }
                        
                        if (strcmp($attrName, ProductVaSpeAttrHandler::GM_ALT_DELIVERY_NOTE_ATTR) === 0) {
                            $value = trim($i18n->getValue());
                            $attrKey = '_alternative_shipping_information';
                            if (!add_post_meta(
                                $productId,
                                $attrKey,
                                $value,
                                true
                            )) {
                                \update_post_meta(
                                    $productId,
                                    $attrKey,
                                    $value,
                                    \get_post_meta($productId, $attrKey, true)
                                );
                            }
                            $altDeliveryNote = true;
                        }
                    }
                    
                    if (strcmp($attrName, ProductVaSpeAttrHandler::PURCHASE_NOTE_ATTR) === 0) {
                        $value = trim($i18n->getValue());
                        $attrKey = '_purchase_note';
                        if (!add_post_meta(
                            $productId,
                            $attrKey,
                            $value,
                            true
                        )) {
                            \update_post_meta(
                                $productId,
                                $attrKey,
                                $value,
                                \get_post_meta($productId, $attrKey, true)
                            );
                        }
                        $purchaseNote = true;
                    }
                    
                    if (strcmp($attrName, ProductVaSpeAttrHandler::DOWNLOADABLE_ATTR) === 0) {
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
                                $value,
                                \get_post_meta($productId, substr($attrName, 2), true)
                            );
                        }
                        $downloadable = true;
                    }
                    
                    if (strcmp($attrName, ProductVaSpeAttrHandler::PURCHASE_ONLY_ONE_ATTR) === 0) {
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
                                $value,
                                \get_post_meta($productId, substr($attrName, 2), true)
                            );
                        }
                        $soldIndividual = true;
                    }
                    
                    if (strcmp($attrName, ProductVaSpeAttrHandler::VIRTUAL_ATTR) === 0) {
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
                                $value,
                                \get_post_meta($productId, substr($attrName, 2), true)
                            );
                        }
                        
                        $virtual = true;
                    }
                    
                    if ($attrName === ProductVaSpeAttrHandler::PAYABLE_ATTR || $attrName === 'payable') {
                        if (strcmp(trim($i18n->getValue()), 'false') === 0) {
                            \wp_update_post([
                                'ID'          => $productId,
                                'post_status' => 'private',
                            ]);
                            $payable = true;
                        }
                    }
                    
                    if ($attrName === ProductVaSpeAttrHandler::NOSEARCH_ATTR || $attrName === 'nosearch') {
                        if (strcmp(trim($i18n->getValue()), 'true') === 0) {
                            \update_post_meta(
                                $productId,
                                '_visibility',
                                'catalog',
                                \get_post_meta($productId, '_visibility', true)
                            );
                            
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
        
        \update_post_meta(
            $productId,
            '_default_attributes',
            $variationPreselect,
            \get_post_meta($productId,
                '_default_attributes',
                true)
        );
        
        //Revert
        if (!$virtual) {
            if (!\add_post_meta(
                $productId,
                '_virtual',
                'no',
                true
            )) {
                \update_post_meta(
                    $productId,
                    '_virtual',
                    'no',
                    \get_post_meta($productId, '_virtual', true)
                );
            }
        }
        
        if (!$downloadable) {
            if (!\add_post_meta(
                $productId,
                '_downloadable',
                'no',
                true
            )) {
                \update_post_meta(
                    $productId,
                    '_downloadable',
                    'no',
                    \get_post_meta($productId, '_downloadable', true)
                );
            }
        }
        
        if (!$soldIndividual) {
            if (!\add_post_meta(
                $productId,
                substr(ProductVaSpeAttrHandler::PURCHASE_ONLY_ONE_ATTR, 2),
                'no',
                true
            )) {
                \update_post_meta(
                    $productId,
                    substr(ProductVaSpeAttrHandler::PURCHASE_ONLY_ONE_ATTR, 2),
                    'no',
                    \get_post_meta($productId, substr(ProductVaSpeAttrHandler::PURCHASE_ONLY_ONE_ATTR, 2), true)
                );
            }
        }
        
        if (!$nosearch) {
            
            if (!\add_post_meta(
                $productId,
                '_visibility',
                'visible',
                true
            )) {
                \update_post_meta(
                    $productId,
                    '_visibility',
                    'visible',
                    \get_post_meta($productId, '_visibility', true)
                );
            }
            \wp_remove_object_terms($productId, ['exclude-from-search'], 'product_visibility');
        }
        
        if (!$purchaseNote) {
            if (!\add_post_meta(
                $productId,
                '_purchase_note',
                '',
                true
            )) {
                \update_post_meta(
                    $productId,
                    '_purchase_note',
                    '',
                    \get_post_meta($productId, '_purchase_note', true)
                );
            }
        }
        
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_GERMAN_MARKET)) {
            if (!$altDeliveryNote) {
                if (!\add_post_meta(
                    $productId,
                    '_alternative_shipping_information',
                    '',
                    true
                )) {
                    \update_post_meta(
                        $productId,
                        '_alternative_shipping_information',
                        '',
                        \get_post_meta($productId, '_alternative_shipping_information', true)
                    );
                }
            }
            
            if (!$digital) {
                if (!\add_post_meta(
                    $productId,
                    '_digital',
                    'no',
                    true
                )) {
                    \update_post_meta(
                        $productId,
                        '_digital',
                        'no',
                        \get_post_meta($productId, '_digital', true)
                    );
                }
            }
            
            if (!$suppressShippingNotice) {
                \delete_post_meta($productId, '_suppress_shipping_notice');
            }
        }
        
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_FB_FOR_WOO)) {
            if (!$fbStatusCode) {
                if (!\add_post_meta(
                    $productId,
                    substr(ProductVaSpeAttrHandler::FACEBOOK_SYNC_STATUS_ATTR, 3),
                    '',
                    true
                )) {
                    \update_post_meta(
                        $productId,
                        substr(ProductVaSpeAttrHandler::FACEBOOK_SYNC_STATUS_ATTR, 3),
                        '',
                        \get_post_meta($productId, substr(ProductVaSpeAttrHandler::FACEBOOK_SYNC_STATUS_ATTR, 3), true)
                    );
                }
            }
        }
        
        if (!$payable) {
            $wcProduct = \wc_get_product($productId);
            $wcProduct->set_status('publish');
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
            if (!(bool)Config::get(Config::OPTIONS_SEND_CUSTOM_PROPERTIES) && $attribute->getIsCustomProperty() === true) {
                continue;
            }
            
            foreach ($attribute->getI18ns() as $i18n) {
                if ($wawiLanguageIso !== $i18n->getLanguageISO()) {
                    continue;
                }
                
                $this->saveAttribute($attribute, $i18n, $attributesFilteredVariationsAndSpecifics);
                break;
            }
        }
        
        return $attributesFilteredVariationsAndSpecifics;
    }

    private function saveAttribute(ProductAttrModel $attribute, ProductAttrI18nModel $i18n, array &$attributes)
    {
        $value = $i18n->getValue();
        if ((bool)Config::get(Config::OPTIONS_ALLOW_HTML_IN_PRODUCT_ATTRIBUTES, false) === false) {
            $value = \wc_clean($i18n->getValue());
        }

        $this->addNewAttributeOrEditExisting($i18n, [
            'name'             => \wc_clean($i18n->getName()),
            'value'            => $value,
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
}
