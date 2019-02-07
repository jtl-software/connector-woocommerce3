<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Product;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Product as ProductModel;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;

class ProductManufacturer extends BaseController
{
    /**
     * @param ProductModel $product
     * @param \WC_Product  $wcProduct
     */
    public function pushData(ProductModel $product, \WC_Product $wcProduct)
    {
        $productId = $product->getId()->getEndpoint();
        
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_PERFECT_WOO_BRANDS)) {
            $manufacturerId = $product->getManufacturerId()->getEndpoint();
            $this->removeManufacturerTerm($productId);
            $term = get_term_by('id', $manufacturerId, 'pwb-brand');
            if ($manufacturerId === '') {
                return;
            }
            if ($term instanceof \WP_Term) {
                wp_set_object_terms($productId, $term->term_id, $term->taxonomy, true);
            }
            
        } else {
            $this->removeManufacturerTerm($productId);
        }
    }
    
    
    /**
     * @param string $productId
     */
    private function removeManufacturerTerm(string $productId)
    {
        $terms = wp_get_object_terms($productId, 'pwb-brand');
        if (count($terms) > 0) {
            /** @var \WP_Term $term */
            foreach ($terms as $key => $term) {
                if ($term instanceof \WP_Term) {
                    wp_remove_object_terms($productId, $term->term_id, 'pwb-brand');
                }
            }
        }
    }
    
    public function pullData(\WC_Product $product, ProductModel $model)
    {
        $productId      = $model->getId()->getEndpoint();
        $manufacturerId = [];
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_PERFECT_WOO_BRANDS)) {
            $terms = wp_get_object_terms($productId, 'pwb-brand');
            
            if (count($terms) > 0) {
                /** @var \WP_Term $term */
                $term           = $terms[0];
                $manufacturerId = (new Identity)->setEndpoint($term->term_id);
            }
        }
        return $manufacturerId;
    }
    
}
