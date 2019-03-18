<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Product;

use Defuse\Crypto\Key;
use jtl\Connector\Linker\ChecksumLinker;
use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\Model\ProductChecksum;
use jtl\Connector\Model\ProductVariation as ProductVariationModel;
use jtl\Connector\Model\ProductVariationI18n;
use jtl\Connector\Model\ProductVariationValue;
use jtl\Connector\Model\ProductVariationValueI18n;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Logger\WpErrorLogger;
use JtlWooCommerceConnector\Utilities\Id;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\Util;
use WP_Error;

class ProductVariation extends BaseController
{
    private $values = [];
    
    public function pullData(\WC_Product $product, ProductModel $model)
    {
        $return = [];
        
        if ($product instanceof \WC_Product_Variable) {
            $this->pullParent($product, $model, $return);
        } elseif ($product instanceof \WC_Product_Variation) {
            $this->pullChild($product, $model, $return);
        }
        
        return $return;
    }
    
    private function pullParent(\WC_Product $product, ProductModel $model, &$return)
    {
        /**
         * @var string                $slug
         * @var \WC_Product_Attribute $attribute
         */
        foreach ($product->get_attributes() as $slug => $attribute) {
            if ( ! $attribute->get_variation()) {
                continue;
            }
            
            $id = new Identity(Id::link([$model->getId()->getEndpoint(), $attribute->get_id()]));
            
            $productVariation = (new ProductVariationModel())
                ->setId($id)
                ->setProductId($model->getId())
                ->setType(ProductVariationModel::TYPE_SELECT)
                ->addI18n((new ProductVariationI18n())
                    ->setProductVariationId($id)
                    ->setName(\wc_attribute_label($attribute->get_name()))
                    ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage()));
            
            if ($attribute->is_taxonomy()) {
                $terms = $attribute->get_terms();
                
                if ( ! is_array($terms)) {
                    continue;
                }
                
                /** @var \WP_Term $term */
                foreach ($terms as $sort => $term) {
                    $valueId = new Identity(Id::link([$id->getEndpoint(), $term->term_id]));
                    
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
            } else {
                $options = $attribute->get_options();
                
                foreach ($options as $sort => $option) {
                    $valueId = new Identity(Id::link([$id->getEndpoint(), \sanitize_key($option)]));
                    
                    $productVariation->addValue((new ProductVariationValue())
                        ->setId($valueId)
                        ->setProductVariationId($id)
                        ->setSort($sort)
                        ->addI18n((new ProductVariationValueI18n())
                            ->setProductVariationValueId($valueId)
                            ->setName($option)
                            ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage()))
                    );
                }
            }
            
            $return[] = $productVariation;
        }
    }
    
    private function pullChild(\WC_Product $product, ProductModel $model, &$return)
    {
        $parent = \wc_get_product($product->get_parent_id());
        
        /**
         * @var string                $slug
         * @var \WC_Product_Attribute $attribute
         */
        foreach ($parent->get_attributes() as $slug => $attribute) {
            $id = new Identity(Id::link([$parent->get_id(), $attribute->get_id()]));
            
            $productVariation = (new ProductVariationModel())
                ->setId($id)
                ->setProductId($model->getId())
                ->setType(ProductVariationModel::TYPE_SELECT)
                ->addI18n((new ProductVariationI18n())
                    ->setProductVariationId($id)
                    ->setName(\wc_attribute_label($attribute->get_name()))
                    ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage()));
            
            if ($attribute->is_taxonomy()) {
                $terms = $attribute->get_terms();
                
                if ( ! is_array($terms)) {
                    continue;
                }
                
                $value = $product->get_attribute($slug);
                
                /** @var \WP_Term $term */
                foreach ($terms as $sort => $term) {
                    if ($term->name !== $value) {
                        continue;
                    }
                    
                    $valueId = new Identity(Id::link([$id->getEndpoint(), $term->term_id]));
                    
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
            } else {
                $value = $product->get_attribute($slug);
                
                foreach ($attribute->get_options() as $sort => $option) {
                    if ($option !== $value) {
                        continue;
                    }
                    
                    $valueId = new Identity(Id::link([$id->getEndpoint(), \sanitize_key($option)]));
                    
                    $productVariation->addValue((new ProductVariationValue())
                        ->setId($valueId)
                        ->setProductVariationId($id)
                        ->setSort($sort)
                        ->addI18n((new ProductVariationValueI18n())
                            ->setProductVariationValueId($valueId)
                            ->setName($option)
                            ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage()))
                    );
                }
            }
            
            $return[] = $productVariation;
        }
    }
    
    public function pushData(ProductModel $product)
    {
        if ($product->getIsMasterProduct()) {
            $this->pushDataParent($product);
        } else {
            $this->pushDataChild($product);
        }
    }
    
    private function pushDataParent(ProductModel $data)
    {
        $wcProduct = \wc_get_product($data->getId()->getEndpoint());
        
        if ($wcProduct === false) {
            return;
        }
        
        $currentWCProductAttributes = [];
        
        $pushedSpecifics  = []; //VAriationspecs
        $productSpecifics = $wcProduct->get_attributes();
        
        /**
         * @var string                $slug
         * @var \WC_Product_Attribute $productSpecifics
         */
        foreach ($productSpecifics as $slug => $product_specific) {
            if ( ! $product_specific->get_variation()) {
                $currentWCProductAttributes[$slug] = [
                    'name'         => $product_specific->get_name(),
                    'value'        => implode(' ' . WC_DELIMITER . ' ', $product_specific->get_options()),
                    'position'     => $product_specific->get_position(),
                    'is_visible'   => $product_specific->get_visible(),
                    'is_variation' => $product_specific->get_variation(),
                    'is_taxonomy'  => $product_specific->get_taxonomy(),
                ];
            }
        }
        
        //GENERATE $variationSpecificData
        $variations            = $data->getVariations();
        $variationSpecificData = [];
        foreach ($variations as $variation) {
            foreach ($variation->getI18ns() as $variationI18n) {
                $taxonomyName = \wc_sanitize_taxonomy_name($variationI18n->getName());
                
                if ( ! Util::getInstance()->isWooCommerceLanguage($variationI18n->getLanguageISO())) {
                    continue;
                }
                
                $values = [];
                
                $this->values = $variation->getValues();
                usort($this->values, [$this, 'sortI18nValues']);
                
                foreach ($this->values as $vv) {
                    foreach ($vv->getI18ns() as $valueI18n) {
                        if ( ! Util::getInstance()->isWooCommerceLanguage($valueI18n->getLanguageISO())) {
                            continue;
                        }
                        
                        $values[] = $valueI18n->getName();
                    }
                }
                
                $variationSpecificData[$taxonomyName] = [
                    'name'         => $variationI18n->getName(),
                    'value'        => implode(' ' . WC_DELIMITER . ' ', $values),
                    'position'     => $variation->getSort(),
                    'is_visible'   => 0,
                    'is_variation' => 1,
                    'is_taxonomy'  => 0,
                ];
            }
        }
        // Update global specifics
        foreach ($variationSpecificData as $key => $variationSpecific) {
            $taxonomy       = 'pa_' . wc_sanitize_taxonomy_name(substr(trim($key), 0, 27));
            $specificID     = $this->database->query(SqlHelper::getSpecificId(sprintf('%s', $key)));
            $specificExists = isset($specificID[0]['attribute_id']) ? true : false;
            $options        = [];
            
            if (array_key_exists($taxonomy, $currentWCProductAttributes)) {
                $currentWCProductAttributes[$taxonomy]['is_variation'] = true;
            }
            
            if ($specificExists) {
                
                //Get existing values
                $pushedValues = explode(' ' . WC_DELIMITER . ' ', $variationSpecific['value']);
                foreach ($pushedValues as $pushedValue) {
                    
                    //check if value did not exists
                    $specificValueId = $this->getSpecificValueId(
                        $taxonomy,
                        trim($pushedValue)
                    );
                    
                    $termId = (int)$specificValueId->getEndpoint();
                    
                    if ( ! $termId > 0) {
                        //Add values
                        $newTerm = \wp_insert_term(
                            $pushedValue,
                            $taxonomy
                        );
                        
                        if ($newTerm instanceof WP_Error) {
                            //  var_dump($newTerm);
                            // die();
                            WpErrorLogger::getInstance()->logError($newTerm);
                            continue;
                        }
                        
                        $termId = $newTerm['term_id'];
                    }
                    
                    if (array_key_exists($taxonomy, $currentWCProductAttributes)) {
                        $currentWCProductAttributes[$taxonomy]['is_variation'] = true;
                        
                        $options = explode(
                            ' ' . WC_DELIMITER . ' ',
                            $currentWCProductAttributes[$taxonomy]['value']
                        );
                        
                        if (( ! in_array($termId, $options))) {
                            array_push($options, $termId);
                        }
                        
                        $currentWCProductAttributes[$taxonomy]['value'] = implode(
                            ' ' . WC_DELIMITER . ' ',
                            $options
                        );
                        
                    } else {
                        array_push($options, $termId);
                        $currentWCProductAttributes[$taxonomy] = [
                            'name'         => $taxonomy,
                            'value'        => implode(
                                ' ' . WC_DELIMITER . ' ',
                                $options
                            ),
                            'position'     => 0,
                            'is_visible'   => Util::showVariationSpecificsOnProductPageEnabled(),
                            'is_variation' => true,
                            'is_taxonomy'  => $taxonomy,
                        ];
                    }
                    
                    foreach ($options as $key => $value) {
                        $options[$key] = (int)$value;
                    }
                    
                    wp_set_object_terms(
                        $wcProduct->get_id(),
                        $options,
                        $currentWCProductAttributes[$taxonomy]['name'],
                        true
                    );
                }
            } else {
                //Create specific and add values
                $endpoint = [
                    'id'       => '',
                    'name'     => $variationSpecific['name'],
                    'slug'     => $taxonomy,
                    'type'     => 'select',
                    'order_by' => 'menu_order',
                    //'attribute_public'  => 0,
                ];
                
                $options = explode(
                    ' ' . WC_DELIMITER . ' ',
                    $variationSpecific['value']
                );
                
                $attributeId = wc_create_attribute($endpoint);
                
                if ($attributeId instanceof WP_Error) {
                    //var_dump($attributeId);
                    //die();
                    //return $termId->get_error_message();
                    WpErrorLogger::getInstance()->logError($attributeId);
                    
                    return;
                }
                
                //Register taxonomy for current request
                register_taxonomy($taxonomy, null);
                
                $assignedValueIds = [];
                
                foreach ($options as $key => $value) {
                    $slug = wc_sanitize_taxonomy_name($value);
                    
                    $endpointValue = [
                        'name' => $value,
                        'slug' => $slug,
                    ];
                    
                    $exValId = $this->database->query(
                        SqlHelper::getSpecificValueId(
                            $taxonomy,
                            $endpointValue['name']
                        )
                    );
                    
                    if (count($exValId) >= 1) {
                        if (isset($exValId[0]['term_id'])) {
                            $exValId = $exValId[0]['term_id'];
                        } else {
                            $exValId = null;
                        }
                    } else {
                        $exValId = null;
                    }
                    
                    if (is_null($exValId)) {
                        $newTerm = \wp_insert_term(
                            $endpointValue['name'],
                            $taxonomy
                        );
                        
                        if ($newTerm instanceof WP_Error) {
                            //  var_dump($newTerm);
                            // die();
                            WpErrorLogger::getInstance()->logError($newTerm);
                            continue;
                        }
                        
                        $termId = $newTerm['term_id'];
                        
                        if ($termId instanceof WP_Error) {
                            // var_dump($termId);
                            // die();
                            WpErrorLogger::getInstance()->logError($termId);
                            continue;
                        }
                        
                        $assignedValueIds[] = $termId;
                    }
                }
                
                $currentWCProductAttributes[$taxonomy] = [
                    'name'         => $taxonomy,
                    'value'        => implode(
                        ' ' . WC_DELIMITER . ' ',
                        $options
                    ),
                    'position'     => null,
                    'is_visible'   => Util::showVariationSpecificsOnProductPageEnabled(),
                    'is_variation' => true,
                    'is_taxonomy'  => $taxonomy,
                ];
                
                wp_set_object_terms(
                    $wcProduct->get_id(),
                    $assignedValueIds,
                    $currentWCProductAttributes[$taxonomy]['name'],
                    true
                );
            }
        }
        $old = \get_post_meta($wcProduct->get_id(), '_product_attributes',true);
        $debug =  \update_post_meta($wcProduct->get_id(), '_product_attributes', $currentWCProductAttributes, $old);
        
        $debug = $debug;
    }
    
    private function pushDataChild(
        ProductModel $product
    ) {
        $updatedAttributeKeys = [];
        
        foreach ($product->getVariations() as $variation) {
            foreach ($variation->getValues() as $value) {
                foreach ($variation->getI18ns() as $variationI18n) {
                    if ( ! Util::getInstance()->isWooCommerceLanguage($variationI18n->getLanguageISO())) {
                        continue;
                    }
                    
                    foreach ($value->getI18ns() as $i18n) {
                        $key                    =
                            'attribute_pa_' . wc_sanitize_taxonomy_name(
                                substr(
                                    trim(
                                        $variationI18n->getName()
                                    ),
                                    0,
                                    27
                                )
                            );
                        $updatedAttributeKeys[] = $key;
                        
                        \update_post_meta($product->getId()->getEndpoint(), $key,
                            wc_sanitize_taxonomy_name($i18n->getName()));
                    }
                    break;
                }
            }
        }
        /*	$attributesToDelete = $this->database->queryList( SqlHelper::productVariationObsoletes(
                $product->getId()->getEndpoint(),
                $updatedAttributeKeys
            ) );
            
            foreach ( $attributesToDelete as $key ) {
                \delete_post_meta( $product->getId()->getEndpoint(), $key );
            }*/
    }
    
    private function sortI18nValues(
        ProductVariationValue $a,
        ProductVariationValue $b
    ) {
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
    
    private function getSpecificValueId(
        $slug,
        $value
    ) {
        $val    = $this->database->query(SqlHelper::getSpecificValueId($slug, $value));
        
        if(count($val) === 0){
            $result = (new Identity);
        }else {
            $result = isset($val[0]['endpoint_id'])
                      && isset($val[0]['host_id'])
                      && !is_null($val[0]['endpoint_id'])
                      && !is_null($val[0]['host_id'])
                ? (new Identity)->setEndpoint($val[0]['endpoint_id'])->setHost($val[0]['host_id'])
                : (new Identity)->setEndpoint($val[0]['term_taxonomy_id']);
        }
        
        return $result;
    }
}
