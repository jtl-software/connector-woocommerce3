<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Integrations\Plugins\Wpml;

use Exception;
use InvalidArgumentException;
use Jtl\Connector\Core\Model\Product;
use Jtl\Connector\Core\Model\ProductVariation;
use Jtl\Connector\Core\Model\ProductVariationI18n as ProductVariationI18nModel;
use Jtl\Connector\Core\Model\ProductVariationValue;
use Jtl\Connector\Core\Model\ProductVariationValueI18n as ProductVariationValueI18nModel;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractComponent;
use JtlWooCommerceConnector\Utilities\Util;
use WC_Product;

/**
 * Class WpmlProductVariation
 *
 * @package JtlWooCommerceConnector\Integrations\Plugins\Wpml
 */
class WpmlProductVariation extends AbstractComponent
{
    /**
     * @param WC_Product       $wcProduct
     * @param string           $wcAttributeSlug
     * @param ProductVariation $productVariation
     * @return void
     * @throws Exception
     */
    public function getTranslations(
        WC_Product $wcProduct,
        string $wcAttributeSlug,
        ProductVariation $productVariation
    ): void {
        /** @var Wpml $wpmlPlugin */
        $wpmlPlugin = $this->getCurrentPlugin();

        /** @var WpmlProduct $wpmlProduct */
        $wpmlProduct        = $wpmlPlugin->getComponent(WpmlProduct::class);
        $translatedProducts = $wpmlProduct->getWooCommerceProductTranslations($wcProduct);

        foreach ($translatedProducts as $wpmlLanguageCode => $translatedProduct) {
            $translatedAttribute = $wpmlProduct
                ->getWooCommerceProductTranslatedAttributeBySlug($translatedProduct, $wcAttributeSlug);

            if (!\is_null($translatedAttribute)) {
                $translatedLabels = \get_post_meta($translatedProduct->get_id(), 'attr_label_translations', true);
                $productVariation->addI18n((new ProductVariationI18nModel())
                    ->setName(
                        (\is_array($translatedLabels) && $translatedLabels[$wpmlLanguageCode][$wcAttributeSlug])
                            ? $translatedLabels[$wpmlLanguageCode][$wcAttributeSlug]
                            : \wc_attribute_label($translatedAttribute->get_name())
                    )
                    ->setLanguageISO($wpmlPlugin->convertLanguageToWawi((string)$wpmlLanguageCode)));
            }
        }
    }

    /**
     * @param int     $productId
     * @param Product $product
     * @return void
     * @throws Exception
     */
    public function updateMeta(int $productId, Product $product): void
    {
        $wcProduct = \wc_get_product($productId);
        if ($wcProduct instanceof WC_Product) {
            /** @var Wpml $wpmlPlugin */
            $wpmlPlugin = $this->getCurrentPlugin();

            /** @var WpmlProduct $wpmlProduct */
            $wpmlProduct  = $wpmlPlugin->getComponent(WpmlProduct::class);
            $translations = $wpmlProduct->getWooCommerceProductTranslations($wcProduct);

            foreach ($translations as $wpmlLanguageCode => $translation) {
                if ($translation instanceof WC_Product) {
                    foreach ($product->getI18ns() as $productI18n) {
                        if (
                            $productI18n->getLanguageISO()
                            === $wpmlPlugin->convertLanguageToWawi((string)$wpmlLanguageCode)
                        ) {
                            \update_post_meta(
                                $translation->get_id(),
                                '_variation_description',
                                $productI18n->getDescription()
                            );
                            \update_post_meta($translation->get_id(), '_mini_dec', $productI18n->getShortDescription());
                        }
                    }
                }
            }
        }
    }

    /**
     * @param WC_Product         $wcProduct
     * @param ProductVariation[] $pushedVariations
     * @param string             $languageCode
     * @return string[]
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function setChildTranslation(WC_Product $wcProduct, array $pushedVariations, string $languageCode): array
    {
        /** @var Wpml $wpmlPlugin */
        $wpmlPlugin  = $this->getCurrentPlugin();
        $languageIso = $wpmlPlugin->convertLanguageToWawi($languageCode);

        $updatedAttributeKeys = [];

        foreach ($pushedVariations as $variation) {
            foreach ($variation->getValues() as $variationValue) {
                foreach ($variation->getI18ns() as $variationI18n) {
                    if ($wpmlPlugin->isDefaultLanguage($variationI18n->getLanguageISO())) {
                        continue;
                    }

                    foreach ($variationValue->getI18ns() as $i18n) {
                        if ($languageIso !== $i18n->getLanguageISO()) {
                            continue;
                        }

                        $metaKey                = (new Util($this->getPluginsManager()->getDatabase()))
                            ->createVariantTaxonomyName($variationI18n->getName());
                        $updatedAttributeKeys[] = $metaKey;
                        \update_post_meta(
                            $wcProduct->get_id(),
                            $metaKey,
                            \wc_sanitize_taxonomy_name($i18n->getName())
                        );
                    }
                }
            }
        }

        return $updatedAttributeKeys;
    }

    /**
     * @param ProductVariationValue $productVariationValue
     * @param \WP_Term              $term
     * @return void
     * @throws Exception
     */
    public function getValueTranslations(ProductVariationValue $productVariationValue, \WP_Term $term): void
    {
        /** @var Wpml $wpmlPlugin */
        $wpmlPlugin = $this->getCurrentPlugin();

        /** @var WpmlTermTranslation $termTranslations */
        $termTranslations = $wpmlPlugin->getComponent(WpmlTermTranslation::class);
        $elementType      = $term->taxonomy;
        $trid             = $wpmlPlugin->getElementTrid($term->term_taxonomy_id, 'tax_' . $elementType);

        $translations = $termTranslations->getTranslations((int)$trid, $elementType);
        foreach ($translations as $wpmlLanguageCode => $translation) {
            $translatedTerm = $termTranslations->getTranslatedTerm($translation->element_id, $elementType);

            if (!empty($translatedTerm)) {
                $productVariationValue->addI18n(
                    (new ProductVariationValueI18nModel())
                        ->setName($translatedTerm['name'])
                        ->setLanguageISO($wpmlPlugin->convertLanguageToWawi($wpmlLanguageCode))
                );
            }
        }
    }

    /**
     * @param WC_Product            $product
     * @param string                $slug
     * @param ProductVariationValue $variationValue
     * @param int                   $sort
     * @return void
     * @throws Exception
     */
    public function getOptionTranslations(
        WC_Product $product,
        string $slug,
        ProductVariationValue $variationValue,
        int $sort
    ): void {
        /** @var Wpml $wpmlPlugin */
        $wpmlPlugin = $this->getCurrentPlugin();

        /** @var WpmlProduct $wpmlProduct */
        $wpmlProduct        = $wpmlPlugin->getComponent(WpmlProduct::class);
        $translatedProducts = $wpmlProduct->getWooCommerceProductTranslations($product);

        foreach ($translatedProducts as $wpmlLanguageCode => $translatedProduct) {
            $translatedAttribute = $wpmlProduct
                ->getWooCommerceProductTranslatedAttributeBySlug($translatedProduct, $slug);

            if (!\is_null($translatedAttribute)) {
                $translatedOptions = $translatedAttribute->get_options();
                $variationValue->addI18n((new ProductVariationValueI18nModel())
                    ->setName($translatedOptions[$sort])
                    ->setLanguageISO($wpmlPlugin->convertLanguageToWawi((string)$wpmlLanguageCode)));
            }
        }
    }
}
