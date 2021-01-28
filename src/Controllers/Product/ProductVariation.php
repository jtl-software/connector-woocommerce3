<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Product;

use jtl\Connector\Core\Exception\LanguageException;
use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\Model\ProductVariation as ProductVariationModel;
use jtl\Connector\Model\ProductVariationI18n as ProductVariationI18nModel;
use jtl\Connector\Model\ProductVariationValue as ProductVariationValueModel;
use jtl\Connector\Model\ProductVariationValueI18n as ProductVariationValueI18nModel;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlProduct;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlProductVariation;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlStringTranslation;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlTermTranslation;
use JtlWooCommerceConnector\Logger\WpErrorLogger;
use JtlWooCommerceConnector\Utilities\Id;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\Util;
use WP_Error;

class ProductVariation extends BaseController
{
    /**
     * @var array
     */
    protected static $originLanguageDetails = [];

    /**
     * @param ProductModel $model
     * @param \WC_Product_Attribute $attribute
     * @param \WC_Product $product
     * @param string $languageIso
     * @return ProductVariationModel|null
     * @throws LanguageException
     * @throws \Exception
     */
    public function pullDataParent(
        ProductModel $model,
        \WC_Product_Attribute $attribute,
        \WC_Product $product,
        $languageIso = ''
    ) {
        $id = new Identity(Id::link([$model->getId()->getEndpoint(), $attribute->get_id()]));

        $productVariation = (new ProductVariationModel())
            ->setId($id)
            ->setProductId($model->getId())
            ->setType(ProductVariationModel::TYPE_SELECT)
            ->addI18n((new ProductVariationI18nModel())
                ->setProductVariationId($id)
                ->setName(\wc_attribute_label($attribute->get_name()))
                ->setLanguageISO($languageIso));

        if ($this->wpml->canBeUsed()) {
            $this->wpml->getComponent(WpmlProductVariation::class)->getTranslations(
                $product,
                wc_sanitize_endpoint_slug($attribute->get_name()),
                $productVariation
            );
        }

        if ($attribute->is_taxonomy()) {
            $terms = $attribute->get_terms();

            if (!is_array($terms)) {
                return null;
            }

            /** @var \WP_Term $term */
            foreach ($terms as $sort => $term) {
                $valueId = new Identity(Id::link([$id->getEndpoint(), $term->term_id]));

                $productVariation->addValue($variationValue = (new ProductVariationValueModel())
                    ->setId($valueId)
                    ->setProductVariationId($id)
                    ->setSort($sort)
                    ->addI18n((new ProductVariationValueI18nModel())
                        ->setProductVariationValueId($valueId)
                        ->setName($term->name)
                        ->setLanguageISO($languageIso))
                );

                if ($this->wpml->canBeUsed()) {
                    $this->wpml->getComponent(WpmlProductVariation::class)->getValueTranslations(
                        $variationValue,
                        $term
                    );
                }
            }
        } else {
            $options = $attribute->get_options();

            foreach ($options as $sort => $option) {
                $valueId = new Identity(Id::link([$id->getEndpoint(), \sanitize_key($option)]));

                $productVariation->addValue($variationValue = (new ProductVariationValueModel())
                    ->setId($valueId)
                    ->setProductVariationId($id)
                    ->setSort($sort)
                    ->addI18n((new ProductVariationValueI18nModel())
                        ->setProductVariationValueId($valueId)
                        ->setName($option)
                        ->setLanguageISO($languageIso))
                );

                if ($this->wpml->canBeUsed()) {
                    $this->wpml->getComponent(WpmlProductVariation::class)->getOptionTranslations(
                        $product,
                        wc_sanitize_endpoint_slug($attribute->get_name()),
                        $variationValue,
                        $sort
                    );
                }
            }
        }

        return $productVariation;
    }

