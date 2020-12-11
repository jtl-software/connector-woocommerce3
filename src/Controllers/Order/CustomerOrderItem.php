<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Order;

use jtl\Connector\Model\CustomerOrderItem as CustomerOrderItemModel;
use jtl\Connector\Model\Identity;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Utilities\Config;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\Id;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\Util;

class CustomerOrderItem extends BaseController
{
    const PRICE_DECIMALS = 4;

    /** @var array $taxRateCache Map tax rate id to tax rate */
    protected static $taxRateCache = [];
    /** @var array $taxClassRateCache Map tax class to tax rate */
    protected static $taxClassRateCache = [];

    public function pullData(\WC_Order $order)
    {
        $customerOrderItems = [];

        if (Config::get(Config::OPTIONS_RECALCULATE_COUPONS_ON_PULL) === true && count($order->get_items('coupon')) > 0) {
            $order->recalculate_coupons();
        }

        $this->pullProductOrderItems($order, $customerOrderItems);
        $this->pullShippingOrderItems($order, $customerOrderItems);
        $this->pullFreePositions($order, $customerOrderItems);
        $this->pullDiscountOrderItems($order, $customerOrderItems);

        return $customerOrderItems;
    }

    /**
     * Add the positions for products. Not that complicated.
     *
     * @param \WC_Order $order
     * @param           $customerOrderItems
     */
    public function pullProductOrderItems(\WC_Order $order, &$customerOrderItems)
    {
        $taxItems = $order->get_items('tax');
        if (is_array($taxItems)) {
            $vatRates = [];
            foreach ($taxItems as $taxItem) {
                $data = $taxItem->get_data();
                if (isset($data['rate_percent'])) {
                    $vatRates[] = (float)$data['rate_percent'];
                }
            }
            $uniqueRates = array_unique($vatRates);
            if (count($uniqueRates) === 1) {
                $singleVatRate = end($uniqueRates);
            }
        }

        /** @var \WC_Order_Item_Product $item */
        foreach ($order->get_items() as $item) {
            $orderItem = (new CustomerOrderItemModel())
                ->setId(new Identity($item->get_id()))
                ->setCustomerOrderId(new Identity($order->get_id()))
                ->setName(html_entity_decode($item->get_name()))
                ->setQuantity($item->get_quantity())
                ->setType(CustomerOrderItemModel::TYPE_PRODUCT);

            $variationId = $item->get_variation_id();

            if (!empty($variationId)) {
                $product = \wc_get_product($variationId);
            } else {
                $product = \wc_get_product($item->get_product_id());
            }

            if ($product instanceof \WC_Product) {

                if (is_string($product->get_sku())) {
                    $orderItem->setSku($product->get_sku());
                }

                $orderItem->setProductId(new Identity($product->get_id()));

                if ($product instanceof \WC_Product_Variation) {
                    switch (Config::get(Config::OPTIONS_VARIATION_NAME_FORMAT)) {
                        case 'space_parent':
                        case 'space':
                            $format = '%s %s';
                            break;
                        case 'brackets_parent':
                        case 'brackets':
                            $format = '%s (%s)';
                            break;
                        default:
                            $format = '%s';
                            break;
                    }

                    $orderItem->setName(sprintf($format, $orderItem->getName(),
                        \wc_get_formatted_variation($product, true)));
                }
            }


            if (isset($singleVatRate)) {
                $vat = $singleVatRate;
            } else {
                $priceNet = (float)$order->get_item_subtotal($item, false, true);
                $priceGross = (float)$order->get_item_subtotal($item, true, true);
                $vat = $this->calculateVat($priceNet, $priceGross, wc_get_price_decimals());
            }

            $priceNet = (float)$order->get_item_subtotal($item, false, false);
            $priceGross = (float)$order->get_item_subtotal($item, true, true);
            $orderItem
                ->setVat($vat)
                ->setPrice(round($priceNet, Util::getPriceDecimals()))
                ->setPriceGross(round($priceGross, Util::getPriceDecimals()));

            $customerOrderItems[] = $orderItem;
        }
    }

    public function pullShippingOrderItems(\WC_Order $order, &$customerOrderItems)
    {
        $this->accurateItemTaxCalculation(
            $order,
            CustomerOrderItemModel::TYPE_SHIPPING,
            $customerOrderItems,
            function ($shippingItem, $order, $taxRateId) {
                return $this->getShippingOrderItem($shippingItem, $order, $taxRateId);
            });
    }

