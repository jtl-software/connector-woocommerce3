<?php

namespace jtl\ProductCustomOptions;

use jtl\Connector\Event\CustomerOrder\CustomerOrderAfterPullEvent;
use JtlWooCommerceConnector\Utilities\Db;

/**
 * Class CustomerOrderListener
 * @package jtl\ProductCustomOptions
 */
class CustomerOrderListener
{
    /**
     * @var array
     */
    protected $customFieldNames = [];

    /**
     * @var \wpdb
     */
    protected $wpdb;

    /**
     * CustomerOrderListener constructor.
     */
    public function __construct()
    {
        global $wpdb;
        $this->customFieldNames = get_option(\THWEPOF_Utils::OPTION_KEY_NAME_TITLE_MAP, []);
        $this->wpdb = $wpdb;
    }

    /**
     * @param CustomerOrderAfterPullEvent $event
     */
    public function onCustomerOrderAfterPull(CustomerOrderAfterPullEvent $event)
    {
        if (!empty($this->getCustomFieldNames())) {
            $customerOrder = $event->getCustomerOrder();
            $customerOrderItems = $customerOrder->getItems();
            foreach ($customerOrderItems as $customerOrderItem) {
                $orderItemId = $customerOrderItem->getId();
                if (!empty($orderItemId->getEndpoint())) {
                    $customProductOptionsInfo = $this->getCustomProductOptions((int)$orderItemId->getEndpoint());
                    if (!empty($customProductOptionsInfo)) {
                        $orderItemNotes = [];
                        if (!empty($customerOrderItem->getNote())) {
                            $orderItemNotes[] = $customerOrderItem->getNote();
                        }
                        $orderItemNotes[] = sprintf('%s: %s', 'Extra Product Options', $customProductOptionsInfo);
                        $customerOrderItem->setNote(join(', ', $orderItemNotes));
                    }
                }
            }
        }
    }

    /**
     * @param int $wcOrderItemId
     * @return string
     */
    public function getCustomProductOptions(int $wcOrderItemId): string
    {
        $customProductOptions = [];

        $sql = sprintf(
            'SELECT meta_key,meta_value FROM %swoocommerce_order_itemmeta WHERE order_item_id = %s AND meta_key IN (\'%s\')',
            $this->wpdb->prefix, $wcOrderItemId, join("','", array_keys($this->getCustomFieldNames()))
        );

        $customOptions = Db::getInstance()->query($sql);
        foreach ($customOptions as $customOption) {
            $label = !empty($this->customFieldNames[$customOption['meta_key']]) ? $this->customFieldNames[$customOption['meta_key']] : $customOption['meta_key'];
            $customProductOptions[] = sprintf('%s = %s', $label, $customOption['meta_value']);
        }

        return join(', ', $customProductOptions);
    }

    /**
     * @return array
     */
    protected function getCustomFieldNames(): array
    {
        return $this->customFieldNames;
    }
}
