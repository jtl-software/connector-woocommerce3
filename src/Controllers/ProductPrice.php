<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers;

use jtl\Connector\Model\ProductPrice as ProductPriceModel;
use JtlWooCommerceConnector\Controllers\GlobalData\CustomerGroup;
use JtlWooCommerceConnector\Controllers\Product\ProductPrice as MainProductPrice;
use JtlWooCommerceConnector\Controllers\Traits\PushTrait;
use JtlWooCommerceConnector\Utilities\Config;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;

class ProductPrice extends BaseController
{
    use PushTrait;
    
    /**
     * @param ProductPriceModel $productPrice
     *
     * @return ProductPriceModel
     */
    public function pushData(ProductPriceModel $productPrice)
    {
        $product = \wc_get_product($productPrice->getProductId()->getEndpoint());
        
        if ($product !== false) {
            $vat = Util::getInstance()->getTaxRateByTaxClass($product->get_tax_class());
            $this->updateProductPrice($product, $productPrice, $vat);
            // Update the max and min prices for the parent product
            if ($product->is_type('variation')) {
                \WC_Product_Variable::sync($product->get_id());
            }
        }
        
        return $productPrice;
    }
    
    /**
     * @param \WC_Product       $product
     * @param ProductPriceModel $productPrice
     * @param                   $vat
     */
    public function updateProductPrice(\WC_Product $product, ProductPriceModel $productPrice, $vat)
    {
        $pd = \wc_get_price_decimals();
        
        if ($pd < 4) {
            $pd = 4;
        }
        
        $customerGroupId = $productPrice->getCustomerGroupId()->getEndpoint();
        
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)
            && version_compare(
                (string)SupportedPlugins::getVersionOf(SupportedPlugins::PLUGIN_B2B_MARKET),
                '1.0.3',
                '>')) {
            /** @var ProductPriceModel $productPrice */
            if (
                (string)$customerGroupId === Config::get('jtlconnector_default_customer_group') ||
                (string)$customerGroupId === ""
            ) {
                
                $salePriceKey = '_sale_price';
                $priceKey = '_price';
                $regularPriceKey = '_regular_price';
                $productId = $product->get_id();
                foreach ($productPrice->getItems() as $item) {
                    if (\wc_prices_include_tax()) {
                        $newPrice = $item->getNetPrice() * (1 + $vat / 100);
                    } else {
                        $newPrice = $item->getNetPrice();
                        $newPrice = Util::getNetPriceCutted($newPrice, $pd);
                    }
                    
                    if ($item->getQuantity() === 0) {
                        $sellPriceIdForGet = $product->get_id();
                        
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
        
        if ($customerGroupId === '') {
            $customerGroupId = MainProductPrice::GUEST_CUSTOMER_GROUP;
        }
        
        if ($customerGroupId === MainProductPrice::GUEST_CUSTOMER_GROUP || !Util::getInstance()->isValidCustomerGroup($customerGroupId)) {
            return;
        }
        
        $parentProduct = null;
        $productId = $product->get_id();
        $customerGroup = null;
        
        if (CustomerGroup::DEFAULT_GROUP === $customerGroupId) {
            $salePriceKey = '_sale_price';
            $priceKey = '_price';
            $regularPriceKey = '_regular_price';
        } elseif (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)) {
            $productType = $product->get_type();
            $customerGroup = \get_post($customerGroupId);
            
            if ($pd > 3) {
                $pd = 3;
            }
            
            if ($productType !== 'variable') {
                $parentProduct = \wc_get_product($product->get_parent_id());
                
                if ($parentProduct === false) {
                    $parentProduct = null;
                }
                
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
            
            $bulkPrices = [];
            
            foreach ($productPrice->getItems() as $item) {
                
                if (\wc_prices_include_tax()) {
                    $newPrice = $item->getNetPrice() * (1 + $vat / 100);
                } else {
                    $newPrice = $item->getNetPrice();
                    $newPrice = Util::getNetPriceCutted($newPrice, $pd);
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
                
                $var1 = $item->getQuantity() > 0;
                $var2 = !is_null($customerGroup);
                $var3 = SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET);
                
                if ($var1 && $var2 && $var3) {
                    $bulkPrices[] = [
                        'bulk_price'      => (string)$newPrice,
                        'bulk_price_from' => (string)$item->getQuantity(),
                        'bulk_price_to'   => '',
                        'bulk_price_type' => 'fix',
                    ];
                }
            }
            
            if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)) {
                if (count($bulkPrices) > 0) {

                    $bulkPrices = Util::setBulkPricesQuantityTo($bulkPrices);
                    
                    $metaKey = sprintf('bm_%s_bulk_prices', $customerGroup->post_name);
                    $metaProductId = $product->get_id();
                    
                    \update_post_meta(
                        $metaProductId,
                        $metaKey,
                        $bulkPrices,
                        \get_post_meta($metaProductId, $metaKey, true)
                    );
                    
                    if ($product->get_parent_id() !== 0) {
                        $metaKey = sprintf('bm_%s_%s_bulk_prices', $customerGroup->post_name,
                            $product->get_id());
                        $metaProductId = $product->get_parent_id();
                        
                        \update_post_meta(
                            $metaProductId,
                            $metaKey,
                            $bulkPrices,
                            \get_post_meta($metaProductId, $metaKey, true)
                        );
                    }
                } else {
                    
                    if (is_null($customerGroup)) {
                        return;
                    }
                    
                    $metaKey = sprintf('bm_%s_bulk_prices', $customerGroup->post_name);
                    $metaProductId = $product->get_id();
                    
                    \delete_post_meta(
                        $metaProductId,
                        $metaKey
                    );
                    
                    if ($product->get_parent_id() !== '0') {
                        $metaKey = sprintf('bm_%s_%s_bulk_prices', $customerGroup->post_name,
                            $product->get_id());
                        $metaProductId = $product->get_parent_id();
                        \delete_post_meta(
                            $metaProductId,
                            $metaKey
                        );
                    }
                }
            }
        }
    }
}
