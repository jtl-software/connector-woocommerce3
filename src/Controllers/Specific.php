<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2018 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Specific as SpecificModel;
use jtl\Connector\Model\SpecificI18n as SpecificI18nModel;
use jtl\Connector\Model\SpecificValue as SpecificValueModel;
use jtl\Connector\Model\SpecificValueI18n as SpecificValueI18nModel;
use JtlWooCommerceConnector\Controllers\Traits\DeleteTrait;
use JtlWooCommerceConnector\Controllers\Traits\PullTrait;
use JtlWooCommerceConnector\Controllers\Traits\PushTrait;
use JtlWooCommerceConnector\Controllers\Traits\StatsTrait;
use JtlWooCommerceConnector\Logger\WpErrorLogger;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\Util;
use WP_Error;
use WP_Query;

class Specific extends BaseController
{
    use PullTrait, PushTrait, DeleteTrait, StatsTrait;
    
    private static $idCache = [];
    
    protected function pullData($limit)
    {
        $specifics = [];
        
        $specificData = $this->database->query(SqlHelper::specificPull($limit));
        
        foreach ($specificData as $specificDataSet) {
            $specific = (new SpecificModel)
                ->setIsGlobal(true)
                ->setId(new Identity($specificDataSet['attribute_id']))
                ->setType('string'); //$specificDataSet['attribute_type']
            
            $specific->addI18n(
                (new SpecificI18nModel)
                    ->setSpecificId($specific->getId())
                    ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage())
                    ->setName($specificDataSet['attribute_label'])
            );
            
            // SpecificValues
            $specificValueData = $this->database->query(
                SqlHelper::specificValuePull(sprintf(
                    'pa_%s',
                    $specificDataSet['attribute_name']
                ))
            );
            
            foreach ($specificValueData as $specificValueDataSet) {
                $specificValue = (new SpecificValueModel)
                    ->setId(new Identity($specificValueDataSet['term_taxonomy_id']))
                    ->setSpecificId($specific->getId());
                
                $specificValue->addI18n((new SpecificValueI18nModel)
                    ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage())
                    ->setSpecificValueId($specificValue->getId())
                    ->setValue($specificValueDataSet['name']));
                
                $specific->addValue($specificValue);
            }
            