    /**
     * @param \WC_Product $product
     * @param ProductModel $model
     * @param string $languageIso
     * @return array
     * @throws LanguageException
     * @throws \Exception
     */
    public function pullDataChild(\WC_Product $product, ProductModel $model, $languageIso = '')
    {
        $parentProduct = \wc_get_product($product->get_parent_id());
        $productVariations = [];
        /**
         * @var string $slug
         * @var \WC_Product_Attribute $attribute
         */
        foreach ($parentProduct->get_attributes() as $slug => $attribute) {
            $id = new Identity(Id::link([$parentProduct->get_id(), $attribute->get_id()]));

            $productVariation = (new ProductVariationModel)
                ->setId($id)
                ->setProductId($model->getId())
                ->setType(ProductVariationModel::TYPE_SELECT)
                ->addI18n((new ProductVariationI18nModel)
                    ->setProductVariationId($id)
                    ->setName(\wc_attribute_label($attribute->get_name()))
                    ->setLanguageISO($languageIso));

            if ($this->wpml->canBeUsed()) {
                $this->wpml->getComponent(WpmlProductVariation::class)->getTranslations(
                    $parentProduct,
                    $slug,
                    $productVariation
                );
            }

            if ($attribute->is_taxonomy()) {
                $terms = $attribute->get_terms();

                if (!is_array($terms)) {
                    continue;
                }

                $value = $product->get_attribute($slug);

                /** @var \WP_Term $term */
                foreach ($terms as $sort => $term) {
                    if ($term->name !== $value) {
                        continue;
                    }

                    $valueId = new Identity(Id::link([$id->getEndpoint(), $term->term_id]));

                    $productVariation->addValue($variationValue = (new ProductVariationValueModel)
                        ->setId($valueId)
                        ->setProductVariationId($id)
                        ->setSort($sort)
                        ->addI18n((new ProductVariationValueI18nModel)
                            ->setProductVariationValueId($valueId)
                            ->setName($term->name)
                            ->setLanguageISO($languageIso))
                    );

                    if ($this->wpml->canBeUsed()) {
                        $this->wpml->getComponent(WpmlProductVariation::class)->getValueTranslations(
                            $variationValue,
                            $term
                        );
                    }
                }
            } else {
                $value = $product->get_attribute($slug);

                foreach ($attribute->get_options() as $sort => $option) {
                    if ($option !== $value) {
                        continue;
                    }

                    $valueId = new Identity(Id::link([$id->getEndpoint(), \sanitize_key($option)]));

                    $productVariation->addValue($variationValue = (new ProductVariationValueModel)
                        ->setId($valueId)
                        ->setProductVariationId($id)
                        ->setSort($sort)
                        ->addI18n((new ProductVariationValueI18nModel)
                            ->setProductVariationValueId($valueId)
                            ->setName($option)
                            ->setLanguageISO($languageIso))
                    );

                    if ($this->wpml->canBeUsed()) {
                        $this->wpml->getComponent(WpmlProductVariation::class)->getOptionTranslations(
                            $parentProduct,
                            wc_sanitize_endpoint_slug($attribute->get_name()),
                            $variationValue,
                            $sort
                        );
                    }
                }
            }

            $productVariations[] = $productVariation;
        }

        return $productVariations;
    }

