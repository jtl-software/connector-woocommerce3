<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\Wpml;

use jtl\Connector\Core\Exception\LanguageException;
use jtl\Connector\Model\Product;
use jtl\Connector\Model\ProductI18n;
use JtlWooCommerceConnector\Controllers\Product\ProductMetaSeo;
use JtlWooCommerceConnector\Controllers\Product\ProductStockLevel;
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
     * @param int $wcBaseTranslationProductId
     * @param string $masterProductId
     * @param Product $jtlProduct
     * @throws \Exception
     */
    public function setProductTranslations(
        int $wcBaseTranslationProductId,
        string $masterProductId,
        Product $jtlProduct
    ) {
        $type = empty($masterProductId) ? self::POST_TYPE : self::POST_TYPE_VARIATION;

        $wpmlPlugin = $this->getCurrentPlugin();

        $trid = $wpmlPlugin->getElementTrid($wcBaseTranslationProductId, $type);
        $masterProductTranslations = [];
        if (!empty($masterProductId)) {
            $masterProductTranslations = $this->getProductTranslationInfo($masterProductId);
        }

        $translationInfo = $this->getProductTranslationInfo($wcBaseTranslationProductId);

        if ($type === self::POST_TYPE) {
            foreach ($jtlProduct->getI18ns() as $productI18n) {
                if ($wpmlPlugin->isDefaultLanguage($productI18n->getLanguageISO())) {
                    continue;
                }

                $languageCode = $wpmlPlugin->convertLanguageToWpml($productI18n->getLanguageISO());

                $this->saveTranslation(
                    $translationInfo,
                    $masterProductTranslations,
                    $languageCode,
                    $jtlProduct,
                    $productI18n,
                    $masterProductId,
                    $trid
                );

            }
        } else {
            foreach ($jtlProduct->getVariations() as $variation) {
                foreach ($variation->getValues() as $variationValue) {
                    foreach ($variation->getI18ns() as $variationI18n) {
                        if ($wpmlPlugin->isDefaultLanguage($variationI18n->getLanguageISO())) {
                            continue;
                        }
                        foreach ($variationValue->getI18ns() as $i18n) {
                            if ($wpmlPlugin->isDefaultLanguage($i18n->getLanguageISO()) || $variationI18n->getLanguageISO() !== $i18n->getLanguageISO()) {
                                continue;
                            }

                            $languageCode = $wpmlPlugin->convertLanguageToWpml($i18n->getLanguageISO());
                            if (!empty($languageCode)) {
                                $productI18n = $this->getDefaultTranslation(
                                    $i18n->getLanguageISO(),
                                    $i18n->getName(),
                                    ...$jtlProduct->getI18ns()
                                );

                                $this->saveTranslation(
                                    $translationInfo,
                                    $masterProductTranslations,
                                    $languageCode,
                                    $jtlProduct,
                                    $productI18n,
                                    $masterProductId,
                                    $trid
                                );
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param string $languageIso
     * @param string $defaultName
     * @param ProductI18n ...$i18ns
     * @return ProductI18n
     */
    protected function getDefaultTranslation(string $languageIso, string $defaultName, ProductI18n ...$i18ns)
    {
        $translation = null;

        foreach ($i18ns as $i18n) {
            if ($i18n->getLanguageISO() === $languageIso) {
                $translation = $i18n;
                break;
            }
        }

        if (is_null($translation)) {
            $translation = (new ProductI18n())
                ->setLanguageISO($languageIso)
                ->setName($defaultName);
        }

        return $translation;
    }

    /**
     * @param $translationInfo
     * @param $masterProductTranslations
     * @param $languageCode
     * @param $jtlProduct
     * @param $productI18n
     * @param $masterProductId
     * @param $trid
     * @throws LanguageException
     */
    protected function saveTranslation(
        $translationInfo,
        $masterProductTranslations,
        $languageCode,
        $jtlProduct,
        $productI18n,
        $masterProductId,
        $trid
    ) {
        $wpmlPlugin = $this->getCurrentPlugin();
        $productController = (new \JtlWooCommerceConnector\Controllers\Product\Product());
        $type = empty($masterProductId) ? self::POST_TYPE : self::POST_TYPE_VARIATION;

        $wcProductId = isset($translationInfo[$languageCode]) ? $translationInfo[$languageCode]->element_id : 0;
        $masterProductId = isset($masterProductTranslations[$languageCode]) ? $masterProductTranslations[$languageCode]->element_id : 0;

        if($type === self::POST_TYPE_VARIATION && $masterProductId === 0){
            return;
        }

        $wcProductId = $wpmlPlugin
            ->getPluginsManager()
            ->get(WooCommerce::class)
            ->getComponent(WooCommerceProduct::class)
            ->saveProduct(
                $wcProductId,
                $masterProductId,
                $jtlProduct,
                $productI18n
            );

        if (!is_null($wcProductId)) {
            $wcProduct = wc_get_product($wcProductId);
            $wcProduct->set_parent_id($masterProductId);
            $wcProduct->save();

            $productStockLevel = new ProductStockLevel();

            switch ($type) {
                case self::POST_TYPE_VARIATION:
                    remove_filter('content_save_pre', 'wp_filter_post_kses');
                    remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
                    $productController->updateVariationCombinationChild($wcProduct, $jtlProduct, $productI18n);
                    add_filter('content_save_pre', 'wp_filter_post_kses');
                    add_filter('content_filtered_save_pre', 'wp_filter_post_kses');

                    $wpmlPlugin
                        ->getComponent(WpmlProductVariation::class)
                        ->setChildTranslation($wcProduct, $jtlProduct->getVariations(), $languageCode);

                    $productStockLevel->pushDataChild($wcProduct, $jtlProduct);
                    break;
                case self::POST_TYPE:
                    (new ProductVaSpeAttrHandler)->pushDataNew($jtlProduct, $wcProduct, $productI18n);
                    $productStockLevel->pushDataParent($wcProduct, $jtlProduct);
                    break;
            }

            $productController->updateProductType($jtlProduct, $wcProduct);

            (new ProductMetaSeo)->pushData($wcProductId, $productI18n);

            $wpmlPlugin->getSitepress()->set_element_language_details(
                $wcProductId,
                $type,
                $trid,
                $languageCode
            );
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
     * @return array
     * @throws \Exception
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
     * @param string $elementType
     * @return array
     * @throws \Exception
     */
    public function getProductTranslationInfo(int $productId, $elementType = self::POST_TYPE): array
    {
        return $this
            ->getCurrentPlugin()
            ->getComponent(WpmlTermTranslation::class)
            ->getTranslations(
                $this->getCurrentPlugin()->getElementTrid($productId, $elementType),
                $elementType
            );
    }

    /**
     * @param \WC_Product $wcProduct
     * @return bool
     * @throws \Exception
     */
    public function deleteTranslations(\WC_Product $wcProduct): bool
    {
        $elementType = $wcProduct->get_type() === 'variation' ? WpmlProduct::POST_TYPE_VARIATION : WpmlProduct::POST_TYPE;
        $productTranslationInfo = $this->getProductTranslationInfo($wcProduct->get_id(), $elementType);
        /** @var Wpml $wpml */
        $wpml = $this->getCurrentPlugin();

        foreach ($productTranslationInfo as $wpmlLanguageCode => $translationInfo) {
            \wp_delete_post($translationInfo->element_id, true);
            \wc_delete_product_transients($translationInfo->element_id);

            $wpml->getSitepress()->delete_element_translation($translationInfo->trid, $elementType, $wpmlLanguageCode);
        }
        return true;
    }
}
