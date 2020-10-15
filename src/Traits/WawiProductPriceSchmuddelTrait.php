<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Traits;

use jtl\Connector\Model\CustomerGroup as CustomerGroupModel;
use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\Model\ProductPrice as ProductPriceModel;
use jtl\Connector\Model\ProductPriceItem as ProductPriceItemModel;
use JtlWooCommerceConnector\Controllers\GlobalData\CustomerGroup;
use JtlWooCommerceConnector\Utilities\Util;

trait WawiProductPriceSchmuddelTrait
{
    private function fixProductPriceForCustomerGroups(ProductModel &$product, \WC_Product $wcProduct)
    {
        $pd = \wc_get_price_decimals();
        $pushedPrices = $product->getPrices();
        $defaultPrices = null;
        $defaultPriceNet = 0;
        $prices = [];
        $vat = Util::getInstance()->getTaxRateByTaxClass($wcProduct->get_tax_class());
        
        foreach ($pushedPrices as $pKey => $pValue) {
            if ($pValue->getCustomerGroupId()->getEndpoint() === '') {
                if (count($product->getPrices()) === 1) {
                    $customerGroups = (new CustomerGroup)->pullData();
    
                    /** @var CustomerGroupModel $customerGroup */
                    foreach ($customerGroups as $cKey => $customerGroup){
                        $missingProductPrice = clone($pValue);
                        $missingProductPrice->setCustomerGroupId($customerGroup->getId());
                        $prices[] = $missingProductPrice;
                    }
                }
                
                $defaultPrices = $pValue;
                
                /** @var ProductPriceItemModel $item */
                foreach ($pValue->getItems() as $ikey => $item) {
                    if ($item->getQuantity() === 0) {
                        if (\wc_prices_include_tax()) {
                            $defaultPriceNet = round($item->getNetPrice() * (1 + $vat / 100), $pd);
                        } else {
                            $defaultPriceNet = $item->getNetPrice();
                            $defaultPriceNet = round($defaultPriceNet, $pd);
                        }
                    }
                }
                
                $prices[] = $pValue;
            } else {
                $prices[] = $pValue;
            }
        }
        
        if ($defaultPrices === null) {
            return;
        }
        
        /** @var ProductPriceModel $productPrice */
        foreach ($prices as $pkey => $productPrice) {
            $hasRegularPrice = false;
            
            if ($productPrice->getCustomerGroupId()->getEndpoint() === '') {
                continue;
            }
            
            foreach ($productPrice->getItems() as $iKey => $iValue) {
                if ($iValue->getQuantity() === 0) {
                    $hasRegularPrice = true;
                }
            }
            
            if (!$hasRegularPrice) {
                $productPrice->addItem((new ProductPriceItemModel())
                    ->setNetPrice((float)$defaultPriceNet)
                    ->setQuantity(0)
                );
            }
        }
        
        $product->setPrices($prices);
    }
}
