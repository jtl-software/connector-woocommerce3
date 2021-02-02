<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Product;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\Model\ProductStockLevel as StockLevelModel;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Utilities\Util;

class ProductStockLevel extends BaseController
{
    public function pullData(\WC_Product $product)
    {
        $stockLevel = $product->get_stock_quantity();

        return (new StockLevelModel())
            ->setProductId(new Identity($product->get_id()))
            ->setStockLevel((double)is_null($stockLevel) ? 0 : $stockLevel);
    }

    public function pushDataChild(\WC_Product $wcProduct, ProductModel $product)
    {
        $wcProductId = $wcProduct->get_id();
        \update_post_meta($wcProductId, '_manage_stock', $product->getConsiderStock() ? 'yes' : 'no');

        $stockLevel = !is_null($product->getStockLevel()) ? $product->getStockLevel()->getStockLevel() : 0;

        \wc_update_product_stock_status($wcProductId, Util::getInstance()->getStockStatus(
            $stockLevel, $product->getPermitNegativeStock(), $product->getConsiderStock()
        ));

        if ($product->getConsiderStock()) {
            \update_post_meta($product->getId()->getEndpoint(), '_backorders', $product->getPermitNegativeStock() ? 'yes' : 'no');
            \wc_update_product_stock($wcProductId, \wc_stock_amount($stockLevel));
        } else {
            \delete_post_meta($wcProductId, '_backorders');
            \delete_post_meta($wcProductId, '_stock');
        }
    }

    public function pushDataParent(\WC_Product $wcProduct, ProductModel $product)
    {
        $wcProductId = $wcProduct->get_id();
        $stockLevel = !is_null($product->getStockLevel()) ? $product->getStockLevel()->getStockLevel() : 0;
        $stockStatus = Util::getInstance()->getStockStatus($stockLevel, $product->getPermitNegativeStock(), $product->getConsiderStock());

        if ('yes' == get_option('woocommerce_manage_stock')) {
            \update_post_meta($wcProductId, '_manage_stock', $product->getConsiderStock() && !$product->getIsMasterProduct() ? 'yes' : 'no');

            \update_post_meta($product->getId()->getEndpoint(), '_backorders', $product->getPermitNegativeStock() ? 'yes' : 'no');

            if ($product->getConsiderStock()) {
                if (!$wcProduct->is_type('variable')) {
                    \wc_update_product_stock_status($wcProductId, $stockStatus);
                }

                \wc_update_product_stock($wcProductId, \wc_stock_amount($stockLevel));
            } else {
                \update_post_meta($wcProductId, '_manage_stock', 'no');
                \update_post_meta($wcProductId, '_stock', '');

                \wc_update_product_stock_status($wcProductId, $stockStatus);
            }

        } elseif (!$wcProduct->is_type('variable')) {
            \wc_update_product_stock_status($wcProductId, $stockStatus);
        }
    }
}
