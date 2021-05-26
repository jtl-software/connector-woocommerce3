<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers;

use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\Model\ProductPrice as JtlProductPrice;
use JtlWooCommerceConnector\Controllers\GlobalData\CustomerGroup;
use JtlWooCommerceConnector\Controllers\Product\Product;
use JtlWooCommerceConnector\Controllers\Product\ProductPrice as MainProductPrice;
use JtlWooCommerceConnector\Controllers\Traits\PushTrait;
use JtlWooCommerceConnector\Utilities\Config;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;

class ProductPrice extends \JtlWooCommerceConnector\Controllers\Product\ProductPrice
{
    use PushTrait;

    /**
     * @param JtlProductPrice $productPrice
     *
     * @return JtlProductPrice
     */
    public function pushData(JtlProductPrice $productPrice)
    {
        $wcProduct = \wc_get_product($productPrice->getProductId()->getEndpoint());

        if ($wcProduct !== false) {
            $vat = $productPrice->getVat();
            if (is_null($vat)) {
                $vat = Util::getInstance()->getTaxRateByTaxClass($wcProduct->get_tax_class());
            }

            parent::pushData(
                $wcProduct,
                $vat,
                $this->getJtlProductType($wcProduct),
                ...[$productPrice]
            );

            // Update the max and min prices for the parent product
            if ($wcProduct->is_type('variation')) {
                \WC_Product_Variable::sync($wcProduct->get_id());
            }

            \wc_delete_product_transients($wcProduct->get_id());
        }

        return $productPrice;
    }

    /**
     * @param \WC_Product $wcProduct
     * @return string
     */
    protected function getJtlProductType(\WC_Product $wcProduct): string
    {
        switch ($wcProduct->get_type()) {
            case 'variable':
                $type = Product::TYPE_PARENT;
                break;
            case 'variation':
                $type = Product::TYPE_CHILD;
                break;
            case 'simple':
            default:
                $type = Product::TYPE_SINGLE;
                break;
        }

        return $type;
    }

}
