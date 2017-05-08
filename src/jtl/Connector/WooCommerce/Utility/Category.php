<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Utility;

class Category
{
    const LEVEL_TABLE = 'jtl_connector_category_level';

    public static function fillCategoryLevelTable(array $parentIds = null, $level = 0)
    {
        global $wpdb;

        $where = 'AND tt.parent = 0';

        if ($parentIds === null) {
            Db::getInstance()->query(sprintf('TRUNCATE TABLE `%s`', self::LEVEL_TABLE));
        } else {
            $where = 'AND tt.parent IN (' . implode(',', $parentIds) . ')';
        }

        $parentIds = [];

        list($table, $column) = self::getTermMetaData();

        $categories = Db::getInstance()->query("
            SELECT tt.term_id, tt.parent
            FROM {$wpdb->term_taxonomy} tt
            LEFT JOIN {$table} tm ON tm.{$column} = tt.term_id
            WHERE tt.taxonomy = 'product_cat' AND tm.meta_key = 'order' {$where}
            ORDER BY tt.parent ASC, cast(tm.meta_value as unsigned) ASC"
        );

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

                Db::getInstance()->query(sprintf(
                    'INSERT IGNORE INTO `%s` VALUES (%d, %d, %d)',
                    self::LEVEL_TABLE, $categoryId, $level, $sort++
                ));
            }
            self::fillCategoryLevelTable($parentIds, $level + 1);
        }
    }

    public static function saveCategoryLevelsAsPreOrder(array $parent = null, &$count = 0)
    {
        if ($count === 0) {
            $categories = Db::getInstance()->query(SQLs::categoryTreePreOrderRoot());
            foreach ((array)$categories as $category) {
                \wc_set_term_order($category['category_id'], ++$count, 'product_cat');
                self::saveCategoryLevelsAsPreOrder($category, $count);
            }
        } else {
            $query = SQLs::categoryTreePreOrder($parent['category_id'], $parent['level'] + 1);
            $categories = Db::getInstance()->query($query);
            foreach ((array)$categories as $category) {
                \wc_set_term_order($category['category_id'], ++$count, 'product_cat');
                self::saveCategoryLevelsAsPreOrder($category, $count);
            }
        }
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
