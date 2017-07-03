<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller\Order;

use jtl\Connector\Model\CustomerOrderItem as CustomerOrderItemModel;
use jtl\Connector\Model\Identity;
use jtl\Connector\WooCommerce\Controller\BaseController;
use jtl\Connector\WooCommerce\Utility\Id;
use jtl\Connector\WooCommerce\Utility\SQL;
use jtl\Connector\WooCommerce\Utility\Util;

class CustomerOrderItem extends BaseController
{
    protected $priceDecimals;

    /** @var array $taxRateCache Map tax rate id to tax rate */
    protected static $taxRateCache = [];
    /** @var array $taxClassRateCache Map tax class to tax rate */
    protected static $taxClassRateCache = [];

    public function __construct()
    {
        parent::__construct();

        $this->priceDecimals = 4;
    }

    public function pullData(\WC_Order $order)
    {
        $customerOrderItems = [];

        $this->pullProductOrderItems($order, $customerOrderItems);
        $this->pullShippingOrderItems($order, $customerOrderItems);
        $this->pullFreePositions($order, $customerOrderItems);
        $this->pullDiscountOrderItems($order, $customerOrderItems);

        return $customerOrderItems;
    }

    public function pullProductOrderItems(\WC_Order $order, &$customerOrderItems)
    {
        /** @var \WC_Order_Item_Product $item */
        foreach ($order->get_items() as $item) {
            $orderItem = (new CustomerOrderItemModel())
                ->setId(new Identity($item->get_id()))
                ->setCustomerOrderId(new Identity($order->get_id()))
                ->setName($item->get_name())
                ->setQuantity($item->get_quantity())
                ->setType(CustomerOrderItemModel::TYPE_PRODUCT);

            $product = $item->get_product();

            if ($product instanceof \WC_Product) {
                $orderItem
                    ->setSku($product->get_sku())
                    ->setProductId(new Identity($product->get_id()));

                if ($product instanceof \WC_Product_Variation) {
                    switch (\get_option(\JtlConnectorAdmin::OPTIONS_VARIATION_NAME_FORMAT)) {
                        case 'space':
                            $format = '%s %s';
                            break;
                        case 'brackets':
                            $format = '%s (%s)';
                            break;
                        case 'space_parent':
                            $format = '%s %s';
                            break;
                        case 'brackets_parent':
                            $format = '%s (%s)';
                            break;
                        default:
                            $format = '%s';
                            break;
                    }

                    $orderItem->setName(sprintf($format, $orderItem->getName(), \wc_get_formatted_variation($product, true)));
                }
            }

            // Tax and price calculation
            $tax = $order->get_item_tax($item); // the tax amount

            if ($tax === 0.0) {
                // Take subtotal because coupons are subtracted in total
                $priceGross = $netPrice = $order->get_item_subtotal($item, true, false);
            } else {
                // Default is an empty tax class and tax amount unequal zero
                $netPrice = $order->get_item_subtotal($item, false, false);
                $priceGross = $order->get_item_subtotal($item, true, false);
            }

            if (isset(self::$taxClassRateCache[$item->get_tax_class()])) {
                $taxRate = self::$taxClassRateCache[$item->get_tax_class()];
            } else {
                $taxRate = Util::getInstance()->getTaxRateByTaxClassAndShopLocation($item->get_tax_class());
                self::$taxClassRateCache[$item->get_tax_class()] = $taxRate;
            }

            $orderItem
                ->setVat($taxRate)
                ->setPrice(round($netPrice, $this->priceDecimals))
                ->setPriceGross(round($priceGross, $this->priceDecimals));

            $customerOrderItems[] = $orderItem;
        }
    }

    public function pullShippingOrderItems(\WC_Order $order, &$customerOrderItems)
    {
        $this->accurateItemTaxCalculation($order, 'shipping', $customerOrderItems, function ($shippingItem, $order, $taxRateId) {
            return $this->getShippingOrderItem($shippingItem, $order, $taxRateId);
        });
    }

    public function pullFreePositions(\WC_Order $order, &$customerOrderItems)
    {
        $this->accurateItemTaxCalculation($order, 'fee', $customerOrderItems, function ($shippingItem, $order, $taxRateId) {
            return $this->getSurchargeOrderItem($shippingItem, $order, $taxRateId);
        });
    }

