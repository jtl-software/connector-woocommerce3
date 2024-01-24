<?php

namespace JtlWooCommerceConnector\Controllers\Product;

use Jtl\Connector\Core\Definition\IdentityType;
use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\Product as ProductModel;
use Jtl\Connector\Core\Model\ProductAttribute;
use Jtl\Connector\Core\Model\TranslatableAttribute;
use Jtl\Connector\Core\Model\TranslatableAttributeI18n;
use JtlWooCommerceConnector\Controllers\AbstractBaseController;

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
            if (in_array($metaKey, $acfFields)) {
                $attributeKey   = 'wc_acf_' . $metaKey;
                $attributeValue = $metaData->get_data()['value'];

                $this->setWawiAcfAttribute($product, $attributeKey, $attributeValue);
            }
        }

    }

    public function pushData(ProductModel $product)
    {
        $productId = $product->getId()->getEndpoint();

        foreach ($product->getAttributes() as $attribute){
            foreach ($attribute->getI18ns() as $i18n) {
                if($this->util->isWooCommerceLanguage($i18n->getLanguageIso())
                    && str_starts_with($i18n->getName(), 'wc_acf_')
                ) {
                    $meta_key = str_replace('wc_acf_', '', $i18n->getName());
                    $meta_value = $i18n->getValue();

                    $acfFieldPostName = $this->getAcfFieldPostName($meta_key);

                    if ($acfFieldPostName === null) {
                        continue;
                    }

                    \update_post_meta($productId, $meta_key, $meta_value);
                    \update_post_meta($productId, '_' . $meta_key, $acfFieldPostName);
                }
            }
        }
    }

    private function getAllAcfExcerpts()
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

        $acfExcerpt = $this->db->queryList($query);

        return $acfExcerpt;
    }

    private function getAcfFieldPostName(string $excerpt)
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

}
