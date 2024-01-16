<?php

namespace jtl\ProductCustomOptions;

use Jtl\Connector\Core\Event\CustomerOrderEvent;
use JtlWooCommerceConnector\Utilities\Db;
use Psr\Log\InvalidArgumentException;

/**
 * Class CustomerOrderListener
 * @package jtl\ProductCustomOptions
 */
class CustomerOrderListener
{
    /**
     * @var array
     */
    protected mixed $customFieldNames = [];

    /**
     * @var Db
     */
    protected Db $db;

    /**
     * CustomerOrderListener constructor.
     */
    public function __construct(Db $db)
    {
        $this->customFieldNames = \get_option(\THWEPOF_Utils::OPTION_KEY_NAME_TITLE_MAP, []);
        $this->db               = $db;
    }

    /**
     * @param CustomerOrderEvent $event
     * @return void
     * @throws \InvalidArgumentException
     */
    public function onCustomerOrderAfterPull(CustomerOrderEvent $event): void
    {
        if (!empty($this->getCustomFieldNames())) {
            $customerOrder      = $event->getOrder();
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
                        $orderItemNotes[] = \sprintf(
                            '%s: %s',
                            'Extra Product Options',
                            $customProductOptionsInfo
                        );
                        $customerOrderItem->setNote(\implode(', ', $orderItemNotes));
                    }
                }
            }
        }
    }

    /**
     * @param int $wcOrderItemId
     * @return string
     * @throws InvalidArgumentException
     */
    public function getCustomProductOptions(int $wcOrderItemId): string
    {
        $customProductOptions = [];

        $sql = \sprintf(
            'SELECT meta_key,meta_value FROM %swoocommerce_order_itemmeta 
                           WHERE order_item_id = %s AND meta_key IN (\'%s\')',
            $this->db->getWpDb()->prefix,
            $wcOrderItemId,
            \join("','", \array_keys($this->getCustomFieldNames()))
        );

        $customOptions = $this->db->query($sql);
        foreach ($customOptions as $customOption) {
            $label                  = !empty($this->customFieldNames[$customOption['meta_key']])
                ? $this->customFieldNames[$customOption['meta_key']]
                : $customOption['meta_key'];
            $customProductOptions[] = \sprintf('%s = %s', $label, $customOption['meta_value']);
        }

        return \join(', ', $customProductOptions);
    }

    /**
     * @return array
     */
    protected function getCustomFieldNames(): array
    {
        return $this->customFieldNames;
    }
}
