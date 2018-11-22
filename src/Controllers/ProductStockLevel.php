<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers;

use jtl\Connector\Model\ProductStockLevel as ProductStockLevelModel;
use JtlWooCommerceConnector\Controllers\Traits\PushTrait;
use JtlWooCommerceConnector\Utilities\Util;

class ProductStockLevel extends BaseController
{
    use PushTrait;

    public function pushData(ProductStockLevelModel $productStockLevel)
    {
        $productId = $productStockLevel->getProductId()->getEndpoint();
        $wcProduct = \wc_get_product($productId);

        if ($wcProduct === false) {
            return $productStockLevel;
        }

        if ('yes' === \get_option('woocommerce_manage_stock')) {
            \update_post_meta($productId, '_manage_stock', 'yes');

            $stockLevel = $productStockLevel->getStockLevel();
            $stockStatus = Util::getInstance()->getStockStatus($stockLevel, $wcProduct->backorders_allowed());

            // Stock status is always determined by children so sync later.
            if (!$wcProduct->is_type('variable')) {
                $wcProduct->set_stock_status($stockStatus);
            }

            \wc_update_product_stock($productId, \wc_stock_amount($stockLevel));

            if ($wcProduct->is_type('variation')) {
                \WC_Product_Variable::sync_stock_status($wcProduct->get_id());
            }
        }

        return $productStockLevel;
    }
}
