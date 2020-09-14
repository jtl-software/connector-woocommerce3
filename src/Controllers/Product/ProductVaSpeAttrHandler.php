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
use jtl\Connector\Model\ProductI18n;
use jtl\Connector\Model\ProductSpecific as ProductSpecificModel;
use jtl\Connector\Model\ProductVariationI18n as ProductVariationI18nModel;
use jtl\Connector\Model\ProductVariationValue as ProductVariationValueModel;
use jtl\Connector\Model\ProductVariationValueI18n as ProductVariationValueI18nModel;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\Wpml;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlProduct;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlProductVariation;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\SupportedPlugins as SupportedPluginsAlias;
use JtlWooCommerceConnector\Utilities\Util;

if (!defined('WC_DELIMITER')) {
    define('WC_DELIMITER', '|');
}

class ProductVaSpeAttrHandler extends BaseController
{
    const PRODUCT_TYPE_ATTR = 'wc_product_type';
    const DELIVERY_TIME_ATTR = 'wc_dt_offset';
    const DOWNLOADABLE_ATTR = 'wc_downloadable';
    const FACEBOOK_VISIBILITY_ATTR = 'wc_fb_visibility';
    const FACEBOOK_SYNC_STATUS_ATTR = 'wc_fb_sync_status';
    const PAYABLE_ATTR = 'wc_payable';
    const NOSEARCH_ATTR = 'wc_nosearch';
    const VIRTUAL_ATTR = 'wc_virtual';
    const PURCHASE_NOTE_ATTR = 'wc_purchase_note';
    const PURCHASE_ONLY_ONE_ATTR = 'wc_sold_individually';

    //GERMAN MARKET
    const GM_DIGITAL_ATTR = 'wc_gm_digital';
    const GM_ALT_DELIVERY_NOTE_ATTR = 'wc_gm_alt_delivery_note';
    const GM_SUPPRESS_SHIPPPING_NOTICE = 'wc_gm_suppress_shipping_notice';

    //GERMANIZED
    const GZD_IS_SERVICE = 'wc_gzd_is_service';

    const VALUE_TRUE = 'true';
    const VALUE_FALSE = 'false';

    private $productData = [
        'productVariation' => [],
        'productAttributes' => [],
        'productSpecifics' => [],
    ];

    /**
     * @param \WC_Product $product
     * @param ProductModel $model
     * @return array
     * @throws \Exception
     */
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

