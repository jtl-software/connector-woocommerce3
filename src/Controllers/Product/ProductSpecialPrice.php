<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Product;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\Model\ProductSpecialPrice as ProductSpecialPriceModel;
use jtl\Connector\Model\ProductSpecialPriceItem;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Controllers\GlobalData\CustomerGroup;
use JtlWooCommerceConnector\Utilities\Util;

class ProductSpecialPrice extends BaseController
{
    public function pullData(\WC_Product $product)
    {
        $salePrice = $product->get_sale_price();

        if (empty($salePrice)) {
            return [];
        }

        return [
            (new ProductSpecialPriceModel())
                ->setId(new Identity($product->get_id()))
                ->setProductId(new Identity($product->get_id()))
                ->setIsActive($product->is_on_sale())
                ->setConsiderDateLimit(!is_null($product->get_date_on_sale_to()))
                ->setActiveFromDate($product->get_date_on_sale_from())
                ->setActiveUntilDate($product->get_date_on_sale_to())
                ->addItem((new ProductSpecialPriceItem())
                    ->setProductSpecialPriceId(new Identity($product->get_id()))
                    ->setCustomerGroupId(new Identity(CustomerGroup::DEFAULT_GROUP))
                    ->setPriceNet($this->priceNet($product)))
        ];
    }

    protected function priceNet(\WC_Product $product)
    {
        $taxRate = Util::getInstance()->getTaxRateByTaxClass($product->get_tax_class());

        if (\wc_prices_include_tax() && $taxRate != 0) {
            $netPrice = ((float)$product->get_sale_price()) / ($taxRate + 100) * 100;
        } else {
            $netPrice = round((float)$product->get_sale_price(), \wc_get_price_decimals());
        }

        return $netPrice;
    }

    public function pushData(ProductModel $product)
    {
        $pd = \wc_get_price_decimals();
        $productId = $product->getId()->getEndpoint();

        foreach ($product->getSpecialPrices() as $specialPrice) {
            foreach ($specialPrice->getItems() as $item) {
                if (\wc_prices_include_tax()) {
                    $salePrice = $item->getPriceNet() * (1 + $product->getVat() / 100);
                } else {
                    $salePrice = $item->getPriceNet();
                }

                \update_post_meta($productId, '_sale_price', \wc_format_decimal($salePrice, $pd));

                if ($specialPrice->getConsiderDateLimit()) {
                    $dateTo = is_null($end = $specialPrice->getActiveUntilDate()) ? null : $end->getTimestamp();
                    $dateFrom = is_null($start = $specialPrice->getActiveFromDate()) ? null : $start->getTimestamp();
                } else {
                    $dateTo = '';
                    $dateFrom = '';
                }

                \update_post_meta($productId, '_sale_price_dates_to', $dateTo);
                \update_post_meta($productId, '_sale_price_dates_from', $dateFrom);

                if ('' !== $salePrice && '' == $dateTo && '' == $dateFrom) {
                    \update_post_meta($productId, '_price', \wc_format_decimal($salePrice, $pd));
                }

                if ('' !== $salePrice && $dateFrom && $dateFrom <= strtotime('NOW', current_time('timestamp'))) {
                    \update_post_meta($productId, '_price', \wc_format_decimal($salePrice, $pd));
                }
            }
        }
    }
}
