<?php

/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers;

use Jtl\Connector\Core\Controller\PushInterface;
use Jtl\Connector\Core\Model\AbstractModel;
use Jtl\Connector\Core\Model\CustomerOrder;
use Jtl\Connector\Core\Model\StatusChange as StatusChangeModel;
use Psr\Log\InvalidArgumentException;
use WC_Order;

class StatusChangeController extends AbstractBaseController implements PushInterface
{
    /**
     * @param StatusChangeModel $model
     * @return StatusChangeModel
     * @throws \WC_Data_Exception|InvalidArgumentException
     */
    public function push(AbstractModel $model): AbstractModel
    {
        $order = \wc_get_order($model->getCustomerOrderId()->getEndpoint());

        if ($order instanceof WC_Order) {
            if ($model->getOrderStatus() === CustomerOrder::STATUS_CANCELLED) {
                \add_filter('woocommerce_can_restore_order_stock', function ($true, $order) {
                    return false;
                }, 10, 2);
            }

            $newStatus = $this->mapStatus($model, $order);
            if ($newStatus !== null) {
                if ($newStatus === 'wc-completed') {
                    $this->linkIfPaymentIsNotLinked($model);
                }

                $order->set_status($newStatus);
                $order->save();
            }
        }

        return $model;
    }

    /**
     * @param StatusChangeModel $statusChange
     * @return void
     * @throws InvalidArgumentException
     */
    protected function linkIfPaymentIsNotLinked(StatusChangeModel $statusChange): void
    {
        global $wpdb;
        $jclp        = $wpdb->prefix . 'jtl_connector_link_payment';
        $endpointId  = $statusChange->getCustomerOrderId()->getEndpoint();
        $paymentLink = $this->db->queryOne(\sprintf(
            'SELECT * FROM %s WHERE `endpoint_id` = %s',
            $jclp,
            $endpointId
        ));
        if (empty($paymentLink)) {
            $this->db->query(\sprintf(
                'INSERT INTO %s (`endpoint_id`, `host_id`) VALUES (%s,%s)',
                $jclp,
                $endpointId,
                0
            ));
        }
    }

    /**
     * @param StatusChangeModel $statusChange
     * @param WC_Order $wcOrder
     * @return string|null
     */
    private function mapStatus(StatusChangeModel $statusChange, WC_Order $wcOrder): ?string
    {
        if ($statusChange->getOrderStatus() === CustomerOrder::STATUS_CANCELLED) {
            return 'wc-cancelled';
        } elseif ($statusChange->getOrderStatus() === CustomerOrder::STATUS_NEW) {
            if ($statusChange->getPaymentStatus() === CustomerOrder::PAYMENT_STATUS_COMPLETED) {
                return 'wc-processing';
            }

            return 'wc-pending';
        } elseif ($statusChange->getOrderStatus() === CustomerOrder::STATUS_SHIPPED) {
            if ($statusChange->getPaymentStatus() === CustomerOrder::PAYMENT_STATUS_COMPLETED) {
                return 'wc-completed';
            }

            if ($wcOrder->has_downloadable_item() === false) {
                return 'wc-processing';
            }

            return 'wc-on-hold';
        } elseif ($statusChange->getOrderStatus() === CustomerOrder::STATUS_PARTIALLY_SHIPPED) {
            return 'wc-processing';
        }

        return null;
    }
}
