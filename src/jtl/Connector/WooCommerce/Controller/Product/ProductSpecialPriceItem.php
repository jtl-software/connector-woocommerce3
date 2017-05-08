<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller\Product;

use jtl\Connector\WooCommerce\Controller\BaseController;

class ProductSpecialPriceItem extends BaseController
{
    public function pullData(\WC_Product $product, $model)
    {
        return [$this->mapper->toHost($product)];
    }
}
