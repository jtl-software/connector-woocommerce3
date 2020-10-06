<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Product;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Product as ProductModel;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Utilities\Germanized;
use JtlWooCommerceConnector\Utilities\Util;

/**
 * Class ProductGermanizedFields
 *
 * @package JtlWooCommerceConnector\Controllers\Product
 */
class ProductGermanizedFields extends BaseController
{
    /**
     * @param ProductModel $product
     * @param \WC_Product  $wcProduct
     */
    public function pullData(ProductModel &$product, \WC_Product $wcProduct)
    {
        $this->setGermanizedAttributes($product, $wcProduct);
    }
    
    /**
     * @param ProductModel $product
     * @param \WC_Product  $wcProduct
     */
    private function setGermanizedAttributes(ProductModel &$product, \WC_Product $wcProduct)
    {
        $units = new \WC_GZD_Units();
        $germanizedUtils = Germanized::getInstance();
        if ($germanizedUtils->hasUnitProduct($wcProduct)) {
            $plugin = \get_plugin_data(WP_PLUGIN_DIR . '/woocommerce-germanized/woocommerce-germanized.php');
            
            if (isset($plugin['Version']) && version_compare($plugin['Version'], '1.6.0') < 0) {
                $unitObject = $units->get_unit_object($wcProduct->gzd_product->unit);
            } else {
                $unit = $germanizedUtils->getUnit($wcProduct);
                $unitObject = \get_term_by('slug', $unit, 'product_unit');
            }
            
            $code = $germanizedUtils->parseUnit($unitObject->slug);
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
     */
    public function pushData(ProductModel $product)
    {
        $this->updateGermanizedAttributes($product);
    }
    
    /**
     * @param ProductModel $product
     */
    private function updateGermanizedAttributes(ProductModel &$product)
    {
        $id = $product->getId()->getEndpoint();
        $this->updateGermanizedBasePriceAndUnits($product, $id);
    }
    
    /**
     * @param ProductModel $product
     * @param              $id
     */
    private function updateGermanizedBasePriceAndUnits(ProductModel $product, $id)
    {
        if ($product->getConsiderBasePrice()) {
            $pd = Util::getPriceDecimals();

            \update_post_meta($id, '_unit_base', $product->getBasePriceQuantity());
            
            if ($product->getBasePriceDivisor() != 0) {
                $divisor      = $product->getBasePriceDivisor();
                $currentPrice = (float)\get_post_meta($id, '_price', true);
                $basePrice    = round($currentPrice / $divisor,  $pd);
                
                \update_post_meta($id, '_unit_price', (float)$basePrice);
                \update_post_meta($id, '_unit_price_regular', (float)$basePrice);
            }
            
            $salePrice = \get_post_meta($id, '_sale_price', true);
            
            if ( ! empty($salePrice)) {
                if ($product->getBasePriceDivisor() !== 0) {
                    $unitSale = round((float)$salePrice / $product->getBasePriceDivisor(), $pd);
                    
                    \update_post_meta($id, '_unit_price_sale', (float)$unitSale);
                    
                    if (\get_post_meta($id, '_price', true) === $salePrice) {
                        \update_post_meta($id, '_unit_price', (float)$unitSale);
                    }
                }
            }
            
            
            \update_post_meta($id, '_unit', $product->getBasePriceUnitName());
            
            if ($product->getMeasurementQuantity() !== 0) {
                \update_post_meta($id, '_unit_product', $product->getMeasurementQuantity());
            }
        }else{
            \delete_post_meta($id, '_unit_product');
            \delete_post_meta($id, '_unit_price');
            \delete_post_meta($id, '_unit_price_sale');
            \delete_post_meta($id, '_unit_price_regular');
            \delete_post_meta($id, '_unit_base');
        }
    }
}
