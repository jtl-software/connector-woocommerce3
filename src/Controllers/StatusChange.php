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

    public function pushData(StatusChangeModel $statusChange)
    {
        $order = \wc_get_order($statusChange->getCustomerOrderId()->getEndpoint());

        if ($order instanceof \WC_Order) {
            $order->set_status($this->mapStatus($statusChange));
            $order->save();
        }

        return $statusChange;
    }

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
        }

        return null;
    }
}
