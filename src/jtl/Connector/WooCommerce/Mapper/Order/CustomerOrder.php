<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Mapper\Order;

use jtl\Connector\Model\CustomerOrder as CustomerOrderModel;
use jtl\Connector\Model\Identity;
use jtl\Connector\WooCommerce\Mapper\BaseMapper;
use jtl\Connector\WooCommerce\Utility\IdConcatenation;
use jtl\Connector\WooCommerce\Utility\Util;

class CustomerOrder extends BaseMapper
{
    const BILLING_ID_PREFIX = 'b_';
    const SHIPPING_ID_PREFIX = 's_';

    protected $pull = [
        'id'                 => 'id',
        'creationDate'       => 'order_date',
        'currencyIso'        => 'order_currency',
        'note'               => 'customer_note',
        'items'              => 'Order\CustomerOrderItem',
        'billingAddress'     => 'Order\CustomerOrderBillingAddress',
        'shippingAddress'    => 'Order\CustomerOrderShippingAddress',
        'customerId'         => null,
        'orderNumber'        => null,
        'shippingMethodName' => null,
        'paymentModuleCode'  => null,
        'paymentStatus'      => null,
        'status'             => null,
        'totalSum'           => null,
        'totalSumGross'      => null,
    ];

    protected function customerId(\WC_Order $order)
    {
        if ($order->get_customer_id() === 0) {
            return new Identity(IdConcatenation::link([IdConcatenation::GUEST_PREFIX, $order->get_id()]));
        }

        return new Identity($order->get_customer_id());
    }

    protected function orderNumber(\WC_Order $order)
    {
        return $order->get_order_number();
    }

    protected function shippingMethodName(\WC_Order $order)
    {
        return $order->get_shipping_method();
    }

    protected function paymentStatus(\WC_Order $order)
    {
        if ($order->has_status('completed')) {
            return CustomerOrderModel::PAYMENT_STATUS_COMPLETED;
        } elseif ($order->has_status('processing')) {
            if ($order->get_payment_method() === 'cod') {
                return CustomerOrderModel::PAYMENT_STATUS_UNPAID;
            }

            return CustomerOrderModel::PAYMENT_STATUS_COMPLETED;
        }

        return CustomerOrderModel::PAYMENT_STATUS_UNPAID;
    }

    protected function status(\WC_Order $order)
    {
        $status = $order->get_status();

        if ($status === 'pending' || $status === 'processing' || $status === 'on-hold' || $status === 'failed') {
            return CustomerOrderModel::STATUS_NEW;
        } elseif ($status === 'cancelled' || $status === 'refunded') {
            return CustomerOrderModel::STATUS_CANCELLED;
        } elseif ($status === 'completed') {
            return CustomerOrderModel::STATUS_SHIPPED;
        }

        return '';
    }

    protected function totalSum(\WC_Order $order)
    {
        return round($order->get_total() - $order->get_total_tax(), \wc_get_price_decimals());
    }

    protected function totalSumGross(\WC_Order $order)
    {
        return round($order->get_total(), \wc_get_price_decimals());
    }

    protected function paymentModuleCode(\WC_Order $order)
    {
        return Util::getInstance()->mapPaymentModuleCode($order);
    }
}
