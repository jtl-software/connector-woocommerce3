<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers;

use jtl\Connector\Model\Category as CategoryModel;
use jtl\Connector\Model\CategoryI18n as CategoryI18nModel;
use jtl\Connector\Model\Identity;
use JtlWooCommerceConnector\Controllers\Traits\DeleteTrait;
use JtlWooCommerceConnector\Controllers\Traits\PullTrait;
use JtlWooCommerceConnector\Controllers\Traits\PushTrait;
use JtlWooCommerceConnector\Controllers\Traits\StatsTrait;
use JtlWooCommerceConnector\Logger\WpErrorLogger;
use JtlWooCommerceConnector\Utilities\Category as CategoryUtil;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;

class Category extends BaseController
{
    use PullTrait, PushTrait, DeleteTrait, StatsTrait;
    
    private static $idCache = [];
    
    protected function pullData($limit)
    {
        $categories = [];
        
        CategoryUtil::fillCategoryLevelTable();
        $categoryData = $this->database->query(SqlHelper::categoryPull($limit));
        
        foreach ($categoryData as $categoryDataSet) {
            $category = (new CategoryModel)
                ->setId(new Identity($categoryDataSet['category_id']))
                ->setLevel((int)$categoryDataSet['level'])
                ->setSort((int)$categoryDataSet['sort']);
            
            if (!empty($categoryDataSet['parent'])) {
                $category->setParentCategoryId(new Identity($categoryDataSet['parent']));
            }
            
            $i18n = (new CategoryI18nModel)
                ->setCategoryId($category->getId())
                ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage())
                ->setName($categoryDataSet['name'])
                ->setDescription($categoryDataSet['description'])
                ->setUrlPath($categoryDataSet['slug'])
                ->setTitleTag($categoryDataSet['name']);
            
            if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO)
                || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO_PREMIUM)) {
                $taxonomySeo = get_option('wpseo_taxonomy_meta');
                
                if (isset($taxonomySeo['product_cat'])) {
                    foreach ($taxonomySeo['product_cat'] as $catId => $seoData) {
                        if ($catId === (int)$categoryDataSet['category_id']) {
                            $i18n->setMetaDescription(isset($seoData['wpseo_desc']) ? $seoData['wpseo_desc'] : '')
                                ->setMetaKeywords(isset($seoData['wpseo_focuskw']) ? $seoData['wpseo_focuskw'] : $categoryDataSet['name'])
                                ->setTitleTag(isset($seoData['wpseo_title']) ? $seoData['wpseo_title'] : $categoryDataSet['name']);
                        }
                    }
                }
            }
            
            $categories[] = $category->addI18n($i18n);
        }
        
        return $categories;
    }
    
    protected function pushData(CategoryModel $category)
    {
        if (!$category->getIsActive()) {
            return $category;
        }
        
        \update_option(CategoryUtil::OPTION_CATEGORY_HAS_CHANGED, 'yes');
        
        $parentCategoryId = $category->getParentCategoryId();
        
        if ($parentCategoryId !== null && isset(self::$idCache[$parentCategoryId->getHost()])) {
            $parentCategoryId->setEndpoint(self::$idCache[$parentCategoryId->getHost()]);
        }
        
        $meta = null;
        $categoryId = (int)$category->getId()->getEndpoint();
        
        foreach ($category->getI18ns() as $i18n) {
            if (Util::getInstance()->isWooCommerceLanguage($i18n->getLanguageISO())) {
                $meta = $i18n;
                break;
            }
        }
        
        if (is_null($meta)) {
            return $category;
        }
        
        $categoryData = [
            'description' => $meta->getDescription(),
            'parent'      => $parentCategoryId->getEndpoint(),
            'name'        => $meta->getName(),
            'taxonomy'    => \wc_sanitize_taxonomy_name($meta->getName()),
        ];
        
        $urlPath = $meta->getUrlPath();
        
        if (!empty($urlPath)) {
            $categoryData['slug'] = $urlPath;
        }
        
        remove_filter('pre_term_description', 'wp_filter_kses');
        
        if (empty($categoryId)) {
            $result = \wp_insert_term($meta->getName(), CategoryUtil::TERM_TAXONOMY, $categoryData);
        } else {
            // WordPress does not create a unique slug itself if the given already exists
            if (isset($categoryData['slug'])) {
                $categoryData['slug'] = wp_unique_term_slug($categoryData['slug'], (object)$categoryData);
            }
            
            $result = \wp_update_term($categoryId, CategoryUtil::TERM_TAXONOMY, $categoryData);
        }
        
        add_filter('pre_term_description', 'wp_filter_kses');
        
        if ($result instanceof \WP_Error) {
            WpErrorLogger::getInstance()->logError($result);
            
            return $category;
        }
    
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO_PREMIUM)) {
            $taxonomySeo = \get_option('wpseo_taxonomy_meta', false);
        
            if ($taxonomySeo === false) {
                $taxonomySeo = ['product_cat' => []];
            }
        
            if (!isset($taxonomySeo['product_cat'])) {
                $taxonomySeo['product_cat'] = [];
            }
            $exists = false;
        
            foreach ($taxonomySeo['product_cat'] as $catKey => $seoData) {
                if ($catKey === (int)$result['term_id']) {
                    $exists = true;
                    $taxonomySeo['product_cat'][$catKey]['wpseo_desc'] = $meta->getMetaDescription();
                    $taxonomySeo['product_cat'][$catKey]['wpseo_focuskw'] = $meta->getMetaKeywords();
                    $taxonomySeo['product_cat'][$catKey]['wpseo_title'] = strcmp($meta->getTitleTag(),
                        '') === 0 ? $meta->getName() : $meta->getTitleTag();
                }
            }
            if ($exists === false) {
                $taxonomySeo['product_cat'][(int)$result['term_id']] = [
                    'wpseo_desc'    => $meta->getMetaDescription(),
                    'wpseo_focuskw' => $meta->getMetaKeywords(),
                    'wpseo_title'   => strcmp($meta->getTitleTag(),
                        '') === 0 ? $meta->getName() : $meta->getTitleTag(),
                ];
            }
        
            \update_option('wpseo_taxonomy_meta', $taxonomySeo, true);
        }
        
        $category->getId()->setEndpoint($result['term_id']);
        self::$idCache[$category->getId()->getHost()] = $result['term_id'];
        
        CategoryUtil::updateCategoryTree($category, empty($categoryId));
        
        return $category;
    }
    
    protected function deleteData(CategoryModel $specific)
    {
        $categoryId = $specific->getId()->getEndpoint();
        
        if (!empty($categoryId)) {
            \update_option(CategoryUtil::OPTION_CATEGORY_HAS_CHANGED, 'yes');
            
            $result = \wp_delete_term($categoryId, CategoryUtil::TERM_TAXONOMY);
            
            if ($result instanceof \WP_Error) {
                WpErrorLogger::getInstance()->logError($result);
                
                return $specific;
            }
            
            unset(self::$idCache[$specific->getId()->getHost()]);
        }
        
        return $specific;
    }
    
    protected function getStats()
    {
        return $this->database->queryOne(SqlHelper::categoryStats());
    }
}
