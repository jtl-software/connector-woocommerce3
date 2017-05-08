<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller\Product;

use jtl\Connector\Linker\ChecksumLinker;
use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\Model\ProductChecksum;
use jtl\Connector\Model\ProductVariation as ProductVariationModel;
use jtl\Connector\Model\ProductVariationI18n;
use jtl\Connector\Model\ProductVariationValue;
use jtl\Connector\Model\ProductVariationValueI18n;
use jtl\Connector\WooCommerce\Controller\BaseController;
use jtl\Connector\WooCommerce\Utility\IdConcatenation;
use jtl\Connector\WooCommerce\Utility\SQLs;
use jtl\Connector\WooCommerce\Utility\Util;

class ProductVariation extends BaseController
{
    private $values = [];

    // <editor-fold defaultstate="collapsed" desc="Pull">
    public function pullData(\WC_Product $product, ProductModel $model)
    {
        $return = [];

        if ($product->is_type('variable')) {
            $allVariations = $product->get_attributes();

            /**
             * @var string $slug
             * @var \WC_Product_Attribute $variation
             */
            foreach ($allVariations as $slug => $variation) {
                if (!$variation->get_variation()) {
                    continue;
                }

                if ($variation->is_taxonomy()) {
                    $productVariation = $this->pullTaxonomyVariation($product, $model, $variation, $slug);
                } else {
                    $productVariation = $this->pullCustomVariation($product, $model, $slug, $variation);
                }
            }
        } elseif ($product->is_type('variation')) {
            $allVariations = $product->get_attributes();

            /**
             * @var string $slug
             * @var \WC_Product_Attribute $variation
             */
            foreach ($allVariations as $slug => $variation) {
                var_dump($variation);
                if ($variation->get_variation()) {
                    if ($variation->is_taxonomy()) {
                        $productVariation = $this->pullTaxonomyVariation($product, $model, $variation, $slug);
                    } else {
                        $productVariation = $this->pullCustomVariation($product, $model, $slug, $variation);
                    }

                    $productVariation->setProductId($model->getId());

                    $return[] = $productVariation;
                }
            }
        }

        return $return;
    }

    private function pullTaxonomyVariation(\WC_Product $product, ProductModel $model, \WC_Product_Attribute $variation, $slug)
    {
        // Term created for variation => take term and taxonomy
        $productVariation = (new ProductVariationModel())
            ->setId(new Identity(IdConcatenation::link([$model->getId()->getEndpoint(), $variation->get_id()])))
            ->setType(ProductVariationModel::TYPE_SELECT);

        $i18n = (new ProductVariationI18n())
            ->setProductVariationId($productVariation->getId())
            ->setName(\wc_attribute_label($variation['name']))
            ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage());

        $terms = \get_terms(['taxonomy' => $slug]);
        $variationAttributes = $product->get_variation_attributes();

        foreach ($terms as $sort => $term) {
            if ($product->is_type('variable')) {
                if (in_array($term->slug, $variationAttributes[$term->taxonomy])) {
                    $this->addTaxonomyVariationValue($productVariation, $productVariation->getId(), $term, $sort);
                }
            } elseif ($product->is_type('variation')) {
                $van = 'attribute_' . \sanitize_title($term->taxonomy);
                if (isset($variationAttributes[$van]) && $variationAttributes[$van] === $term->slug) {
                    $this->addTaxonomyVariationValue($productVariation, $productVariation->getId(), $term, $sort);
                }
            }
        }

        $productVariation->addI18n($i18n);

