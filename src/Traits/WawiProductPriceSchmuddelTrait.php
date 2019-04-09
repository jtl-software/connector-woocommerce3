<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Traits;

use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\Model\ProductPrice as ProductPriceModel;
use jtl\Connector\Model\ProductPriceItem as ProductPriceItemModel;
use JtlWooCommerceConnector\Utilities\Util;

trait WawiProductPriceSchmuddelTrait
{
    private function fixProductPriceForCustomerGroups(ProductModel &$product, \WC_Product $wcProduct)
    {
        $pushedPrices = $product->getPrices();
        $defaultPrices = null;
        $defaultPriceNetto = 0;
        $prices = [];
        $vat = Util::getInstance()->getTaxRateByTaxClass($wcProduct->get_tax_class());
        
        foreach ($pushedPrices as $pKey => $pValue) {
            if ($pValue->getCustomerGroupId()->getEndpoint() === '') {
                $defaultPrices = $pValue;
                
                /** @var ProductPriceItemModel $item */
                foreach ($pValue->getItems() as $ikey => $item) {
                    if ($item->getQuantity() === 0) {
                        if (\wc_prices_include_tax()) {
                            $defaultPriceNetto = $item->getNetPrice() * (1 + $vat / 100);
                        } else {
                            $defaultPriceNetto = $item->getNetPrice();
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
                    ->setNetPrice($defaultPriceNetto)
                ->setQuantity(0)
                );
            }
        }
        
        $product->setPrices($prices);
    }
}
