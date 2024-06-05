<?php

namespace JtlWooCommerceConnector\Controllers\Product;

use Jtl\Connector\Core\Definition\IdentityType;
use Jtl\Connector\Core\Exception\TranslatableAttributeException;
use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\Product as ProductModel;
use Jtl\Connector\Core\Model\ProductAttribute;
use Jtl\Connector\Core\Model\TranslatableAttribute;
use Jtl\Connector\Core\Model\TranslatableAttributeI18n;
use JtlWooCommerceConnector\Controllers\AbstractBaseController;
use Psr\Log\InvalidArgumentException;

/**
 * Class ProductAdvancedCustomFields
 *
 * @package JtlWooCommerceConnector\Controllers\Product
 */
class ProductAdvancedCustomFieldsController extends AbstractBaseController
{
    public function pullData(ProductModel &$product, \WC_Product $wcProduct): void
    {
        #finde alle acf fields
        $acfFields = $this->getAllAcfExcerpts();

        foreach ($wcProduct->get_meta_data() as $metaData) {
            $metaKey = $metaData->get_data()['key'];
            if (\in_array($metaKey, $acfFields)) {
                $attributeKey   = 'wc_acf_' . $metaKey;
                $attributeValue = $metaData->get_data()['value'];

                $this->setWawiAcfAttribute($product, $attributeKey, $attributeValue);
            }
        }
    }

    /**
     * @throws InvalidArgumentException
     * @throws TranslatableAttributeException
     */
    public function pushData(ProductModel $product): void
    {
        $productId     = $product->getId()->getEndpoint();
        $wawiAcfFields = [];

        foreach ($product->getAttributes() as $attribute) {
            foreach ($attribute->getI18ns() as $i18n) {
                if (
                    $this->util->isWooCommerceLanguage($i18n->getLanguageIso())
                    && \str_starts_with($i18n->getName(), 'wc_acf_')
                ) {
                    $meta_key        = \str_replace('wc_acf_', '', $i18n->getName());
                    $meta_value      = $i18n->getValue();
                    $wawiAcfFields[] = $meta_key;

                    $acfFieldPostName = $this->getAcfFieldPostName($meta_key);

                    if ($acfFieldPostName === null) {
                        continue;
                    }

                    \update_post_meta($productId, $meta_key, $meta_value);
                    \update_post_meta($productId, '_' . $meta_key, $acfFieldPostName);
                }
            }
        }

        $this->deleteRemovedAttributes($productId, $wawiAcfFields);
    }

    private function getAllAcfExcerpts(): array
    {
        global $wpdb;

        $query = \sprintf(
            "
            SELECT post_excerpt
            FROM {$wpdb->posts}
			WHERE `post_type` = '%s'
			AND `post_status` = '%s'",
            'acf-field',
            'publish'
        );

        return $this->db->queryList($query);
    }

    /**
* @param string $excerpt
* @return string|null
* @throws InvalidArgumentException
     */
    private function getAcfFieldPostName(string $excerpt): ?string
    {
        global $wpdb;

        $query = \sprintf(
            "
            SELECT post_name
            FROM {$wpdb->posts}
            WHERE post_status = '%s'
            AND post_type = '%s'
            AND post_excerpt = '%s'",
            'publish',
            'acf-field',
            $excerpt
        );

        return $this->db->queryOne($query);
    }

    /**
     * @throws TranslatableAttributeException
     * @throws \JsonException
     */
    private function setWawiAcfAttribute($product, $attributeKey, $attributeValue): void
    {
        $i18n = (new TranslatableAttributeI18n())
            ->setName($attributeKey)
            ->setValue($attributeValue)
            ->setLanguageIso($this->util->getWooCommerceLanguage());

        $attribute = (new ProductAttribute())
            ->setId(new Identity($product->getId()->getEndpoint() . '_' . $attributeKey))
            ->setI18ns($i18n);

        $product->addAttribute($attribute);
    }

    private function deleteRemovedAttributes($productId, $wawiAcfFields)
    {
        global $wpdb;

        $query = \sprintf(
            "
            SELECT post_excerpt
            FROM {$wpdb->posts}
            WHERE post_status = '%s'
            AND post_Type = '%s'",
            'publish',
            'acf-field'
        );

        $existingAcfFields = $this->db->queryList($query);
        $removedAcfFields  = \array_diff($existingAcfFields, $wawiAcfFields);

        if ($removedAcfFields) {
            $removedAcfFieldsUnderscore = \array_map(function ($value) {
                return '_' . $value;
            }, $removedAcfFields);

            $removedAcfFields = \array_merge($removedAcfFields, $removedAcfFieldsUnderscore);

            $query = \sprintf(
                "
                DELETE FROM {$wpdb->postmeta}
                WHERE post_id = '%s'
                AND meta_key IN (",
                $productId,
            );

            $firstIteration = true;

            foreach ($removedAcfFields as $field) {
                $query = $firstIteration
                    ? $query . '"' . $field . '"'
                    : $query . ', "' . $field . '"';

                $firstIteration = false;
            }

            $query = $query . ')';

            $this->db->query($query);
        }
    }
}
