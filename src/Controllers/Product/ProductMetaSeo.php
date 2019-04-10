<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Product;

use Exception;
use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\Model\ProductI18n as ProductI18nModel;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;

class ProductMetaSeo extends BaseController
{
    /**
     * @param ProductModel     $product
     * @param                  $newPostId
     * @param ProductI18nModel $tmpMeta
     */
    public function pushData(ProductModel $product, $newPostId, ProductI18nModel $tmpMeta)
    {
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO_PREMIUM)) {
            $productId = $product->getId()->getEndpoint();
            try {
                $wcProduct = \wc_get_product($newPostId);
                if (!$wcProduct instanceof \WC_Product) {
                    throw new Exception('Can´t find Product');
                }
                
                /* Debug
                            $var1 = $wcProduct->get_slug();
                            $var2 = $tmpMeta->getUrlPath();
                */
                if ($wcProduct->get_slug() !== $tmpMeta->getUrlPath()) {
                    $tmpWcProduct = \wc_get_product((int)$product->getId()->getEndpoint());
                    if (!$tmpWcProduct instanceof \WC_Product) {
                        throw new Exception('Can´t find Product');
                    }
                    $tmpWcProduct->set_name($tmpMeta->getUrlPath());
                }
                
                $updated_title = update_post_meta($productId, '_yoast_wpseo_title', $tmpMeta->getTitleTag());
                $updated_desc = update_post_meta($productId, '_yoast_wpseo_metadesc', $tmpMeta->getMetaDescription());
                $updated_kw = update_post_meta($productId, '_yoast_wpseo_focuskw', $tmpMeta->getMetaKeywords());
                
            } catch (Exception $e) {
            
            }
        }
    }
    
    /**
     * @param \WC_Product  $product
     * @param ProductModel $model
     *
     * @return array|null
     */
    public function pullData(\WC_Product $product, ProductModel $model)
    {
        $productId = $model->getId()->getEndpoint();
        $values = null;
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO_PREMIUM)) {
            $values = [
                'titleTag' => get_post_meta($productId, '_yoast_wpseo_title'),
                'metaDesc' => get_post_meta($productId, '_yoast_wpseo_metadesc'),
                'keywords' => get_post_meta($productId, '_yoast_wpseo_focuskw'),
                'permlink' => $product->get_slug(),
                //$product->get_permalink()
            ];
            
            foreach ($values as $key => $value) {
                if (strcmp($key, 'permalink') === 0) {
                    continue;
                }
                if (is_array($value) && count($value) > 0) {
                    $values[$key] = $value[0];
                }
                
            }
        }
        
        return $values;
    }
}