    /**
     * Create an order item with the basic non price relevant information.
     *
     * @param \WC_Order_Item_Shipping $shippingItem
     * @param \WC_Order $order
     * @param null $taxRateId
     *
     * @return CustomerOrderItemModel
     */
    private function getShippingOrderItem(\WC_Order_Item_Shipping $shippingItem, \WC_Order $order, $taxRateId = null)
    {
        return (new CustomerOrderItemModel())
            ->setId(new Identity($shippingItem->get_id() . (is_null($taxRateId) ? '' : Id::SEPARATOR . $taxRateId)))
            ->setCustomerOrderId(new Identity($order->get_id()))
            ->setType(CustomerOrderItemModel::TYPE_SHIPPING)
            ->setName($shippingItem->get_name())
            ->setQuantity(1);
    }

    public function pullFreePositions(\WC_Order $order, &$customerOrderItems)
    {
        $this->accurateItemTaxCalculation($order, 'fee', $customerOrderItems,
            function ($shippingItem, $order, $taxRateId) {
                return $this->getSurchargeOrderItem($shippingItem, $order, $taxRateId);
            });
    }

    /**
     * Create an order item with the basic non price relevant information.
     *
     * @param \WC_Order_Item_Fee $feeItem
     * @param \WC_Order $order
     * @param null $taxRateId
     *
     * @return CustomerOrderItemModel
     */
    private function getSurchargeOrderItem(\WC_Order_Item_Fee $feeItem, \WC_Order $order, $taxRateId = null)
    {
        return (new CustomerOrderItemModel())
            ->setId(new Identity($feeItem->get_id() . (is_null($taxRateId) ? '' : Id::SEPARATOR . $taxRateId)))
            ->setCustomerOrderId(new Identity($order->get_id()))
            ->setType(CustomerOrderItemModel::TYPE_SURCHARGE)
            ->setName($feeItem->get_name())
            ->setQuantity(1);
    }

    /**
     * @param \WC_Order $order
     * @param           $type
     * @param           $customerOrderItems
     * @param callable $getItem
     */
    private function accurateItemTaxCalculation(\WC_Order $order, $type, &$customerOrderItems, callable $getItem)
    {
        $highestVatRateFallback = 0.;
        if ($type === CustomerOrderItemModel::TYPE_SHIPPING) {
            foreach ($customerOrderItems as $orderItem) {
                if ($orderItem->getVat() > $highestVatRateFallback) {
                    $highestVatRateFallback = $orderItem->getVat();
                }
            }
        }

        $productTotalByVat = $this->groupProductsByTaxRate($customerOrderItems);
        $productTotalByVatWithoutZero = array_filter($productTotalByVat, function ($vat) {
            return (float)$vat !== 0;
        }, ARRAY_FILTER_USE_KEY);
        $totalProductItemsWithoutZero = array_sum(array_values($productTotalByVatWithoutZero));

        /** @var \WC_Order_Item_Shipping $shippingItem */
        foreach ($order->get_items($type) as $shippingItem) {
            $taxes = $shippingItem->get_taxes();
            $total = (float)$shippingItem->get_total();
            $totalTax = (float)$shippingItem->get_total_tax();
            $costs = (float)$order->get_item_total($shippingItem, false, true);

            if (isset($taxes['total']) && !empty($taxes['total']) && count($taxes['total']) > 1) {
                foreach ($taxes['total'] as $taxRateId => $taxAmount) {
                    /** @var CustomerOrderItemModel $customerOrderItem */
                    $customerOrderItem = $getItem($shippingItem, $order, $taxRateId);

                    if (isset(self::$taxRateCache[$taxRateId])) {
                        $taxRate = self::$taxRateCache[$taxRateId];
                    } else {
                        $taxRate = (float)$this->database->queryOne(SqlHelper::taxRateById($taxRateId));
                        self::$taxRateCache[$taxRateId] = $taxRate;
                    }

                    $customerOrderItem->setVat($taxRate);

                    if ($taxRate === 0.0) {
                        continue;
                    }

                    if (!isset($productTotalByVatWithoutZero[$taxRate])) {
                        $factor = 1;
                    } else {
                        $factor = $productTotalByVatWithoutZero[$taxRate] / $totalProductItemsWithoutZero;
                    }
                    $netPrice = $costs * $factor;
                    $priceGross = $netPrice + $taxAmount;

                    $customerOrderItem
                        ->setPrice(round($netPrice, Util::getPriceDecimals()))
                        ->setPriceGross(round($priceGross, Util::getPriceDecimals()));

                    $customerOrderItems[] = $customerOrderItem;
                }
            } else {
                /** @var CustomerOrderItemModel $customerOrderItem */
                $customerOrderItem = $getItem($shippingItem, $order, null);

                if ($total != 0) {

                    $priceGross = $total + $totalTax;
                    $vat = $this->calculateVat($total, $priceGross, wc_get_price_decimals());

                    $customerOrderItem->setVat($vat)
                        ->setPrice(round($total, Util::getPriceDecimals()))
                        ->setPriceGross(round($priceGross, Util::getPriceDecimals()));
                }

                if ($type === CustomerOrderItemModel::TYPE_SHIPPING && $customerOrderItem->getVat() === 0. && $highestVatRateFallback !== 0.) {
                    $customerOrderItem->setVat($highestVatRateFallback);
                }

                $customerOrderItems[] = $customerOrderItem;
            }
        }
    }

