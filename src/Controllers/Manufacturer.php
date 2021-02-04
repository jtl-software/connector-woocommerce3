<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2018 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Manufacturer as ManufacturerModel;
use jtl\Connector\Model\ManufacturerI18n as ManufacturerI18nModel;
use JtlWooCommerceConnector\Controllers\Traits\DeleteTrait;
use JtlWooCommerceConnector\Controllers\Traits\PullTrait;
use JtlWooCommerceConnector\Controllers\Traits\PushTrait;
use JtlWooCommerceConnector\Controllers\Traits\StatsTrait;
use JtlWooCommerceConnector\Logger\WpErrorLogger;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;
use WP_Error;

class Manufacturer extends BaseController
{
    use PullTrait, PushTrait, DeleteTrait, StatsTrait;
    
    private static $idCache = [];
    
    protected function pullData($limit)
    {
        $manufacturers = [];
        if (SupportedPlugins::isPerfectWooCommerceBrandsActive()) {
            $sql = SqlHelper::manufacturerPull($limit);
            $manufacturerData = $this->database->query($sql);
            
            foreach ($manufacturerData as $manufacturerDataSet) {
                $manufacturer = (new ManufacturerModel)
                    ->setId(new Identity($manufacturerDataSet['term_id']))
                    ->setName($manufacturerDataSet['name']);
                
                $i18n = (new ManufacturerI18nModel)
                    ->setManufacturerId($manufacturer->getId())
                    ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage())
                    ->setDescription($manufacturerDataSet['description']);
                
                if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO)
                    || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO_PREMIUM)) {
                    $taxonomySeo = get_option('wpseo_taxonomy_meta');
                    if (isset($taxonomySeo['pwb-brand'])) {
                        foreach ($taxonomySeo['pwb-brand'] as $brandKey => $seoData) {
                            if ($brandKey === (int)$manufacturerDataSet['term_id']) {
                                $i18n->setMetaDescription(isset($seoData['wpseo_desc']) ? $seoData['wpseo_desc'] : '')
                                    ->setMetaKeywords(isset($seoData['wpseo_focuskw']) ? $seoData['wpseo_focuskw'] : $manufacturerDataSet['name'])
                                    ->setTitleTag(isset($seoData['wpseo_title']) ? $seoData['wpseo_title'] : '');
                            }
                        }
                    }
                }
                
                $manufacturer->addI18n(
                    $i18n
                );
                
                $manufacturers[] = $manufacturer;
            }
        }
        
        return $manufacturers;
    }
    
    protected function pushData(ManufacturerModel $manufacturer)
    {
        if (SupportedPlugins::isPerfectWooCommerceBrandsActive()) {
            $meta = (new ManufacturerI18nModel())
                ->setManufacturerId($manufacturer->getId());

            foreach ($manufacturer->getI18ns() as $i18n) {
                if (Util::getInstance()->isWooCommerceLanguage($i18n->getLanguageISO())) {
                    $meta = $i18n;
                    break;
                }
            }

            $name = wc_sanitize_taxonomy_name(substr(trim($manufacturer->getName()), 0, 27));
            $term = get_term_by('slug', $name, 'pwb-brand');

            remove_filter('pre_term_description', 'wp_filter_kses');

            if ($term === false) {
                //Add term
                /** @var \WP_Term $newTerm */
                $newTerm = \wp_insert_term(
                    $manufacturer->getName(),
                    'pwb-brand',
                    [
                        'description' => $meta->getDescription(),
                        'slug' => $name,
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

                if (!$term instanceof \WP_Term) {
                    $term = get_term_by('id', $term['term_id'], 'pwb-brand');
                }

            } else {
                wp_update_term($term->term_id, 'pwb-brand', [
                    'name' => $manufacturer->getName(),
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

                if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO)
                    || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO_PREMIUM)
                ) {
                    $taxonomySeo = \get_option('wpseo_taxonomy_meta', false);

                    if ($taxonomySeo === false) {
                        $taxonomySeo = ['pwb-brand' => []];
                    }

                    if (!isset($taxonomySeo['pwb-brand'])) {
                        $taxonomySeo['pwb-brand'] = [];
                    }
                    $exists = false;

                    foreach ($taxonomySeo['pwb-brand'] as $brandKey => $seoData) {
                        if ($brandKey === (int)$term->term_id) {
                            $exists = true;
                            $taxonomySeo['pwb-brand'][$brandKey]['wpseo_desc'] = $meta->getMetaDescription();
                            $taxonomySeo['pwb-brand'][$brandKey]['wpseo_focuskw'] = $meta->getMetaKeywords();
                            $taxonomySeo['pwb-brand'][$brandKey]['wpseo_title'] = strcmp($meta->getTitleTag(),
                                '') === 0 ? $manufacturer->getName() : $meta->getTitleTag();
                        }
                    }
                    if ($exists === false) {
                        $taxonomySeo['pwb-brand'][(int)$term->term_id] = [
                            'wpseo_desc' => $meta->getMetaDescription(),
                            'wpseo_focuskw' => $meta->getMetaKeywords(),
                            'wpseo_title' => strcmp($meta->getTitleTag(),
                                '') === 0 ? $manufacturer->getName() : $meta->getTitleTag(),
                        ];
                    }

                    \update_option('wpseo_taxonomy_meta', $taxonomySeo, true);
                }
            }

        }
        
        return $manufacturer;
    }
    
    protected function deleteData(ManufacturerModel $manufacturer)
    {
        if (SupportedPlugins::isPerfectWooCommerceBrandsActive()) {
            $manufacturerId = (int)$manufacturer->getId()->getEndpoint();
            
            if (!empty($manufacturerId)) {
                
                unset(self::$idCache[$manufacturer->getId()->getHost()]);
                
                wp_delete_term($manufacturerId, 'pwb-brand');
            }
        }
        
        return $manufacturer;
    }
    
    protected function getStats()
    {
        if (SupportedPlugins::isPerfectWooCommerceBrandsActive()) {
            return $this->database->queryOne(SqlHelper::manufacturerStats());
        } else {
            return 0;
        }
    }
}
