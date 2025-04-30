<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Controllers;

use Automattic\WooCommerce\Internal\DependencyManagement\ContainerException;
use Jtl\Connector\Core\Controller\PushInterface;
use Jtl\Connector\Core\Model\AbstractModel;
use Jtl\Connector\Core\Model\CustomerOrder;
use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\StatusChange as StatusChangeModel;
use Psr\Log\InvalidArgumentException;
use WC_Order;

class StatusChangeController extends AbstractBaseController implements PushInterface
{
    /**
     * @param AbstractModel ...$models
     * @return StatusChangeModel[]
     * @throws \InvalidArgumentException
     * @throws ContainerException
     * @throws \WC_Data_Exception
     */
    public function push(AbstractModel ...$models): array
    {
        $returnModels = [];

        foreach ($models as $model) {
            /** @var StatusChangeModel $model */
            $customerOrderId = $model->getCustomerOrderId();
            $endpointId      = $customerOrderId instanceof Identity ? $customerOrderId->getEndpoint() : false;
            $order           = \wc_get_order($endpointId);

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

            $returnModels[] = $model;
        }
        return $returnModels;
    }

    /**
     * @param StatusChangeModel $statusChange
     * @return void
     * @throws InvalidArgumentException
     */
    protected function linkIfPaymentIsNotLinked(StatusChangeModel $statusChange): void
    {
        global $wpdb;
        $jclp            = $wpdb->prefix . 'jtl_connector_link_payment';
        $customerOrderId = $statusChange->getCustomerOrderId();
        $endpointId      = $customerOrderId instanceof Identity ? $customerOrderId->getEndpoint() : "0";

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
     * @param WC_Order          $wcOrder
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
