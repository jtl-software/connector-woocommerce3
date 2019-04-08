<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Utilities;

use jtl\Connector\Core\Exception\LanguageException;
use jtl\Connector\Core\Utilities\Language;
use jtl\Connector\Core\Utilities\Singleton;
use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\Model\ProductPrice as ProductPriceModel;
use jtl\Connector\Payment\PaymentTypes;
use JtlConnectorAdmin;
use JtlWooCommerceConnector\Controllers\GlobalData\CustomerGroup;
use JtlWooCommerceConnector\Controllers\Product\Product;
use JtlWooCommerceConnector\Controllers\Product\ProductPrice;

final class Util extends Singleton
{
    const TO_SYNC = 'jtlconnector_master_products_to_sync';
    const TO_SYNC_COUNT = 'jtlconnector_master_products_to_sync_count';
    const TO_SYNC_MOD = 100;
    
    private $locale;
    private $namespaceMapping;
    
    public function __construct()
    {
        parent::__construct();
        $this->locale = $this->mapLanguageIso(\get_locale());
        
        $this->namespaceMapping = [
            'CustomerOrder' => 'Order\\',
            'GlobalData'    => 'GlobalData\\',
            'Product'       => 'Product\\',
        ];
    }
    
    public function getControllerNamespace($controller)
    {
        if (isset($this->namespaceMapping[$controller])) {
            return Constants::CONTROLLER_NAMESPACE . $this->namespaceMapping[$controller] . $controller;
        }
        
        return Constants::CONTROLLER_NAMESPACE . $controller;
    }
    
    public function getWooCommerceLanguage()
    {
        return $this->locale;
    }
    
    public function isWooCommerceLanguage($language)
    {
        return $language === self::getWooCommerceLanguage();
    }
    
    public function getTaxRateByTaxClass($taxClass, \WC_Order $order = null)
    {
        $countryIso = \get_option('woocommerce_default_country');
        
        if (!is_null($order)) {
            $option = \get_option('woocommerce_tax_based_on', 'base');
            
            if ($option === 'shipping') {
                $countryIso = $order->get_shipping_country();
            }
            
            if ($option === 'billing' || $option === 'shipping' && empty($country)) {
                $countryIso = $order->get_billing_country();
            }
        }
        
        $taxRates = \WC_Tax::find_rates([
            'tax_class' => $taxClass,
            'country'   => $countryIso,
        ]);
        
        if (!empty($taxRates)) {
            return (double)array_values($taxRates)[0]['rate'];
        }
        
        return 0.0;
    }
    
    public function getStockStatus($stockLevel, $backorders, $managesStock = false)
    {
        $stockStatus = $stockLevel > 0;
        
        if (version_compare(WC()->version, '2.6', '>=')) {
            $stockStatus = $stockStatus || $backorders;
        }
        
        return $stockStatus || !$managesStock ? 'instock' : 'outofstock';
    }
    
    // Quick Sync
    public function updateProductPrice(\WC_Product $product, ProductPriceModel $productPrice, $vat)
    {
        $customerGroupId = $productPrice->getCustomerGroupId()->getEndpoint();
        
        if ($customerGroupId === '') {
            $customerGroupId = ProductPrice::GUEST_CUSTOMER_GROUP;
        }
        
        if ($customerGroupId === ProductPrice::GUEST_CUSTOMER_GROUP || !$this->isValidCustomerGroup($customerGroupId)) {
            return;
        }
        
        $parentProduct = null;
        $productId = $product->get_id();
        
        if (CustomerGroup::DEFAULT_GROUP === $customerGroupId) {
            $salePriceKey = '_sale_price';
            $priceKey = '_price';
            $regularPriceKey = '_regular_price';
        } elseif (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)) {
            $productType = $product->get_type();
            $customerGroup = \get_post($customerGroupId);
            
            if ($productType !== 'variable') {
                $parentProduct = \wc_get_product($product->get_parent_id());
                
                $salePriceKey = sprintf('_jtlwcc_bm_%s_%s_sale_price', $customerGroup->post_name, $productId);
                $priceKey = sprintf('bm_%s_price', $customerGroup->post_name);
                $regularPriceKey = sprintf('_jtlwcc_bm_%s_regular_price', $customerGroup->post_name);
            } else {
                return;
            }
        } else {
            return;
        }
        
