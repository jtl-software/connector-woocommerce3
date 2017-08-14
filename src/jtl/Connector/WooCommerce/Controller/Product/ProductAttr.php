<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller\Product;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\Model\ProductAttr as ProductAttrModel;
use jtl\Connector\Model\ProductAttrI18n as ProductAttrI18nModel;
use jtl\Connector\WooCommerce\Controller\BaseController;
use jtl\Connector\WooCommerce\Utility\Db;
use jtl\Connector\WooCommerce\Utility\Id;
use jtl\Connector\WooCommerce\Utility\SQL;
use jtl\Connector\WooCommerce\Utility\Util;

class ProductAttr extends BaseController
{
    const PAYABLE = 'payable';
    const NOSEARCH = 'nosearch';

    // <editor-fold defaultstate="collapsed" desc="Pull">
    public function pullData(\WC_Product $product, ProductModel $model)
    {
        $productAttributes = [];

        if (!$product->is_type('variation')) {
            $attributes = $product->get_attributes();

            /**
             * @var string $slug
             * @var \WC_Product_Attribute $attribute
             */
            foreach ($attributes as $slug => $attribute) {
                if ($attribute->get_variation() || !$attribute->get_visible()) {
                    continue;
                }

                $productAttribute = $product->get_attribute($attribute->get_name());

                $values = explode(WC_DELIMITER, $productAttribute);

                foreach ($values as $i => $value) {
                    $i18n = (new ProductAttrI18nModel())
                        ->setProductAttrId(new Identity(Id::link([$slug, $i])))
                        ->setName($attribute->get_name())
                        ->setValue(trim($value))
                        ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage());

                    $productAttributes[] = (new ProductAttrModel())
                        ->setId($i18n->getProductAttrId())
                        ->setProductId(new Identity($product->get_id()))
                        ->setIsCustomProperty($attribute->is_taxonomy())
                        ->addI18n($i18n);;
                }
            }
        }

