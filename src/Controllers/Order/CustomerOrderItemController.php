<?php

namespace JtlWooCommerceConnector\Controllers\Order;

use InvalidArgumentException;
use Jtl\Connector\Core\Model\CustomerOrderItem as CustomerOrderItemModel;
use Jtl\Connector\Core\Model\Identity;
use JtlWooCommerceConnector\Controllers\AbstractBaseController;
use JtlWooCommerceConnector\Utilities\Config;
use JtlWooCommerceConnector\Utilities\Id;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\Util;
use WC_Order;
use WC_Order_Item_Product;
use WC_Order_Item_Shipping;

class CustomerOrderItemController extends AbstractBaseController
{
    public const PRICE_DECIMALS = 4;

    /** @var array $taxRateCache Map tax rate id to tax rate */
    protected static array $taxRateCache = [];
    /** @var array $taxClassRateCache Map tax class to tax rate */
    protected static array $taxClassRateCache = [];

    /**
     * @param WC_Order $order
     * @return array
     * @throws InvalidArgumentException
     * @throws \WC_Data_Exception
     */
    public function pull(WC_Order $order): array
    {
        $customerOrderItems = [];

        if (
            Config::get(Config::OPTIONS_RECALCULATE_COUPONS_ON_PULL) === true
            && \count($order->get_items('coupon'))
            > 0
        ) {
            $order->recalculate_coupons();
        }

        $this->pullProductOrderItems($order, $customerOrderItems);
        $this->pullShippingOrderItems($order, $customerOrderItems);
        $this->pullFreePositions($order, $customerOrderItems);
        $this->pullDiscountOrderItems($order, $customerOrderItems);

        return $customerOrderItems;
    }

    /**
     * @param WC_Order $wcOrder
     * @return float|null
     */
    protected function getSingleVatRate(WC_Order $wcOrder): ?float
    {
        $singleVatRate = null;
        $taxItems      = $wcOrder->get_items('tax');
        if (\is_array($taxItems)) {
            $vatRates = [];
            foreach ($taxItems as $taxItem) {
                $data = $taxItem->get_data();
                if (isset($data['rate_percent'])) {
                    $vatRates[] = (float)$data['rate_percent'];
                }
            }
            $uniqueRates = \array_unique($vatRates);
            if (\count($uniqueRates) === 1) {
                $singleVatRate = (float)\end($uniqueRates);
            }
        }
        return $singleVatRate;
    }

    /**
     * Add the positions for products. Not that complicated.
     *
     * @param WC_Order $order
     * @param $customerOrderItems
     * @return void
     * @throws InvalidArgumentException
     */
    public function pullProductOrderItems(WC_Order $order, &$customerOrderItems): void
    {
        $singleVatRate = $this->getSingleVatRate($order);

        /** @var WC_Order_Item_Product $item */
        foreach ($order->get_items() as $item) {
            $orderItem = (new CustomerOrderItemModel())
                ->setId(new Identity($item->get_id()))
                ->setName(\html_entity_decode($item->get_name()))
                ->setQuantity($item->get_quantity())
                ->setType(CustomerOrderItemModel::TYPE_PRODUCT);

            $variationId = $item->get_variation_id();

            if (!empty($variationId)) {
                $product = \wc_get_product($variationId);
            } else {
                $product = \wc_get_product($item->get_product_id());
            }

            if ($product instanceof \WC_Product) {
                if (\is_string($product->get_sku())) {
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

                    $orderItem->setName(\sprintf(
                        $format,
                        $orderItem->getName(),
                        \wc_get_formatted_variation($product, true)
                    ));
                }
            }

            $taxes = $item->get_taxes();

            $priceNet   = (float)$order->get_item_subtotal($item, false, true);
            $priceGross = (float)$order->get_item_subtotal($item, true, true);

            $useWcTaxes = false;
            if (!empty($taxes) && isset($taxes['subtotal']) && \is_array($taxes['subtotal'])) {
                $useWcTaxes = true;
                $taxesTotal = \array_sum($taxes['subtotal']);

                if (!\is_null($item->get_quantity())) {
                    $taxesTotal /= $item->get_quantity();
                }

                $priceNet   = (float)$order->get_item_subtotal($item, false, false);
                $priceGross = (float)($priceNet + $taxesTotal);
            }

            if (!\is_null($singleVatRate)) {
                $vat = $singleVatRate;
            } else {
                $vat = $this->calculateVat($priceNet, $priceGross, \wc_get_price_decimals());
            }

            if ($vat == 0.0 && $priceNet == 0.0 && $priceGross == 0.0) {
                $taxRateId = \array_key_first($taxes['total']);
                $vat       = (float)$this->db->queryOne(SqlHelper::taxRateById($taxRateId));
            }

            if ($useWcTaxes === false) {
                $priceNet = (float)$order->get_item_subtotal($item, false, false);
            }

            $orderItem
                ->setVat($vat)
                ->setPrice(\round($priceNet, Util::getPriceDecimals()))
                ->setPriceGross(\round($priceGross, Util::getPriceDecimals()));

            $customerOrderItems[] = $orderItem;
        }
    }

