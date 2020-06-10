<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\Wpml;

use jtl\Connector\Model\Product;
use JtlWooCommerceConnector\Controllers\Product\ProductAttr;
use JtlWooCommerceConnector\Controllers\Product\ProductSpecific;
use JtlWooCommerceConnector\Controllers\Product\ProductVariation;
use JtlWooCommerceConnector\Controllers\Product\ProductVaSpeAttrHandler;
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
        POST_TYPE = 'post_product',
        POST_TYPE_VARIATION = 'post_product_variation';

    /**
     * @param int $wcProductId
     * @param string $masterProductId
     * @param Product $jtlProduct
     * @throws \Exception
     */
    public function setProductTranslations(int $wcProductId, string $masterProductId, Product $jtlProduct)
    {
        $type = empty($masterProductId) ? self::POST_TYPE : self::POST_TYPE_VARIATION;

        $trid = $this->getCurrentPlugin()->getElementTrid($wcProductId, $type);

        $masterProductTranslations = [];
        if (!empty($masterProductId)) {
            $masterProductTranslations = $this->getProductTranslationInfo($masterProductId);
        }

        $translationInfo = $this->getProductTranslationInfo($wcProductId);

        foreach ($jtlProduct->getI18ns() as $productI18n) {
            $languageCode = $this->getCurrentPlugin()->convertLanguageToWpml($productI18n->getLanguageISO());
            if ($this->getCurrentPlugin()->getDefaultLanguage() === $languageCode) {
                continue;
            }

            $translationElementId = isset($translationInfo[$languageCode]) ? $translationInfo[$languageCode]->element_id : 0;
            $masterProductId = isset($masterProductTranslations[$languageCode]) ? $masterProductTranslations[$languageCode]->element_id : 0;

            $translationElementId = $this->getCurrentPlugin()
                ->getPluginsManager()
                ->get(WooCommerce::class)
                ->getComponent(WooCommerceProduct::class)
                ->saveProduct(
                    $translationElementId,
                    $masterProductId,
                    $jtlProduct,
                    $productI18n
                );

            if (!is_null($translationElementId)) {

                $product = wc_get_product($translationElementId);
                $product->set_parent_id($masterProductId);
                $product->save();

                $this->getCurrentPlugin()->getSitepress()->set_element_language_details(
                    $translationElementId,
                    $type,
                    $trid,
                    $languageCode
                );
            }
        }
    }

    /**
     * @param int|null $limit
     * @return array
     */
    public function getProducts(int $limit = null): array
    {
        $wpdb = $this->getCurrentPlugin()->getWpDb();
        $jclp = $wpdb->prefix . 'jtl_connector_link_product';
        $translations = $wpdb->prefix . 'icl_translations';
        $defaultLanguage = $this->getCurrentPlugin()->getDefaultLanguage();

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
            AND wpmlt.element_type IN ('post_product','post_product_variation')
            AND wpmlt.language_code = '{$defaultLanguage}'
            AND wpmlt.source_language_code IS NULL
            GROUP BY p.ID
            ORDER BY p.post_type
            {$limitQuery}";

        $result = $this->getCurrentPlugin()->getPluginsManager()->getDatabase()->queryList($query);

        return is_array($result) ? $result : [];
    }

    /**
     * @param \WC_Product $wcProduct
     * @param Product $jtlProduct
     * @throws \Exception
     */
    public function getTranslations(\WC_Product $wcProduct, Product $jtlProduct)
    {
        $wcProductTranslations = $this->getProductTranslationInfo((int)$wcProduct->get_id());

        foreach ($wcProductTranslations as $wpmlLanguageCode => $wpmlTranslationInfo) {

            $wcProductTranslation = wc_get_product($wpmlTranslationInfo->element_id);
            $languageIso = $this->getCurrentPlugin()->convertLanguageToWawi($wpmlLanguageCode);

            if ($wcProductTranslation instanceof \WC_Product) {
                $i18n = $this->getCurrentPlugin()
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

    /**
     * @param \WC_Product $wcProduct
     * @return \WC_Product[]
     */
    public function getWooCommerceProductTranslations(\WC_Product $wcProduct): array
    {
        $translations = [];
        $info = $this->getProductTranslationInfo($wcProduct->get_id());
        foreach ($info as $wpmlLanguageCode => $item) {
            $translatedProduct = wc_get_product($item->element_id);
            if ($translatedProduct instanceof \WC_Product) {
                $translations[$wpmlLanguageCode] = $translatedProduct;
            }
        }

        return $translations;
    }

    /**
     * @param \WC_Product $wcProduct
     * @param string $slug
     * @return \WC_Product_Attribute|null
     */
    public function getWooCommerceProductTranslatedAttributeBySlug(
        \WC_Product $wcProduct,
        string $slug
    ): ?\WC_Product_Attribute {
        $translatedAttribute = null;
        $attributes = $wcProduct->get_attributes();

        foreach ($attributes as $attributeSlug => $attribute) {
            if ($attributeSlug === $slug) {
                $translatedAttribute = $attribute;
                break;
            }
        }

        return $translatedAttribute;
    }

    /**
     * @param \WC_Product $wcProduct
     * @param string $slug
     * @return string|null
     */
    public function getWooCommerceProductTranslatedAttributeValueBySlug(
        \WC_Product_Variation $wcProduct,
        string $slug
    ): ?string {
        $translatedAttribute = null;
        $attributes = $wcProduct->get_attributes();

        foreach ($attributes as $attributeSlug => $attribute) {
            if ($attributeSlug === $slug) {
                $translatedAttribute = $attribute;
                break;
            }
        }

        return $translatedAttribute;
    }


    /**
     * @param int $productId
     * @return array
     */
    public function getProductTranslationInfo(int $productId): array
    {
        return $this
            ->getCurrentPlugin()
            ->getComponent(WpmlTermTranslation::class)
            ->getTranslations(
                $this->getCurrentPlugin()->getElementTrid($productId, self::POST_TYPE),
                self::POST_TYPE
            );
    }
}
