<?php

/**
 * @copyright 2010-2019 JTL-Software GmbH
 * @package Jtl\Connector\Core\Application
 */

namespace JtlWooCommerceConnector\Controllers;

use Exception;
use Jtl\Connector\Core\Controller\PushInterface;
use Jtl\Connector\Core\Model\AbstractModel;
use Jtl\Connector\Core\Model\DeliveryNote as DeliveryNoteModel;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use WC_Advanced_Shipment_Tracking_Actions;
use AST_Pro_Actions;

class DeliveryNoteController extends AbstractBaseController implements PushInterface
{
    /**
     * @param DeliveryNoteModel $model
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
        } elseif (
            SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO)
        ) {
            //TODO: entferne alle Kommentare nach CR
            #WC Order wird inkl items geholt
            $orderId = $model->getCustomerOrderId()->getEndpoint();
            $order   = \wc_get_order($orderId);
            $order->get_items();

            #Shipments zum order werden geholt
            $wcShipments = \wc_gzd_get_shipments_by_order($order->get_id());
            $wcShipment  = false;

            $statusSet = false;

            foreach ($wcShipments as $wcShipment) {
                #Beachte nur die Shipments mit status processing/draft, da abgeschlossene nicht relevant sind
                if ($wcShipment->get_status() == 'processing' || $wcShipment->get_status() == 'draft') {
                    $numWcShipmentPositions   = 0;
                    $numWawiShipmentPositions = 0;

                    #Da Wawi Federführend, gibt es nur einen einzigen Fall, indem in WC ein offenes Shipment
                    #existieren kann, undzwar, wenn WC mit Bestelleingang automatisch eine Sendung erstellt
                    #und alle Items reinpackt
                    #wenn wir von der Wawi aus ebenfalls alle items gleichzeitig in einer Sendung schicken
                    #dann Stimmen die Anzahl-Wawi items mit Anzahl-WC items überein.

                    #hole Anzahl WC Items
                    foreach ($wcShipment->get_items() as $wcShipmentItem) {
                        $numWcShipmentPositions += (int)$wcShipmentItem->get_quantity();
                    }

                    #hole Amzahl Wawi Items
                    foreach ($model->getItems() as $item) {
                        $numWawiShipmentPositions += (int)$item->getQuantity();
                    }

                    #wenn sie übereinstimmen, dann kann die bereits existierende Sendung genutzt werden. Da muss
                    #nur noch der Status auf shipped gesetzt werden. Wenn die Zahl nicht übereinstimmt, dann handeld
                    #es sich um eine Teilsendung => Behalte die existierende Sendung, aber lösche alle ihre Items
                    #um sie für den nächsten Schritt vorzubereiten
                    if ($numWcShipmentPositions != $numWawiShipmentPositions) {
                        $wcShipment->remove_items();
                        break;
                    } else {
                        $wcShipment->set_status('shipped');
                        $statusSet = true;
                        break;
                    }
                } else {
                    #es kann vorkommen, dass nur bereits abgeschlossene Sendungen in WC existieren. Daher wird die
                    #Variable hier false gesetzt um später daran zu erkennen, ob eine neue Sendung erstellt werden muss
                    $wcShipment = false;
                }
            }

            #erstelle eine neue Sendung (WC fügt automatisch alle items hinzu) und leere die items.
            if ($wcShipment === false) {
                $wcShipmentOrder = \wc_gzd_get_shipment_order($order);
                $wcShipment      = \wc_gzd_create_shipment(
                    $wcShipmentOrder,
                    array('props' => array('status' => 'processing'))
                );
                $wcShipment->remove_items();
            }

            #solange der Sendungsstatus oben nicht auf 'shipped' gesetzt wurde, muss die Sendung noch aktualisiert
            #werden. Über die $orderItemId kann jeweils ein Item erstellt und der Sendung hinzugefügt werden
            if (!$statusSet) {
                foreach ($model->getItems() as $item) {
                    $orderItemName = $this->db->query(
                        \sprintf(
                            "SELECT post_title FROM `%s%s` WHERE id = '%s'",
                            $this->db->getWpDb()->prefix,
                            'posts',
                            $item->getProductId()->getEndpoint()
                        )
                    )[0]['post_title'];

                    $orderItemId = $this->db->query(
                        \sprintf(
                            "SELECT * FROM `%s%s` WHERE order_id = '%s' AND order_item_name LIKE '%s'",
                            $this->db->getWpDb()->prefix,
                            'woocommerce_order_items',
                            $orderId,
                            $orderItemName
                        )
                    )[0]['order_item_id'];

                    $newItem = new \WC_Order_Item_Product($orderItemId);
                    $item    = \wc_gzd_create_shipment_item(
                        $wcShipment,
                        $newItem,
                        array('quantity' => $item->getQuantity())
                    );
                    $wcShipment->add_item($item);
                }

                $wcShipment->set_status('shipped');
                $wcShipment->save();
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
