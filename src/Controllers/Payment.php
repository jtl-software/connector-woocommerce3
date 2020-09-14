<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Payment as PaymentModel;
use JtlWooCommerceConnector\Controllers\Traits\PullTrait;
use JtlWooCommerceConnector\Controllers\Traits\PushTrait;
use JtlWooCommerceConnector\Controllers\Traits\StatsTrait;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\Util;

class Payment extends BaseController
{
    const PAY_UPON_INVOICE = 'PAY_UPON_INVOICE';

    use PullTrait, PushTrait, StatsTrait;

    public function pullData($limit)
    {
        $payments = [];

        $includeCompletedOrders = Util::includeCompletedOrders();

        $completedOrders = $this->database->queryList(SqlHelper::paymentCompletedPull($includeCompletedOrders, $limit));

        foreach ($completedOrders as $orderId) {
            $order = \wc_get_order((int)$orderId);

            if (!$order instanceof \WC_Order) {
                continue;
            }

            $payments[] = (new PaymentModel())
                ->setId(new Identity($order->get_id()))
                ->setCustomerOrderId(new Identity($order->get_id()))
                ->setTotalSum((float)$order->get_total())
                ->setPaymentModuleCode(Util::getInstance()->mapPaymentModuleCode($order))
                ->setTransactionId($order->get_transaction_id())
                ->setCreationDate($order->get_date_paid() ? $order->get_date_paid() : $order->get_date_completed());
        }

        return $payments;
    }

    protected function pushData(PaymentModel $data)
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

    protected function getStats()
    {
        $includeCompletedOrders = Util::includeCompletedOrders();

        return (int)$this->database->queryOne(SqlHelper::paymentCompletedPull($includeCompletedOrders));
    }
}
