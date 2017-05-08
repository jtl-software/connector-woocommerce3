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
    protected $pull = [
        // Identifier
        'id'                     => null,
        'isMasterProduct'        => null,
        'masterProductId'        => null,
        // Base data
        'sku'                    => null,
        'vat'                    => null,
        'sort'                   => null,
        'isTopProduct'           => null,
        'productTypeId'          => null,
        'keywords'               => null,
        // Dates
        'creationDate'           => null,
        'modified'               => null,
        'availableFrom'          => null,
        // Dimensions
        'height'                 => 'height',
        'length'                 => 'length',
        'width'                  => 'width',
        'shippingWeight'         => 'weight',
        // Stock
        'considerStock'          => null,
        'considerVariationStock' => null,
        'permitNegativeStock'    => null,
        'stockLevel'             => 'Product\ProductStockLevel',
        // Other
        'shippingClassId'        => null,
        // Price
        'prices'                 => 'Product\ProductPrice',
        'specialPrices'          => 'Product\ProductSpecialPrice',
        // Relations
        'categories'             => 'Product\Product2Category',
        'i18ns'                  => 'Product\ProductI18n',
        'attributes'             => 'Product\ProductAttr',
        'variations'             => 'Product\ProductVariation',
        'checksums'              => 'Product\ProductChecksum',
    ];

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

    protected function id(\WC_Product $product)
    {
        return new Identity($product->get_id());
    }

    protected function isMasterProduct(\WC_Product $product)
    {
        return $product->is_type('variable');
    }

    protected function masterProductId(\WC_Product $product)
    {
        if ($product->get_parent_id() === 0) {
            return null;
        } else {
            return new Identity($product->get_parent_id());
        }
    }

    protected function productTypeId(\WC_Product $product)
    {
        return new Identity($product->get_type());
    }

    protected function sku(\WC_Product $product)
    {
        return $product->get_sku();
    }

    protected function sort(\WC_Product $product)
    {
        return $product->get_menu_order();
    }

    protected function isTopProduct(\WC_Product $product)
    {
        return $product->get_featured();
    }

    protected function keywords(\WC_Product $product)
    {
        return \wc_get_product_tag_list($product->get_id(), ' ');
    }

    protected function vat(\WC_Product $product)
    {
        return Util::getInstance()->getTaxRateByTaxClassAndShopLocation($product->get_tax_class());
    }

    protected function creationDate(\WC_Product $product)
    {
        return $product->get_date_created();
    }

    protected function modified(\WC_Product $product)
    {
        return $product->get_date_modified();
    }

    protected function availableFrom(\WC_Product $product)
    {
        $postDate = $product->get_date_created();
        $modDate = $product->get_date_modified();

        return $postDate <= $modDate ? null : $postDate;
    }

    protected function considerStock(\WC_Product $product)
    {
        return $product->managing_stock();
    }

    protected function considerVariationStock(\WC_Product $product)
    {
        return $this->considerStock($product);
    }

    protected function permitNegativeStore(\WC_Product $product)
    {
        return $product->backorders_allowed();
    }

    protected function shippingClassId(\WC_Product $product)
    {
        return new Identity($product->get_shipping_class_id());
    }

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
