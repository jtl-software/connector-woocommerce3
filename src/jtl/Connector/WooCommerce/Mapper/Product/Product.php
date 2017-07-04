<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Mapper\Product;

use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\WooCommerce\Mapper\BaseObjectMapper;

class Product extends BaseObjectMapper
{
    protected $push = [
        'id'                  => 'id',
        'post_type'           => null,
        'type'                => null,
        'post_date'           => null,
        'Product\ProductI18n' => 'i18ns',
    ];

    protected function post_type(ProductModel $product)
    {
        if (empty($product->getMasterProductId()->getEndpoint())) {
            return 'product';
        }

        return 'product_variation';
    }

    protected function post_date(ProductModel $product)
    {
        $date = is_null($product->getAvailableFrom()) ? $product->getCreationDate() : $product->getAvailableFrom();

        if (is_null($date)) {
            return null;
        }

        $date->setTimezone(new \DateTimeZone('UTC'));

        return $date->format('Y-m-d H:i:s');
    }

    protected function type(ProductModel $product)
    {
        $variations = $product->getVariations();
        $type = $product->getProductTypeId()->getEndpoint();

        if (in_array($type, \wc_get_product_types())) {
            return $type;
        } elseif (!empty($variations)) {
            return 'variable';
        }

        return 'simple';
    }
}
