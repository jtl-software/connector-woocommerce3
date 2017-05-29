<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller\Order;

use jtl\Connector\Model\CustomerOrderItem as CustomerOrderItemModel;
use jtl\Connector\Model\Identity;
use jtl\Connector\WooCommerce\Controller\BaseController;
use jtl\Connector\WooCommerce\Utility\IdConcatenation;
use jtl\Connector\WooCommerce\Utility\SQLs;
use jtl\Connector\WooCommerce\Utility\Util;

class CustomerOrderItem extends BaseController
{
    protected $priceDecimals;
    private $hasNoTax = false;

    protected static $taxRateCache = [];
    protected static $taxClassRateCache = [];

    public function __construct()
    {
        parent::__construct();
        $this->priceDecimals = \wc_get_price_decimals();
    }

    public function pullData(\WC_Order $order)
    {
        $customerOrderItems = [];

        $this->pullProductOrderItems($order, $customerOrderItems);
        $this->pullFreePositions($order, $customerOrderItems);
        $this->pullShippingOrderItems($order, $customerOrderItems);
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

                    $orderItem->setName(sprintf($format, $orderItem->getName(), wc_get_formatted_variation($product)));
                }
            }

            // Tax and price calculation
            $tax = $order->get_item_tax($item); // the tax amount

            if ($tax === 0.0) {
                // Take subtotal because coupons are subtracted in total
                $this->hasNoTax = true;
                $priceGross = $netPrice = $order->get_item_subtotal($item, true, false);
            } else {
                // Default is an empty tax class and tax amount unequal zero
                $this->hasNoTax = false;
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

    public function pullFreePositions(\WC_Order $order, &$customerOrderItems)
    {
        /** @var \WC_Order_Item_Fee $fee */
        foreach ($order->get_fees() as $fee) {
            $taxes = $fee->get_taxes();

            if (isset($taxes['total'])) {
                foreach ($taxes['total'] as $taxRateId => $taxAmount) {
                    $customerOrderItem = (new CustomerOrderItemModel())
                        ->setId(new Identity(IdConcatenation::link([$fee->get_id(), $taxRateId])))
                        ->setCustomerOrderId(new Identity($order->get_id()))
                        ->setType(CustomerOrderItemModel::TYPE_SURCHARGE)
                        ->setName($fee->get_name())
                        ->setQuantity(1);

                    $netPrice = $order->get_item_total($fee, false, false);
                    $priceGross = $order->get_item_total($fee, true, false);

                    $customerOrderItem
                        ->setVat(round(($priceGross / $netPrice - 1) * 100))
                        ->setPrice(round($netPrice, $this->priceDecimals))
                        ->setPriceGross(round($priceGross, $this->priceDecimals));

                    $customerOrderItems[] = $customerOrderItem;
                }
            } else {
                $customerOrderItem = (new CustomerOrderItemModel())
                    ->setId(new Identity($fee->get_id()))
                    ->setCustomerOrderId(new Identity($order->get_id()))
                    ->setType(CustomerOrderItemModel::TYPE_SURCHARGE)
                    ->setName($fee->get_name())
                    ->setQuantity(1);

                $priceGross = $netPrice = $order->get_item_total($fee, false, false);

                $customerOrderItem
                    ->setVat(0)
                    ->setPrice(round($netPrice, $this->priceDecimals))
                    ->setPriceGross(round($priceGross, $this->priceDecimals));

                $customerOrderItems[] = $customerOrderItem;
            }
        }
    }

    public function pullShippingOrderItems(\WC_Order $order, &$customerOrderItems)
    {
        /** @var \WC_Order_Item_Shipping $shippingItem */
        foreach ($order->get_shipping_methods() as $shippingItem) {
            $taxes = $shippingItem->get_taxes();
            $costs = (float)$shippingItem->get_total();

            if (isset($taxes['total'])) {
                foreach ($taxes['total'] as $taxRateId => $taxAmount) {
                    $customerOrderItem = (new CustomerOrderItemModel())
                        ->setId(new Identity($shippingItem->get_id()))
                        ->setCustomerOrderId(new Identity($order->get_id()))
                        ->setType(CustomerOrderItemModel::TYPE_SHIPPING)
                        ->setName($shippingItem->get_name())
                        ->setPriceGross(round($costs + $taxAmount, $this->priceDecimals))
                        ->setQuantity(1);

                    if (isset(self::$taxRateCache[$taxRateId])) {
                        $customerOrderItem->setVat(self::$taxRateCache[$taxRateId]);
                    } else {
                        $rate = (float)$this->database->queryOne(SQLs::taxRateById($taxRateId));
                        $customerOrderItem->setVat($rate);
                        self::$taxRateCache[$taxRateId] = $rate;
                    }

                    $customerOrderItem->setPrice(round($costs, $this->priceDecimals));

                    $customerOrderItems[] = $customerOrderItem;
                }
            } else {
                $customerOrderItem = (new CustomerOrderItemModel())
                    ->setId(new Identity($shippingItem->get_id()))
                    ->setCustomerOrderId(new Identity($order->get_id()))
                    ->setType(CustomerOrderItemModel::TYPE_SHIPPING)
                    ->setName($shippingItem->get_name())
                    ->setQuantity(1);

                $taxes = array_values($order->get_taxes());

                if (!empty($taxes) && isset($taxes['rate_id'])) {
                    $taxRateId = $taxes['rate_id'];
                    if (isset(self::$taxRateCache[$taxRateId])) {
                        $customerOrderItem->setVat(self::$taxRateCache[$taxRateId]);
                    } else {
                        $rate = (float)$this->database->queryOne(SQLs::taxRateById($taxRateId));
                        $customerOrderItem->setVat($rate);
                        self::$taxRateCache[$taxRateId] = $rate;
                    }
                }

                $customerOrderItem->setPrice(round($costs, $this->priceDecimals));
                $customerOrderItem->setPriceGross(round($costs, $this->priceDecimals));

                $customerOrderItems[] = $customerOrderItem;
            }
        }
    }

    public function pullDiscountOrderItems(\WC_Order $order, &$customerOrderItems)
    {
        foreach ($order->get_items('coupon') as $itemId => $couponItem) {
            $customerOrderItem = $this->getCommonOrderItem($order, $itemId, $couponItem);
            if ($this->hasNoTax) {
                $price = (float)$couponItem['discount_amount'];
                $priceGross = $price;
            } else {
                $price = (float)$couponItem['discount_amount'];
                $priceGross = (float)$couponItem['discount_amount'] + (float)$couponItem['discount_amount_tax'];
            }
            $customerOrderItem->setPrice(-1 * round($price, $this->priceDecimals));
            $customerOrderItem->setPriceGross(-1 * round($priceGross, $this->priceDecimals));
            $customerOrderItem->setType(CustomerOrderItemModel::TYPE_COUPON);
            $customerOrderItems[] = $customerOrderItem;
        }
    }

    public function getCommonOrderItem(\WC_Order $order, $itemId, $item)
    {
        $customerOrderItem = (new CustomerOrderItemModel())
            ->setId(new Identity($itemId))
            ->setCustomerOrderId(new Identity($order->get_id()))
            ->setName(isset($item['name']) ? $item['name'] : '')
            ->setQuantity(1);

        return $customerOrderItem;
    }
}