    /**
     * @param \WC_Order $order
     * @param array $customerOrderItems
     */
    public function pullDiscountOrderItems(\WC_Order $order, array &$customerOrderItems)
    {
        $orderItemsVatRates = [];
        $highestVatRate = 0;
        /** @var \jtl\Connector\Model\CustomerOrderItem $orderItem */
        foreach ($customerOrderItems as $orderItem) {
            $orderItemsVatRates[] = $orderItem->getVat();
            $highestVatRate = $orderItem->getVat() > $highestVatRate ? $orderItem->getVat() : $highestVatRate;
        }

        /**
         * @var integer $itemId
         * @var \WC_Order_Item_Coupon $item
         */
        foreach ($order->get_items('coupon') as $itemId => $item) {

            $itemName = $item->get_name();

            $total = (float)$item->get_discount();
            $discountTax = (float)$item->get_discount_tax();
            $totalGross = $total + $discountTax;

            $pd = Util::getPriceDecimals();

            $vat = $this->calculateVat($total, $totalGross, wc_get_price_decimals());
            if (!in_array($vat, $orderItemsVatRates)) {
                $vat = $highestVatRate;
                $total = $totalGross * 100 / ($vat + 100);
                $total = number_format((float)$total, $pd, '.', '');
            }

            $customerOrderItems[] = (new CustomerOrderItemModel())
                ->setId(new Identity($itemId))
                ->setCustomerOrderId(new Identity($order->get_id()))
                ->setName(empty($itemName) ? $item->get_code() : $itemName)
                ->setType(CustomerOrderItemModel::TYPE_COUPON)
                ->setPrice(round(-1 * $total, Util::getPriceDecimals()))
                ->setPriceGross(round(-1 * $totalGross, Util::getPriceDecimals()))
                ->setVat($vat)
                ->setQuantity(1);
        }
    }

    /**
     * @param float $totalNet
     * @param float $totalGross
     * @return float
     */
    private function calculateVat(float $totalNet, float $totalGross, $wooCommerceRoundPrecision = 2, int $vatRoundPrecision = 2): float
    {
        $vat = .0;
        if ($totalNet > 0 && $totalGross > 0 && $totalGross > $totalNet) {
            $vat = round($totalGross / $totalNet, $vatRoundPrecision) * 100 - 100;
        }

        $totalGrossCalculated = round(($totalNet * ($vat / 100 + 1)), $wooCommerceRoundPrecision);

        $isCalcualtedGrossSame = abs($totalGrossCalculated - $totalGross) < 0.00001;

        if ($vatRoundPrecision <= 6 && $vat !== .0 && $isCalcualtedGrossSame === false) {
            return $this->calculateVat($totalNet, $totalGross, $wooCommerceRoundPrecision, $vatRoundPrecision + 1);
        }

        return round($vat, 2);
    }

    /**
     * @param array $customerOrderItems
     * @return array
     */
    private function groupProductsByTaxRate(array $customerOrderItems)
    {
        $totalPriceForVats = [];

        foreach ($customerOrderItems as $item) {
            if ($item instanceof CustomerOrderItemModel && $item->getType() == CustomerOrderItemModel::TYPE_PRODUCT) {
                $taxRate = (string)$item->getVat();

                if (isset($totalPriceForVats[$taxRate])) {
                    $totalPriceForVats[$taxRate] += $item->getPrice();
                } else {
                    $totalPriceForVats[$taxRate] = $item->getPrice();
                }
            }
        }

        return $totalPriceForVats;
    }
}