        if ($product !== false) {
            $pd = \wc_get_price_decimals();
            
            foreach ($productPrice->getItems() as $item) {
                
                if (\wc_prices_include_tax()) {
                    $newPrice = $item->getNetPrice() * (1 + $vat / 100);
                } else {
                    $newPrice = $item->getNetPrice();
                }
                
                if ($item->getQuantity() === 0) {
                    $sellPriceIdForGet = is_null($parentProduct) ? $productId : $parentProduct->get_id();
                    
                    $salePrice = \get_post_meta($sellPriceIdForGet, $salePriceKey, true);
                    $oldPrice = \get_post_meta($productId, $priceKey, true);
                    $oldRegularPrice = \get_post_meta($productId, $regularPriceKey, true);
                    
                    if (empty($salePrice) || $salePrice !== $oldPrice) {
                        \update_post_meta(
                            $productId,
                            $priceKey,
                            \wc_format_decimal($newPrice, $pd),
                            $oldPrice
                        );
                    }
                    
                    \update_post_meta(
                        $productId,
                        $regularPriceKey,
                        \wc_format_decimal($newPrice, $pd),
                        $oldRegularPrice
                    );
                }
            }
        }
    }
    
    //Normal
    public function updateProductPrices($productPrices, ProductModel $product, $vat)
    {
        $productId = $product->getId()->getEndpoint();
        $pd = \wc_get_price_decimals();
        
        /** @var ProductPriceModel $productPrice */
        foreach ($productPrices as $customerGroupId => $productPrice) {
            if (!$this->isValidCustomerGroup((string)$customerGroupId)
                || (string)$customerGroupId === ProductPrice::GUEST_CUSTOMER_GROUP) {
                continue;
            }
            
            $customerGroupMeta = null;
            
            if (is_int($customerGroupId)) {
                $customerGroupMeta = \get_post_meta($customerGroupId);
            }
            
            if ($customerGroupId === CustomerGroup::DEFAULT_GROUP && is_null($customerGroupMeta)) {
                foreach ($productPrice->getItems() as $item) {
                    if (\wc_prices_include_tax()) {
                        $regularPrice = $item->getNetPrice() * (1 + $vat / 100);
                    } else {
                        $regularPrice = $item->getNetPrice();
                    }
                    if ($item->getQuantity() === 0) {
                        $salePrice = \get_post_meta($productId, '_sale_price', true);
                        
                        if (empty($salePrice) || $salePrice !== \get_post_meta($productId, '_price', true)) {
                            \update_post_meta($productId, '_price', \wc_format_decimal($regularPrice, $pd),
                                \get_post_meta($productId, '_price', true));
                        }
                        
                        \update_post_meta($productId, '_regular_price', \wc_format_decimal($regularPrice, $pd),
                            \get_post_meta($productId, '_regular_price', true));
                    }
                }
            } elseif (!is_null($customerGroupMeta)
                && SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)
            ) {
                $customerGroup = get_post($customerGroupId);
                $productType = (new Product)->getType($product);
                $bulkPrices = [];
                
                foreach ($productPrice->getItems() as $item) {
                   
                    if ($item->getQuantity() === 0) {
                        if (\wc_prices_include_tax()) {
                            $regularPrice = $item->getNetPrice() * (1 + $vat / 100);
                        } else {
                            $regularPrice = $item->getNetPrice();
                        }
                        $metaKeyForCustomerGroupPrice = sprintf(
                            'bm_%s_price',
                            $customerGroup->post_name
                        );
                        
                        if ($productType !== 'variable') {
                            $metaKeyForCustomerGroupRegularPrice = sprintf(
                                '_jtlwcc_bm_%s_regular_price',
                                $customerGroup->post_name
                            );
                            
                            if ($productType === 'product_variation') {
                                $parentProduct = \wc_get_product($product->getMasterProductId()->getEndpoint());
                                if ($parentProduct instanceof \WC_Product) {
                                    $childParentPrice = sprintf(
                                        'bm_%s_%s_price',
                                        $customerGroup->post_name,
                                        $productId
                                    );
                                    \update_post_meta($parentProduct->get_id(),
                                        $childParentPrice,
                                        \wc_format_decimal($regularPrice, $pd),
                                        \get_post_meta($parentProduct->get_id(), $childParentPrice, true));
                                    
                                    $childParentKey = sprintf(
                                        'bm_%s_%s_price_type',
                                        $customerGroup->post_name,
                                        $productId
                                    );
                                    \update_post_meta($parentProduct->get_id(),
                                        $childParentKey,
                                        'fix',
                                        \get_post_meta($parentProduct->get_id(), $childParentKey, true));
                                }
                            }
                        }
                        $metaKeyForCustomerGroupPriceType = $metaKeyForCustomerGroupPrice . '_type';
                        
                        \update_post_meta(
                            $productId,
                            $metaKeyForCustomerGroupPrice,
                            \wc_format_decimal($regularPrice, $pd),
                            \get_post_meta($productId, $metaKeyForCustomerGroupPrice, true)
                        );
                        
                        if ($productType !== 'variable' && isset($metaKeyForCustomerGroupRegularPrice)) {
                            \update_post_meta($productId, $metaKeyForCustomerGroupRegularPrice,
                                \wc_format_decimal($regularPrice, $pd),
                                \get_post_meta($productId, $metaKeyForCustomerGroupRegularPrice, true));
                        }
                        
                        \update_post_meta(
                            $productId,
                            $metaKeyForCustomerGroupPriceType,
                            'fix',
                            \get_post_meta($productId, $metaKeyForCustomerGroupPriceType, true)
                        );
                    } else {
                        $bulkPrices[] = [
                            'bulk_price'      => (string)$item->getNetPrice(),
                            'bulk_price_from' => (string)$item->getQuantity(),
                            'bulk_price_to'   => '',
                            'bulk_price_type' => 'fix',
                        ];
                    }
                }
                
                if (count($bulkPrices) > 0) {
                    
                    $metaKey = sprintf('bm_%s_bulk_prices', $customerGroup->post_name);
                    $metaProductId = $product->getId()->getEndpoint();
                    
                    \update_post_meta(
                        $metaProductId,
                        $metaKey,
                        $bulkPrices,
                        \get_post_meta($metaProductId, $metaKey, true)
                    );
                    
                    if (!$product->getIsMasterProduct()) {
                        $metaKey = sprintf('bm_%s_%s_bulk_prices', $customerGroup->post_name,
                            $product->getId()->getEndpoint());
                        $metaProductId = $product->getMasterProductId()->getEndpoint();
                        
                        \update_post_meta(
                            $metaProductId,
                            $metaKey,
                            $bulkPrices,
                            \get_post_meta($metaProductId, $metaKey, true)
                        );
                    }
                }
                else{
                    
                    $metaKey = sprintf('bm_%s_bulk_prices', $customerGroup->post_name);
                    $metaProductId = $product->getId()->getEndpoint();
                    
                    \delete_post_meta(
                        $metaProductId,
                        $metaKey
                    );
                    
                    if (!$product->getIsMasterProduct()) {
                        $metaKey = sprintf('bm_%s_%s_bulk_prices', $customerGroup->post_name,
                            $product->getId()->getEndpoint());
                        $metaProductId = $product->getMasterProductId()->getEndpoint();
                        \delete_post_meta(
                            $metaProductId,
                            $metaKey
                        );
                    }
                }
            }
        }
    }
    
    public function isValidCustomerGroup($group)
    {
        $result = empty($group) || $group === CustomerGroup::DEFAULT_GROUP;
        
        if ($result) {
            return $result;
        }
        
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)) {
            $customerGroups = Db::getInstance()->query(SqlHelper::customerGroupPull());
            foreach ($customerGroups as $cKey => $customerGroup) {
                if (isset($customerGroup['ID']) && $customerGroup['ID'] === $group) {
                    $result = true;
                }
            }
        }
        
        return $result;
    }
    
    public function addMasterProductToSync($productId)
    {
        $masterProductsToSyncCount = (int)\get_option(self::TO_SYNC_COUNT, 0);
        $page = ($masterProductsToSyncCount + 1) % self::TO_SYNC_MOD + 1;
        $masterProductsToSync = \get_option(self::TO_SYNC . '_' . $page, []);
        $masterProductsToSync[] = $productId;
        
        \update_option(self::TO_SYNC . '_' . $page, array_unique($masterProductsToSync));
    }
    
    public function syncMasterProducts()
    {
        $masterProductsToSyncCount = (int)\get_option(self::TO_SYNC_COUNT, 0);
        
        if ($masterProductsToSyncCount > 0) {
            $page = ($masterProductsToSyncCount + 1) % self::TO_SYNC_MOD + 1;
            
            for ($i = 1; $i <= $page; $i++) {
                $masterProductsToSync = \get_option(self::TO_SYNC . '_' . $page, []);
                
                if (!empty($masterProductsToSync)) {
                    foreach ($masterProductsToSync as $productId) {
                        \WC_Product_Variable::sync($productId);
                    }
                    
                    \delete_option(self::TO_SYNC . '_' . $page);
                }
            }
            
            \update_option(self::TO_SYNC_COUNT, 0);
        }
    }
    
    public function countCategories()
    {
        $offset = 0;
        $limit = 100;
        
        while (!empty($result)) {
            $result = Db::getInstance()->query(SqlHelper::categoryProductsCount($offset, $limit));
            
            foreach ($result as $category) {
                Db::getInstance()->query(SqlHelper::termTaxonomyCountUpdate($category['term_taxonomy_id'],
                    $category['count']));
                Db::getInstance()->query(SqlHelper::categoryMetaCountUpdate($category['term_id'],
                    $category['count']));
            }
            
            $offset += $limit;
        }
    }
    
    public function countProductTags()
    {
        $offset = 0;
        $limit = 100;
        
        while (!empty($result)) {
            $result = Db::getInstance()->query(SqlHelper::productTagsCount($offset, $limit));
            
            foreach ($result as $tag) {
                Db::getInstance()->query(SqlHelper::termTaxonomyCountUpdate($tag['term_taxonomy_id'],
                    $tag['count']));
            }
            
            $offset += $limit;
        }
    }
    
    public function mapLanguageIso($locale)
    {
        $result = null;
        try {
            if (strpos($locale, '_')) {
                $result = Language::map(substr($locale, 0, 5));
            } else {
                if (count($locale) === 2) {
                    $result = is_null(Language::convert($locale)) ? null : $locale;
                } elseif (count($locale) === 3) {
                    $result = Language::convert(null, $locale);
                }
            }
        } catch (LanguageException $exception) {
            //
        }
        
        if (empty($result)) {
            return $this->getWooCommerceLanguage();
        } else {
            return $result;
        }
    }
    
    public function mapPaymentModuleCode(\WC_Order $order)
    {
        switch ($order->get_payment_method()) {
            case 'paypal':
                return PaymentTypes::TYPE_PAYPAL_EXPRESS;
            case 'express_checkout':
                return PaymentTypes::TYPE_PAYPAL_EXPRESS;
            case 'cod':
                return PaymentTypes::TYPE_CASH_ON_DELIVERY;
            case 'bacs':
                return PaymentTypes::TYPE_BANK_TRANSFER;
            case 'direct-debit':
                return PaymentTypes::TYPE_DIRECT_DEBIT;
            case 'german_market_purchase_on_account':
                return PaymentTypes::TYPE_INVOICE;
            case 'german_market_sepa_direct_debit':
                return PaymentTypes::TYPE_DIRECT_DEBIT;
            case 'invoice':
                return PaymentTypes::TYPE_INVOICE;
            default:
                return $order->get_payment_method_title();
        }
    }
    
    public static function getAttributeTaxonomyIdByName($name)
    {
        $name = str_replace('pa_', '', $name);
        $taxonomies = \wp_list_pluck(\wc_get_attribute_taxonomies(), 'attribute_id', 'attribute_name');
        
        return isset($taxonomies[$name]) ? (int)$taxonomies[$name] : 0;
    }
    
    public static function removeSpecialchars($str)
    {
        return strtr($str, [
            "Ä" => "AE",
            "Ö" => "OE",
            "Ü" => "UE",
            "ä" => "ae",
            "ö" => "oe",
            "ü" => "ue",
        ]);
    }
    
    public static function sendCustomPropertiesEnabled()
    {
        if (Config::has(JtlConnectorAdmin::OPTIONS_SEND_CUSTOM_PROPERTIES)) {
            $result = (boolean)Config::get(JtlConnectorAdmin::OPTIONS_SEND_CUSTOM_PROPERTIES);
        } else {
            Config::set(JtlConnectorAdmin::OPTIONS_SEND_CUSTOM_PROPERTIES, true);
            $result = true;
        }
        
        return $result;
    }
    
    public static function useGtinAsEanEnabled()
    {
        if (Config::has(JtlConnectorAdmin::OPTIONS_USE_GTIN_FOR_EAN)) {
            $result = (boolean)Config::get(JtlConnectorAdmin::OPTIONS_USE_GTIN_FOR_EAN);
        } else {
            Config::set(JtlConnectorAdmin::OPTIONS_USE_GTIN_FOR_EAN, true);
            $result = true;
        }
        
        return $result;
    }
    
    public static function showVariationSpecificsOnProductPageEnabled()
    {
        if (Config::has(JtlConnectorAdmin::OPTIONS_SHOW_VARIATION_SPECIFICS_ON_PRODUCT_PAGE)) {
            $result = (boolean)Config::get(JtlConnectorAdmin::OPTIONS_SHOW_VARIATION_SPECIFICS_ON_PRODUCT_PAGE);
        } else {
            Config::set(JtlConnectorAdmin::OPTIONS_SHOW_VARIATION_SPECIFICS_ON_PRODUCT_PAGE, true);
            $result = true;
        }
        
        return $result;
    }
    
    /**
     * @return Singleton|$this
     */
    public static function getInstance()
    {
        return parent::getInstance();
    }
}