        return $productVariation;
    }

    private function addTaxonomyVariationValue(ProductVariationModel &$productVariation, $productVariationId, $term, $sort)
    {
        $i18n = (new ProductVariationValueI18n())
            ->setProductVariationValueId(new Identity(IdConcatenation::link([$productVariationId, $term->term_id])))
            ->setName($term->name)
            ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage());

        $productVariationValue = (new ProductVariationValue())
            ->setId($productVariationId)
            ->setProductVariationId($i18n->getProductVariationValueId())
            ->setSort($sort)
            ->addI18n($i18n);

        $productVariation->addValue($productVariationValue);
    }

    private function pullCustomVariation(\WC_Product $product, ProductModel $model, $slug, $variation)
    {
        // Custom variation => take post meta
        $productVarId = new Identity(IdConcatenation::link([$model->getId()->getEndpoint(), $slug]));
        $productVariation = (new ProductVariationModel())
            ->setId($productVarId);

        $productVarI18n = (new ProductVariationI18n())
            ->setProductVariationId($productVarId)
            ->setName($variation['name'])
            ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage());

        $values = array_map('trim', explode(WC_DELIMITER, $variation['value']));
        $variationAttributes = $product->get_variation_attributes();

        foreach ($values as $sort => $value) {
            if ($product->is_type('variable')) {
                $this->addCustomVariationValue($productVariation, $productVarId, $value, $sort);
            } elseif ($product->is_type('variation')) {
                $van = 'attribute_' . \sanitize_title($slug);
                if (isset($variationAttributes[$van]) && $variationAttributes[$van] === $value) {
                    $this->addCustomVariationValue($productVariation, $productVarId, $value, $sort);
                }
            }
        }

        $productVariation->addI18n($productVarI18n);

        return $productVariation;
    }

    private function addCustomVariationValue(ProductVariationModel &$productVariation, Identity $productVarId, $value, $sort)
    {
        $varValueId = new Identity(IdConcatenation::link([$productVarId->getEndpoint(), \sanitize_key($value)]));

        $i18n = (new ProductVariationValueI18n())
            ->setProductVariationValueId($varValueId)
            ->setName($value)
            ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage());

        $productVariationValue = (new ProductVariationValue())
            ->setId($varValueId)
            ->setProductVariationId($productVarId)
            ->setSort($sort)
            ->addI18n($i18n);

        $productVariation->addValue($productVariationValue);
    }
    // </editor-fold>

    // <editor-fold defaultstate="collapsed" desc="Push">
    public function pushData(ProductModel $product, $model)
    {
        if ($product->getIsMasterProduct()) {
            if ($this->hasVariationChanges($product)) {
                $this->pushDataParent($product);
            }
        } else {
            $this->pushDataChild($product);
        }
    }

    private function pushDataParent(ProductModel $data)
    {
        $attributes = [];
        $productId = $data->getId()->getEndpoint();
        $product = \wc_get_product($productId);
        $existingAttributes = $product->get_attributes();
        $this->addProductAttributes($existingAttributes, $attributes);
        $variations = $data->getVariations();
        foreach ($variations as $variation) {
            foreach ($variation->getI18ns() as $variationI18n) {
                $taxonomyName = \wc_sanitize_taxonomy_name($variationI18n->getName());
                if (!Util::getInstance()->isWooCommerceLanguage($variationI18n->getLanguageISO())) {
                    continue;
                }
                $values = [];
                $this->values = $variation->getValues();
                usort($this->values, [$this, 'sortI18nValues']);
                foreach ($this->values as $vv) {
                    foreach ($vv->getI18ns() as $valueI18n) {
                        if (!Util::getInstance()->isWooCommerceLanguage($valueI18n->getLanguageISO())) {
                            continue;
                        }
                        $values[] = $valueI18n->getName();
                        break;
                    }
                }
                $attributes[$taxonomyName] = [
                    'name'         => $variationI18n->getName(),
                    'value'        => implode(' ' . WC_DELIMITER . ' ', $values),
                    'position'     => $variation->getSort(),
                    'is_visible'   => 0,
                    'is_variation' => 1,
                    'is_taxonomy'  => 0,
                ];
                break;
            }
        }
        \update_post_meta($productId, '_product_attributes', $attributes);
    }

    private function pushDataChild(ProductModel $product)
    {
        $updatedAttributeKeys = [];

        foreach ($product->getVariations() as $variation) {
            foreach ($variation->getValues() as $value) {
                foreach ($variation->getI18ns() as $variationI18n) {
                    if (!Util::getInstance()->isWooCommerceLanguage($variationI18n->getLanguageISO())) {
                        continue;
                    }

                    foreach ($value->getI18ns() as $i18n) {
                        $key = 'attribute_' . \sanitize_title($variationI18n->getName());
                        $updatedAttributeKeys[] = $key;

                        \update_post_meta($product->getId()->getEndpoint(), $key, $i18n->getName());
                    }
                    break;
                }
            }
        }
        $attributesToDelete = $this->database->queryList(SQLs::productVariationObsoletes(
            $product->getId()->getEndpoint(),
            $updatedAttributeKeys
        ));

        foreach ($attributesToDelete as $key) {
            \delete_post_meta($product->getId()->getEndpoint(), $key);
        }
    }

    private function sortI18nValues(ProductVariationValue $a, ProductVariationValue $b)
    {
        if ($a->getSort() === $b->getSort()) {
            if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
                return 0;
            } else {
                $indexA = $indexB = 0;
                foreach ($this->values as $index => $value) {
                    if ($value->getId() === $a->getId()) {
                        $indexA = $index;
                    } elseif ($value->getId() === $b->getId()) {
                        $indexB = $index;
                    }
                }

                return ($indexA < $indexB) ? -1 : 1;
            }
        }

        return ($a->getSort() < $b->getSort()) ? -1 : 1;
    }

    private function hasVariationChanges(ProductModel $product)
    {
        if (count($product->getVariations()) > 0) {
            $productId = $product->getId()->getEndpoint();
            if (!empty($productId)) {
                $checksum = ChecksumLinker::find($product, ProductChecksum::TYPE_VARIATION);
                if ($checksum === null) {
                    return false;
                }

                return $checksum->hasChanged();
            } else {
                return true;
            }
        }

        return false;
    }

    /**
     * Add product attributes as they will be overwritten if they are not added again.
     *
     * @param array $existingAttributes Existing attributes of the product.
     * @param array $attributes Product attributes.
     */
    private function addProductAttributes(array $existingAttributes, array &$attributes)
    {
        foreach ($existingAttributes as $slug => $existingAttribute) {
            if (!$existingAttribute['is_variation']) {
                $attributes[$slug] = $existingAttribute;
            }
        }
    }
    // </editor-fold>
}