        if (!$product->is_purchasable()) {
            $isPurchasable = false;

            if ($product->has_child()) {
                $isPurchasable = true;

                foreach ($product->get_children() as $childId) {
                    $child = \wc_get_product($childId);
                    $isPurchasable = $isPurchasable & $child->is_purchasable();
                }
            }

            if (!$isPurchasable) {
                $attrI18n = (new ProductAttrI18nModel())
                    ->setProductAttrId(new Identity(self::PAYABLE))
                    ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage())
                    ->setName(self::PAYABLE)
                    ->setValue('false');

                $productAttributes[] = (new ProductAttrModel())
                    ->setId(new Identity(self::PAYABLE))
                    ->setIsCustomProperty(true)
                    ->addI18n($attrI18n);
            }
        }

        return $productAttributes;
    }
    // </editor-fold>

    // <editor-fold defaultstate="collapsed" desc="Push">
    public function pushData(ProductModel $product, array $model)
    {
        $wcProduct = \wc_get_product($product->getId()->getEndpoint());

        if ($wcProduct === false) {
            return;
        }

        if ($wcProduct->get_parent_id() !== 0) {
            return;
        }

        $attributes = $this->getVariationAttributes($wcProduct);

        foreach ($product->getAttributes() as $attribute) {
            foreach ($attribute->getI18ns() as $i18n) {
                if (!Util::getInstance()->isWooCommerceLanguage($i18n->getLanguageISO())) {
                    continue;
                }

                $this->saveAttribute($attribute, $i18n, $wcProduct->get_id(), $attributes);
                break;
            }
        }

        if (!empty($attributes)) {
            \update_post_meta($wcProduct->get_id(), '_product_attributes', $attributes);
        }
    }

    /**
     * Get variation attributes as they will be overwritten if they are not added again.
     *
     * @param \WC_Product $product The product.
     *
     * @return array The variation attributes.
     */
    private function getVariationAttributes(\WC_Product $product)
    {
        $attributes = [];

        $currentAttributes = $product->get_attributes();

        /**
         * @var string $slug The attributes unique slug.
         * @var \WC_Product_Attribute $attribute The attribute.
         */
        foreach ($currentAttributes as $slug => $attribute) {
            if ($attribute->get_variation()) {
                $attributes[$slug] = [
                    'name' => $attribute->get_name(),
                    'value' => implode(' ' . WC_DELIMITER . ' ', $attribute->get_options()),
                    'position' => $attribute->get_position(),
                    'is_visible' => $attribute->get_visible(),
                    'is_variation' => $attribute->get_variation(),
                    'is_taxonomy' => $attribute->get_taxonomy()
                ];
            }
        }

        return $attributes;
    }

    /**
     * Check if the attribute is a custom property or a simple attribute and save it regarding to that fact.
     *
     * @param ProductAttrModel $attribute The attribute.
     * @param ProductAttrI18nModel $i18n The used language attribute.
     * @param string $productId The product id.
     * @param array $attributes The product attributes.
     */
    private function saveAttribute(ProductAttrModel $attribute, ProductAttrI18nModel $i18n, $productId, array &$attributes)
    {
        if (strtolower($i18n->getName()) === strtolower(self::PAYABLE)) {
            \wp_update_post(['ID' => $productId, 'post_status' => 'private']);

            return;
        } elseif (strtolower($i18n->getName()) === strtolower(self::NOSEARCH)) {
            \update_post_meta($productId, '_visibility', 'catalog');

            return;
        }

        if ($attribute->getIsCustomProperty()) {
            $this->saveGlobalAttribute($attribute, $i18n);
        }

        $this->addNewAttributeOrEditExisting($i18n, [
            'name'             => \wc_clean($i18n->getName()),
            'value'            => \wc_clean($i18n->getValue()),
            'isCustomProperty' => $attribute->getIsCustomProperty(),
        ], $attributes);
    }

    private function saveGlobalAttribute(ProductAttrModel $attribute, ProductAttrI18nModel $i18n)
    {
        global $wpdb;

        $productId = (int)$attribute->getProductId()->getEndpoint();
        $taxonomy = \wc_attribute_taxonomy_name($i18n->getName());

        $this->handleAttributeTaxonomy($i18n, $taxonomy);

        list($termTaxonomyId, $termId) = $this->handleTermTaxonomy($i18n->getValue(), $productId, $taxonomy);

        $this->setTermRelationShips($productId, $termTaxonomyId, $termId);

        $wpdb->delete(
            $wpdb->term_taxonomy,
            ['term_id' => 0, 'taxonomy' => $taxonomy],
            ['%s', '%s']
        );

        \delete_transient('wc_attribute_taxonomies');
    }

    private function handleAttributeTaxonomy(ProductAttrI18nModel $i18n, $taxonomy)
    {
        global $wpdb;

        $slug = \wc_sanitize_taxonomy_name($i18n->getName());

        if (\taxonomy_exists($taxonomy)) {
            $wpdb->update(
                $wpdb->prefix . 'woocommerce_attribute_taxonomies',
                ['attribute_label' => $i18n->getName()],
                ['attribute_name' => $slug],
                ['%s'],
                ['%s']
            );
        } else {
            $wpdb->insert(
                $wpdb->prefix . 'woocommerce_attribute_taxonomies',
                [
                    'attribute_label'   => $i18n->getName(),
                    'attribute_name'    => $slug,
                    'attribute_type'    => 'text',
                    'attribute_orderby' => 'menu_order',
                    'attribute_public'  => 0,
                ],
                ['%s', '%s', '%s', '%s', '%d']
            );
        }
    }

    private function handleTermTaxonomy($value, $productId, $taxonomy)
    {
        global $wpdb;

        $results = Db::getInstance()->query(SQL::findTermsForProduct($productId, $taxonomy));

        if (empty($results)) {
            $result = $wpdb->insert(
                $wpdb->term_taxonomy,
                ['term_id' => 0, 'taxonomy' => $taxonomy],
                ['%d', '%s']
            );
            $termTaxonomyId = $result !== false ? $wpdb->insert_id : null;
        } else {
            $termTaxonomyId = $results[0]['term_taxonomy_id'];
        }

        $result = \term_exists($value, $taxonomy);

        // Term taxonomy created and term is not existing
        if (!is_null($termTaxonomyId) && !is_array($result)) {
            foreach ($results as $existingTerm) {
                $wpdb->delete(
                    $wpdb->terms,
                    ['term_id' => $existingTerm['term_id']],
                    ['%d']
                );

                $wpdb->delete(
                    $wpdb->term_relationships,
                    ['term_taxonomy_id' => $existingTerm['term_taxonomy_id'], 'product_id' => $productId],
                    ['%d', '%d']
                );
            }

            $result = $wpdb->insert(
                $wpdb->terms,
                ['name' => $value, 'slug' => \sanitize_title($value)],
                ['%s', '%s']
            );

            if ($result !== false) {
                $termId = $wpdb->insert_id;

                $wpdb->update(
                    $wpdb->term_taxonomy,
                    ['term_id' => $termId],
                    ['term_taxonomy_id' => $termTaxonomyId],
                    ['%d'],
                    ['%d']
                );

                return [$termTaxonomyId, $termId];
            }

            return [$termTaxonomyId, $results[0]['term_id']];
        }

        // TODO: undefined offset
        return [$termTaxonomyId, $results[0]['term_id']];
    }

    private function setTermRelationShips($productId, $termTaxonomyId, $termId)
    {
        global $wpdb;

        $result = $wpdb->get_var(SQL::findTermTaxonomyRelation($productId, $termTaxonomyId));

        if (is_null($result)) {
            $result = $wpdb->insert(
                $wpdb->term_relationships,
                [
                    'object_id'        => $productId,
                    'term_taxonomy_id' => $termTaxonomyId,
                    'term_order'       => 0,
                ],
                ['%d', '%d', '%d']
            );

            if ($result === false) {
                $wpdb->delete($wpdb->terms, ['term_id' => $termId], ['%d']);
            }
        }
    }

    private function addNewAttributeOrEditExisting(ProductAttrI18nModel $i18n, array $data, array &$attributes)
    {
        $slug = \wc_sanitize_taxonomy_name($i18n->getName());

        if (isset($attributes[$slug])) {
            $this->editAttribute($slug, $i18n->getValue(), $attributes);
        } else {
            $this->addAttribute($slug, $data, $attributes);
        }
    }

    private function editAttribute($slug, $value, array &$attributes)
    {
        $values = explode(WC_DELIMITER, $attributes[$slug]['value']);
        $values[] = \wc_clean($value);
        $attributes[$slug]['value'] = implode(' ' . WC_DELIMITER . ' ', $values);
    }

    private function addAttribute($slug, array $data, array &$attributes)
    {
        if ($data['isCustomProperty']) {
            $attributes['pa_' . $slug] = [
                'name'         => 'pa_' . $slug,
                'value'        => '',
                'position'     => 0,
                'is_visible'   => 1,
                'is_variation' => 0,
                'is_taxonomy'  => 1,
            ];
        } else {
            $attributes[$slug] = [
                'name'         => $data['name'],
                'value'        => $data['value'],
                'position'     => 0,
                'is_visible'   => 1,
                'is_variation' => 0,
                'is_taxonomy'  => (int)$data['isCustomProperty'],
            ];
        }
    }
    // </editor-fold>
}
