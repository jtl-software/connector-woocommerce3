<?php
/**
 * @copyright 2010-2019 JTL-Software GmbH
 * @package Jtl\Connector\Core\Application
 */

namespace JtlWooCommerceConnector\Controllers;

use JtlWooCommerceConnector\Controllers\Traits\PushTrait;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use WC_Advanced_Shipment_Tracking_Actions;

class DeliveryNote extends BaseController
{
    use PushTrait;

    /**
     * @param \jtl\Connector\Model\DeliveryNote $deliveryNote
     * @return \jtl\Connector\Model\DeliveryNote
     */
    protected function pushData(\jtl\Connector\Model\DeliveryNote $deliveryNote)
    {
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_ADVANCED_SHIPMENT_TRACKING_FOR_WOOCOMMERCE)) {

            $orderId = $deliveryNote->getCustomerOrderId()->getEndpoint();

            $order = \wc_get_order($orderId);

            if (!$order instanceof \WC_Order) {
                return $deliveryNote;
            }

            $shipmentTrackingActions = WC_Advanced_Shipment_Tracking_Actions::get_instance();

            foreach ($deliveryNote->getTrackingLists() as $trackingList) {

                $trackingInfoItem = [];
                $trackingInfoItem['data_shipped'] = $deliveryNote->getCreationDate()->format("Y-m-d");

                $providerSlug = $this->findTrackingProviderSlug($trackingList->getName(),$shipmentTrackingActions);
                if($providerSlug !== null){
                    $trackingInfoItem['tracking_provider'] = $providerSlug;
                } else {
                    $trackingInfoItem['custom_tracking_provider'] = $trackingList->getName();
                }

                foreach ($trackingList->getCodes() as $trackingCode) {
                    $trackingInfoItem['tracking_number'] = $trackingCode;
                    $shipmentTrackingActions->add_tracking_item($order->get_id(), $trackingInfoItem);
                }
            }
        }

        return $deliveryNote;
    }

    /**
     * @param $providerName
     * @param $shipmentTrackingActions
     * @return string|null
     */
    private function findTrackingProviderSlug($providerName,$shipmentTrackingActions)
    {
        $providers = $shipmentTrackingActions->get_providers();

        $trackingProviderSlug = null;
        foreach($providers as $providerSlug => $provider){
            if(stripos($providerName,$provider['provider_name'])!==false){
                $trackingProviderSlug = (string) $providerSlug;
                break;
            }
        }

        return $trackingProviderSlug;
    }
}