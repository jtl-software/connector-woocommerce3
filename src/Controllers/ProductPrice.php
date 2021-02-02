<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers;

use jtl\Connector\Model\ProductPrice as JtlProductPrice;
use JtlWooCommerceConnector\Controllers\Product\Product;
use JtlWooCommerceConnector\Controllers\Traits\PushTrait;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlProduct;
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
            $vat = Util::getInstance()->getTaxRateByTaxClass($wcProduct->get_tax_class());

            $wcProducts = [$wcProduct];
            if ($this->wpml->canBeUsed()) {
                $wcProductTranslations = $this->wpml->getComponent(WpmlProduct::class)->getWooCommerceProductTranslations($wcProduct);
                $wcProducts = array_merge($wcProducts, $wcProductTranslations);
            }
            foreach ($wcProducts as $wcProduct) {
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
