<?php

namespace JtlWooCommerceConnector\Controllers;

use Exception;
use Jtl\Connector\Core\Controller\PushInterface;
use Jtl\Connector\Core\Model\AbstractModel;
use Jtl\Connector\Core\Model\DeliveryNote as DeliverNoteModel;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use WC_Advanced_Shipment_Tracking_Actions;
use AST_Pro_Actions;

class DeliveryNoteController extends AbstractBaseController implements PushInterface
{
    /**
     * @param DeliverNoteModel $model
     * @return AbstractModel
     * @throws Exception
     */
    public function push(AbstractModel $model): AbstractModel
    {
        if (
            SupportedPlugins::isActive(SupportedPlugins::PLUGIN_ADVANCED_SHIPMENT_TRACKING_FOR_WOOCOMMERCE)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_ADVANCED_SHIPMENT_TRACKING_PRO)
        ) {
            $orderId = $model->getCustomerOrderId()->getEndpoint();

            $order = \wc_get_order($orderId);

            if (!$order instanceof \WC_Order) {
                return $model;
            }

            $shipmentTrackingActions = $this->getShipmentTrackingActions();

            foreach ($model->getTrackingLists() as $trackingList) {
                $trackingInfoItem                 = [];
                $trackingInfoItem['date_shipped'] = $model->getCreationDate()->format("Y-m-d");

                $trackingProviders = $shipmentTrackingActions->get_providers();

                $shippingProviderName = \trim($trackingList->getName());

                $providerSlug = $this->findTrackingProviderSlug(
                    $shippingProviderName,
                    \is_array($trackingProviders)
                    ? $trackingProviders
                    : []
                );
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

        return $model;
    }

    /**
     * @return object|WC_Advanced_Shipment_Tracking_Actions|null
     */
    protected function getShipmentTrackingActions()
    {
        $shipmentTrackingActions = null;
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_ADVANCED_SHIPMENT_TRACKING_FOR_WOOCOMMERCE)) {
            $shipmentTrackingActions = WC_Advanced_Shipment_Tracking_Actions::get_instance();
        } else {
            if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_ADVANCED_SHIPMENT_TRACKING_PRO)) {
                $shipmentTrackingActions = AST_Pro_Actions::get_instance();
            }
        }
        return $shipmentTrackingActions;
    }

    /**
     * @param string $shippingMethodName
     * @param array $trackingProviders
     * @return string|null
     */
    private function findTrackingProviderSlug(string $shippingMethodName, array $trackingProviders): ?string
    {
        $searchResultSlug         = null;
        $searchResultLength       = 0;
        $sameSearchResultQuantity = 0;

        foreach ($trackingProviders as $trackingProviderSlug => $trackingProvider) {
            $providerName       = $trackingProvider['provider_name'];
            $providerNameLength = \strlen($providerName);

            $shippingMethodNameStartsWithProviderName
                = \substr($shippingMethodName, 0, $providerNameLength) === $providerName;
            $newResultIsMoreSimilarThanPrevious       = $providerNameLength > $searchResultLength;
            $newResultHasSameLengthAsPrevious         = $providerNameLength === $searchResultLength;

            if ($shippingMethodNameStartsWithProviderName) {
                if ($newResultIsMoreSimilarThanPrevious) {
                    $searchResultSlug         = (string)$trackingProviderSlug;
                    $searchResultLength       = $providerNameLength;
                    $sameSearchResultQuantity = 0;
                } elseif ($newResultHasSameLengthAsPrevious) {
                    $searchResultSlug = null;
                    $sameSearchResultQuantity++;
                }
            }
        }

        return $sameSearchResultQuantity > 1 ? null : $searchResultSlug;
    }
}
