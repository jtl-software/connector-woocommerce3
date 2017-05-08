<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller\Product;

use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\WooCommerce\Controller\BaseController;

class ProductSpecialPrice extends BaseController
{
    public function pullData(\WC_Product $product, $model)
    {
        if (!empty($product->sale_price)) {
            return [$this->mapper->toHost($product)];
        }

        return [];
    }

    public function pushData(ProductModel $product, $model)
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
