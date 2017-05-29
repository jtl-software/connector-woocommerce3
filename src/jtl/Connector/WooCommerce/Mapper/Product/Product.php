<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Mapper\Product;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\WooCommerce\Mapper\BaseObjectMapper;
use jtl\Connector\WooCommerce\Utility\Util;

class Product extends BaseObjectMapper
{
    protected $push = [
        'id'                  => 'id',
        'sku'                 => 'sku',
        'menu_order'          => 'sort',
        'post_modified'       => 'modified',
        'post_parent'         => null,
        'post_type'           => null,
        'type'                => null,
        'post_status'         => null,
        'post_date'           => null,
        'height'              => null,
        'length'              => null,
        'weight'              => null,
        'width'               => null,
        'Product\ProductI18n' => 'i18ns',
    ];

    protected function post_parent(ProductModel $product)
    {
        $parent = $product->getMasterProductId()->getEndpoint();

        return empty($parent) ? 0 : (int)$parent;
    }

    protected function post_type(ProductModel $product)
    {
        $parentId = $product->getMasterProductId()->getEndpoint();
        if (empty($parentId)) {
            return 'product';
        }

        return 'product_variation';
    }

    protected function post_status(ProductModel $product)
    {
        if (is_null($product->getAvailableFrom())) {
            return $product->getIsActive() ? 'publish' : 'draft';
        }

        return 'future';
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

    protected function height(ProductModel $product)
    {
        return \wc_format_decimal($product->getHeight());
    }

    protected function length(ProductModel $product)
    {
        return \wc_format_decimal($product->getLength());
    }

    protected function width(ProductModel $product)
    {
        return \wc_format_decimal($product->getWidth());
    }

    protected function weight(ProductModel $product)
    {
        return \wc_format_decimal($product->getShippingWeight());
    }
}