    public function pullDiscountOrderItems(\WC_Order $order, &$customerOrderItems)
    {
        /**
         * @var integer $itemId
         * @var \WC_Order_Item_Coupon $item
         */
        foreach ($order->get_items('coupon') as $itemId => $item) {
            $customerOrderItems[] = (new CustomerOrderItemModel())
                ->setId(new Identity($itemId))
                ->setCustomerOrderId(new Identity($order->get_id()))
                ->setName(empty($item->get_name()) ? $item->get_code() : $item->get_name())
                ->setType(CustomerOrderItemModel::TYPE_COUPON)
                ->setPrice(-1 * round((float)$item->get_discount(), $this->priceDecimals))
                ->setPriceGross(-1 * round((float)$item->get_discount() + (float)$item->get_discount_tax(), $this->priceDecimals))
                ->setQuantity(1);
        }
    }

    private function accurateItemTaxCalculation(\WC_Order $order, $type, &$customerOrderItems, callable $getItem)
    {
        $productTotalByVat = $this->getProductTotalByVat($customerOrderItems);
        $productTotalByVatWithoutZero = array_filter($productTotalByVat, function ($vat) {
            return $vat !== 0;
        }, ARRAY_FILTER_USE_KEY);
        $totalProductItems = array_sum(array_values($productTotalByVat));
        $totalProductItemsWithoutZero = array_sum(array_values($productTotalByVatWithoutZero));

        foreach ($order->get_items($type) as $shippingItem) {
            $taxes = $shippingItem->get_taxes();
            $total = (float)$shippingItem->get_total();
            $totalTax = (float)$shippingItem->get_total_tax();
            $costs = (float)$order->get_item_total($shippingItem, false, false);

            if (isset($taxes['total']) && !empty($taxes['total']) && count($taxes['total']) > 1) {
                foreach ($taxes['total'] as $taxRateId => $taxAmount) {
                    $taxAmount = (float)$taxAmount;
                    /** @var CustomerOrderItemModel $customerOrderItem */
                    $customerOrderItem = $getItem($shippingItem, $order, $taxRateId);

                    if (isset(self::$taxRateCache[$taxRateId])) {
                        $taxRate = self::$taxRateCache[$taxRateId];
                    } else {
                        $taxRate = (float)$this->database->queryOne(SQL::taxRateById($taxRateId));
                        self::$taxRateCache[$taxRateId] = $taxRate;
                    }

                    $customerOrderItem->setVat($taxRate);

                    if ($taxRate === 0.0) {
                        continue;
                    } else {
                        $vatKey = "" . round($taxRate);

                        if (!isset($productTotalByVatWithoutZero[$vatKey])) {
                            $factor = 1;
                        } else {
                            $factor = $productTotalByVatWithoutZero[$vatKey] / $totalProductItemsWithoutZero;
                        }

                        $fees = $costs * $factor;

                        $netPrice = round($fees, $this->priceDecimals);
                        $priceGross = round($fees + $taxAmount, $this->priceDecimals);
                    }

                    $customerOrderItem->setPrice($netPrice);
                    $customerOrderItem->setPriceGross($priceGross);

                    $customerOrderItems[] = $customerOrderItem;
                }
            } else {
                /** @var CustomerOrderItemModel $customerOrderItem */
                $customerOrderItem = $getItem($shippingItem, $order, null);

                $customerOrderItem->setVat(round(100 / $total * ($total + $totalTax) - 100, 1));
                $customerOrderItem->setPrice(round($total, $this->priceDecimals));
                $customerOrderItem->setPriceGross(round($total + $totalTax, $this->priceDecimals));

                $customerOrderItems[] = $customerOrderItem;
            }
        }
    }

    private function getShippingOrderItem(\WC_Order_Item_Shipping $shippingItem, \WC_Order $order, $taxRateId = null)
    {
        return (new CustomerOrderItemModel())
            ->setId(new Identity($shippingItem->get_id() . is_null($taxRateId) ? '' : Id::SEPARATOR . $taxRateId))
            ->setCustomerOrderId(new Identity($order->get_id()))
            ->setType(CustomerOrderItemModel::TYPE_SHIPPING)
            ->setName($shippingItem->get_name())
            ->setQuantity(1);
    }

    private function getSurchargeOrderItem(\WC_Order_Item_Fee $feeItem, \WC_Order $order, $taxRateId = null)
    {
        return (new CustomerOrderItemModel())
            ->setId(new Identity($feeItem->get_id() . is_null($taxRateId) ? '' : Id::SEPARATOR . $taxRateId))
            ->setCustomerOrderId(new Identity($order->get_id()))
            ->setType(CustomerOrderItemModel::TYPE_SURCHARGE)
            ->setName($feeItem->get_name())
            ->setQuantity(1);
    }

    private function getProductTotalByVat(array $customerOrderItems)
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
