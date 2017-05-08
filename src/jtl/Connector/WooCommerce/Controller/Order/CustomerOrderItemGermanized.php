<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller\Order;

use jtl\Connector\Model\CustomerOrderItem as CustomerOrderItemModel;
use jtl\Connector\Model\Identity;
use jtl\Connector\WooCommerce\Utility\IdConcatenation;
use jtl\Connector\WooCommerce\Utility\SQLs;

class CustomerOrderItemGermanized extends CustomerOrderItem
{
    protected static $taxRateIdCache = [];

    public function pullFreePositions(\WC_Order $order, &$customerOrderItems)
    {
        if ($order === false) {
            return;
        }

        $totalPriceForVats = $this->getTotalPricesForVats($customerOrderItems);

        $productSum = array_sum(array_values($totalPriceForVats));

        /** @var \WC_Order_Item_Fee $fee */
        foreach ($order->get_fees() as $fee) {
            $taxes = $fee->get_taxes();

            if (!isset($taxes['total'])) {
                return;
            }

            foreach ($taxes['total'] as $taxRateId => $taxAmount) {
                $customerOrderItem = (new CustomerOrderItemModel())
                    ->setId(new Identity(IdConcatenation::link([$fee->get_id(), $taxRateId])))
                    ->setCustomerOrderId(new Identity($order->get_id()))
                    ->setName($fee->get_name())
                    ->setType(CustomerOrderItemModel::TYPE_SURCHARGE)
                    ->setQuantity(1);

                if (isset(self::$taxRateIdCache[$taxRateId])) {
                    $taxRate = self::$taxRateIdCache[$taxRateId];
                } else {
                    $taxRate = (float)$this->database->queryOne(SQLs::taxRateById($taxRateId));
                    self::$taxRateIdCache[$taxRateId] = $taxRate;
                }

                $customerOrderItem->setVat($taxRate);

                if ($taxRate === 0.0) {
                    $priceGross = $netPrice = (double)$fee->get_total();
                } else {
                    $vatKey = "" . round($taxRate);

                    if (!isset($totalPriceForVats[$vatKey])) {
                        $factor = 1;
                    } else {
                        $factor = 1 / $productSum * $totalPriceForVats[$vatKey];
                    }

                    $fees = (float)$order->get_item_total($fee) * $factor;

                    $netPrice = round($fees, $this->priceDecimals);
                    $priceGross = round($fees + $taxAmount, $this->priceDecimals);
                }

                $customerOrderItem->setPrice($netPrice);
                $customerOrderItem->setPriceGross($priceGross);

                $customerOrderItems[] = $customerOrderItem;
            }
        }
    }

    public function pullShippingOrderItems(\WC_Order $order, &$customerOrderItems)
    {
        if ($order === false) {
            return;
        }

        $totalPriceForVats = $this->getTotalPricesForVats($customerOrderItems);

        $shippingCosts = (float)$order->get_shipping_total();
        $productSum = array_sum(array_values($totalPriceForVats));

        /** @var \WC_Order_Item_Shipping $shippingItem */
        foreach ($order->get_shipping_methods() as $shippingItem) {
            $taxes = $shippingItem->get_taxes();

            if (!isset($taxes['total'])) {
                return;
            }

            foreach ($taxes['total'] as $taxRateId => $taxAmount) {
                $customerOrderItem = (new CustomerOrderItemModel())
                    ->setId(new Identity(IdConcatenation::link([$shippingItem->get_id(), $taxRateId])))
                    ->setCustomerOrderId(new Identity($order->get_id()))
                    ->setName($shippingItem->get_name())
                    ->setType(CustomerOrderItemModel::TYPE_SHIPPING)
                    ->setQuantity(1);

                if (isset(self::$taxRateIdCache[$taxRateId])) {
                    $taxRate = self::$taxRateIdCache[$taxRateId];
                } else {
                    $taxRate = (float)$this->database->queryOne(SQLs::taxRateById($taxRateId));
                    self::$taxRateIdCache[$taxRateId] = $taxRate;
                }

                $customerOrderItem->setVat($taxRate);

                if ($taxRate === 0.0) {
                    $priceGross = $netPrice = (double)$shippingItem->get_total();
                } else {
                    if (!isset($totalPriceForVats["{$taxRate}"])) {
                        $factor = 1;
                    } else {
                        $factor = 1 / $productSum * $totalPriceForVats["{$taxRate}"];
                    }

                    $netPrice = $shippingCosts * $factor;
                    $priceGross = $netPrice * (1 + $taxRate / 100);
                }

                $customerOrderItem->setPrice(round($netPrice, $this->priceDecimals));
                $customerOrderItem->setPriceGross(round($priceGross, $this->priceDecimals));

                $customerOrderItems[] = $customerOrderItem;
            }
        }
    }

    private function getTotalPricesForVats(&$customerOrderItems)
    {
        $totalPriceForVats = [];

        foreach ($customerOrderItems as $item) {
            if ($item instanceof CustomerOrderItemModel && $item->getType() == CustomerOrderItemModel::TYPE_PRODUCT) {
                $taxRate = $item->getVat();
                if (isset($totalPriceForVats["{$taxRate}"])) {
                    $totalPriceForVats["{$taxRate}"] += $item->getPrice();
                } else {
                    $totalPriceForVats["{$taxRate}"] = $item->getPrice();
                }
            }
        }

        return $totalPriceForVats;
    }
}
