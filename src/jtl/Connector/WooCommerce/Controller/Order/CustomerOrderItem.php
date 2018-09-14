<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
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
    const PRICE_DECIMALS = 4;
    
    /** @var array $taxRateCache Map tax rate id to tax rate */
    protected static $taxRateCache = [];
    /** @var array $taxClassRateCache Map tax class to tax rate */
    protected static $taxClassRateCache = [];
    
    public function pullData(\WC_Order $order)
    {
        $customerOrderItems = [];
        
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
     * @param $customerOrderItems
     */
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
            
            $variationId = $item->get_variation_id();
            
            if (!empty($variationId)) {
                $product = \wc_get_product($variationId);
            } else {
                $product = \wc_get_product($item->get_product_id());
            }
            
            if ($product instanceof \WC_Product) {
                
                if(is_string($product->get_sku())){
                    $orderItem->setSku($product->get_sku());
                }
                
                $orderItem->setProductId(new Identity($product->get_id()));
                
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
                    
                    $orderItem->setName(sprintf($format, $orderItem->getName(),
                        \wc_get_formatted_variation($product, true)));
                }
            }
            
            $tax = $order->get_item_tax($item); // the tax amount
            
            if ($tax === 0.0) {
                $netPrice = $priceGross = $order->get_item_subtotal($item, true, false);
            } else {
                $netPrice = $order->get_item_subtotal($item, false, false);
                $priceGross = $order->get_item_subtotal($item, true, false);
                
                // changed  get_item_total to get_item_subtotal because discount problems
                /* $netPrice = $order->get_item_total($item, false, false);
                 $priceGross = $order->get_item_total($item, true, false);*/
            }
            
            if (isset(self::$taxClassRateCache[$item->get_tax_class()])) {
                $taxRate = self::$taxClassRateCache[$item->get_tax_class()];
            } else {
                $taxRate = Util::getInstance()->getTaxRateByTaxClass($item->get_tax_class(), $order);
                self::$taxClassRateCache[$item->get_tax_class()] = $taxRate;
            }
            
            $orderItem
                ->setVat($taxRate)
                ->setPrice(round($netPrice, self::PRICE_DECIMALS))
                ->setPriceGross(round($priceGross, self::PRICE_DECIMALS));
            
            $customerOrderItems[] = $orderItem;
        }
    }
    
    public function pullShippingOrderItems(\WC_Order $order, &$customerOrderItems)
    {
        $this->accurateItemTaxCalculation($order, 'shipping', $customerOrderItems,
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
     * @param $type
     * @param $customerOrderItems
     * @param callable $getItem
     */
    private function accurateItemTaxCalculation(\WC_Order $order, $type, &$customerOrderItems, callable $getItem)
    {
        $productTotalByVat = $this->getProductTotalByVat($customerOrderItems);
        $productTotalByVatWithoutZero = array_filter($productTotalByVat, function ($vat) {
            return $vat !== 0;
        }, ARRAY_FILTER_USE_KEY);
        $totalProductItemsWithoutZero = array_sum(array_values($productTotalByVatWithoutZero));
        
        /** @var \WC_Order_Item_Shipping $shippingItem */
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
                        if (!isset($productTotalByVatWithoutZero[$taxRate])) {
                            $factor = 1;
                        } else {
                            $factor = $productTotalByVatWithoutZero[$taxRate] / $totalProductItemsWithoutZero;
                        }
                        
                        $fees = $costs * $factor;
                        
                        $netPrice = round($fees, self::PRICE_DECIMALS);
                        $priceGross = round($fees + $taxAmount, self::PRICE_DECIMALS);
                    }
                    
                    $customerOrderItem->setPrice($netPrice);
                    $customerOrderItem->setPriceGross($priceGross);
                    
                    $customerOrderItems[] = $customerOrderItem;
                }
            } else {
                /** @var CustomerOrderItemModel $customerOrderItem */
                $customerOrderItem = $getItem($shippingItem, $order, null);
                
                if ($total != 0) {
                    $tmpVat = round(100 / $total * ($total + $totalTax) - 100, 1);
                    $vat = 0.0;
                    $taxRates = (array)SQL::getAllTaxRates();
                    
                    foreach ($taxRates as $taxrate){
                        if($tmpVat >= $taxrate)
                        {
                            $vat = $taxrate;
                        }
                    }
                    
                    $customerOrderItem->setVat((double)$vat);
                    $customerOrderItem->setPrice(round($total, self::PRICE_DECIMALS));
                    $customerOrderItem->setPriceGross(round($total + $totalTax, self::PRICE_DECIMALS));
                }
                
                $customerOrderItems[] = $customerOrderItem;
            }
        }
    }
    
    /**
     * @param \WC_Order $order
     * @param $customerOrderItems
     */
    public function pullDiscountOrderItems(\WC_Order $order, &$customerOrderItems)
    {
        /**
         * @var integer $itemId
         * @var \WC_Order_Item_Coupon $item
         */
        foreach ($order->get_items('coupon') as $itemId => $item) {
            $itemName = $item->get_name();
            
            $customerOrderItems[] = (new CustomerOrderItemModel())
                ->setId(new Identity($itemId))
                ->setCustomerOrderId(new Identity($order->get_id()))
                ->setName(empty($itemName) ? $item->get_code() : $itemName)
                ->setType(CustomerOrderItemModel::TYPE_COUPON)
                ->setPrice(-1 * round((float)$item->get_discount(), self::PRICE_DECIMALS))
                ->setPriceGross(-1 * round((float)$item->get_discount() + (float)$item->get_discount_tax(),
                        self::PRICE_DECIMALS))
                ->setQuantity(1);
        }
    }
    
    private function getProductTotalByVat(array $customerOrderItems)
    {
        $totalPriceForVats = [];
        
        foreach ($customerOrderItems as $item) {
            if ($item instanceof CustomerOrderItemModel && $item->getType() == CustomerOrderItemModel::TYPE_PRODUCT) {
                $taxRate = $item->getVat();
                
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
