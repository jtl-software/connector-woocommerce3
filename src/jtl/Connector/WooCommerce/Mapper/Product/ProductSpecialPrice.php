<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Mapper\Product;

use jtl\Connector\Model\Identity;
use jtl\Connector\WooCommerce\Mapper\BaseObjectMapper;

class ProductSpecialPrice extends BaseObjectMapper
{
    protected $pull = [
        'id'                => null,
        'productId'         => null,
        'activeFromDate'    => null,
        'activeUntilDate'   => null,
        'considerDateLimit' => null,
        'isActive'          => null,
        'items'             => 'Product\ProductSpecialPriceItem',
    ];

    protected function id(\WC_Product $product)
    {
        return $this->productId($product);
    }

    protected function productId(\WC_Product $product)
    {
        return new Identity($product->get_id());
    }

    protected function activeFromDate(\WC_Product $product)
    {
        $date = \get_post_meta($this->productId($product)->getEndpoint(), '_sale_price_dates_from', true);

        return empty($date) ? null : \DateTime::createFromFormat('U.u', number_format($date, 6, '.', ''));
    }

    protected function activeUntilDate(\WC_Product $product)
    {
        $date = \get_post_meta($this->productId($product)->getEndpoint(), '_sale_price_dates_to', true);

        return empty($date) ? null : \DateTime::createFromFormat('U.u', number_format($date, 6, '.', ''));
    }

    protected function considerDateLimit(\WC_Product $product)
    {
        $date = \get_post_meta($this->productId($product)->getEndpoint(), '_sale_price_dates_to', true);

        return !empty($date);
    }

    protected function isActive(\WC_Product $product)
    {
        return $product->is_on_sale();
    }
}
