<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\Wpml;

use jtl\Connector\Model\Product;
use jtl\Connector\Model\ProductVariation;
use jtl\Connector\Model\ProductVariationI18n;
use jtl\Connector\Model\ProductVariationI18n as ProductVariationI18nModel;
use jtl\Connector\Model\ProductVariationValue;
use jtl\Connector\Model\ProductVariationValueI18n as ProductVariationValueI18nModel;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractComponent;
use JtlWooCommerceConnector\Utilities\Util;

/**
 * Class WpmlProductVariation
 * @package JtlWooCommerceConnector\Integrations\Plugins\Wpml
 */
class WpmlProductVariation extends AbstractComponent
{
    /**
     * @param \WC_Product $wcProduct
     * @param string $wcAttributeSlug
     * @param ProductVariation $productVariation
     */
    public function getTranslations(
        \WC_Product $wcProduct,
        string $wcAttributeSlug,
        ProductVariation $productVariation
    ) {
        $translatedProducts = $this->getCurrentPlugin()->getComponent(WpmlProduct::class)->getWooCommerceProductTranslations($wcProduct);
        foreach ($translatedProducts as $wpmlLanguageCode => $translatedProduct) {
            $translatedAttribute = $this->getCurrentPlugin()
                ->getComponent(WpmlProduct::class)
                ->getWooCommerceProductTranslatedAttributeBySlug($translatedProduct, $wcAttributeSlug);

            if (!is_null($translatedAttribute)) {
                $translatedLabels = get_post_meta($translatedProduct->get_id(), 'attr_label_translations', true);
                $productVariation->addI18n((new ProductVariationI18nModel())
                    ->setProductVariationId($productVariation->getId())
                    ->setName(
                        (is_array($translatedLabels) && $translatedLabels[$wpmlLanguageCode][$wcAttributeSlug])
                            ? $translatedLabels[$wpmlLanguageCode][$wcAttributeSlug]
                            : \wc_attribute_label($translatedAttribute->get_name())
                    )
                    ->setLanguageISO($this->getCurrentPlugin()->convertLanguageToWawi($wpmlLanguageCode)));
            }
        }
    }

    /**
     * @param int $productId
     * @param Product $product
     */
    public function updateMeta(int $productId, Product $product)
    {
        $wcProduct = wc_get_product($productId);
        if ($wcProduct instanceof \WC_Product) {
            $translations = $this->getCurrentPlugin()->getComponent(WpmlProduct::class)->getWooCommerceProductTranslations($wcProduct);

            foreach ($translations as $wpmlLanguageCode => $translation) {
                if ($translation instanceof \WC_Product) {
                    foreach ($product->getI18ns() as $productI18n) {
                        if ($productI18n->getLanguageISO() === $this->getCurrentPlugin()->convertLanguageToWawi($wpmlLanguageCode)) {
                            \update_post_meta($translation->get_id(), '_variation_description',
                                $productI18n->getDescription());
                            \update_post_meta($translation->get_id(), '_mini_dec', $productI18n->getShortDescription());
                        }
                    }
                }
            }
        }
    }

    /**
     * @param \WC_Product $wcProduct
     * @param $pushedVariations
     * @param $languageCode
     * @return array
     */
    public function setChildTranslation(\WC_Product $wcProduct, $pushedVariations, $languageCode)
    {
        $updatedAttributeKeys = [];
        $languageIso = $this->getCurrentPlugin()->convertLanguageToWawi($languageCode);
        if ($wcProduct instanceof \WC_Product) {
            foreach ($pushedVariations as $variation) {
                foreach ($variation->getValues() as $variationValue) {
                    foreach ($variation->getI18ns() as $variationI18n) {
                        if ($this->getCurrentPlugin()->isDefaultLanguage($variationI18n->getLanguageISO()) === false) {
                            continue;
                        }

                        foreach ($variationValue->getI18ns() as $i18n) {
                            if ($languageIso !== $i18n->getLanguageISO()) {
                                continue;
                            }

                            $metaKey = Util::createVariantTaxonomyName($variationI18n->getName());
                            $updatedAttributeKeys[] = $metaKey;
                            \update_post_meta($wcProduct->get_id(), $metaKey, wc_sanitize_taxonomy_name($i18n->getName()));
                        }
                    }
                }
            }
        }

        return $updatedAttributeKeys;
    }

    /**
     * @param ProductVariationValue $productVariationValue
     * @param \WP_Term $term
     */
    public function getValueTranslations(ProductVariationValue $productVariationValue, \WP_Term $term)
    {
        $termTranslations = $this->getCurrentPlugin()->getComponent(WpmlTermTranslation::class);
        $elementType = $term->taxonomy;
        $trid = $this->getCurrentPlugin()->getElementTrid($term->term_taxonomy_id, 'tax_' . $elementType);

        $translations = $termTranslations->getTranslations($trid, $elementType);
        foreach ($translations as $wpmlLanguageCode => $translation) {
            $translatedTerm = $termTranslations->getTranslatedTerm($translation->element_id, $elementType);

            if (!empty($translatedTerm)) {
                $productVariationValue->addI18n(
                    (new ProductVariationValueI18nModel())
                        ->setProductVariationValueId($productVariationValue->getId())
                        ->setName($translatedTerm['name'])
                        ->setLanguageISO($this->getCurrentPlugin()->convertLanguageToWawi($wpmlLanguageCode))
                );
            }
        }
    }

    /**
     * @param \WC_Product $product
     * @param string $slug
     * @param ProductVariationValue $variationValue
     * @param int $sort
     */
    public function getOptionTranslations(
        \WC_Product $product,
        string $slug,
        ProductVariationValue $variationValue,
        int $sort
    ) {
        $translatedProducts = $this->getCurrentPlugin()->getComponent(WpmlProduct::class)->getWooCommerceProductTranslations($product);
        foreach ($translatedProducts as $wpmlLanguageCode => $translatedProduct) {
            $translatedAttribute = $this->getCurrentPlugin()
                ->getComponent(WpmlProduct::class)
                ->getWooCommerceProductTranslatedAttributeBySlug($translatedProduct, $slug);

            if (!is_null($translatedAttribute)) {
                $translatedOptions = $translatedAttribute->get_options();
                $variationValue->addI18n((new ProductVariationValueI18nModel())
                    ->setProductVariationValueId($variationValue->getId())
                    ->setName($translatedOptions[$sort])
                    ->setLanguageISO($this->getCurrentPlugin()->convertLanguageToWawi($wpmlLanguageCode)));
            }
        }
    }
}