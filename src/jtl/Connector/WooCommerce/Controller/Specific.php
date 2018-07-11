<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2018 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller;

use jtl\Connector\Model\Specific as SpecificModel;
use jtl\Connector\Model\SpecificI18n as SpecificI18nModel;
use jtl\Connector\Model\SpecificValue as SpecificValueModel;
use jtl\Connector\Model\SpecificValueI18n as SpecificValueI18nModel;
use jtl\Connector\Model\Identity;
use jtl\Connector\WooCommerce\Controller\Traits\DeleteTrait;
use jtl\Connector\WooCommerce\Controller\Traits\PullTrait;
use jtl\Connector\WooCommerce\Controller\Traits\PushTrait;
use jtl\Connector\WooCommerce\Controller\Traits\StatsTrait;
use jtl\Connector\WooCommerce\Logger\WpErrorLogger;
use jtl\Connector\WooCommerce\Utility\SQL;
use jtl\Connector\WooCommerce\Utility\Util;

class Specific extends BaseController
{
    use PullTrait, PushTrait, DeleteTrait, StatsTrait;
    
    private static $idCache = [];
    
    protected function pullData($limit)
    {
        $specifics = [];
        
        $specificData = $this->database->query(SQL::specificPull($limit));
        
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
                SQL::specificValuePull(sprintf(
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
        
        foreach ($specific->getI18ns() as $i18n) {
            if (Util::getInstance()->isWooCommerceLanguage($i18n->getLanguageISO())) {
                $meta = $i18n;
                break;
            }
        }
        
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
            'type'     => 'select',
            'order_by' => 'menu_order',
            //'attribute_public'  => 0,
        ];
        
        if ($endpoint['id'] === 0) {
            $attributeId = wc_create_attribute($endpoint);
        } else {
            $attributeId = wc_update_attribute($endpoint['id'], $endpoint);
        }
        
        if ($endpoint['id'] === 0 && $attributeId instanceof \WP_Error) {
            return $attributeId->get_error_message();
        }
        
        $specific->getId()->setEndpoint($attributeId);
        
        //Get taxonomy
        $taxonomy = wc_attribute_taxonomy_name($attrName);
        //Register taxonomy for current request
        register_taxonomy($taxonomy, null);
        
        /** @var SpecificValueModel $value */
        foreach ($specific->getValues() as $key => $value) {
            $value->getSpecificId()->setEndpoint($attributeId);
            $metaValue = null;
            
            //Get i18n
            foreach ($value->getI18ns() as $i18n) {
                if (Util::getInstance()->isWooCommerceLanguage($i18n->getLanguageISO())) {
                    $metaValue = $i18n;
                    break;
                }
            }
            
            if (is_null($metaValue)){
                continue;
            }
            
            $slug = wc_sanitize_taxonomy_name($metaValue->getValue());
            
            $endpointValue = [
                'name' => $metaValue->getValue(),
                'slug' => $slug,
            ];
            
            $exValId = $this->database->query(
                SQL::getSpecificValueId(
                    $taxonomy,
                    $endpointValue['name']
                )
            );
            
            if (isset($exValId) && count($exValId) > 1) {
                if (isset($exValId[0]['term_id'])) {
                    $exValId = $exValId[0]['term_id'];
                } else {
                    $exValId = null;
                }
            } else {
                $exValId = null;
            }
            
            $endValId = (int)$value->getId()->getEndpoint();
            
            if ($exValId === null && $endValId === 0) {
                $termId = \wp_insert_term(
                    $endpointValue['name'],
                    $taxonomy
                )['term_id'];
            } elseif ($exValId === null && $endValId !== 0) {
                $termId = \wp_update_term($endValId, $taxonomy, $endpointValue);
            } else {
                $termId = $exValId;
            }
            
            if ($endValId === 0 && $termId instanceof \WP_Error) {
                return $termId->get_error_message();
            }
            
            $value->getId()->setEndpoint($termId);
        }
        
        return $specific;
    }
    
    protected function deleteData(SpecificModel $specific)
    {
        $specificId = (int)$specific->getId()->getEndpoint();
        
        if (!empty($specificId)) {
            $taxonomy = wc_attribute_taxonomy_name_by_id($specificId);
            
            $specificValueData = $this->database->query(
                SQL::forceSpecificValuePull($taxonomy)
            );
            
            foreach ($specificValueData as $value) {
                \wp_delete_term($value['term_id'], $taxonomy);
            }
            
            wc_delete_attribute($specificId);
            
            unset(self::$idCache[$specific->getId()->getHost()]);
        }
        
        return $specific;
    }
    
    protected function getStats()
    {
        return $this->database->queryOne(SQL::specificStats());
    }
}
