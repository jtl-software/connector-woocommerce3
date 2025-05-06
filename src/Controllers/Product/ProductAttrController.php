<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Controllers\Product;

use Jtl\Connector\Core\Exception\MustNotBeNullException;
use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Exception\TranslatableAttributeException;
use Jtl\Connector\Core\Model\Product as ProductModel;
use Jtl\Connector\Core\Model\ProductAttribute;
use Jtl\Connector\Core\Model\TranslatableAttribute as ProductAttrModel;
use Jtl\Connector\Core\Model\TranslatableAttributeI18n as ProductAttrI18nModel;
use JtlWooCommerceConnector\Controllers\AbstractBaseController;
use JtlWooCommerceConnector\Utilities\Config;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use Psr\Log\InvalidArgumentException;

class ProductAttrController extends AbstractBaseController
{
    public const
        VISIBILITY_HIDDEN  = 'hidden',
        VISIBILITY_CATALOG = 'catalog',
        VISIBILITY_SEARCH  = 'search',
        VISIBILITY_VISIBLE = 'visible';

    /**
     * @param \WC_Product           $product
     * @param \WC_Product_Attribute $attribute
     * @param string                $slug
     * @param string                $languageIso
     * @return ProductAttrModel
     * @throws TranslatableAttributeException
     * @throws \JsonException
     */
    public function pullData(
        \WC_Product $product,
        \WC_Product_Attribute $attribute,
        string $slug,
        string $languageIso
    ): ProductAttrModel {
        return $this->buildAttribute($product, $attribute, $slug, $languageIso);
    }

    /**
     * @param int                                                    $productId
     * @param ProductAttribute[]                                     $pushedAttributes
     * @param array<int|string, array<string, bool|int|string|null>> $attributesFilteredVariationsAndSpecifics
     * @param ProductModel                                           $product
     * @return array<int|string, array<string, bool|int|string|null>>
     * @throws TranslatableAttributeException
     * @throws MustNotBeNullException
     * @throws InvalidArgumentException
     * @throws \TypeError
     */
    public function pushData(
        int $productId,
        array $pushedAttributes,
        array $attributesFilteredVariationsAndSpecifics,
        ProductModel $product
    ): array {
        //FUNCTION ATTRIBUTES BY JTL
        $virtual        = false;
        $downloadable   = false;
        $soldIndividual = false;
        $payable        = false;
        $nosearch       = false;
        $fbStatusCode   = false;
        $purchaseNote   = false;

        //GERMAN MARKET
        $digital                = false;
        $altDeliveryNote        = false;
        $suppressShippingNotice = false;
        $variationPreselect     = [];

        //GERMANIZED PRO
        $food = false;

        foreach ($pushedAttributes as $key => $pushedAttribute) {
            foreach ($pushedAttribute->getI18ns() as $i18n) {
                if (!$this->util->isWooCommerceLanguage($i18n->getLanguageISO())) {
                    if (!\in_array($i18n->getLanguageIso(), \array_keys($this->wpml->getActiveLanguages()))) {
                        continue;
                    }
                }

                $attrName = \strtolower(\trim($i18n->getName()));

                $attrName = $this->convertLegacyAttributeName($attrName);

                if ($this->hasWcAttributePrefix($attrName) === 1) {
                    /** @var string $i18nValue */
                    $i18nValue = $i18n->getValue();

                    if (
                        SupportedPlugins::isActive(SupportedPlugins::PLUGIN_FB_FOR_WOO)
                        && $attrName === ProductVaSpeAttrHandlerController::FACEBOOK_SYNC_STATUS_ATTR
                    ) {
                        $value = $this->util->isTrue($i18nValue) ? '1' : '';
                        $this->addOrUpdateMetaField($productId, \substr($attrName, 3), $value);
                        $fbStatusCode = true;
                    }

                    if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)) {
                        if ($i18n->getName() === ProductVaSpeAttrHandlerController::GZD_IS_SERVICE) {
                            $value = $this->util->isTrue($i18nValue) ? 'yes' : 'no';
                            $this->addOrUpdateMetaField($productId, '_service', $value);
                        }
                        if ($i18n->getName() === ProductVaSpeAttrHandlerController::GZD_MIN_AGE) {
                            $this->addOrUpdateMetaField($productId, '_min_age', $i18nValue);
                        }
                    }

                    if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO)) {
                        if ($i18n->getName() === ProductVaSpeAttrHandlerController::GZD_IS_FOOD) {
                            $value = $this->util->isTrue($i18nValue) ? 'yes' : 'no';
                            $this->addOrUpdateMetaField($productId, '_is_food', $value);
                            $food = true;
                        }
                    }

