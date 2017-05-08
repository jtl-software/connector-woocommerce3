<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller\Order;

use jtl\Connector\Model\CustomerOrder as CustomerOrderModel;
use jtl\Connector\WooCommerce\Controller\BaseController;
use jtl\Connector\WooCommerce\Controller\Traits\PullTrait;
use jtl\Connector\WooCommerce\Controller\Traits\StatsTrait;
use jtl\Connector\WooCommerce\Utility\SQLs;

abstract class AbstractCustomerOrder extends BaseController
{
    use PullTrait, StatsTrait;

    public function pullData($limit)
    {
        $orders = [];

        $includeCompletedOrders = \get_option(\JtlConnectorAdmin::OPTIONS_COMPLETED_ORDERS, 'yes') === 'yes';

        $nonLinkedOrderIds = $this->database->queryList(SQLs::customerOrderPull($limit, $includeCompletedOrders));

        foreach ($nonLinkedOrderIds as $orderId) {
            $order = \wc_get_order($orderId);

            if ($order instanceof \WC_Order) {
                $host = $this->mapper->toHost($order);
                if ($host instanceof CustomerOrderModel) {
                    $this->onCustomerOrderMapped($host);
                    $orders[] = $host;
                }
            }
        }

        return $orders;
    }

    public function getStats()
    {
        $includeCompletedOrders = \get_option(\JtlConnectorAdmin::OPTIONS_COMPLETED_ORDERS, 'yes') === 'yes';

        return $this->database->queryOne(SQLs::customerOrderPull(null, $includeCompletedOrders));
    }

    abstract protected function onCustomerOrderMapped(CustomerOrderModel &$customerOrder);
}
