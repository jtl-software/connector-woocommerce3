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

        if ($product instanceof \WC_Product_Variable) {
            /**
             * @var string $slug
             * @var \WC_Product_Attribute $attribute
             */
            foreach ($product->get_attributes() as $slug => $attribute) {
                if (!$attribute->get_variation()) {
                    continue;
                }

                $id = new Identity(IdConcatenation::link([$model->getId()->getEndpoint(), $attribute->get_id()]));

                $productVariation = (new ProductVariationModel())
                    ->setId($id)
                    ->setProductId($model->getId())
                    ->setType(ProductVariationModel::TYPE_SELECT)
                    ->addI18n((new ProductVariationI18n())
                        ->setProductVariationId($id)
                        ->setName(\wc_attribute_label($attribute->get_name()))
                        ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage()));

                /** @var \WP_Term $term */
                foreach ($attribute->get_terms() as $sort => $term) {
                    $valueId = new Identity(IdConcatenation::link([$id->getEndpoint(), $term->term_id]));

                    $productVariation->addValue((new ProductVariationValue())
                        ->setId($valueId)
                        ->setProductVariationId($id)
                        ->setSort($sort)
                        ->addI18n((new ProductVariationValueI18n())
                            ->setProductVariationValueId($valueId)
                            ->setName($term->name)
                            ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage()))
                    );
                }

                $return[] = $productVariation;
            }
        } elseif ($product instanceof \WC_Product_Variation) {
            $parent = \wc_get_product($product->get_parent_id());

            /**
             * @var string $slug
             * @var \WC_Product_Attribute $attribute
             */
            foreach ($product->get_attributes() as $slug => $attribute) {
                foreach (\get_terms(['taxonomy' => $slug]) as $term) {
                    if ($term->slug !== $attribute) {
                        continue;
                    }

                    /** @var \WC_Product_Attribute $parentAttribute */
                    foreach ($parent->get_attributes() as $parentAttribute) {
                        if ($parentAttribute->get_name() === $slug) {
                            $attribute = $parentAttribute;
                            break;
                        }
                    }

                    $id = new Identity(IdConcatenation::link([$model->getId()->getEndpoint(), $attribute->get_id()]));

                    $productVariation = (new ProductVariationModel())
                        ->setId($id)
                        ->setProductId($model->getId())
                        ->setType(ProductVariationModel::TYPE_SELECT)
                        ->addI18n((new ProductVariationI18n())
                            ->setProductVariationId($id)
                            ->setName(\wc_attribute_label($attribute->get_name()))
                            ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage()));

                    $valueId = new Identity(IdConcatenation::link([$id->getEndpoint(), $term->term_id]));

                    $productVariation->addValue((new ProductVariationValue())
                        ->setId($valueId)
                        ->setProductVariationId($id)
                        ->addI18n((new ProductVariationValueI18n())
                            ->setProductVariationValueId($valueId)
                            ->setName($term->name)
                            ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage()))
                    );

                    $return[] = $productVariation;
                }
            }
        }

        return $return;
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
