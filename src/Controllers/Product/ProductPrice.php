<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Product;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\Model\ProductPrice as ProductPriceModel;
use jtl\Connector\Model\ProductPriceItem;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Controllers\GlobalData\CustomerGroup;
use JtlWooCommerceConnector\Utilities\Util;

class ProductPrice extends BaseController
{
    const GUEST_CUSTOMER_GROUP = 'wc_guest_customer_group';
    
    public function pullData(\WC_Product $product)
    {
        return (new ProductPriceModel())
            ->setId(new Identity($product->get_id()))
            ->setProductId(new Identity($product->get_id()))
            ->setCustomerGroupId(new Identity(CustomerGroup::DEFAULT_GROUP))
            ->addItem((new ProductPriceItem())
                ->setProductPriceId(new Identity($product->get_id()))
                ->setQuantity(1)
                ->setNetPrice($this->netPrice($product)));
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
            Util::getInstance()->updateProductPrices($productPrices, $product, $product->getVat());
        }
    }
}