    /**
     * @param WC_Order $order
     * @param $customerOrderItems
     * @return void
     * @throws InvalidArgumentException
     */
    public function pullShippingOrderItems(WC_Order $order, &$customerOrderItems): void
    {
        $this->accurateItemTaxCalculation(
            $order,
            CustomerOrderItemModel::TYPE_SHIPPING,
            $customerOrderItems,
            function ($shippingItem, $order, $taxRateId) {
                return $this->getShippingOrderItem($shippingItem, $order, $taxRateId);
            }
        );
    }

    /**
     * Create an order item with the basic non price relevant information.
     *
     * @param WC_Order_Item_Shipping $shippingItem
     * @param WC_Order $order
     * @param $taxRateId
     * @return CustomerOrderItemModel
     */
    private function getShippingOrderItem(
        WC_Order_Item_Shipping $shippingItem,
        WC_Order $order,
        $taxRateId = null
    ): CustomerOrderItemModel {
        return (new CustomerOrderItemModel())
            ->setId(new Identity($shippingItem->get_id() . (\is_null($taxRateId) ? '' : Id::SEPARATOR . $taxRateId)))
            ->setType(CustomerOrderItemModel::TYPE_SHIPPING)
            ->setName($shippingItem->get_name())
            ->setQuantity(1);
    }

    /**
     * @param WC_Order $order
     * @param $customerOrderItems
     * @return void
     * @throws InvalidArgumentException
     */
    public function pullFreePositions(WC_Order $order, &$customerOrderItems): void
    {
        $this->accurateItemTaxCalculation(
            $order,
            'fee',
            $customerOrderItems,
            function ($shippingItem, $order, $taxRateId) {
                return $this->getSurchargeOrderItem($shippingItem, $order, $taxRateId);
            }
        );
    }

    /**
     * Create an order item with the basic non price relevant information.
     *
     * @param \WC_Order_Item_Fee $feeItem
     * @param WC_Order $order
     * @param $taxRateId
     * @return CustomerOrderItemModel
     */
    private function getSurchargeOrderItem(
        \WC_Order_Item_Fee $feeItem,
        WC_Order $order,
        $taxRateId = null
    ): CustomerOrderItemModel {
        return (new CustomerOrderItemModel())
            ->setId(
                new Identity($feeItem->get_id() . (\is_null($taxRateId)
                        ? ''
                        : Id::SEPARATOR . $taxRateId))
            )
            ->setType(CustomerOrderItemModel::TYPE_SURCHARGE)
            ->setName($feeItem->get_name())
            ->setQuantity(1);
    }

