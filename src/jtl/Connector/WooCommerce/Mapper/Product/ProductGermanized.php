<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Mapper\Product;

class ProductGermanized extends Product
{
    public function __construct()
    {
        parent::__construct('Product');

        $this->pull['i18ns'] = 'Product\ProductI18nGermanized';
        $this->push['Product\ProductI18nGermanized'] = 'i18ns';
    }
}
