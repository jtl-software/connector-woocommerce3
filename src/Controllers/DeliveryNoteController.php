<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Controllers;

use Exception;
use http\Exception\InvalidArgumentException;
use Jtl\Connector\Core\Controller\PushInterface;
use Jtl\Connector\Core\Model\AbstractModel;
use Jtl\Connector\Core\Model\DeliveryNote as DeliverNoteModel;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use WC_Advanced_Shipment_Tracking_Actions;
use AST_Pro_Actions;

class DeliveryNoteController extends AbstractBaseController implements PushInterface
{
    /**
     * @param AbstractModel $model
     * @return AbstractModel
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function push(AbstractModel $model): AbstractModel
    {
        if (
            SupportedPlugins::isActive(SupportedPlugins::PLUGIN_ADVANCED_SHIPMENT_TRACKING_FOR_WOOCOMMERCE)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_ADVANCED_SHIPMENT_TRACKING_PRO)
        ) {
            /** @var DeliverNoteModel $model */
            $orderId = $model->getCustomerOrderId()->getEndpoint();

            $order = \wc_get_order($orderId);

            if (!$order instanceof \WC_Order) {
                return $model;
            }

            $shipmentTrackingActions = $this->getShipmentTrackingActions();

            if (!$shipmentTrackingActions instanceof WC_Advanced_Shipment_Tracking_Actions) {
                throw new InvalidArgumentException(
                    "shipmentTrackingActions expected to be instance of
                    WC_Advanced_Shipment_Tracking_Actions but got null or object instead."
                );
            }

            foreach ($model->getTrackingLists() as $trackingList) {
                $trackingInfoItem                 = [];
                $trackingInfoItem['date_shipped'] = $model->getCreationDate()
                    ? $model->getCreationDate()->format("Y-m-d")
                    : '';

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

                    foreach ($trackingList->getTrackingURLs() as $trackingURL) {
                        if (\str_contains($trackingURL, $trackingCode)) {
                            $trackingInfoItem['custom_tracking_link'] = $trackingURL;
                            break;
                        }
                    }

                    $shipmentTrackingActions->add_tracking_item($order->get_id(), $trackingInfoItem);
                }
            }
        }

        return $model;
    }

    /**
     * @return object|null
     */
    protected function getShipmentTrackingActions(): object|null
    {
        $shipmentTrackingActions = null;
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_ADVANCED_SHIPMENT_TRACKING_FOR_WOOCOMMERCE)) {
            $shipmentTrackingActions = WC_Advanced_Shipment_Tracking_Actions::get_instance();
        } else {
            if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_ADVANCED_SHIPMENT_TRACKING_PRO)) {
                /** @phpstan-ignore class.notFound */
                $shipmentTrackingActions = AST_Pro_Actions::get_instance();
            }
        }
        return $shipmentTrackingActions;
    }

    /**
     * @param string                                   $shippingMethodName
     * @param array<int|string, array<string, string>> $trackingProviders
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
