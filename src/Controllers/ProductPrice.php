<?php

/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers;

use Exception;
use InvalidArgumentException;
use jtl\Connector\Model\ProductPrice as JtlProductPrice;
use JtlWooCommerceConnector\Controllers\Product\Product;
use JtlWooCommerceConnector\Utilities\Util;

class ProductPrice extends \JtlWooCommerceConnector\Controllers\Product\ProductPrice
{
    /**
     * @param JtlProductPrice $productPrice
     * @return JtlProductPrice
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function pushData(JtlProductPrice $productPrice): JtlProductPrice
    {
        $wcProduct = \wc_get_product($productPrice->getProductId()->getEndpoint());

        if ($wcProduct !== false) {
            $vat = $productPrice->getVat();
            if (\is_null($vat)) {
                $vat = Util::getInstance()->getTaxRateByTaxClass($wcProduct->get_tax_class());
            }

            $this->savePrices(
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
        $type = match ($wcProduct->get_type()) {
            'variable' => Product::TYPE_PARENT,
            'variation' => Product::TYPE_CHILD,
            default => Product::TYPE_SINGLE,
        };

        return $type;
    }
}
