<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Mapper\Product;

use jtl\Connector\Model\Identity;
use jtl\Connector\WooCommerce\Mapper\LocaleBaseObjectMapper;

class ProductI18n extends LocaleBaseObjectMapper
{
    protected $pull = [
        'name'             => null,
        'productId'        => null,
        'description'      => null,
        'languageISO'      => null,
        'shortDescription' => null,
        'urlPath'          => null,
    ];

    protected $push = [
        'post_title'   => 'name',
        'post_name'    => 'urlPath',
        'post_content' => 'description',
        'post_excerpt' => 'shortDescription',
    ];

    protected function productId(\WC_Product $product)
    {
        return new Identity($product->get_id());
    }

    protected function name(\WC_Product $product)
    {
        return $product->get_name();
    }

    protected function description(\WC_Product $product)
    {
        return $product->get_description();
    }

    protected function shortDescription(\WC_Product $product)
    {
        return $product->get_short_description();
    }

    protected function urlPath(\WC_Product $product)
    {
        return $product->get_slug();
    }
}
