<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers;

use Jtl\Connector\Core\Controller\PushInterface;
use Jtl\Connector\Core\Model\AbstractModel;
use JtlWooCommerceConnector\Controllers\Product\ProductController;
use JtlWooCommerceConnector\Utilities\Util;

class ProductPriceController extends \JtlWooCommerceConnector\Controllers\Product\ProductPrice implements PushInterface
{
    /**
     * @param AbstractModel $model
     * @return AbstractModel
     */
    public function push(AbstractModel $model) : AbstractModel
    {
        $wcProduct = \wc_get_product($model->getProductId()->getEndpoint());

        if ($wcProduct !== false) {
            $vat = $model->getVat();
            if (is_null($vat)) {
                $vat = $this->util->getTaxRateByTaxClass($wcProduct->get_tax_class());
            }

            $this->savePrices(
                $wcProduct,
                $vat,
                $this->getJtlProductType($wcProduct),
                ...[$model]
            );

            // Update the max and min prices for the parent product
            if ($wcProduct->is_type('variation')) {
                \WC_Product_Variable::sync($wcProduct->get_id());
            }

            \wc_delete_product_transients($wcProduct->get_id());
        }

        return $model;
    }

    /**
     * @param \WC_Product $wcProduct
     * @return string
     */
    protected function getJtlProductType(\WC_Product $wcProduct): string
    {
        switch ($wcProduct->get_type()) {
            case 'variable':
                $type = ProductController::TYPE_PARENT;
                break;
            case 'variation':
                $type = ProductController::TYPE_CHILD;
                break;
            case 'simple':
            default:
                $type = ProductController::TYPE_SINGLE;
                break;
        }

        return $type;
    }

}
