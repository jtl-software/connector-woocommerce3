<?php
/**
 * @copyright 2010-2019 JTL-Software GmbH
 * @package Jtl\Connector\Core\Application
 */

namespace JtlWooCommerceConnector\Controllers;

use JtlWooCommerceConnector\Controllers\Traits\PushTrait;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use Vendidero\Germanized\Shipments\Order;
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
        $orderId = $deliveryNote->getCustomerOrderId()->getEndpoint();

        $order = \wc_get_order($orderId);

        if (!$order instanceof \WC_Order) {
            return $deliveryNote;
        }

        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_ADVANCED_SHIPMENT_TRACKING_FOR_WOOCOMMERCE)) {

            $shipmentTrackingActions = WC_Advanced_Shipment_Tracking_Actions::get_instance();

            foreach ($deliveryNote->getTrackingLists() as $trackingList) {

                $trackingInfoItem = [];
                $trackingInfoItem['date_shipped'] = $deliveryNote->getCreationDate()->format("Y-m-d");

                $trackingProviders = $shipmentTrackingActions->get_providers();

                $shippingProviderName = trim($trackingList->getName());

                $providerSlug = $this->findTrackingProviderSlug($shippingProviderName, is_array($trackingProviders) ? $trackingProviders : []);
                if ($providerSlug !== null) {
                    $trackingInfoItem['tracking_provider'] = $providerSlug;
                } else {
                    $trackingInfoItem['custom_tracking_provider'] = $shippingProviderName;
                }

                foreach ($trackingList->getCodes() as $trackingCode) {
                    $trackingInfoItem['tracking_number'] = $trackingCode;
                    $shipmentTrackingActions->add_tracking_item($order->get_id(), $trackingInfoItem);
                }
            }
        }

        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO)) {

            global $wpdb;

            if (in_array('woocommerce_gzd_shipments', $wpdb->tables) && function_exists('wc_gzd_get_shipment_order')) {
                $shipmentOrder = wc_gzd_get_shipment_order($order);
                if($shipmentOrder instanceof Order) {
                    foreach ($deliveryNote->getTrackingLists() as $trackingList) {
                        $methodName = $trackingList->getName();
                        $shipments = $shipmentOrder->get_shipments();

                        if (!empty($shipments)) {
                            foreach ($shipments as $shipment) {
                                $orderShippingMethod = $shipment->get_shipping_method();
                                $shippingMethodDelimiter = strpos($orderShippingMethod, ':');
                                if ($shippingMethodDelimiter !== false) {
                                    $shippingMethod = substr($orderShippingMethod, 0, $shippingMethodDelimiter);

                                    $wcShippingMethods = \WC()->shipping()->get_shipping_methods();
                                    $mappedWcShippingMethod = null;
                                    foreach ($wcShippingMethods as $wcShippingMethod) {
                                        if ($shippingMethod === $wcShippingMethod->id) {
                                            $mappedWcShippingMethod = $wcShippingMethod;
                                            break;
                                        }
                                    }

                                    $fullTitleMatch = $mappedWcShippingMethod->get_method_title() === $methodName;
                                    $methodIdMatch = $mappedWcShippingMethod->id === str_replace(' ', '_', strtolower($methodName));

                                    if (!is_null($mappedWcShippingMethod) && ($fullTitleMatch || $methodIdMatch)) {
                                        $shipment->set_status('shipped', true);
                                        $shipment->set_tracking_id(join(',', $trackingList->getCodes()));
                                        $shipment->save();
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $deliveryNote;
    }

    /**
     * @param string $shippingMethodName
     * @param array $trackingProviders
     * @return string|null
     */
    private function findTrackingProviderSlug($shippingMethodName, $trackingProviders)
    {
        $searchResultSlug = null;
        $searchResultLength  = 0;
        $sameSearchResultQuantity = 0;

        foreach($trackingProviders as $trackingProviderSlug => $trackingProvider){

            $providerName = $trackingProvider['provider_name'];
            $providerNameLength = strlen($providerName);

            $shippingMethodNameStartsWithProviderName
                = substr($shippingMethodName,0,$providerNameLength) === $providerName;
            $newResultIsMoreSimilarThanPrevious = $providerNameLength > $searchResultLength;
            $newResultHasSameLengthAsPrevious = $providerNameLength === $searchResultLength;

            if($shippingMethodNameStartsWithProviderName){
                if($newResultIsMoreSimilarThanPrevious){
                    $searchResultSlug = (string) $trackingProviderSlug;
                    $searchResultLength = $providerNameLength;
                    $sameSearchResultQuantity = 0;
                }
                elseif($newResultHasSameLengthAsPrevious){
                    $searchResultSlug = null;
                    $sameSearchResultQuantity++;
                }
            }
        }

        return $sameSearchResultQuantity > 1 ? null : $searchResultSlug;
    }
}