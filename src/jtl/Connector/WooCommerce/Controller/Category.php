<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller;

use jtl\Connector\Model\Category as CategoryModel;
use jtl\Connector\Model\CategoryI18n as CategoryI18nModel;
use jtl\Connector\Model\Identity;
use jtl\Connector\WooCommerce\Controller\Traits\DeleteTrait;
use jtl\Connector\WooCommerce\Controller\Traits\PullTrait;
use jtl\Connector\WooCommerce\Controller\Traits\PushTrait;
use jtl\Connector\WooCommerce\Controller\Traits\StatsTrait;
use jtl\Connector\WooCommerce\Logger\WpErrorLogger;
use jtl\Connector\WooCommerce\Utility\Category as CategoryUtil;
use jtl\Connector\WooCommerce\Utility\SQL;
use jtl\Connector\WooCommerce\Utility\Util;

class Category extends BaseController
{
    use PullTrait, PushTrait, DeleteTrait, StatsTrait;
    
    private static $idCache = [];
    
    protected function pullData($limit)
    {
        $categories = [];
        
        CategoryUtil::fillCategoryLevelTable();
        
        $categoryData = $this->database->query(SQL::categoryPull($limit));
        
        foreach ($categoryData as $categoryDataSet) {
            $category = (new CategoryModel)
                ->setId(new Identity($categoryDataSet['category_id']))
                ->setLevel((int)$categoryDataSet['level'])
                ->setSort((int)$categoryDataSet['sort']);
            
            if (!empty($categoryDataSet['parent'])) {
                $category->setParentCategoryId(new Identity($categoryDataSet['parent']));
            }
            
            $categories[] = $category->addI18n((new CategoryI18nModel)
                ->setCategoryId($category->getId())
                ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage())
                ->setName($categoryDataSet['name'])
                ->setDescription($categoryDataSet['description'])
                ->setUrlPath($categoryDataSet['slug'])
                ->setTitleTag($categoryDataSet['name']));
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
        $categoryId = $category->getId()->getEndpoint();
        
        foreach ($category->getI18ns() as $i18n) {
            if (Util::getInstance()->isWooCommerceLanguage($i18n->getLanguageISO())) {
                $meta = $i18n;
                break;
            }
        }
        
        if (is_null($meta)) {
            return $specific;
        }
        
        $categoryData = [
            'description' => $meta->getDescription(),
            'parent'      => $parentCategoryId->getEndpoint(),
            'name'        => $meta->getName(),
        ];
        
        $urlPath = $meta->getUrlPath();
        
        if (!empty($urlPath)) {
            $categoryData['slug'] = $urlPath;
        }
        
        if (empty($categoryId)) {
            $result = \wp_insert_term($meta->getName(), CategoryUtil::TERM_TAXONOMY, $categoryData);
        } else {
            // WordPress does not create a unique slug itself if the given already exists
            if (isset($categoryData['slug'])) {
                $categoryData['slug'] = wp_unique_term_slug($categoryData['slug'], (object)$categoryData);
            }
            
            $result = \wp_update_term($categoryId, CategoryUtil::TERM_TAXONOMY, $categoryData);
        }
        
        if ($result instanceof \WP_Error) {
            WpErrorLogger::getInstance()->logError($result);
            
            return $category;
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
        return $this->database->queryOne(SQL::categoryStats());
    }
}
