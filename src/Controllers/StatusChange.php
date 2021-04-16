<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers;

use jtl\Connector\Model\CustomerOrder;
use jtl\Connector\Model\StatusChange as StatusChangeModel;
use JtlWooCommerceConnector\Controllers\Traits\PushTrait;

class StatusChange extends BaseController
{
    use PushTrait;

    /**
     * @param StatusChangeModel $statusChange
     * @return StatusChangeModel
     */
    public function pushData(StatusChangeModel $statusChange)
    {
        $order = \wc_get_order($statusChange->getCustomerOrderId()->getEndpoint());

        if ($order instanceof \WC_Order) {

            if ($statusChange->getOrderStatus() === CustomerOrder::STATUS_CANCELLED) {
                add_filter('woocommerce_can_restore_order_stock', function ($true, $order) {
                    return false;
                }, 10, 2);
            }

            $newStatus = $this->mapStatus($statusChange);
            if ($newStatus !== null) {
                if($newStatus === 'wc-completed'){
                    $this->linkIfPaymentIsNotLinked($statusChange);
                }

                $order->set_status($newStatus);
                $order->save();
            }
        }

        return $statusChange;
    }

    protected function linkIfPaymentIsNotLinked(StatusChangeModel $statusChange)
    {
        global $wpdb;
        $jclp = $wpdb->prefix . 'jtl_connector_link_payment';
        $paymentLink = $this->database->queryOne('SELECT * FROM %s WHERE `endpoint_id` = %s', $jclp, $endpointId = $statusChange->getCustomerOrderId()->getEndpoint());
        if (empty($paymentLink)) {
            $this->database->query('INSERT INTO %s (`endpoint_id`, `host_id`) VALUES (%s,%s)', $jclp, $endpointId, 0);
        }
    }

    /**
     * @param StatusChangeModel $statusChange
     * @return string|null
     */
    private function mapStatus(StatusChangeModel $statusChange)
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

            return 'wc-on-hold';
        } elseif ($statusChange->getOrderStatus() === CustomerOrder::STATUS_PARTIALLY_SHIPPED) {
            return 'wc-processing';
        }

        return null;
    }
}
