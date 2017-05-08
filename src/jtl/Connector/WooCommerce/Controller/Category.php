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
use jtl\Connector\WooCommerce\Utility\SQLs;
use jtl\Connector\WooCommerce\Utility\Util;

class Category extends BaseController
{
    use PullTrait, PushTrait, DeleteTrait, StatsTrait;

    const TERM_TAXONOMY = 'product_cat';
    const OPTION_CATEGORY_HAS_CHANGED = 'jtlconnector_category_has_changes';

    private static $idCache = [];

    public function pullData($limit)
    {
        $categories = [];

        CategoryUtil::fillCategoryLevelTable();

        $categoryData = $this->database->query(SQLs::categoryPull($limit));

        foreach ($categoryData as $categoryDataSet) {
            $category = (new CategoryModel)
                ->setId(new Identity($categoryDataSet['category_id']))
                ->setLevel((int)$categoryDataSet['level'])
                ->setSort((int)$categoryDataSet['sort'])
                ->setIsActive($categoryDataSet['count'] != 0);

            if (!empty($categoryDataSet['parent'])) {
                $category->setParentCategoryId(new Identity($categoryDataSet['parent']));
            }

            $categories[] = $category->addI18n((new CategoryI18nModel)
                ->setCategoryId($category->getId())
                ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage())
                ->setName($categoryDataSet['name'])
                ->setDescription($categoryDataSet['description'])
                ->setUrlPath($categoryDataSet['slug']));
        }

        return $categories;
    }

    public function pushData(CategoryModel $category)
    {
        if (!$category->getIsActive()) {
            return $category;
        }

        \update_option(self::OPTION_CATEGORY_HAS_CHANGED, 'yes');

        $parentCategoryId = $category->getParentCategoryId();

        if ($parentCategoryId !== null && isset(self::$idCache[$parentCategoryId->getHost()])) {
            $parentCategoryId->setEndpoint(self::$idCache[$parentCategoryId->getHost()]);
        }

        $categoryMeta = null;
        $categoryId = $category->getId()->getEndpoint();

        foreach ($category->getI18ns() as $i18n) {
            if (Util::getInstance()->isWooCommerceLanguage($i18n->getLanguageISO())) {
                $categoryMeta = $i18n;
                break;
            }
        }

        if (is_null($categoryMeta)) {
            return $category;
        }

        $categoryData = [
            'description' => $categoryMeta->getDescription(),
            'parent'      => $category->getParentCategoryId()->getEndpoint(),
            'slug'        => $this->getSlug($categoryMeta),
        ];

        if (empty($categoryId)) {
            $result = \wp_insert_term($categoryMeta->getName(), self::TERM_TAXONOMY, $categoryData);
        } else {
            $result = \wp_update_term((int)$categoryId, self::TERM_TAXONOMY, $categoryData);
        }

        if ($result instanceof \WP_Error) {
            WpErrorLogger::getInstance()->logError($result);

            return $category;
        }

        $category->getId()->setEndpoint($result['term_id']);
        self::$idCache[$category->getId()->getHost()] = $result['term_id'];

        $this->updateCategoryTree($category, empty($categoryId));

        return $category;
    }

    public function deleteData(CategoryModel $category)
    {
        $categoryId = $category->getId()->getEndpoint();

        if (!empty($categoryId)) {
            \update_option(self::OPTION_CATEGORY_HAS_CHANGED, 'yes');

            $result = \wp_delete_term($categoryId, self::TERM_TAXONOMY);

            if ($result instanceof \WP_Error) {
                WpErrorLogger::getInstance()->logError($result);

                return $category;
            }

            unset(self::$idCache[$category->getId()->getHost()]);
        }

        return $category;
    }

    public function getStats()
    {
        return $this->database->queryOne(SQLs::categoryStats());
    }

    // <editor-fold defaultstate="collapsed" desc="Private Methods">
    private function getSlug(CategoryI18nModel $i18n)
    {
        $url = $i18n->getUrlPath();
        $slug = empty($url) ? \sanitize_title($i18n->getName()) : $i18n->getUrlPath();
        $term = \get_term_by('slug', $slug, Category::TERM_TAXONOMY);

        if ($term !== false && $term->term_id != $i18n->getCategoryId()->getEndpoint()) {
            $num = 1;

            do {
                $oldSlug = $slug . '_' . ++$num;
                $slugCheck = $this->database->queryOne(SQLs::categorySlug($oldSlug));
            } while ($slugCheck);

            $slug = $oldSlug;
        }

        return $slug;
    }

    private function updateCategoryTree(CategoryModel $category, $isNew)
    {
        if ($isNew) {
            $categoryTreeQuery = SQLs::categoryTreeAdd($category->getId()->getEndpoint(), $category->getLevel(), $category->getSort());
        } else {
            $categoryTreeQuery = SQLs::categoryTreeUpdate($category->getId()->getEndpoint(), $category->getLevel(), $category->getSort());
        }

        $this->database->query($categoryTreeQuery);
    }
    // </editor-fold>
}
