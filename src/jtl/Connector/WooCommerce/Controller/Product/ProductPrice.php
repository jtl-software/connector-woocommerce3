<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller\Product;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\Model\ProductPrice as ProductPriceModel;
use jtl\Connector\Model\ProductPriceItem;
use jtl\Connector\WooCommerce\Controller\BaseController;
use jtl\Connector\WooCommerce\Controller\GlobalData\CustomerGroup;
use jtl\Connector\WooCommerce\Utility\Util;

class ProductPrice extends BaseController
{
    public function pullData(\WC_Product $product, $model)
    {
        return [
            (new ProductPriceModel())
                ->setId(new Identity($product->get_id()))
                ->setProductId(new Identity($product->get_id()))
                ->setCustomerGroupId(new Identity(CustomerGroup::DEFAULT_GROUP))
                ->addItem((new ProductPriceItem())
                    ->setProductPriceId(new Identity($product->get_id()))
                    ->setQuantity(1)
                    ->setNetPrice($this->netPrice($product))),
        ];
    }

    protected function netPrice(\WC_Product $product)
    {
        $taxRate = Util::getInstance()->getTaxRateByTaxClassAndShopLocation($product->get_tax_class());

        if (\wc_prices_include_tax() && $taxRate != 0) {
            $netPrice = ((float)$product->get_regular_price()) / ($taxRate + 100) * 100;
        } else {
            $netPrice = round((float)$product->get_regular_price(), \wc_get_price_decimals());
        }

        return $netPrice;
    }

    public function pushData(ProductModel $product, $model)
    {
        $productPrice = null;

        foreach ($product->getPrices() as &$price) {
            $endpoint = $price->getCustomerGroupId()->getEndpoint();

            if (Util::getInstance()->isValidCustomerGroup($endpoint)) {
                if (is_null($productPrice)) {
                    $productPrice = $price;
                } else {
                    /** @var ProductPriceModel $productPrice */
                    if ($productPrice->getCustomerGroupId()->getEndpoint() === '') {
                        $productPrice = $price;
                    }
                }
            }
        }

        if (!is_null($productPrice)) {
            $productPrice->setProductId($product->getId());
            Util::getInstance()->updateProductPrice($productPrice, $product->getVat());
        }
    }
}
