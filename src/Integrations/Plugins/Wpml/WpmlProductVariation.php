<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\Wpml;

use jtl\Connector\Model\ProductVariation;
use jtl\Connector\Model\ProductVariationI18n as ProductVariationI18nModel;
use jtl\Connector\Model\ProductVariationValue;
use jtl\Connector\Model\ProductVariationValueI18n as ProductVariationValueI18nModel;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractComponent;

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