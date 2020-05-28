<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\Wpml;

use jtl\Connector\Core\Utilities\Language;
use jtl\Connector\Model\Product;
use jtl\Connector\Model\ProductI18n;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractComponent;
use JtlWooCommerceConnector\Integrations\Plugins\WooCommerce\WooCommerce;
use JtlWooCommerceConnector\Integrations\Plugins\WooCommerce\WooCommerceProduct;

/**
 * Class WpmlProduct
 * @package JtlWooCommerceConnector\Integrations\Plugins\Wpml
 */
class WpmlProduct extends AbstractComponent
{
    public const
        POST_TYPE = 'post_product';

    /**
     * @param int $limit
     * @return array
     */
    public function getProducts(int $limit): array
    {
        $wpdb = $this->getPlugin()->getWpDb();
        $jclp = $wpdb->prefix . 'jtl_connector_link_product';
        $translations = $wpdb->prefix . 'icl_translations';
        $defaultLanguage = $this->getPlugin()->getDefaultLanguage();

        $limitQuery = is_null($limit) ? '' : 'LIMIT ' . $limit;
        $query = "SELECT p.ID
            FROM {$wpdb->posts} p
            LEFT JOIN {$jclp} l ON p.ID = l.endpoint_id
            LEFT JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            LEFT JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
            LEFT JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
            LEFT JOIN {$translations} wpmlt ON p.ID = wpmlt.element_id
            WHERE l.host_id IS NULL
            AND (
                (p.post_type = 'product' AND (p.post_parent IS NULL OR p.post_parent = 0) )
                OR (
                    p.post_type = 'product_variation' AND p.post_parent IN
                    (
                        SELECT p2.ID FROM {$wpdb->posts} p2
                        WHERE p2.post_type = 'product'
                        AND p2.post_status
                        IN ('draft', 'future', 'publish', 'inherit', 'private')
                    )
                )
            )
            AND p.post_status IN ('draft', 'future', 'publish', 'inherit', 'private')
            AND wpmlt.element_type = 'post_product'
            AND wpmlt.language_code = '{$defaultLanguage}'
            AND wpmlt.source_language_code IS NULL
            GROUP BY p.ID
            ORDER BY p.post_type
            {$limitQuery}";

        $result = $this->getPlugin()->getPluginsManager()->getDatabase()->queryList($query);

        return is_array($result) ? $result : [];
    }

    /**
     * @param \WC_Product $wcProduct
     * @param Product $jtlProduct
     * @throws \Exception
     */
    public function getTranslations(\WC_Product $wcProduct, Product $jtlProduct)
    {
        $trid = (int)$this->getPlugin()->getElementTrid((int)$wcProduct->get_id(),
            self::POST_TYPE);

        $wcTranslations = $this
            ->getPlugin()
            ->getComponent(WpmlTermTranslation::class)
            ->getTranslations($trid, self::POST_TYPE);

        foreach ($wcTranslations as $languageCode => $wcTranslation) {

            $wcProductTranslation = wc_get_product($wcTranslation->element_id);
            $languageIso = $this->getPlugin()->convertLanguageToWawi($languageCode);

            if ($wcProductTranslation instanceof \WC_Product) {
                $i18n = $this->getPlugin()
                    ->getPluginsManager()
                    ->get(WooCommerce::class)
                    ->getComponent(WooCommerceProduct::class)
                    ->getI18ns(
                        $wcProductTranslation,
                        $jtlProduct,
                        $languageIso
                    );
                $jtlProduct->addI18n($i18n);
            }
        }
    }
}
