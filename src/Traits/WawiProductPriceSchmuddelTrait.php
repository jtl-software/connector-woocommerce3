<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Traits;

use Jtl\Connector\Core\Model\CustomerGroup as CustomerGroupModel;
use Jtl\Connector\Core\Model\Product as ProductModel;
use Jtl\Connector\Core\Model\ProductPrice as ProductPriceModel;
use Jtl\Connector\Core\Model\ProductPriceItem as ProductPriceItemModel;
use JtlWooCommerceConnector\Controllers\GlobalData\CustomerGroup;

trait WawiProductPriceSchmuddelTrait
{
    private function fixProductPriceForCustomerGroups(ProductModel $product, \WC_Product $wcProduct)
    {
        $pd = \wc_get_price_decimals();
        $pushedPrices = $product->getPrices();
        $defaultPrices = null;
        $defaultPriceNet = 0;
        $prices = [];
        $vat = $this->util->getTaxRateByTaxClass($wcProduct->get_tax_class());
        
        foreach ($pushedPrices as $pKey => $pValue) {
            if ($pValue->getCustomerGroupId()->getEndpoint() === '') {
                if (count($product->getPrices()) === 1) {
                    $customerGroups = (new CustomerGroup($this->database, $this->util))->pullData();
    
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
        
        $product->setPrices(...$prices);
    }
}
