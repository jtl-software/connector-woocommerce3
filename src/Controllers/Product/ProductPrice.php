<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Product;

use jtl\Connector\Model\CustomerGroup as CustomerGroupModel;
use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\Model\ProductPrice as ProductPriceModel;
use jtl\Connector\Model\ProductPriceItem;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Controllers\GlobalData\CustomerGroup;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;

class ProductPrice extends BaseController
{
    const GUEST_CUSTOMER_GROUP = 'wc_guest_customer_group';
    
    public function pullData(\WC_Product $product, ProductModel $model)
    {
        $prices = [];
        $groupController = (new CustomerGroup);
        
        if (!SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)) {
            $prices[] = (new ProductPriceModel())
                ->setId(new Identity($product->get_id()))
                ->setProductId(new Identity($product->get_id()))
                ->setCustomerGroupId(new Identity(CustomerGroup::DEFAULT_GROUP))
                ->addItem((new ProductPriceItem())
                    ->setProductPriceId(new Identity($product->get_id()))
                    ->setQuantity(1)
                    ->setNetPrice($this->netPrice($product)));
        } else {
            $customerGroups = $groupController->pullData();
            
            /** @var CustomerGroupModel $customerGroup */
            foreach ($customerGroups as $cKey => $customerGroup) {
                
                $items = [];
                
                if ($customerGroup->getId()->getEndpoint() === CustomerGroup::DEFAULT_GROUP) {
                    $items [] = (new ProductPriceItem())
                        ->setProductPriceId(new Identity($product->get_id()))
                        ->setQuantity(1)
                        ->setNetPrice($this->netPrice($product));
                } else {
                    $groupSlug = $groupController->getSlugById($customerGroup->getId()->getEndpoint());
                    
                    if ($model->getIsMasterProduct()) {
                        $productIdForMeta = $product->get_id();
                        $priceKeyForMeta = sprintf('bm_%s_price', $groupSlug);
                    } else {
                        $productIdForMeta = $product->get_parent_id();
                        $priceKeyForMeta = sprintf('bm_%s_%s_price', $groupSlug, $product->get_id());
                    }
                    
                    $price = \get_post_meta($productIdForMeta, $priceKeyForMeta, true);
                    $items [] = (new ProductPriceItem())
                        ->setProductPriceId(new Identity($product->get_id()))
                        ->setQuantity(1)
                        ->setNetPrice((float)$price);
                    
                    $items = $this->getBulkPrices($items, $customerGroup, $groupSlug, $product, $model);
                }
                
                $prices[] = (new ProductPriceModel())
                    ->setId(new Identity($product->get_id()))
                    ->setProductId(new Identity($product->get_id()))
                    ->setCustomerGroupId($customerGroup->getId())
                    ->setItems($items);
            }
        }
        
        return $prices;
    }
    
    private function getBulkPrices(
        $items,
        CustomerGroupModel $customerGroup,
        $groupSlug,
        \WC_Product $product,
        ProductModel $model
    ) {
        if ($model->getIsMasterProduct()) {
            $metaKey = sprintf('bm_%s_bulk_prices', $groupSlug);
            $metaProductId = $product->get_id();
        } else {
            $metaKey = sprintf('bm_%s_%s_bulk_prices', $groupSlug, $product->get_id());
            $metaProductId = $product->get_parent_id();
        }
        
        $bulkPrices = \get_post_meta($metaProductId, $metaKey, true);
        
        if (!is_array($bulkPrices)) {
            $bulkPrices = [];
        }
        
        foreach ($bulkPrices as $bulkPrice) {
            if ($bulkPrice['bulk_price_type'] === 'fix') {
                
                $items[] = (new ProductPriceItem())
                    ->setProductPriceId(new Identity($product->get_id()))
                    ->setQuantity((int)$bulkPrice['bulk_price_from'])
                    ->setNetPrice((float)$bulkPrice['bulk_price']);
            }
        }
        
        return $items;
    }
    
    protected function netPrice(\WC_Product $product)
    {
        $taxRate = Util::getInstance()->getTaxRateByTaxClass($product->get_tax_class());
        
        if (\wc_prices_include_tax() && $taxRate != 0) {
            $netPrice = ((float)$product->get_regular_price()) / ($taxRate + 100) * 100;
        } else {
            $netPrice = round((float)$product->get_regular_price(), \wc_get_price_decimals());
        }
        
        return $netPrice;
    }
    
    public function pushData(ProductModel $product)
    {
        $productPrices = [];
        
        foreach ($product->getPrices() as &$price) {
            $endpoint = $price->getCustomerGroupId()->getEndpoint();
            
            if (Util::getInstance()->isValidCustomerGroup($endpoint)) {
                if ($endpoint === '') {
                    $endpoint = self::GUEST_CUSTOMER_GROUP;
                }
                $price->setProductId($product->getId());
                $productPrices[$endpoint] = $price;
            }
        }
        
        if (count($productPrices) > 0) {
            $this->updateProductPrices($productPrices, $product, $product->getVat());
        }
    }
    
    public function updateProductPrices($productPrices, ProductModel $product, $vat)
    {
        $productId = $product->getId()->getEndpoint();
        $pd = \wc_get_price_decimals();
        
        /** @var ProductPriceModel $productPrice */
        foreach ($productPrices as $customerGroupId => $productPrice) {
            if (!Util::getInstance()->isValidCustomerGroup((string)$customerGroupId)
                || (string)$customerGroupId === self::GUEST_CUSTOMER_GROUP) {
                continue;
            }
            
            $customerGroupMeta = null;
            
            if (is_int($customerGroupId)) {
                $customerGroupMeta = \get_post_meta($customerGroupId);
            }
            
            if ($customerGroupId === CustomerGroup::DEFAULT_GROUP && is_null($customerGroupMeta)) {
                if ($pd < 4){
                    $pd = 4;
                }
                
                foreach ($productPrice->getItems() as $item) {
                    if (\wc_prices_include_tax()) {
                        $regularPrice = $item->getNetPrice() * (1 + $vat / 100);
                    } else {
                        $regularPrice = $item->getNetPrice();
                        $regularPrice = Util::getNetPriceCutted($regularPrice, $pd);
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
                if ($pd > 3){
                    $pd = 3;
                }
                $customerGroup = get_post($customerGroupId);
                $productType = (new Product)->getType($product);
                $bulkPrices = [];
                
                foreach ($productPrice->getItems() as $item) {
                    if (\wc_prices_include_tax()) {
                        $regularPrice = $item->getNetPrice() * (1 + $vat / 100);
                    } else {
                        $regularPrice = $item->getNetPrice();
                        $regularPrice = Util::getNetPriceCutted($regularPrice, $pd);
                    }
                    
                    if ($item->getQuantity() === 0) {
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
                            'bulk_price'      => (string)$regularPrice,
                            'bulk_price_from' => (string)$regularPrice,
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
                    
                    if (!$product->getMasterProductId()->getHost() === 0) {
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
                } else {
                    
                    $metaKey = sprintf('bm_%s_bulk_prices', $customerGroup->post_name);
                    $metaProductId = $product->getId()->getEndpoint();
                    
                    \delete_post_meta(
                        $metaProductId,
                        $metaKey
                    );
                    
                    if (!$product->getMasterProductId()->getHost() === 0) {
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
}
