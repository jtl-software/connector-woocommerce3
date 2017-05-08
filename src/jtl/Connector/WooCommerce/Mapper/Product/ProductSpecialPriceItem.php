<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Mapper\Product;

use jtl\Connector\Model\Identity;
use jtl\Connector\WooCommerce\Controller\GlobalData\CustomerGroup;
use jtl\Connector\WooCommerce\Mapper\BaseObjectMapper;
use jtl\Connector\WooCommerce\Utility\Util;

class ProductSpecialPriceItem extends BaseObjectMapper
{
    protected $pull = [
        'customerGroupId'       => null,
        'productSpecialPriceId' => null,
        'priceNet'              => null,
    ];

    protected function customerGroupId()
    {
        return new Identity(CustomerGroup::DEFAULT_GROUP);
    }

    protected function productSpecialPriceId(\WC_Product $product)
    {
        return new Identity($product->get_id());
    }

    protected function priceNet(\WC_Product $product)
    {
        $taxRate = Util::getInstance()->getTaxRateByTaxClassAndShopLocation($product->get_tax_class());

        if (\wc_prices_include_tax() && $taxRate != 0) {
            $netPrice = ((float)$product->get_sale_price()) / ($taxRate + 100) * 100;
        } else {
            $netPrice = round((float)$product->get_sale_price(), \wc_get_price_decimals());
        }

        return $netPrice;
    }
}