    /**
     * @param WC_Order $order
     * @param $type
     * @param $customerOrderItems
     * @param callable $getItem
     * @return void
     * @throws InvalidArgumentException
     */
    private function accurateItemTaxCalculation(WC_Order $order, $type, &$customerOrderItems, callable $getItem): void
    {
        $highestVatRateFallback = 0.;
        if ($type === CustomerOrderItemModel::TYPE_SHIPPING) {
            foreach ($customerOrderItems as $orderItem) {
                if ($orderItem->getVat() > $highestVatRateFallback) {
                    $highestVatRateFallback = $orderItem->getVat();
                }
            }
        }
        $singleVatRate = $this->getSingleVatRate($order);

        $productTotalByVat            = $this->groupProductsByTaxRate($customerOrderItems);
        $productTotalByVatWithoutZero = \array_filter($productTotalByVat, function ($vat) {
            return (float)$vat !== 0;
        }, \ARRAY_FILTER_USE_KEY);
        $totalProductItemsWithoutZero = \array_sum(\array_values($productTotalByVatWithoutZero));

        /** @var WC_Order_Item_Shipping $shippingItem */
        foreach ($order->get_items($type) as $shippingItem) {
            $taxes    = $shippingItem->get_taxes();
            $total    = (float)$shippingItem->get_total();
            $totalTax = (float)$shippingItem->get_total_tax();
            $costs    = (float)$order->get_item_total($shippingItem, false, true);

            if (!empty($taxes['total']) && \count($taxes['total']) > 1) {
                foreach ($taxes['total'] as $taxRateId => $taxAmount) {
                    /** @var CustomerOrderItemModel $customerOrderItem */
                    $customerOrderItem = $getItem($shippingItem, $order, $taxRateId);

                    if (isset(self::$taxRateCache[$taxRateId])) {
                        $taxRate = self::$taxRateCache[$taxRateId];
                    } else {
                        $taxRate                        = (float)$this->db->queryOne(
                            SqlHelper::taxRateById($taxRateId)
                        );
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
                    $netPrice   = ($costs * $factor);
                    $priceGross = $netPrice + (float) $taxAmount;

                    $customerOrderItem
                        ->setPrice(\round($netPrice, Util::getPriceDecimals()))
                        ->setPriceGross(\round($priceGross, Util::getPriceDecimals()));

                    if (!($customerOrderItem->getType() == 'shipping' && $customerOrderItem->getPrice() == 0.0)) {
                        $customerOrderItems[] = $customerOrderItem;
                    }
                }
            } else {
                /** @var CustomerOrderItemModel $customerOrderItem */
                $customerOrderItem = $getItem($shippingItem, $order, null);

                if ($total != 0) {
                    $detailedTax = $totalTax;
                    if (isset($taxes['total']) && \is_array($taxes['total'])) {
                        $detailedTax = \array_sum($taxes['total']);
                    }

                    $detailedPriceGross = $total + $detailedTax;
                    $vat                = $this->calculateVat($total, $detailedPriceGross, \wc_get_price_decimals());
                    if (!\is_null($singleVatRate)) {
                        $vat = $singleVatRate;
                    }

                    $customerOrderItem->setVat($vat)
                        ->setPrice(\round($total, Util::getPriceDecimals()))
                        ->setPriceGross(\round($detailedPriceGross, Util::getPriceDecimals()));
                }

                if (
                    $type === CustomerOrderItemModel::TYPE_SHIPPING
                    && $customerOrderItem->getVat() === 0.
                    && $highestVatRateFallback !== 0.
                ) {
                    $customerOrderItem->setVat($highestVatRateFallback);
                }

                $customerOrderItems[] = $customerOrderItem;
            }
        }
    }

    /**
     * @param WC_Order $order
     * @param array $customerOrderItems
     * @return void
     */
    public function pullDiscountOrderItems(WC_Order $order, array &$customerOrderItems): void
    {
        $orderItemsVatRates = [];
        $highestVatRate     = 0;
        /** @var CustomerOrderItemModel $orderItem */
        foreach ($customerOrderItems as $orderItem) {
            $orderItemsVatRates[] = $orderItem->getVat();
            $highestVatRate       = \max($orderItem->getVat(), $highestVatRate);
        }

        /**
         * @var integer $itemId
         * @var \WC_Order_Item_Coupon $item
         */
        foreach ($order->get_items('coupon') as $itemId => $item) {
            $itemName = $item->get_name();

            $total       = (float)$item->get_discount();
            $discountTax = (float)$item->get_discount_tax();
            $totalGross  = $total + $discountTax;

            $pd = Util::getPriceDecimals();

            $vat = $this->calculateVat($total, $totalGross, \wc_get_price_decimals());
            if (!\in_array($vat, $orderItemsVatRates)) {
                $vat   = $highestVatRate;
                $total = $totalGross * 100 / ($vat + 100);
                $total = \number_format((float)$total, $pd, '.', '');
            }

            $customerOrderItems[] = (new CustomerOrderItemModel())
                ->setId(new Identity($itemId))
                ->setName(empty($itemName) ? $item->get_code() : $itemName)
                ->setType(CustomerOrderItemModel::TYPE_COUPON)
                ->setPrice(\round(-1 * $total, Util::getPriceDecimals()))
                ->setPriceGross(\round(-1 * $totalGross, Util::getPriceDecimals()))
                ->setVat($vat)
                ->setQuantity(1);
        }
    }

    /**
     * @param float $totalNet
     * @param float $totalGross
     * @param int $wooCommerceRoundPrecision
     * @param int $vatRoundPrecision
     * @return float
     */
    private function calculateVat(
        float $totalNet,
        float $totalGross,
        int $wooCommerceRoundPrecision = 2,
        int $vatRoundPrecision = 2
    ): float {
        $totalGrossPrecision = Util::getDecimalPrecision($totalGross);
        $vat                 = .0;
        if ($totalNet > 0 && $totalGross > 0 && $totalGross > $totalNet) {
            $vat = \round($totalGross / $totalNet, $vatRoundPrecision) * 100 - 100;
        }

        $totalGrossCalculated = \round(($totalNet * ($vat / 100 + 1)), $totalGrossPrecision);

        $isCalculatedGrossSame = \abs($totalGrossCalculated - $totalGross) < 0.00001;

        if ($vatRoundPrecision <= 6 && $vat !== .0 && $isCalculatedGrossSame === false) {
            return $this->calculateVat(
                $totalNet,
                $totalGross,
                $totalGrossPrecision,
                $vatRoundPrecision + 1
            );
        }

        return \round($vat, 2);
    }

    /**
     * @param array $customerOrderItems
     * @return array
     */
    private function groupProductsByTaxRate(array $customerOrderItems): array
    {
        $totalPriceForVats = [];

        foreach ($customerOrderItems as $item) {
            if ($item instanceof CustomerOrderItemModel && $item->getType() == CustomerOrderItemModel::TYPE_PRODUCT) {
                $taxRate = (string)$item->getVat();

                if (isset($totalPriceForVats[$taxRate])) {
                    $totalPriceForVats[$taxRate] += $item->getQuantity() * $item->getPrice();
                } else {
                    $totalPriceForVats[$taxRate] = $item->getQuantity() * $item->getPrice();
                }
            }
        }

        return $totalPriceForVats;
    }
}
