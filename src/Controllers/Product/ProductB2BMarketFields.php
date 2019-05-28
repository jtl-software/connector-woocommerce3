<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Product;

use jtl\Connector\Model\Product as ProductModel;
use JtlWooCommerceConnector\Controllers\BaseController;

class ProductB2BMarketFields extends BaseController
{
    /**
     * @param ProductModel $product
     * @param \WC_Product  $wcProduct
     */
    public function pullData(ProductModel &$product, \WC_Product $wcProduct)
    {
        $this->setRRPProperty($product, $wcProduct);
    }
    
    /**
     * @param ProductModel $product
     * @param \WC_Product  $wcProduct
     */
    private function setRRPProperty(ProductModel &$product, \WC_Product $wcProduct)
    {
        $rrp = get_post_meta($wcProduct->get_id(), 'bm_rrp', true);
        if ($rrp !== '' && !is_null($rrp) && !empty($rrp)) {
            $product->setRecommendedRetailPrice((float)$rrp);
        }
    }
    
    /**
     * @param ProductModel $product
     */
    public function pushData(ProductModel $product)
    {
        $this->updateRRP($product);
    }
    
    /**
     * @param ProductModel $product
     */
    private function updateRRP(ProductModel $product)
    {
        $wcProduct = \wc_get_product($product->getId()->getEndpoint());
        $rrp = $product->getRecommendedRetailPrice();
        $oldValue = \get_post_meta($wcProduct->get_id(), 'bm_rrp', true);
        if ($rrp !== 0) {
            if ($rrp !== $oldValue) {
                if (!$product->getMasterProductId()->getHost() === 0) {
                    $vKey = sprintf('bm_%s_rrp', $wcProduct->get_id());
                    \update_post_meta(
                        $wcProduct->get_parent_id(),
                        $vKey,
                        $rrp,
                        \get_post_meta($wcProduct->get_parent_id(), $vKey, true)
                    );
                }
                \update_post_meta(
                    $wcProduct->get_id(),
                    'bm_rrp',
                    $rrp,
                    \get_post_meta($wcProduct->get_id(), 'bm_rrp', true)
                );
            }
        } else {
            if (!$product->getMasterProductId()->getHost() === 0) {
                $vKey = sprintf('bm_%s_rrp', $wcProduct->get_id());
                \delete_post_meta($wcProduct->get_parent_id(), $vKey);
            }
            \delete_post_meta($wcProduct->get_id(), 'bm_rrp');
        }
    }
}
