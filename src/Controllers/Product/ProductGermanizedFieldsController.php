<?php

namespace JtlWooCommerceConnector\Controllers\Product;

use http\Exception\InvalidArgumentException;
use Jtl\Connector\Core\Exception\TranslatableAttributeException;
use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\Product as ProductModel;
use Jtl\Connector\Core\Model\TranslatableAttribute;
use Jtl\Connector\Core\Model\TranslatableAttributeI18n as ProductAttrI18nModel;
use JtlWooCommerceConnector\Controllers\AbstractBaseController;
use JtlWooCommerceConnector\Utilities\Germanized;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;
use WC_Product;

/**
 * Class ProductGermanizedFields
 *
 * @package JtlWooCommerceConnector\Controllers\Product
 */
class ProductGermanizedFieldsController extends AbstractBaseController
{
    /**
     * @param ProductModel $product
     * @param WC_Product $wcProduct
     * @return void
     * @throws InvalidArgumentException
     */
    public function pullData(ProductModel &$product, \WC_Product $wcProduct): void
    {
        $this->setGermanizedAttributes($product, $wcProduct);
    }

    /**
     * @param ProductModel $product
     * @param WC_Product $wcProduct
     * @return void
     * @throws \InvalidArgumentException
     */
    private function setGermanizedAttributes(ProductModel &$product, \WC_Product $wcProduct): void
    {
        $units           = new \WC_GZD_Units();
        $germanizedUtils = (new Germanized());
        if ($germanizedUtils->hasUnitProduct($wcProduct)) {
            $plugin = \get_plugin_data(\WP_PLUGIN_DIR . '/woocommerce-germanized/woocommerce-germanized.php');

            if (isset($plugin['Version']) && \version_compare($plugin['Version'], '1.6.0') < 0) {
                $unitObject = $units->get_unit_object($wcProduct->gzd_product->unit);
            } else {
                $unit       = $germanizedUtils->getUnit($wcProduct);
                $unitObject = \get_term_by('slug', $unit, 'product_unit');
            }

            $code            = $germanizedUtils->parseUnit($unitObject->slug);
            $productQuantity = (double)$germanizedUtils->getUnitProduct($wcProduct);
            $product->setMeasurementQuantity($productQuantity);
            $product->setMeasurementUnitId(new Identity($unitObject->term_id));
            $product->setMeasurementUnitCode($code);

            $product->setConsiderBasePrice(true);
            $baseQuantity = (double)$germanizedUtils->getUnitBase($wcProduct);

            if ($baseQuantity !== 0.0) {
                $product->setBasePriceDivisor($productQuantity / $baseQuantity);
            }

            $product->setBasePriceQuantity($baseQuantity);
            $product->setBasePriceUnitId(new Identity($unitObject->term_id));
            $product->setBasePriceUnitCode($code);
            $product->setBasePriceUnitName($unitObject->name);
        }
    }

    /**
     * @param ProductModel $product
     * @return void
     */
    public function pushData(ProductModel $product): void
    {
        $this->updateGermanizedAttributes($product);
    }

    /**
     * @param ProductModel $product
     * @return void
     */
    private function updateGermanizedAttributes(ProductModel &$product): void
    {
        $id = $product->getId()->getEndpoint();

        \update_post_meta($id, '_ts_mpn', (string)$product->getManufacturerNumber());

        $this->updateGermanizedBasePriceAndUnits($product, $id);
    }

    /**
     * @param ProductModel $product
     * @param $id
     * @return void
     */
    private function updateGermanizedBasePriceAndUnits(ProductModel $product, $id): void
    {
        if ($product->getConsiderBasePrice()) {
            $pd = Util::getPriceDecimals();

            \update_post_meta($id, '_unit_base', $product->getBasePriceQuantity());

            if ($product->getBasePriceDivisor() != 0) {
                $divisor      = $product->getBasePriceDivisor();
                $currentPrice = (float)\get_post_meta($id, '_price', true);
                $basePrice    = \round($currentPrice / $divisor, $pd);

                \update_post_meta($id, '_unit_price', (float)$basePrice);
                \update_post_meta($id, '_unit_price_regular', (float)$basePrice);
            }

            $salePrice = \get_post_meta($id, '_sale_price', true);

            if (! empty($salePrice)) {
                if ($product->getBasePriceDivisor() !== 0.0) {
                    $unitSale = \round((float)$salePrice / $product->getBasePriceDivisor(), $pd);

                    \update_post_meta($id, '_unit_price_sale', (float)$unitSale);

                    if (\get_post_meta($id, '_price', true) === $salePrice) {
                        \update_post_meta($id, '_unit_price', (float)$unitSale);
                    }
                }
            }

            \update_post_meta($id, '_unit', $product->getBasePriceUnitName());

            if ($product->getMeasurementQuantity() !== 0.0) {
                \update_post_meta($id, '_unit_product', $product->getMeasurementQuantity());
            }
        } else {
            \delete_post_meta($id, '_unit_product');
            \delete_post_meta($id, '_unit_price');
            \delete_post_meta($id, '_unit_price_sale');
            \delete_post_meta($id, '_unit_price_regular');
            \delete_post_meta($id, '_unit_base');
        }
    }
}
