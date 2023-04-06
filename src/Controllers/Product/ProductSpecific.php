<?php

/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2018 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Product;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\Model\ProductAttr;
use jtl\Connector\Model\ProductSpecific as ProductSpecificModel;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\Util;
use WC_Product_Attribute;

class ProductSpecific extends BaseController
{
    // <editor-fold defaultstate="collapsed" desc="Pull">
    /**
     * @param ProductModel $model
     * @param \WC_Product $product
     * @param WC_Product_Attribute $attribute
     * @param $slug
     * @return array
     * @throws \InvalidArgumentException
     */
    public function pullData(
        ProductModel $model,
        \WC_Product $product,
        WC_Product_Attribute $attribute,
        $slug
    ): array {
        $name             = $attribute->get_name();
        $productAttribute = $product->get_attribute($name);
        $results          = [];
        $values           = \array_map('trim', \explode(',', $productAttribute));

        foreach ($values as $value) {
            if (empty($value)) {
                continue;
            }
            $results[] = $this->buildProductSpecific($slug, $value, $model);
        }

        return $results;
    }

    // </editor-fold>

    // <editor-fold defaultstate="collapsed" desc="Push">
    /**
     * @param $productId
     * @param $curAttributes
     * @param array $specificData
     * @param array $pushedJtlSpecifics
     * @param array $pushedJtlAttributes
     * @return array
     */
    public function pushData(
        $productId,
        $curAttributes,
        array $specificData = [],
        array $pushedJtlSpecifics = [],
        array $pushedJtlAttributes = []
    ): array {
        $newSpecifics = [];

        /** @var ProductSpecificModel $specific */
        foreach ($pushedJtlSpecifics as $specific) {
            $endpointId      = $specific->getId()->getEndpoint();
            $specificValueId = $specific->getSpecificValueId()->getEndpoint();
            if (empty($endpointId) || empty($specificValueId)) {
                continue;
            }
            $specificData[(int)$endpointId]['options'][] = (int)$specificValueId;
        }

        /**
         * FILTER Attributes & UPDATE EXISTING
         *
         * @var WC_Product_Attribute $wcProductAttribute
         */
        foreach ($curAttributes as $slug => $wcProductAttribute) {
            if (!\str_starts_with($slug, 'pa_')) {
                $newSpecifics[$slug] = [
                    'name'         => $wcProductAttribute->get_name(),
                    'value'        => Util::getInstance()->findAttributeValue(
                        $wcProductAttribute,
                        ...$pushedJtlAttributes
                    ),
                    'position'     => $wcProductAttribute->get_position(),
                    'is_visible'   => $wcProductAttribute->get_visible(),
                    'is_variation' => $wcProductAttribute->get_variation(),
                    'is_taxonomy'  => $wcProductAttribute->get_taxonomy(),
                ];
            } elseif (
                \str_starts_with($slug, 'pa_')
                && \array_key_exists($wcProductAttribute->get_id(), $specificData)
            ) {
                $cOldOptions = $wcProductAttribute->get_options();
                unset($specificData[$slug]);

                $newSpecifics[$slug] = [
                    'name'         => $wcProductAttribute->get_name(),
                    'value'        => '',
                    'position'     => $wcProductAttribute->get_position(),
                    'is_visible'   => $wcProductAttribute->get_visible(),
                    'is_variation' => $wcProductAttribute->get_variation(),
                    'is_taxonomy'  => $wcProductAttribute->get_taxonomy(),
                ];

                foreach ($cOldOptions as $value) {
                    if ($wcProductAttribute->get_variation()) {
                        continue;
                    }
                    \wp_remove_object_terms($productId, $value, $slug);
                }
            }
        }

        foreach ($specificData as $key => $specific) {
            $slug                = \wc_attribute_taxonomy_name_by_id($key);
            $newSpecifics[$slug] = [
                'name'         => $slug,
                'value'        => '',
                'position'     => null,
                'is_visible'   => 1,
                'is_variation' => 0,
                'is_taxonomy'  => $slug,
            ];
            $values              = [];

            if (isset($specific) && \count($specific['options']) > 0) {
                foreach ($specific['options'] as $valId) {
                    $term = \get_term_by('id', $valId, $slug);
                    if ($term instanceof \WP_Term) {
                        $values[] = $term->slug;
                    }
                }
            }

            \wp_set_object_terms($productId, $values, $slug, true);
        }

        return $newSpecifics;
    }
    // </editor-fold>

    // <editor-fold defaultstate="collapsed" desc="Methods">
    /**
     * @param $slug
     * @return string
     */
    public function getSpecificId($slug): string
    {
        $name = \substr($slug, 3);
        $val  = $this->database->query(SqlHelper::getSpecificId($name));

        return $val[0]['attribute_id'] ?? '';
    }

    /**
     * @param $slug
     * @param $value
     * @param ProductModel $result
     * @return ProductSpecificModel
     * @throws \InvalidArgumentException
     */
    private function buildProductSpecific($slug, $value, ProductModel $result): ProductSpecificModel
    {
        $parent     = (new ProductVaSpeAttrHandler());
        $valueId    = $parent->getSpecificValueId($slug, $value);
        $specificId = (new Identity())->setEndpoint($this->getSpecificId($slug));

        return (new ProductSpecificModel())
            ->setId($specificId)
            ->setProductId($result->getId())
            ->setSpecificValueId($valueId);
    }

    // </editor-fold>
}