                    if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_GERMAN_MARKET)) {
                        if ($attrName === ProductVaSpeAttrHandlerController::GM_DIGITAL_ATTR) {
                            $value = $this->util->isTrue($i18nValue) ? 'yes' : 'no';
                            $this->addOrUpdateMetaField($productId, \substr($attrName, 5), $value);
                            $digital = true;
                        }

                        if ($attrName === ProductVaSpeAttrHandlerController::GM_SUPPRESS_SHIPPPING_NOTICE) {
                            $value = $this->util->isTrue($i18nValue) ? 'on' : '';
                            if ($value) {
                                $this->addOrUpdateMetaField($productId, \substr($attrName, 5), $value);
                            }
                            $suppressShippingNotice = true;
                        }

                        if ($attrName === ProductVaSpeAttrHandlerController::GM_ALT_DELIVERY_NOTE_ATTR) {
                            $value = \trim($i18nValue);
                            $this->addOrUpdateMetaField($productId, '_alternative_shipping_information', $value);
                            $altDeliveryNote = true;
                        }

                        if (
                            \preg_match('/^(wc_gm_v_preselect_)[a-zA-Z0-9-\_]+$/', $attrName)
                            && $product->getMasterProductId()->getHost() === 0
                        ) {
                            $attrName = \substr($attrName, 18);

                            $term = $this->getTermBy(
                                'slug',
                                $this->wcSanitizeTaxonomyName(
                                    \substr(\trim($i18nValue), 0, 27)
                                ),
                                'pa_' . $attrName
                            );

                            if ($term instanceof \WP_Term) {
                                $variationPreselect[$term->taxonomy] = $term->slug;
                            }
                        }
                    }

                    if (
                        \preg_match('/^(wc_v_preselect_)[a-zA-Z0-9-\_]+$/', $attrName)
                        && $product->getMasterProductId()->getHost() === 0
                    ) {
                        $attrName = \substr($attrName, 15);

                        $term = $this->getTermBy(
                            'slug',
                            $this->wcSanitizeTaxonomyName(\substr(\trim($i18nValue), 0, 27)),
                            'pa_' . $attrName
                        );

                        if ($term instanceof \WP_Term) {
                            $variationPreselect[$term->taxonomy] = $term->slug;
                        }
                    }

                    if ($attrName === ProductVaSpeAttrHandlerController::PURCHASE_NOTE_ATTR) {
                        $value = \trim($i18nValue);
                        $this->addOrUpdateMetaField($productId, '_purchase_note', $value);
                        $purchaseNote = true;
                    }

                    if ($attrName === ProductVaSpeAttrHandlerController::DOWNLOADABLE_ATTR) {
                        $value = $this->util->isTrue($i18nValue) ? 'yes' : 'no';
                        $this->addOrUpdateMetaField($productId, \substr($attrName, 2), $value);
                        $downloadable = true;
                    }

                    if ($attrName === ProductVaSpeAttrHandlerController::PURCHASE_ONLY_ONE_ATTR) {
                        $value = $this->util->isTrue($i18nValue) ? 'yes' : 'no';
                        $this->addOrUpdateMetaField($productId, \substr($attrName, 2), $value);
                        $soldIndividual = true;
                    }

                    if ($attrName === ProductVaSpeAttrHandlerController::VIRTUAL_ATTR) {
                        $value = $this->util->isTrue($i18nValue) ? 'yes' : 'no';
                        $this->addOrUpdateMetaField($productId, \substr($attrName, 2), $value);
                        $virtual = true;
                    }

                    if (
                        ($attrName === ProductVaSpeAttrHandlerController::PAYABLE_ATTR)
                        && $this->util->isTrue($i18nValue) === false
                    ) {
                        $this->wpUpdatePost(['ID' => $productId, 'post_status' => 'private']);
                        $payable = true;
                    }

                    if (
                        ($attrName === ProductVaSpeAttrHandlerController::NOSEARCH_ATTR)
                        && $this->util->isTrue($i18nValue)
                    ) {
                        $this->updatePostMeta($productId, '_visibility', 'catalog');

                        $this->wpSetObjectTerms($productId, ['exclude-from-search'], 'product_visibility', true);
                        $nosearch = true;
                    }

                    if ($attrName === ProductVaSpeAttrHandlerController::VISIBILITY) {
                        $this->updateProductVisibility($i18nValue, $productId);
                        $nosearch = true;
                    }

                    unset($pushedAttributes[$key]);
                }
            }
        }

        $this->updatePostMeta($productId, '_default_attributes', $variationPreselect);

        //Revert
        if (!$virtual) {
            $this->addOrUpdateMetaField($productId, '_virtual', 'no');
        }

        if (!$downloadable) {
            $this->addOrUpdateMetaField($productId, '_downloadable', 'no');
        }

        if (!$soldIndividual) {
            $this->addOrUpdateMetaField(
                $productId,
                \substr(ProductVaSpeAttrHandlerController::PURCHASE_ONLY_ONE_ATTR, 2),
                'no'
            );
        }

        if (!$nosearch) {
            $this->addOrUpdateMetaField($productId, '_visibility', 'visible');
            $this->wpRemoveObjectTerms($productId, ['exclude-from-search'], 'product_visibility');
        }

        if (!$purchaseNote) {
            $this->addOrUpdateMetaField($productId, '_purchase_note', '');
        }

        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_GERMAN_MARKET)) {
            if (!$altDeliveryNote) {
                $this->addOrUpdateMetaField($productId, '_alternative_shipping_information', '');
            }

            if (!$digital) {
                $this->addOrUpdateMetaField($productId, '_digital', 'no');
            }

            if (!$suppressShippingNotice) {
                $this->deletePostMeta($productId, '_suppress_shipping_notice');
            }
        }

        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_FB_FOR_WOO) && !$fbStatusCode) {
            $this->addOrUpdateMetaField(
                $productId,
                \substr(ProductVaSpeAttrHandlerController::FACEBOOK_SYNC_STATUS_ATTR, 3),
                ''
            );
        }

        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO) && !$food) {
            $this->addOrUpdateMetaField($productId, '_is_food', 'no');
        }

        if (!$payable) {
            $wcProduct = \wc_get_product($productId);
            if ($wcProduct instanceof \WC_Product) {
                $wcProduct->set_status('publish');
            } else {
                $this->logger->warning('Wc product not found for productId ' . $productId);
            }
        }

        foreach ($attributesFilteredVariationsAndSpecifics as $key => $attr) {
            if ($attr['is_variation'] === true || $attr['is_variation'] === false && $attr['value'] === '') {
                continue;
            }
            $tmp = false;

            foreach ($pushedAttributes as $pushedAttribute) {
                if ($attr['id'] == $pushedAttribute->getId()->getEndpoint()) {
                    $tmp = true;
                }
            }

            if ($tmp) {
                unset($attributesFilteredVariationsAndSpecifics[$key]);
            }
        }

        $sentCustomProperties = [];

        foreach ($pushedAttributes as $attribute) {
            if (
                !(bool)Config::get(Config::OPTIONS_SEND_CUSTOM_PROPERTIES)
                && $attribute->getIsCustomProperty() === true
            ) {
                continue;
            }

            foreach ($attribute->getI18ns() as $i18n) {
                if (!$this->util->isWooCommerceLanguage($i18n->getLanguageISO())) {
                    continue;
                }

                $sentCustomProperties[] = $attribute->getName();

                $this->saveAttribute($attribute, $i18n, $attributesFilteredVariationsAndSpecifics);
                break;
            }
        }

        $this->deleteRemovedCustomProperties($productId, $sentCustomProperties);

        return $attributesFilteredVariationsAndSpecifics;
    }

    /**
     * @param \WC_Product           $product
     * @param \WC_Product_Attribute $attribute
     * @param string                $slug
     * @param string                $languageIso
     * @return ProductAttrModel
     * @throws TranslatableAttributeException
     * @throws \JsonException
     */
    private function buildAttribute(
        \WC_Product $product,
        \WC_Product_Attribute $attribute,
        string $slug,
        string $languageIso
    ): ProductAttrModel {
        $productAttribute = $product->get_attribute($attribute->get_name());
        $isTax            = $attribute->is_taxonomy();

        // Divided by |
        $values = \explode(\WC_DELIMITER, $productAttribute);

        $i18n = (new ProductAttrI18nModel())
            ->setName($attribute->get_name())
            ->setValue(\implode(', ', $values))
            ->setLanguageISO($languageIso);

        return (new ProductAttrModel())
            ->setId(new Identity($product->get_id() . '_' . \wc_sanitize_taxonomy_name($attribute->get_name())))
            ->setIsCustomProperty($isTax)
            ->addI18n($i18n);
    }

    /**
     * @param ProductAttrModel                                       $attribute
     * @param ProductAttrI18nModel                                   $i18n
     * @param array<int|string, array<string, bool|int|string|null>> $attributes
     * @return void
     * @throws TranslatableAttributeException
     */
    private function saveAttribute(ProductAttrModel $attribute, ProductAttrI18nModel $i18n, array &$attributes): void
    {
        /** @var string $value */
        $value = $i18n->getValue();
        if ((bool)Config::get(Config::OPTIONS_ALLOW_HTML_IN_PRODUCT_ATTRIBUTES, false) === false) {
            /** @var string $value */
            $value = $this->wcClean($value);
        }

        /** @var string $attributeName */
        $attributeName = $this->wcClean($i18n->getName());

        $this->createOrUpdateExistingAttribute($i18n, [
            'name' => $attributeName,
            'value' => $value,
            'isCustomProperty' => $attribute->getIsCustomProperty(),
            'isVisible' => ($attribute->getIsTranslated() || $attribute->getIsCustomProperty()) ? 1 : 0,
        ], $attributes);
    }

    /**
     * @param ProductAttrI18nModel                                   $i18n
     * @param array<string, bool|int|string>                         $data
     * @param array<int|string, array<string, bool|int|string|null>> $attributes
     * @return void
     * @throws TranslatableAttributeException
     */
    private function createOrUpdateExistingAttribute(ProductAttrI18nModel $i18n, array $data, array &$attributes): void
    {
        $slug = $this->wcSanitizeTaxonomyName($i18n->getName());

        if (isset($attributes[$slug])) {
            /** @var string $value */
            $value = $i18n->getValue();
            $this->updateAttribute($slug, $value, $attributes);
        } else {
            $this->createAttribute($slug, $data, $attributes);
        }
    }

    /**
     * @param string                                                 $slug
     * @param string                                                 $value
     * @param array<int|string, array<string, bool|int|string|null>> $attributes
     * @return void
     */
    private function updateAttribute(string $slug, string $value, array &$attributes): void
    {
        /** @var string $attributeValue */
        $attributeValue = $attributes[$slug]['value'];
        $values         = \explode(',', $attributeValue);

        /** @var string $wcCleanValue */
        $wcCleanValue               = $this->wcClean($value);
        $values[]                   = $wcCleanValue;
        $attributes[$slug]['value'] = \implode(' | ', $values);
    }

    /**
     * @param string                                                 $slug
     * @param array<string, bool|int|string>                         $data
     * @param array<int|string, array<string, bool|int|string|null>> $attributes
     * @return void
     */
    private function createAttribute(string $slug, array $data, array &$attributes): void
    {
        $attributes[$slug] = [
            'name' => $data['name'],
            'value' => $data['value'],
            'position' => 0,
            'is_visible' => $data['isVisible'],
            'is_variation' => 0,
            'is_taxonomy' => 0,
        ];
    }

    /**
     * @param int      $productId
     * @param string[] $sentCustomProperties
     * @return void
     * @throws InvalidArgumentException
     */
    private function deleteRemovedCustomProperties(int $productId, array $sentCustomProperties): void
    {
        global $wpdb;

        $query = \sprintf(
            "
            SELECT meta_value
            FROM {$wpdb->postmeta}
            WHERE post_id = %d
            AND meta_key = '_product_attributes'",
            $productId
        );

        /** @var string[] $existingPropertyNames */
        $existingPropertyNames = [];

        /** @var array<int, array<string, string>> $existingProperties */
        $existingProperties = $this->db->query($query);

        if ($existingProperties) {
            /** @var array<int|string, array<string, bool|int|string|null>> $existingProperties */
            $existingProperties = \unserialize($existingProperties[0]['meta_value']);

            foreach ($existingProperties as $property) {
                $existingPropertyNames[] = $property['name'];
            }
        }

        $missingProperties = \array_diff($existingPropertyNames, $sentCustomProperties);

        if ($missingProperties) {
            foreach ($missingProperties as $missingKey) {
                unset($existingProperties[\str_replace(' ', '-', \strtolower((string)$missingKey))]);
            }

            \update_post_meta($productId, '_product_attributes', $existingProperties);
        }
    }

    /**
     * @param string $attrName
     * @return false|int
     */
    protected function hasWcAttributePrefix(string $attrName): false|int
    {
        return \preg_match('/^(wc_)[a-zA-Z0-9-\_]+$/', $attrName);
    }

    /**
     * @param string $attrName
     * @return string
     */
    protected function convertLegacyAttributeName(string $attrName): string
    {
        $legacyAttributesWithoutPrefix = ['payable', 'nosearch'];

        return \in_array($attrName, $legacyAttributesWithoutPrefix, true)
            ? \sprintf('wc_%s', $attrName)
            : $attrName;
    }

    /**
     * @param int    $productId
     * @param string $metaKey
     * @param string $value
     * @return void
     */
    protected function addOrUpdateMetaField(int $productId, string $metaKey, string $value): void
    {
        if (!$this->addPostMeta($productId, $metaKey, $value)) {
            $this->updatePostMeta($productId, $metaKey, $value);
        }
    }

    /**
     * @param string $value
     * @param int    $productId
     * @return string
     */
    protected function updateProductVisibility(string $value, int $productId): string
    {
        $excludeFromCatalog = 'exclude-from-catalog';
        $excludeFromSearch  = 'exclude-from-search';
        $productVisibility  = 'product_visibility';

        $this->wpRemoveObjectTerms($productId, [$excludeFromCatalog, $excludeFromSearch], $productVisibility);
        switch ($value) {
            case self::VISIBILITY_HIDDEN:
                $this->wpSetObjectTerms($productId, [$excludeFromCatalog, $excludeFromSearch], $productVisibility);
                break;
            case self::VISIBILITY_CATALOG:
                $this->wpSetObjectTerms($productId, [$excludeFromSearch], $productVisibility);
                break;
            case self::VISIBILITY_SEARCH:
                $this->wpSetObjectTerms($productId, [$excludeFromCatalog], $productVisibility);
                break;
        }

        if (
            \in_array(
                $value,
                [self::VISIBILITY_HIDDEN, self::VISIBILITY_CATALOG, self::VISIBILITY_SEARCH, self::VISIBILITY_VISIBLE]
            )
        ) {
            $this->updatePostMeta($productId, '_visibility', $value);
        }
        return $value;
    }
}
