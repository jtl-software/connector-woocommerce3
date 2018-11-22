<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Order;

use jtl\Connector\Model\CustomerOrder as CustomerOrderModel;
use jtl\Connector\Model\CustomerOrderPaymentInfo;
use jtl\Connector\Model\Identity;
use jtl\Connector\Payment\PaymentTypes;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Controllers\Traits\PullTrait;
use JtlWooCommerceConnector\Controllers\Traits\StatsTrait;
use JtlWooCommerceConnector\Utilities\Germanized;
use JtlWooCommerceConnector\Utilities\Id;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\Util;

class CustomerOrder extends BaseController
{
    use PullTrait, StatsTrait;

    /** Order received (unpaid) */
    const STATUS_PENDING = 'pending';
    /** Payment received – the order is awaiting fulfillment */
    const STATUS_PROCESSING = 'processing';
    /** Order fulfilled and complete */
    const STATUS_COMPLETED = 'completed';
    /** Awaiting payment – stock is reduced, but you need to confirm payment */
    const STATUS_ON_HOLD = 'on-hold';
    /** Payment failed or was declined (unpaid) */
    const STATUS_FAILED = 'failed';
    /** Cancelled by an admin or the customer */
    const STATUS_CANCELLED = 'cancelled';
    /** Already paid */
    const STATUS_REFUNDED = 'refunded';

    const BILLING_ID_PREFIX = 'b_';
    const SHIPPING_ID_PREFIX = 's_';

    public function pullData($limit)
    {
        $orders = [];

        $orderIds = $this->database->queryList(SqlHelper::customerOrderPull($limit));

        foreach ($orderIds as $orderId) {
            $order = \wc_get_order($orderId);

            if (!$order instanceof \WC_Order) {
                continue;
            }

            $customerOrder = (new CustomerOrderModel())
                ->setId(new Identity($order->get_id()))
                ->setCreationDate($order->get_date_created())
                ->setCurrencyIso($order->get_currency())
                ->setNote($order->get_customer_note())
                ->setCustomerId($order->get_customer_id() === 0
                    ? new Identity(Id::link([Id::GUEST_PREFIX, $order->get_id()]))
                    : new Identity($order->get_customer_id())
                )
                ->setOrderNumber($order->get_order_number())
                ->setShippingMethodName($order->get_shipping_method())
                ->setPaymentModuleCode(Util::getInstance()->mapPaymentModuleCode($order))
                ->setPaymentStatus($this->paymentStatus($order))
                ->setStatus($this->status($order))
                ->setTotalSum(round($order->get_total() - $order->get_total_tax(), \wc_get_price_decimals()))
                ->setTotalSumGross(round($order->get_total(), \wc_get_price_decimals()));

            $customerOrder
                ->setItems(CustomerOrderItem::getInstance()->pullData($order))
                ->setBillingAddress(CustomerOrderBillingAddress::getInstance()->pullData($order))
                ->setShippingAddress(CustomerOrderShippingAddress::getInstance()->pullData($order));

            if ($order->is_paid()) {
                $customerOrder->setPaymentDate($order->get_date_paid());
            }

            if (Germanized::getInstance()->isActive()) {
                $this->setPaymentInfo($customerOrder);
            }

            $orders[] = $customerOrder;
        }

        return $orders;
    }

    protected function paymentStatus(\WC_Order $order)
    {
        if ($order->has_status(self::STATUS_COMPLETED)) {
            return CustomerOrderModel::PAYMENT_STATUS_COMPLETED;
        } elseif ($order->has_status(self::STATUS_PROCESSING)) {
            if ($order->get_payment_method() === 'cod') {
                return CustomerOrderModel::PAYMENT_STATUS_UNPAID;
            }

            return CustomerOrderModel::PAYMENT_STATUS_COMPLETED;
        }

        return CustomerOrderModel::PAYMENT_STATUS_UNPAID;
    }

    protected function status(\WC_Order $order)
    {
        if ($order->has_status(self::STATUS_COMPLETED)) {
            return CustomerOrderModel::STATUS_SHIPPED;
        } elseif ($order->has_status([self::STATUS_CANCELLED, self::STATUS_REFUNDED])) {
            return CustomerOrderModel::STATUS_CANCELLED;
        }

        return CustomerOrderModel::STATUS_NEW;
    }

    protected function setPaymentInfo(CustomerOrderModel &$customerOrder)
    {
        $directDebitGateway = new \WC_GZD_Gateway_Direct_Debit();

        if ($customerOrder->getPaymentModuleCode() === PaymentTypes::TYPE_DIRECT_DEBIT) {
            $orderId = $customerOrder->getId()->getEndpoint();

            $bic = $directDebitGateway->maybe_decrypt(\get_post_meta($orderId, '_direct_debit_bic', true));
            $iban = $directDebitGateway->maybe_decrypt(\get_post_meta($orderId, '_direct_debit_iban', true));

            $paymentInfo = (new CustomerOrderPaymentInfo())
                ->setBic($bic)
                ->setIban($iban)
                ->setAccountHolder(\get_post_meta($orderId, '_direct_debit_holder', true));

            $customerOrder->setPaymentInfo($paymentInfo);

        } elseif ($customerOrder->getPaymentModuleCode() === PaymentTypes::TYPE_INVOICE) {
            $settings = \get_option('woocommerce_invoice_settings');

            if (!empty($settings) && isset($settings['instructions'])) {
                $customerOrder->setPui($settings['instructions']);
            }
        }
    }

    public function getStats()
    {
        return $this->database->queryOne(SqlHelper::customerOrderPull(null));
    }
}
