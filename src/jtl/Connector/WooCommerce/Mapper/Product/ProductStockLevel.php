<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Mapper\Product;

use jtl\Connector\Model\Identity;
use jtl\Connector\WooCommerce\Mapper\BaseObjectMapper;

class ProductStockLevel extends BaseObjectMapper
{
    protected $pull = [
        'productId'  => null,
        'stockLevel' => null,
    ];

    protected function productId(\WC_Product $product)
    {
        return new Identity($product->get_id());
    }

    protected function stockLevel(\WC_Product $product)
    {
        return (double)$product->get_stock_quantity();
    }
}
