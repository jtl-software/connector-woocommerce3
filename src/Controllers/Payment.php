<?php

/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Payment as PaymentModel;
use jtl\Connector\Payment\PaymentTypes;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\Util;

/**
 * Class Payment
 * @package JtlWooCommerceConnector\Controllers
 */
class Payment extends BaseController
{
    /**
     *
     */
    public const PAY_UPON_INVOICE = 'PAY_UPON_INVOICE';

    /**
     * @param $limit
     * @return array
     * @throws \InvalidArgumentException
     */
    public function pullData($limit): array
    {
        $payments = [];

        $includeCompletedOrders = Util::includeCompletedOrders();

        $completedOrders = $this->database->queryList(SqlHelper::paymentCompletedPull($includeCompletedOrders, $limit));

        foreach ($completedOrders as $orderId) {
            $order = \wc_get_order((int)$orderId);

            if (!$order instanceof \WC_Order) {
                continue;
            }

            $paymentModuleCode = Util::getInstance()->mapPaymentModuleCode($order);

            $payments[] = (new PaymentModel())
                ->setId(new Identity($order->get_id()))
                ->setCustomerOrderId(new Identity($order->get_id()))
                ->setTotalSum((float)$order->get_total())
                ->setPaymentModuleCode($paymentModuleCode)
                ->setTransactionId($this->getTransactionId($paymentModuleCode, $order))
                ->setCreationDate(
                    $order->get_date_paid()
                        ? $order->get_date_paid()
                        : $order->get_date_completed()
                );
        }

        return $payments;
    }

    /**
     * @param string $paymentModuleCode
     * @param \WC_Order $order
     * @return string
     */
    protected function getTransactionId(string $paymentModuleCode, \WC_Order $order): string
    {
        $transactionId = $order->get_transaction_id();
        if ($paymentModuleCode == PaymentTypes::TYPE_AMAPAY) {
            $transactionId = $order->get_meta('amazon_charge_id');
        }

        return (string)$transactionId;
    }

    /**
     * @param PaymentModel $data
     * @return PaymentModel
     * @throws \WC_Data_Exception
     */
    public function pushData(PaymentModel $data): PaymentModel
    {
        $order = \wc_get_order((int)$data->getCustomerOrderId()->getEndpoint());

        if (!$order instanceof \WC_Order) {
            return $data;
        }

        $order->set_transaction_id($data->getTransactionId());
        $order->set_date_paid($data->getCreationDate());
        $order->save();

        return $data;
    }

    /**
     * @return int
     */
    protected function getStats(): int
    {
        $includeCompletedOrders = Util::includeCompletedOrders();

        return (int)$this->database->queryOne(SqlHelper::paymentCompletedPull($includeCompletedOrders));
    }
}
