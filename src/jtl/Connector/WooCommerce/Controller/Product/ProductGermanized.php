<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller\Product;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\WooCommerce\Logger\WpErrorLogger;
use jtl\Connector\WooCommerce\Utility\SQL;
use jtl\Connector\WooCommerce\Utility\Util;
use jtl\Connector\WooCommerce\Utility\Germanized;

class ProductGermanized extends Product
{
    protected function onProductMapped(ProductModel &$product)
    {
        parent::onProductMapped($product);

        $units = new \WC_GZD_Units();
        $wcProduct = \wc_get_product($product->getId()->getEndpoint());

        if ($wcProduct === false) {
            return;
        }

        if ($wcProduct->gzd_product->has_product_units()) {
            $plugin = \get_plugin_data(WP_PLUGIN_DIR . '/woocommerce-germanized/woocommerce-germanized.php');

            if (isset($plugin['Version']) && version_compare($plugin['Version'], '1.6.0') < 0) {
                $unitObject = $units->get_unit_object($wcProduct->gzd_product->unit);
            } else {
                $unitObject = \get_term_by('slug', $wcProduct->gzd_product->unit, 'product_unit');
            }

            $code = Germanized::getInstance()->parseUnit($unitObject->slug);

            $productQuantity = (double)$wcProduct->gzd_product->unit_product;
            $product->setMeasurementQuantity($productQuantity);
            $product->setMeasurementUnitId(new Identity($unitObject->term_id));
            $product->setMeasurementUnitCode($code);

            $product->setConsiderBasePrice(true);

            $baseQuantity = (double)$wcProduct->gzd_product->unit_base;
            if ($baseQuantity !== 0.0) {
                $product->setBasePriceDivisor($productQuantity / $baseQuantity);
            }
            $product->setBasePriceQuantity($baseQuantity);
            $product->setBasePriceUnitId(new Identity($unitObject->term_id));
            $product->setBasePriceUnitCode($code);
            $product->setBasePriceUnitName($unitObject->name);
        }
    }

    protected function onProductInserted(ProductModel &$product, array &$endpoint)
    {
        parent::onProductInserted($product, $endpoint);

        $id = $product->getId()->getEndpoint();
        $this->updateBasePriceAndUnits($product, $id);
        $this->updateDeliveryStatus($product, $id);
    }

    private function updateBasePriceAndUnits(ProductModel $product, $id)
    {
        if ($product->getConsiderBasePrice()) {
            $pd = \wc_get_price_decimals();
            \update_post_meta($id, '_unit_base', $product->getBasePriceQuantity());
            if ($product->getBasePriceDivisor() != 0) {
                $divisor = $product->getBasePriceDivisor();
                \update_post_meta($id, '_unit_price', round((float)\get_post_meta($id, '_price', true) / $divisor, $pd));
                \update_post_meta($id, '_unit_price_regular', round((float)\get_post_meta($id, '_regular_price', true) / $divisor, $pd));
            }
            $salePrice = \get_post_meta($id, '_sale_price', true);
            if (!empty($salePrice)) {
                if ($product->getBasePriceDivisor() !== 0) {
                    $unitSale = (float)$salePrice / $product->getBasePriceDivisor();
                    \update_post_meta($id, '_unit_price_sale', round($unitSale, $pd));
                    if (\get_post_meta($id, '_price', true) === $salePrice) {
                        \update_post_meta($id, '_unit_price', round($unitSale, $pd));
                    }
                }
            }
        }
        \update_post_meta($id, '_unit', $product->getBasePriceUnitName());
        if ($product->getMeasurementQuantity() !== 0) {
            \update_post_meta($id, '_unit_product', $product->getMeasurementQuantity());
        }
    }

    private function updateDeliveryStatus(ProductModel $product, $id)
    {
        foreach ($product->getI18ns() as $i18n) {
            $deliveryStatus = $i18n->getDeliveryStatus();
            if (Util::getInstance()->isWooCommerceLanguage($deliveryStatus) && !empty($deliveryStatus)) {
                $term = $this->database->queryOne(SQL::deliveryStatusByText($deliveryStatus));
                if (empty($term)) {
                    $result = \wp_insert_term($i18n->getDeliveryStatus(), 'product_delivery_time');
                    if ($result instanceof \WP_Error) {
                        WpErrorLogger::getInstance()->logError($result);
                        break;
                    }
                    $term = $result['term_id'];
                }
                $result = \wp_set_object_terms($id, (int)$term, 'product_delivery_time');
                if ($result instanceof \WP_Error) {
                    WpErrorLogger::getInstance()->logError($result);
                }
                break;
            }
        }
    }
}