            $specifics[] = $specific;
        }
        
        return $specifics;
    }
    
    protected function pushData(SpecificModel $specific)
    {
        //WooFix
        $specific->setType('string');
        $meta = null;
        $defaultAvailable = false;
        
        foreach ($specific->getI18ns() as $i18n) {
            $languageSet = Util::getInstance()->isWooCommerceLanguage($i18n->getLanguageISO());
            
            if (strcmp($i18n->getLanguageISO(), 'ger') === 0) {
                $defaultAvailable = true;
            }
            
            if ($languageSet) {
                $meta = $i18n;
                break;
            }
        }
        
        //Fallback 'ger' if incorrect language code was given
        if ($meta === null && $defaultAvailable) {
            foreach ($specific->getI18ns() as $i18n) {
                if (strcmp($i18n->getLanguageISO(), 'ger') === 0) {
                    $meta = $i18n;
                }
            }
        }
        
        if ($meta !== null) {
            
            $attrName = wc_sanitize_taxonomy_name(Util::removeSpecialchars($meta->getName()));
            
            //STOP here if already exists
            $exId = Util::getAttributeTaxonomyIdByName($attrName);
            $endId = (int)$specific->getId()->getEndpoint();
            
            if ($exId !== 0) {
                if ($exId !== $endId) {
                    $attrId = $exId;
                } else {
                    $attrId = $endId;
                }
            } else {
                $attrId = $endId;
            }
            
            $endpoint = [
                'id'       => $attrId,
                'name'     => $meta->getName(),
                'slug'     => wc_sanitize_taxonomy_name(substr(trim($meta->getName()), 0, 27)),
                'type'     => 'select',
                'order_by' => 'menu_order',
                //'attribute_public'  => 0,
            ];
            
            if ($endpoint['id'] === 0) {
                $attributeId = wc_create_attribute($endpoint);
            } else {
                $attributeId = wc_update_attribute($endpoint['id'], $endpoint);
            }
            
            if ($attributeId instanceof WP_Error) {
                //var_dump($attributeId);
                //die();
                //return $termId->get_error_message();
                WpErrorLogger::getInstance()->logError($attributeId);
                
                return $specific;
                
            }
            
            $specific->getId()->setEndpoint($attributeId);
            
            //Get taxonomy
            $taxonomy = $attrName ?
                'pa_' . wc_sanitize_taxonomy_name(substr(trim($meta->getName()), 0, 27))
                : '';
            
            //Register taxonomy for current request
            register_taxonomy($taxonomy, null);
            
            /** @var SpecificValueModel $value */
            foreach ($specific->getValues() as $key => $value) {
                $value->getSpecificId()->setEndpoint($attributeId);
                $metaValue = null;
                $defaultValueAvailable = false;
                
                //Get i18n
                foreach ($value->getI18ns() as $i18n) {
                    $languageValueSet = Util::getInstance()->isWooCommerceLanguage($i18n->getLanguageISO());
                    
                    if (strcmp($i18n->getLanguageISO(), 'ger') === 0) {
                        $defaultValueAvailable = true;
                    }
                    
                    if ($languageValueSet) {
                        $metaValue = $i18n;
                        break;
                    }
                }
                
                //Fallback 'ger' if incorrect language code was given
                if ($meta === null && $defaultValueAvailable) {
                    foreach ($value->getI18ns() as $i18n) {
                        if (strcmp($i18n->getLanguageISO(), 'ger') === 0) {
                            $metaValue = $i18n;
                        }
                    }
                }
                
                if (is_null($metaValue)) {
                    continue;
                }
                
                $slug = wc_sanitize_taxonomy_name($metaValue->getValue());
                
                $endpointValue = [
                    'name' => $metaValue->getValue(),
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
                
                $endValId = (int)$value->getId()->getEndpoint();
                
                if (is_null($exValId) && $endValId === 0) {
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
                } elseif (is_null($exValId) && $endValId !== 0) {
                    $termId = \wp_update_term($endValId, $taxonomy, $endpointValue);
                } else {
                    $termId = $exValId;
                }
                
                if ($termId instanceof WP_Error) {
                    // var_dump($termId);
                    // die();
                    WpErrorLogger::getInstance()->logError($termId);
                    continue;
                }
                
                if (is_array($termId)) {
                    $termId = $termId['term_id'];
                }
                
                $value->getId()->setEndpoint($termId);
            }
        }
        
        return $specific;
    }
    
    protected function deleteData(SpecificModel $specific)
    {
        $specificId = (int)$specific->getId()->getEndpoint();
        
        if (!empty($specificId)) {
            
            unset(self::$idCache[$specific->getId()->getHost()]);
            
            $this->database->query(SqlHelper::removeSpecificLinking($specificId));
            $taxonomy = wc_attribute_taxonomy_name_by_id($specificId);
            /** @var \WC_Product_Attribute $specific */
            //$specific = wc_get_attribute($specificId);
            
            $specificValueData = $this->database->query(
                SqlHelper::forceSpecificValuePull($taxonomy)
            );
            
            $terms = [];
            foreach ($specificValueData as $specificValue) {
                $terms[] = $specificValue['slug'];
                
                $this->database->query(SqlHelper::removeSpecificValueLinking($specificValue['term_id']));
            }
            
            $products = new WP_Query([
                'post_type'      => ['product'],
                'posts_per_page' => -1,
                'tax_query'      => [
                    [
                        'taxonomy' => $taxonomy,
                        'field'    => 'slug',
                        'terms'    => $terms,
                        'operator' => 'IN',
                    ],
                ],
            ]);
            
            $isVariation = false;
            
            $posts = $products->get_posts();
            
            /** @var \WP_Post $post */
            foreach ($posts as $post) {
                $wcProduct = \wc_get_product($post->ID);
                $productSpecifics = $wcProduct->get_attributes();
                
                /** @var \WC_Product_Attribute $productSpecific */
                foreach ($productSpecifics as $productSpecific) {
                    if ($productSpecific->get_variation()) {
                        $isVariation = true;
                    }
                }
            }
            
            if (!$isVariation) {
                
                foreach ($specificValueData as $value) {
                    \wp_delete_term($value['term_id'], $taxonomy);
                }
                
                wc_delete_attribute($specificId);
                
            }
        }
        
        return $specific;
    }
    
    protected function getStats()
    {
        return $this->database->queryOne(SqlHelper::specificStats());
    }
}
