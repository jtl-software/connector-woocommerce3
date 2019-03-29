<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Product;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\Model\ProductVariation as ProductVariationModel;
use jtl\Connector\Model\ProductVariationI18n as ProductVariationI18nModel;
use jtl\Connector\Model\ProductVariationValue as ProductVariationValueModel;
use jtl\Connector\Model\ProductVariationValueI18n as ProductVariationValueI18nModel;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Utilities\Id;

class ProductVariation extends BaseController
{
    
    /**
     * @param ProductModel $model
     * @param \WC_Product_Attribute $attribute
     * @param string $slug
     * @param string $languageIso
     * @return ProductVariationModel|null
     */
    public function pullDataParent(
        ProductModel $model,
        \WC_Product_Attribute $attribute,
        $slug = '',
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
        
        if ($attribute->is_taxonomy()) {
            $terms = $attribute->get_terms();
            
            if (!is_array($terms)) {
                return null;
            }
            
            /** @var \WP_Term $term */
            foreach ($terms as $sort => $term) {
                $valueId = new Identity(Id::link([$id->getEndpoint(), $term->term_id]));
                
                $productVariation->addValue((new ProductVariationValueModel())
                    ->setId($valueId)
                    ->setProductVariationId($id)
                    ->setSort($sort)
                    ->addI18n((new ProductVariationValueI18nModel())
                        ->setProductVariationValueId($valueId)
                        ->setName($term->name)
                        ->setLanguageISO($languageIso))
                );
            }
        } else {
            $options = $attribute->get_options();
            
            foreach ($options as $sort => $option) {
                $valueId = new Identity(Id::link([$id->getEndpoint(), \sanitize_key($option)]));
                
                $productVariation->addValue((new ProductVariationValueModel())
                    ->setId($valueId)
                    ->setProductVariationId($id)
                    ->setSort($sort)
                    ->addI18n((new ProductVariationValueI18nModel())
                        ->setProductVariationValueId($valueId)
                        ->setName($option)
                        ->setLanguageISO($languageIso))
                );
            }
        }
        
        return $productVariation;
    }
    
    
    /**
     * @param \WC_Product $product
     * @param ProductModel $model
     * @param string $languageIso
     * @return ProductVariationModel|null
     */
    public function pullDataChild(\WC_Product $product, ProductModel $model, $languageIso = '')
    {
        $parentProduct = \wc_get_product($product->get_parent_id());
        $productVariation = null;
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
                    
                    $productVariation->addValue((new ProductVariationValueModel)
                        ->setId($valueId)
                        ->setProductVariationId($id)
                        ->setSort($sort)
                        ->addI18n((new ProductVariationValueI18nModel)
                            ->setProductVariationValueId($valueId)
                            ->setName($term->name)
                            ->setLanguageISO($languageIso))
                    );
                }
            } else {
                $value = $product->get_attribute($slug);
                
                foreach ($attribute->get_options() as $sort => $option) {
                    if ($option !== $value) {
                        continue;
                    }
                    
                    $valueId = new Identity(Id::link([$id->getEndpoint(), \sanitize_key($option)]));
                    
                    $productVariation->addValue((new ProductVariationValueModel)
                        ->setId($valueId)
                        ->setProductVariationId($id)
                        ->setSort($sort)
                        ->addI18n((new ProductVariationValueI18nModel)
                            ->setProductVariationValueId($valueId)
                            ->setName($option)
                            ->setLanguageISO($languageIso))
                    );
                }
            }
        }
        
        return $productVariation;
    }
}