                if (!$isVariation && !$taxonomyExistsCurrentAttr) {
                    $productAttribute = $product->get_attribute($attribute->get_name());
                    $isTax = $attribute->is_taxonomy();

                    // Divided by |
                    $values = explode(WC_DELIMITER, $productAttribute);

                    $i18n = (new ProductAttrI18nModel)
                        ->setProductAttrId(new Identity($slug))
                        ->setName($attribute->get_name())
                        ->setValue(implode(', ', $values))
                        ->setLanguageISO($languageIso);

                    $productAttr = (new ProductAttrModel)
                        ->setId($i18n->getProductAttrId())
                        ->setProductId(new Identity($product->get_id()))
                        ->setIsCustomProperty($isTax)
                        ->addI18n($i18n);

                    if ($this->wpml->canBeUsed()) {
                        $translatedProducts = $this->wpml->getComponent(WpmlProduct::class)->getWooCommerceProductTranslations($product);
                        foreach ($translatedProducts as $wpmlLanguageCode => $translatedProduct) {
                            $translatedAttribute = $this->wpml->getComponent(WpmlProduct::class)->getWooCommerceProductTranslatedAttributeBySlug($translatedProduct,
                                $slug);
                            if (!is_null($translatedAttribute)) {
                                $translatedProductAttributeValue = $translatedProduct->get_attribute($translatedAttribute->get_name());
                                $i18n = (new ProductAttrI18nModel)
                                    ->setProductAttrId($i18n->getProductAttrId())
                                    ->setName($translatedAttribute->get_name())
                                    ->setValue(implode(', ', explode(WC_DELIMITER, $translatedProductAttributeValue)))
                                    ->setLanguageISO($this->wpml->convertLanguageToWawi($wpmlLanguageCode));
                                $productAttr->addI18n($i18n);
                                $productAttr->setIsTranslated(true);
                            }
                        }
                    }

                    $this->productData['productAttributes'][] = $productAttr;
                } elseif (!$isVariation && $taxonomyExistsCurrentAttr) {

                    $name = $attribute->get_name();
                    $values = array_map('trim', explode(',', $product->get_attribute($name)));

                    foreach ($values as $value) {
                        if (empty($value)) {
                            continue;
                        }
                        $valueId = $this->getSpecificValueId($slug, $value);

                        $name = substr($slug, 3);
                        $val = $this->database->query(SqlHelper::getSpecificId($name));

                        $specificIdValue = isset($val[0]['attribute_id']) ? $val[0]['attribute_id'] : '';
                        $specificId = (new Identity)->setEndpoint($specificIdValue);

                        $specific = (new ProductSpecificModel)
                            ->setId($specificId)
                            ->setProductId($model->getId())
                            ->setSpecificValueId($valueId);

                        $this->productData['productSpecifics'][] = $specific;
                    }

                } elseif ($isVariation && $isProductVariationParent) {
                    $tmp = (new ProductVariation)
                        ->pullDataParent(
                            $model,
                            $attribute,
                            $product,
                            $languageIso
                        );
                    if (!is_null($tmp)) {
                        $this->productData['productVariation'][] = $tmp;
                    }
                }
            }
        } else {
            $tmp = (new ProductVariation)
                ->pullDataChild(
                    $product,
                    $model,
                    $languageIso
                );
            if (!is_null($tmp)) {
                $this->productData['productVariation'] = $tmp;
            }
        }

        $this->handleCustomPropertyAttributes($product, $languageIso);
        $this->setProductFunctionAttributes($product, $languageIso);

        return $this->productData;
    }

    /**
     * @param ProductModel $jtlProduct
     * @param \WC_Product $wcProduct
     * @param ProductI18n $productI18n
     * @throws \jtl\Connector\Core\Exception\LanguageException
     */
    public function pushDataNew(ProductModel $jtlProduct, \WC_Product $wcProduct, ProductI18n $productI18n)
    {
        if ($wcProduct === false) {
            return;
        }
        //Identify Master = parent/simple
        $isMaster = $jtlProduct->getMasterProductId()->getHost() === 0;

        $productId = $wcProduct->get_id();

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
            $variationSpecificData = $this->generateVariationSpecificData(
                $productI18n->getLanguageISO(),
                $jtlProduct->getVariations()
            );
            $specificData = $this->generateSpecificData($jtlProduct->getSpecifics());

            //handleAttributes
            $finishedAttr = (new ProductAttr)->pushData(
                $productId,
                $jtlProduct->getAttributes(),
                $attributesFilteredVariationsAndSpecifics,
                $jtlProduct,
                $productI18n->getLanguageISO()
            );
            $this->mergeAttributes($newProductAttributes, $finishedAttr);

            // handleSpecifics
            $finishedSpecifics = (new ProductSpecific)->pushData(
                $productId, $curAttributes, $specificData, $jtlProduct->getSpecifics()
            );
            $this->mergeAttributes($newProductAttributes, $finishedSpecifics);

            // handleVarSpecifics
            $finishedVarSpecifics = (new ProductVariation)->pushMasterData(
                $productId,
                $variationSpecificData,
                $attributesFilteredVariationSpecifics,
                $productI18n->getLanguageISO()
            );

            if (!is_array($finishedVarSpecifics)) {
                $finishedVarSpecifics = [];
            }

            $this->mergeAttributes($newProductAttributes, $finishedVarSpecifics);
            $old = \get_post_meta($productId, '_product_attributes', true);
            \update_post_meta($productId, '_product_attributes', $newProductAttributes, $old);

        } else {
            (new ProductVariation)->pushChildData(
                $productId,
                $jtlProduct->getVariations()
            );
        }
        // remove the transient to renew the cache
        delete_transient('wc_attribute_taxonomies');
    }

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
                    'id' => $attribute->get_id(),
                    'name' => $attribute->get_name(),
                    'value' => implode(' ' . WC_DELIMITER . ' ', $attribute->get_options()),
                    'position' => $attribute->get_position(),
                    'is_visible' => $attribute->get_visible(),
                    'is_variation' => $attribute->get_variation(),
                    'is_taxonomy' => $attribute->get_taxonomy(),
                ];
            } elseif (taxonomy_exists($slug)) {
                $filteredAttributes[$slug] =
                    [
                        'id' => $attribute->get_id(),
                        'name' => $attribute->get_name(),
                        'value' => '',
                        'position' => $attribute->get_position(),
                        'is_visible' => $attribute->get_visible(),
                        'is_variation' => $attribute->get_variation(),
                        'is_taxonomy' => $attribute->get_taxonomy(),
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
                    'name' => $product_specific->get_name(),
                    'value' => implode(' ' . WC_DELIMITER . ' ', $product_specific->get_options()),
                    'position' => $product_specific->get_position(),
                    'is_visible' => $product_specific->get_visible(),
                    'is_variation' => $product_specific->get_variation(),
                    'is_taxonomy' => $product_specific->get_taxonomy(),
                ];
            }
        }

        return $filteredAttributes;
    }

    private function generateSpecificData($pushedSpecifics = [])
    {
        $specificData = [];
        foreach ($pushedSpecifics as $specific) {
            $specificData[(int)$specific->getId()->getEndpoint()]['options'][] =
                (int)$specific->getSpecificValueId()->getEndpoint();
        }

        return $specificData;
    }

    private function generateVariationSpecificData(string $wawiIsoLanguage, $pushedVariations = [])
    {
        $variationSpecificData = [];
        foreach ($pushedVariations as $variation) {
            /** @var ProductVariationI18nModel $variationI18n */
            foreach ($variation->getI18ns() as $variationI18n) {
                $taxonomyName = \wc_sanitize_taxonomy_name($variationI18n->getName());
                $customSort = false;

                if ($wawiIsoLanguage !== $variationI18n->getLanguageISO()) {
                    continue;
                }

                $values = [];

                $variationValues = $variation->getValues();

                foreach ($variationValues as $vv) {
                    if ($vv->getSort() !== 0) {
                        $customSort = true;
                    }
                }

                if ($customSort) {
                    usort($variationValues, [
                        $this,
                        'sortI18nValues',
                    ]);
                }

                foreach ($variationValues as $vv) {
                    /** @var ProductVariationValueI18nModel $valueI18n */
                    foreach ($vv->getI18ns() as $valueI18n) {
                        if ($wawiIsoLanguage !== $valueI18n->getLanguageISO()) {
                            continue;
                        }

                        $values[] = $valueI18n->getName();
                    }
                }

                $variationSpecificData[$taxonomyName] = [
                    'name' => $variationI18n->getName(),
                    'value' => implode(' ' . WC_DELIMITER . ' ', $values),
                    'position' => $variation->getSort(),
                    'is_visible' => 0,
                    'is_variation' => 1,
                    'is_taxonomy' => 0,
                ];
            }
        }

        return $variationSpecificData;
    }

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
                    ->setValue(self::VALUE_FALSE);

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
            $this->getOnlyOneFunctionAttribute(
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
            $this->getProductTypeFunctionAttribute(
                $product,
                $languageIso
            ),
            $this->getPurchaseNoteFunctionAttribute(
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
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)) {
            $gzdProduct = wc_gzd_get_product($product);
            if ($gzdProduct instanceof \WC_GZD_Product && $product->meta_exists('_service')) {
                $functionAttributes[] = $this->getIsServiceFunctionAttribute(
                    $gzdProduct,
                    $languageIso
                );
            }
        }

        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_GERMAN_MARKET)) {
            $functionAttributes[] = $this->getDigitalFunctionAttribute(
                $product,
                $languageIso
            );

            $functionAttributes[] = $this->getSuppressShippingNoticeFunctionAttribute(
                $product,
                $languageIso
            );

            $functionAttributes[] = $this->getAltDeliveryNoteFunctionAttribute(
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
        $value = $product->is_downloadable() ? self::VALUE_TRUE : self::VALUE_FALSE;
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

    private function getIsServiceFunctionAttribute(\WC_GZD_Product $product, $languageIso = '')
    {
        $value = $product->get_service() === true ? self::VALUE_TRUE : self::VALUE_FALSE;

        $i18n = (new ProductAttrI18nModel)
            ->setProductAttrId(new Identity($product->get_id() . '_' . self::GZD_IS_SERVICE))
            ->setName(self::GZD_IS_SERVICE)
            ->setValue((string)$value)
            ->setLanguageISO($languageIso);

        $attribute = (new ProductAttrModel)
            ->setId($i18n->getProductAttrId())
            ->setProductId(new Identity($product->get_id()))
            ->setIsCustomProperty(false)
            ->addI18n($i18n);

        return $attribute;
    }

    private function getOnlyOneFunctionAttribute(\WC_Product $product, $languageIso = '')
    {
        $value = $product->is_sold_individually() ? self::VALUE_TRUE : self::VALUE_FALSE;
        $i18n = (new ProductAttrI18nModel)
            ->setProductAttrId(new Identity($product->get_id() . '_' . self::PURCHASE_ONLY_ONE_ATTR))
            ->setName(self::PURCHASE_ONLY_ONE_ATTR)
            ->setValue((string)$value)
            ->setLanguageISO($languageIso);

        $attribute = (new ProductAttrModel)
            ->setId($i18n->getProductAttrId())
            ->setProductId(new Identity($product->get_id()))
            ->setIsCustomProperty(false)
            ->addI18n($i18n);

        return $attribute;
    }

    private function getDigitalFunctionAttribute(\WC_Product $product, $languageIso = '')
    {
        $digital = get_post_meta($product->get_id(), '_digital');

        if (count($digital) > 0 && strcmp($digital[0], 'yes') === 0) {
            $value = self::VALUE_TRUE;
        } else {
            $value = self::VALUE_FALSE;
        }

        $i18n = (new ProductAttrI18nModel)
            ->setProductAttrId(new Identity($product->get_id() . '_' . self::GM_DIGITAL_ATTR))
            ->setName(self::GM_DIGITAL_ATTR)
            ->setValue((string)$value)
            ->setLanguageISO($languageIso);

        $attribute = (new ProductAttrModel)
            ->setId($i18n->getProductAttrId())
            ->setProductId(new Identity($product->get_id()))
            ->setIsCustomProperty(false)
            ->addI18n($i18n);

        return $attribute;
    }

    private function getSuppressShippingNoticeFunctionAttribute(\WC_Product $product, $languageIso = '')
    {
        $value = \get_post_meta($product->get_id(), '_suppress_shipping_notice', true);

        if (strcmp($value, 'on') === 0) {
            $value = self::VALUE_TRUE;
        } else {
            $value = self::VALUE_FALSE;
        }

        $i18n = (new ProductAttrI18nModel)
            ->setProductAttrId(new Identity($product->get_id() . '_' . self::GM_SUPPRESS_SHIPPPING_NOTICE))
            ->setName(self::GM_SUPPRESS_SHIPPPING_NOTICE)
            ->setValue((string)$value)
            ->setLanguageISO($languageIso);

        $attribute = (new ProductAttrModel)
            ->setId($i18n->getProductAttrId())
            ->setProductId(new Identity($product->get_id()))
            ->setIsCustomProperty(false)
            ->addI18n($i18n);

        return $attribute;
    }

    private function getAltDeliveryNoteFunctionAttribute(\WC_Product $product, $languageIso = '')
    {
        $info = \get_post_meta($product->get_id(), '_alternative_shipping_information', true);

        $i18n = (new ProductAttrI18nModel)
            ->setProductAttrId(new Identity($product->get_id() . '_' . self::GM_ALT_DELIVERY_NOTE_ATTR))
            ->setName(self::GM_ALT_DELIVERY_NOTE_ATTR)
            ->setValue((string)$info)
            ->setLanguageISO($languageIso);

        $attribute = (new ProductAttrModel)
            ->setId($i18n->getProductAttrId())
            ->setProductId(new Identity($product->get_id()))
            ->setIsCustomProperty(false)
            ->addI18n($i18n);

        return $attribute;
    }

    private function getPurchaseNoteFunctionAttribute(\WC_Product $product, $languageIso = '')
    {
        $info = \get_post_meta($product->get_id(), '_purchase_note', true);

        $i18n = (new ProductAttrI18nModel)
            ->setProductAttrId(new Identity($product->get_id() . '_' . self::PURCHASE_NOTE_ATTR))
            ->setName(self::PURCHASE_NOTE_ATTR)
            ->setValue((string)$info)
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
        $value = strcmp(get_post_status($product->get_id()), 'private') !== 0 ? self::VALUE_TRUE : self::VALUE_FALSE;

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
            $value = self::VALUE_TRUE;
        } else {
            $value = self::VALUE_FALSE;
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
        $value = $product->is_virtual() ? self::VALUE_TRUE : self::VALUE_FALSE;
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

    private function getProductTypeFunctionAttribute(\WC_Product $product, $languageIso = '')
    {
        $value = $product->get_type();

        $i18n = (new ProductAttrI18nModel)
            ->setProductAttrId(new Identity($product->get_id() . '_' . self::PRODUCT_TYPE_ATTR))
            ->setName(self::PRODUCT_TYPE_ATTR)
            ->setValue((string)$value)
            ->setLanguageISO($languageIso);

        return (new ProductAttrModel)
            ->setId($i18n->getProductAttrId())
            ->setProductId(new Identity($product->get_id()))
            ->setIsCustomProperty(false)
            ->addI18n($i18n);
    }

    private function getFacebookSyncStatusFunctionAttribute(\WC_Product $product, $languageIso = '')
    {
        $value = self::VALUE_FALSE;
        $status = get_post_meta($product->get_id(), 'fb_sync_status');

        if (count($status) > 0 && strcmp($status[0], '1') === 0) {
            $value = self::VALUE_TRUE;
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

    //ALL
    public function getSpecificValueId(
        $slug,
        $value
    ) {
        $val = $this->database->query(SqlHelper::getSpecificValueId($slug, $value));

        if (count($val) === 0) {
            $val = $this->database->query(SqlHelper::getSpecificValueIdBySlug($slug, $value));
        }

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
        return ($a->getSort() - $b->getSort());
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
