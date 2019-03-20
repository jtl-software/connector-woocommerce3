<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2018 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers;

use jtl\Connector\Model\Manufacturer as ManufacturerModel;
use jtl\Connector\Model\ManufacturerI18n as ManufacturerI18nModel;
use jtl\Connector\Model\Identity;
use JtlWooCommerceConnector\Controllers\Traits\DeleteTrait;
use JtlWooCommerceConnector\Controllers\Traits\PullTrait;
use JtlWooCommerceConnector\Controllers\Traits\PushTrait;
use JtlWooCommerceConnector\Controllers\Traits\StatsTrait;
use JtlWooCommerceConnector\Logger\WpErrorLogger;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;
use WP_Error;
use WP_Query;

class Manufacturer extends BaseController
{
    use PullTrait, PushTrait, DeleteTrait, StatsTrait;
    
    private static $idCache = [];
    
    protected function pullData($limit)
    {
        $manufacturers = [];
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_PERFECT_WOO_BRANDS)) {
            $sql              = SqlHelper::manufacturerPull($limit);
            $manufacturerData = $this->database->query($sql);
            
            foreach ($manufacturerData as $manufacturerDataSet) {
                $manufacturer = (new ManufacturerModel)
                    ->setId(new Identity($manufacturerDataSet['term_id']))
                    ->setName($manufacturerDataSet['name']);
                
                $manufacturer->addI18n(
                    (new ManufacturerI18nModel)
                        ->setManufacturerId($manufacturer->getId())
                        ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage())
                        ->setDescription($manufacturerDataSet['description'])
                );
                
                $manufacturers[] = $manufacturer;
            }
        }
        
        return $manufacturers;
    }
    
    protected function pushData(ManufacturerModel $manufacturer)
    {
        
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_PERFECT_WOO_BRANDS)) {
            $meta             = null;
            $defaultAvailable = false;
            
            foreach ($manufacturer->getI18ns() as $i18n) {
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
                foreach ($manufacturer->getI18ns() as $i18n) {
                    if (strcmp($i18n->getLanguageISO(), 'ger') === 0) {
                        $meta = $i18n;
                    }
                }
            }
            
            if ($meta !== null) {
                
                $name = wc_sanitize_taxonomy_name(substr(trim($manufacturer->getName()), 0, 27));
                
                $term = get_term_by('slug', $name, 'pwb-brand');
                
                remove_filter('pre_term_description', 'wp_filter_kses');
                
                if ($term === false) {
                    //Add term
                    /** @var WP_Term $newTerm */
                    $newTerm = \wp_insert_term(
                        $manufacturer->getName(),
                        'pwb-brand',
                        [
                            'description' => $meta->getDescription(),
                            'slug'        => $name,
                        ]
                    );
                    
                    if ($newTerm instanceof WP_Error) {
                        //  var_dump($newTerm);
                        // die();
                        $error = new WP_Error('invalid_taxonomy', 'Could not create manufacturer.');
                        WpErrorLogger::getInstance()->logError($error);
                        WpErrorLogger::getInstance()->logError($newTerm);
                    }
                    $term = $newTerm;
                } else {
                    
                    wp_update_term($term->term_id, 'pwb-brand', [
                        'name'        => $manufacturer->getName(),
                        'description' => $meta->getDescription(),
                    ]);
                }
    
                add_filter('pre_term_description', 'wp_filter_kses');
                
                if ($term instanceof \WP_Term) {
                    $manufacturer->getId()->setEndpoint($term->term_id);
                    foreach ($manufacturer->getI18ns() as $i18n) {
                        /** @var ManufacturerI18nModel $i18n */
                        $i18n->getManufacturerId()->setEndpoint($term->term_id);
                    }
                }
            }
        }
        
        return $manufacturer;
    }
    
    protected function deleteData(ManufacturerModel $manufacturer)
    {
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_PERFECT_WOO_BRANDS)) {
            $manufacturerId = (int)$manufacturer->getId()->getEndpoint();
            
            if ( ! empty($manufacturerId)) {
                
                unset(self::$idCache[$manufacturer->getId()->getHost()]);
                
                wp_delete_term($manufacturerId, 'pwb-brand');
            }
        }
        
        return $manufacturer;
    }
    
    protected function getStats()
    {
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_PERFECT_WOO_BRANDS)) {
            return $this->database->queryOne(SqlHelper::manufacturerStats());
        } else {
            return 0;
        }
    }
}
