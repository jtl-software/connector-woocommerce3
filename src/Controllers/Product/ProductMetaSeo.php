<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Product;

use Exception;
use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\Model\ProductI18n;
use jtl\Connector\Model\ProductI18n as ProductI18nModel;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Integrations\Plugins\YoastSeo\YoastSeo;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;

class ProductMetaSeo extends BaseController
{
    /**
     * @param $newPostId
     * @param ProductI18nModel $tmpMeta
     * @throws Exception
     */
    public function pushData($newPostId, ProductI18nModel $tmpMeta)
    {
        $yoastSeo = $this->getPluginsManager()->get(YoastSeo::class);
        if ($yoastSeo->canBeUsed()) {
            try {
                $wcProduct = \wc_get_product($newPostId);
                if (!$wcProduct instanceof \WC_Product) {
                    throw new Exception('Can´t find Product');
                }

                if ($wcProduct->get_slug() !== $tmpMeta->getUrlPath()) {
                    $tmpWcProduct = \wc_get_product($newPostId);
                    if (!$tmpWcProduct instanceof \WC_Product) {
                        throw new Exception('Can´t find Product');
                    }
                    $tmpWcProduct->set_name($tmpMeta->getUrlPath());
                }

                update_post_meta($newPostId, '_yoast_wpseo_title', $tmpMeta->getTitleTag());
                update_post_meta($newPostId, '_yoast_wpseo_metadesc', $tmpMeta->getMetaDescription());
                update_post_meta($newPostId, '_yoast_wpseo_focuskw', $tmpMeta->getMetaKeywords());

            } catch (Exception $e) {

            }
        }
    }
}