    /**
     * @param $productId
     * @param $variationSpecificData
     * @param $attributesFilteredVariationSpecifics
     * @param $wawiIsoLanguage
     * @return |null |null
     * @throws LanguageException
     */
    public function pushMasterData(
        $productId,
        $variationSpecificData,
        $attributesFilteredVariationSpecifics,
        $wawiIsoLanguage
    ) {
        $result = null;
        $parent = (new ProductVaSpeAttrHandler);

        $isDefaultLanguage = Util::getInstance()->isWooCommerceLanguage($wawiIsoLanguage);
        if ($this->wpml->canBeUsed()) {
            $isDefaultLanguage = $this->wpml->isDefaultLanguage($wawiIsoLanguage);
        }

        if ($isDefaultLanguage === true) {
            self::$originLanguageDetails = [
                'term' => [],
                'attribute' => []
            ];
        }

        $translationDetailInfo = [
            'term' => [],
            'attribute' => []
        ];

        $k = 0;
        foreach ($variationSpecificData as $key => $variationSpecific) {
            $k++;

            if ($this->wpml->canBeUsed() && $isDefaultLanguage === false) {
                $key = self::$originLanguageDetails['attribute'][$k]['key'];
            }

            $taxonomy = 'pa_' . wc_sanitize_taxonomy_name(substr(trim($key), 0, 27));
            $specificID = $this->database->query(SqlHelper::getSpecificId($key));
            $specificExists = isset($specificID[0]['attribute_id']);
            $options = [];

            if (array_key_exists($taxonomy, $attributesFilteredVariationSpecifics)) {
                $attributesFilteredVariationSpecifics[$taxonomy]['is_variation'] = true;
            }

            if($isDefaultLanguage === true){
                self::$originLanguageDetails['attribute'][$k] = [
                    'taxonomy' => $taxonomy,
                    'name' => $variationSpecific['name'],
                    'key' => $key
                ];
            }

            if ($specificExists) {

                if ($this->wpml->canBeUsed() && $isDefaultLanguage === false) {
                    $name = self::$originLanguageDetails['attribute'][$k]['name'];
                    $this->wpml->getComponent(WpmlStringTranslation::class)->translate($name,
                        $variationSpecific['name'], $wawiIsoLanguage);
                }

                //Get existing values
                $pushedValues = explode(' ' . WC_DELIMITER . ' ', $variationSpecific['value']);
                foreach ($pushedValues as $pushedValue) {

                    //check if value did not exists
                    $specificValueId = $parent->getSpecificValueId(
                        $taxonomy,
                        trim($pushedValue)
                    );

                    $termId = (int)$specificValueId->getEndpoint();

                    if (!$termId > 0) {
                        //Add values
                        $newTerm = \wp_insert_term(
                            $pushedValue,
                            $taxonomy
                        );

                        if ($newTerm instanceof WP_Error) {
                            WpErrorLogger::getInstance()->logError($newTerm);
                            continue;
                        }

                        $termId = $newTerm['term_id'];
                        $specificValueId->setEndpoint($termId);
                    }

                    if ($isDefaultLanguage === true) {
                        self::$originLanguageDetails['term'][] = [
                            'tax' => $taxonomy,
                            'id' => $termId
                        ];
                    }
                    $translationDetailInfo['term'][] = [
                        'tax' => $taxonomy,
                        'id' => $termId
                    ];

                    if (array_key_exists($taxonomy, $attributesFilteredVariationSpecifics)) {
                        $attributesFilteredVariationSpecifics[$taxonomy]['is_variation'] = true;

                        $options = explode(
                            ' ' . WC_DELIMITER . ' ',
                            $attributesFilteredVariationSpecifics[$taxonomy]['value']
                        );

                        if ((!in_array($termId, $options))) {
                            array_push($options, $termId);
                        }

                        $attributesFilteredVariationSpecifics[$taxonomy]['value'] = implode(
                            ' ' . WC_DELIMITER . ' ',
                            $options
                        );

                    } else {
                        array_push($options, $termId);
                        $attributesFilteredVariationSpecifics[$taxonomy] = [
                            'name' => $taxonomy,
                            'value' => implode(
                                ' ' . WC_DELIMITER . ' ',
                                $options
                            ),
                            'position' => 0,
                            'is_visible' => Util::showVariationSpecificsOnProductPageEnabled(),
                            'is_variation' => true,
                            'is_taxonomy' => $taxonomy,
                        ];
                    }

                    foreach ($options as $key => $value) {
                        $options[$key] = (int)$value;
                    }

                    wp_set_object_terms(
                        $productId,
                        $options,
                        $attributesFilteredVariationSpecifics[$taxonomy]['name'],
                        true
                    );
                }

            } else {
                //Create specific and add values
                if($isDefaultLanguage === true) {
                    $endpoint = [
                        'id' => '',
                        'name' => $variationSpecific['name'],
                        'slug' => $taxonomy,
                        'type' => 'select',
                        'order_by' => 'menu_order'
                    ];

                    $attributeId = wc_create_attribute($endpoint);

                    if ($attributeId instanceof WP_Error) {
                        WpErrorLogger::getInstance()->logError($attributeId);
                        return null;
                    }
                }

                $options = explode(
                    ' ' . WC_DELIMITER . ' ',
                    $variationSpecific['value']
                );

                //Register taxonomy for current request
                register_taxonomy($taxonomy, null);

                if($this->wpml->canBeUsed()){
                    if ($isDefaultLanguage === true) {
                        $this->wpml->getComponent(WpmlStringTranslation::class)->registerString($taxonomy, $endpoint['name'], $wawiIsoLanguage);
                    } else {
                        $name = self::$originLanguageDetails['attribute'][$k]['name'];
                        $this->wpml->getComponent(WpmlStringTranslation::class)->translate($name,
                            $variationSpecific['name'], $wawiIsoLanguage);
                    }
                }

                $assignedValueIds = [];

                foreach ($options as $optionKey => $optionValue) {
                    $slug = wc_sanitize_taxonomy_name($optionValue);

                    $endpointValue = [
                        'name' => $optionValue,
                        'slug' => $slug,
                    ];

                    $exValId = $this->database->query(
                        SqlHelper::getSpecificValueId(
                            $taxonomy,
                            $endpointValue['name']
                        )
                    );
                    $exValId = $exValId[0]['term_id'] ?? null;

                    if($isDefaultLanguage === false){
                        $taxonomy = self::$originLanguageDetails['attribute'][$k]['taxonomy'];
                    }

                    if (is_null($exValId)) {
                        $newTerm = \wp_insert_term(
                            $endpointValue['name'],
                            $taxonomy
                        );

                        if ($newTerm instanceof WP_Error) {
                            WpErrorLogger::getInstance()->logError($newTerm);
                            continue;
                        }

                        $termId = $newTerm['term_id'];

                        if ($termId instanceof WP_Error) {
                            WpErrorLogger::getInstance()->logError($termId);
                            continue;
                        }

                        $assignedValueIds[] = $termId;

                        if ($isDefaultLanguage === true) {
                            self::$originLanguageDetails['term'][] = [
                                'tax' => $taxonomy,
                                'id' => $termId
                            ];
                        }
                        $translationDetailInfo['term'][] = [
                            'tax' => $taxonomy,
                            'id' => $termId
                        ];
                    }
                }

                $attributesFilteredVariationSpecifics[$taxonomy] = [
                    'name' => $taxonomy,
                    'value' => implode(
                        ' ' . WC_DELIMITER . ' ',
                        $options
                    ),
                    'position' => null,
                    'is_visible' => Util::showVariationSpecificsOnProductPageEnabled(),
                    'is_variation' => true,
                    'is_taxonomy' => $taxonomy,
                ];

                wp_set_object_terms(
                    $productId,
                    $assignedValueIds,
                    $attributesFilteredVariationSpecifics[$taxonomy]['name'],
                    true
                );
            }
            $result = $attributesFilteredVariationSpecifics;
        }

        if ($this->wpml->canBeUsed() && $isDefaultLanguage === false && !empty(self::$originLanguageDetails)) {
            foreach ($translationDetailInfo['term'] as $detail) {
                $originLanguage= null;
                foreach(self::$originLanguageDetails['term'] as $info){
                    if($info['tax'] === $detail['tax']){
                        $originLanguage = $info;
                        break;
                    }
                }
                if(!is_null($originLanguage)) {
                    $type = 'tax_' . $originLanguage['tax'];
                    $languageCode = $this->wpml->convertLanguageToWpml($wawiIsoLanguage);
                    $trid = $this->wpml->getElementTrid($originLanguage['id'], $type);

                    $this->wpml->getSitepress()->set_element_language_details(
                        $detail['id'],
                        $type,
                        $trid,
                        $languageCode
                    );
                }
            }
        }

        return $result;
    }

