<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Controllers\Product;

use Jtl\Connector\Core\Exception\MustNotBeNullException;
use Jtl\Connector\Core\Exception\TranslatableAttributeException;
use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\Product as ProductModel;
use Jtl\Connector\Core\Model\ProductSpecific as ProductSpecificModel;
use Jtl\Connector\Core\Model\TranslatableAttribute;
use JtlWooCommerceConnector\Controllers\AbstractBaseController;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use Psr\Log\InvalidArgumentException;
use WC_Product_Attribute;

class ProductSpecificController extends AbstractBaseController
{
    // <editor-fold defaultstate="collapsed" desc="Pull">
    /**
     * @param ProductModel         $model
     * @param \WC_Product          $product
     * @param WC_Product_Attribute $attribute
     * @param string               $slug
     * @return ProductSpecificModel[]
     * @throws \InvalidArgumentException
     */
    public function pullData(
        ProductModel $model,
        \WC_Product $product,
        WC_Product_Attribute $attribute,
        string $slug
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
     * @param int                                               $productId
     * @param array<string, WC_Product_Attribute>               $curAttributes
     * @param array<int, array<string, array<int, int|string>>> $specificData
     * @param ProductSpecificModel[]                            $pushedJtlSpecifics
     * @param TranslatableAttribute[]                           $pushedJtlAttributes
     * @return array<string, array<string, bool|int|string|null>>
     * @throws TranslatableAttributeException
     * @throws MustNotBeNullException
     * @throws \TypeError
     * @throws \InvalidArgumentException
     */
    public function pushData(
        int $productId,
        array $curAttributes,
        array $specificData = [],
        array $pushedJtlSpecifics = [],
        array $pushedJtlAttributes = []
    ): array {
        $newSpecifics = [];

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
            if (!\str_starts_with((string)$slug, 'pa_')) {
                $newSpecifics[$slug] = [
                    'name'         => $wcProductAttribute->get_name(),
                    'value'        => $this->util->findAttributeValue(
                        $wcProductAttribute,
                        ...$pushedJtlAttributes
                    ),
                    'position'     => $wcProductAttribute->get_position(),
                    'is_visible'   => $wcProductAttribute->get_visible(),
                    'is_variation' => $wcProductAttribute->get_variation(),
                    'is_taxonomy'  => $wcProductAttribute->get_taxonomy(),
                ];
            } elseif (
                \str_starts_with((string)$slug, 'pa_')
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
                    \wp_remove_object_terms($productId, $value, (string)$slug);
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

            if (\count($specific['options']) > 0) {
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
     * @param string $slug
     * @return string
     * @throws InvalidArgumentException
     */
    public function getSpecificId(string $slug): string
    {
        $name = \substr($slug, 3);
        /** @var array<int, array<string, int|string>> $val */
        $val = $this->db->query(SqlHelper::getSpecificId($name));

        return $val[0]['attribute_id'] ? (string)$val[0]['attribute_id'] : '';
    }

    /**
     * @param string       $slug
     * @param string       $value
     * @param ProductModel $result
     * @return ProductSpecificModel
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    private function buildProductSpecific(string $slug, string $value, ProductModel $result): ProductSpecificModel
    {
        $parent     = (new ProductVaSpeAttrHandlerController($this->db, $this->util));
        $valueId    = $parent->getSpecificValueId($slug, $value);
        $specificId = (new Identity())->setEndpoint($this->getSpecificId($slug));

        return (new ProductSpecificModel())
            ->setId($specificId)
            ->setSpecificValueId($valueId);
    }

    // </editor-fold>
}
