<?php

namespace JtlWooCommerceConnector\Controllers;

use Jtl\Connector\Core\Controller\PullInterface;
use Jtl\Connector\Core\Controller\PushInterface;
use Jtl\Connector\Core\Controller\StatisticInterface;
use Jtl\Connector\Core\Model\AbstractModel;
use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\Payment as PaymentModel;
use Jtl\Connector\Core\Model\QueryFilter;
use Jtl\Connector\Core\Definition\PaymentType;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\Util;
use Psr\Log\InvalidArgumentException;

/**
 * Class Payment
 * @package JtlWooCommerceConnector\Controllers
 */
class PaymentController extends AbstractBaseController implements PullInterface, PushInterface, StatisticInterface
{
    /**
     *
     */
    public const PAY_UPON_INVOICE = 'PAY_UPON_INVOICE';

    /**
     * @param QueryFilter $query
     * @return array|AbstractModel[]
     * @throws InvalidArgumentException
     */
    public function pull(QueryFilter $query): array
    {
        $payments = [];

        $includeCompletedOrders = Util::includeCompletedOrders();

        $completedOrders = $this->db->queryList(
            SqlHelper::paymentCompletedPull($includeCompletedOrders, $query->getLimit())
        );

        foreach ($completedOrders as $orderId) {
            $order = \wc_get_order((int)$orderId);

            if (!$order instanceof \WC_Order) {
                continue;
            }

            $orderHostId = $this->getOrderHostId($order->get_id());

            $paymentModuleCode = $this->util->mapPaymentModuleCode($order);

            $payments[] = (new PaymentModel())
                ->setId(new Identity($order->get_id()))
                ->setCustomerOrderId(new Identity($order->get_id(), $orderHostId))
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
    * @param int $endpointId
    * @return int
    * @throws InvalidArgumentException
     */
    public function getOrderHostId(int $endpointId): int
    {
        $query = \sprintf(
            "Select host_id from %sjtl_connector_link_order where endpoint_id = %s",
            $this->db->getWpDb()->prefix,
            $endpointId
        );

        return $this->db->queryOne($query);
    }

    /**
     * @param string $paymentModuleCode
     * @param \WC_Order $order
     * @return string
     */
    protected function getTransactionId(string $paymentModuleCode, \WC_Order $order): string
    {
        $transactionId = $order->get_transaction_id();
        if ($paymentModuleCode == PaymentType::AMAPAY) {
            $transactionId = $order->get_meta('amazon_charge_id');
        }

        return (string)$transactionId;
    }

    /**
     * @param PaymentModel $model
     * @return PaymentModel
     * @throws \WC_Data_Exception
     */
    public function push(AbstractModel $model): AbstractModel
    {
        $order = \wc_get_order((int)$model->getCustomerOrderId()->getEndpoint());

        if (!$order instanceof \WC_Order) {
            return $model;
        }

        $order->set_transaction_id($model->getTransactionId());
        $order->set_date_paid($model->getCreationDate());
        $order->save();

        return $model;
    }

    /**
     * @param QueryFilter $query
     * @return int
     * @throws InvalidArgumentException
     */
    public function statistic(QueryFilter $query): int
    {
        $includeCompletedOrders = Util::includeCompletedOrders();

        return (int)$this->db->queryOne(SqlHelper::paymentCompletedPull($includeCompletedOrders));
    }
}
