<?php

/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers;

use Jtl\Connector\Core\Controller\PushInterface;
use Jtl\Connector\Core\Model\AbstractModel;
use Jtl\Connector\Core\Model\ProductStockLevel as ProductStockLevelModel;

class ProductStockLevel extends AbstractBaseController implements PushInterface
{
    /**
     * @param ProductStockLevelModel $model
     * @return ProductStockLevelModel
     * @throws \Exception
     */
    public function push(AbstractModel $model): AbstractModel
    {
        $productId = $model->getProductId()->getEndpoint();
        $wcProduct = \wc_get_product($productId);

        if ($wcProduct === false) {
            return $model;
        }

        if ('yes' === \get_option('woocommerce_manage_stock')) {
            \update_post_meta($productId, '_manage_stock', 'yes');

            $stockLevel  = $model->getStockLevel();
            $stockStatus = $this->util->getStockStatus($stockLevel, $wcProduct->backorders_allowed());

            // Stock status is always determined by children so sync later.
            if (!$wcProduct->is_type('variable')) {
                $wcProduct->set_stock_status($stockStatus);
            }

            \wc_update_product_stock($productId, \wc_stock_amount($stockLevel));

            if ($wcProduct->is_type('variation')) {
                \WC_Product_Variable::sync_stock_status($wcProduct->get_id());
            }

            \wc_delete_product_transients($wcProduct->get_id());
        }

        return $model;
    }
}