    /**
     * @param $productId
     * @param $pushedVariations
     * @return array
     * @throws LanguageException
     */
    public function pushChildData(
        $productId,
        $pushedVariations
    ) {
        $updatedAttributeKeys = [];

        /** @var ProductVariationModel $variation */
        foreach ($pushedVariations as $variation) {
            foreach ($variation->getValues() as $variationValue) {
                foreach ($variation->getI18ns() as $variationI18n) {
                    if ($this->skipNotDefaultLanguage($variationI18n->getLanguageISO())) {
                        continue;
                    }

                    foreach ($variationValue->getI18ns() as $i18n) {
                        if ($this->skipNotDefaultLanguage($i18n->getLanguageISO())) {
                            continue;
                        }

                        $metaKey = Util::createVariantTaxonomyName($variationI18n->getName());
                        $updatedAttributeKeys[] = $metaKey;

                        \update_post_meta($productId, $metaKey, wc_sanitize_taxonomy_name($i18n->getName()));
                    }
                    break;
                }
            }
        }

        return $updatedAttributeKeys;
    }

    /**
     * @param string $wawiLanguageIso
     * @return bool
     * @throws LanguageException
     */
    protected function skipNotDefaultLanguage(string $wawiLanguageIso): bool
    {
        if ($this->wpml->canBeUsed()) {
            if ($this->wpml->convertLanguageToWawi($this->wpml->getDefaultLanguage()) !== $wawiLanguageIso) {
                return true;
            }
        } else {
            if (!Util::getInstance()->isWooCommerceLanguage($wawiLanguageIso)) {
                return true;
            }
        }
        return false;
    }
}
