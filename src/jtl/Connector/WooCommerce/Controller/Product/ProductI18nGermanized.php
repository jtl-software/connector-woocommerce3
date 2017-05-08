<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller\Product;

use jtl\Connector\Model\ProductI18n as ProductI18nModel;

class ProductI18nGermanized extends ProductI18n
{
    protected function onProductI18nMapped(ProductI18nModel &$i18n, \WC_Product $product)
    {
        parent::onProductI18nMapped($i18n, $product);

        if ($product->gzd_product->has_product_units()) {
            $i18n->setMeasurementUnitName($product->gzd_product->unit);
        }
    }
}
