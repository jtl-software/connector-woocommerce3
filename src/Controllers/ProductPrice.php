<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers;

use jtl\Connector\Model\ProductPrice as ProductPriceModel;
use JtlWooCommerceConnector\Controllers\Traits\PushTrait;
use JtlWooCommerceConnector\Utilities\Util;

class ProductPrice extends BaseController
{
    use PushTrait;

    public function pushData(ProductPriceModel $productPrice)
    {
        $product = \wc_get_product($productPrice->getProductId()->getEndpoint());

        if ($product !== false) {
            $vat = Util::getInstance()->getTaxRateByTaxClass($product->get_tax_class());
            Util::getInstance()->updateProductPrice($productPrice, $vat);
            // Update the max and min prices for the parent product
            if ($product->is_type('variation')) {
                \WC_Product_Variable::sync($product->get_id());
            }
        }

        return $productPrice;
    }
}
