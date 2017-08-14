<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Utility;

use jtl\Connector\Model\Category as CategoryModel;
use jtl\Connector\Model\CategoryI18n;

class Category
{
    const TERM_TAXONOMY = 'product_cat';
    const LEVEL_TABLE = 'jtl_connector_category_level';
    const OPTION_CATEGORY_HAS_CHANGED = 'jtlconnector_category_has_changes';

    public static function fillCategoryLevelTable(array $parentIds = null, $level = 0)
    {
        $where = ' AND tt.parent = 0';

        if ($parentIds === null) {
            Db::getInstance()->query(sprintf('TRUNCATE TABLE `%s`', self::LEVEL_TABLE));
        } else {
            $where = 'AND tt.parent IN (' . implode(',', $parentIds) . ')';
        }

        $parentIds = [];

        $categories = Db::getInstance()->query(SQL::categoryTreeGet($where));

        if (!empty($categories)) {
            $sort = 0;
            $parents = [];

            foreach ($categories as $category) {
                $categoryId = (int)$category['term_id'];
                $parentCategoryId = (int)$category['parent'];

                if (!in_array($parentCategoryId, $parents)) {
                    $sort = 0;
                    $parents[] = $parentCategoryId;
                }

                $parentIds[] = $categoryId;

                Db::getInstance()->query(SQL::categoryTreeAddIgnore($categoryId, $level, $sort++));
            }

            self::fillCategoryLevelTable($parentIds, $level + 1);
        }
    }

    public static function saveCategoryLevelsAsPreOrder(array $parent = null, &$count = 0)
    {
        if ($count === 0) {
            $categories = Db::getInstance()->query(SQL::categoryTreePreOrderRoot());
            foreach ((array)$categories as $category) {
                \wc_set_term_order($category['category_id'], ++$count, 'product_cat');
                self::saveCategoryLevelsAsPreOrder($category, $count);
            }
        } else {
            $query = SQL::categoryTreePreOrder($parent['category_id'], $parent['level'] + 1);
            $categories = Db::getInstance()->query($query);
            foreach ((array)$categories as $category) {
                \wc_set_term_order($category['category_id'], ++$count, 'product_cat');
                self::saveCategoryLevelsAsPreOrder($category, $count);
            }
        }
    }

    public static function getSlug(CategoryI18n $i18n)
    {
        $url = $i18n->getUrlPath();
        $slug = empty($url) ? \sanitize_title($i18n->getName()) : $i18n->getUrlPath();
        $term = \get_term_by('slug', $slug, self::TERM_TAXONOMY);

        if ($term !== false && $term->term_id != $i18n->getCategoryId()->getEndpoint()) {
            $num = 1;

            do {
                $oldSlug = $slug . '_' . ++$num;
                $slugCheck = Db::getInstance()->queryOne(SQL::categorySlug($oldSlug));
            } while ($slugCheck);

            $slug = $oldSlug;
        }

        return $slug;
    }

    public static function updateCategoryTree(CategoryModel $category, $isNew)
    {
        $id = $category->getId()->getEndpoint();

        if ($isNew) {
            $categoryTreeQuery = SQL::categoryTreeAdd($id, $category->getLevel(), $category->getSort());
        } else {
            $categoryTreeQuery = SQL::categoryTreeUpdate($id, $category->getLevel(), $category->getSort());
        }

        Db::getInstance()->query($categoryTreeQuery);
    }

    public static function getTermMetaData()
    {
        global $wpdb;

        if (version_compare(WC()->version, '2.6', '>=')) {
            return [$wpdb->termmeta, 'term_id'];
        } else {
            return ["{$wpdb->prefix}woocommerce_termmeta", 'woocommerce_term_id'];
        }
    }
}
