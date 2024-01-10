<?php

namespace JtlWooCommerceConnector\Controllers\Product;

use Jtl\Connector\Core\Model\Product as ProductModel;
use JtlWooCommerceConnector\Controllers\AbstractBaseController;

/**
 * Class ProductAdvancedCustomFields
 *
 * @package JtlWooCommerceConnector\Controllers\Product
 */
class ProductAdvancedCustomFieldsController extends AbstractBaseController
{
    public function pullData(ProductModel &$product, \WC_Product $wcProduct)
    {
    }

    public function pushData(ProductModel $product)
    {
    }
}
