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

class ProductGermanizedFields extends BaseController
{
    public function pullData(ProductModel &$product, \WC_Product $wcProduct)
    {
        $this->setGermanizedAttributes($product, $wcProduct);
    }
    
    private function setGermanizedAttributes(ProductModel &$product, \WC_Product $wcProduct)
    {
        $units = new \WC_GZD_Units();
        
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
    
    public function pushData(ProductModel $product)
    {
        $this->updateGermanizedAttributes($product);
    }
    
    private function updateGermanizedAttributes(ProductModel &$product)
    {
        $id = $product->getId()->getEndpoint();
        $this->updateGermanizedBasePriceAndUnits($product, $id);
    }
    
    private function updateGermanizedBasePriceAndUnits(ProductModel $product, $id)
    {
        if ($product->getConsiderBasePrice()) {
            $pd = \wc_get_price_decimals();
            \update_post_meta($id, '_unit_base', $product->getBasePriceQuantity());
            
            if ($product->getBasePriceDivisor() != 0) {
                $divisor = $product->getBasePriceDivisor();
                \update_post_meta($id, '_unit_price',
                    round((float)\get_post_meta($id, '_price', true) / $divisor, $pd));
                \update_post_meta($id, '_unit_price_regular',
                    round((float)\get_post_meta($id, '_regular_price', true) / $divisor, $pd));
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
}
