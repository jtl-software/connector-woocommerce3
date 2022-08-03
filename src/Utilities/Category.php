<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Utilities;


class Category
{
    const TERM_TAXONOMY = 'product_cat';
    const LEVEL_TABLE = 'jtl_connector_category_level';
    const OPTION_CATEGORY_HAS_CHANGED = 'jtlconnector_category_has_changes';

    protected $database;

    /**
     * @param Db $database
     */
    public function __construct(Db $database)
    {
        $this->database = $database;
    }

    public function fillCategoryLevelTable(array $parentIds = null, $level = 0)
    {
        global $wpdb;
        $where = ' AND tt.parent = 0';

        if ($parentIds === null) {
            $this->database->query(sprintf(
                'TRUNCATE TABLE `%s%s`',
                $wpdb->prefix,
                self::LEVEL_TABLE
            ));
        } else {
            $where = 'AND tt.parent IN (' . implode(',', $parentIds) . ')';
        }

        $parentIds = [];

        $categories = $this->database->query(SqlHelper::categoryTreeGet($where));

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

                $this->database->query(SqlHelper::categoryTreeAddIgnore($categoryId, $level, $sort++));
            }

            $this->fillCategoryLevelTable($parentIds, $level + 1);
        }
    }

    public function saveCategoryLevelsAsPreOrder(array $parent = null, &$count = 0)
    {
        if ($count === 0) {
            $categories = $this->database->query(SqlHelper::categoryTreePreOrderRoot());
            foreach ((array)$categories as $category) {
                \wc_set_term_order($category['category_id'], ++$count, 'product_cat');
                $this->saveCategoryLevelsAsPreOrder($category, $count);
            }
        } else {
            $query = SqlHelper::categoryTreePreOrder($parent['category_id'], $parent['level'] + 1);
            $categories = $this->database->query($query);
            foreach ((array)$categories as $category) {
                \wc_set_term_order($category['category_id'], ++$count, 'product_cat');
                $this->saveCategoryLevelsAsPreOrder($category, $count);
            }
        }
    }

    public function updateCategoryTree(\Jtl\Connector\Core\Model\Category $category, $isNew)
    {
        $id = $category->getId()->getEndpoint();

        if ($isNew) {
            $categoryTreeQuery = SqlHelper::categoryTreeAdd($id, $category->getLevel(), $category->getSort());
        } else {
            $categoryTreeQuery = SqlHelper::categoryTreeUpdate($id, $category->getLevel(), $category->getSort());
        }

        $this->database->query($categoryTreeQuery);
    }

    public static function getTermMetaData()
    {
        global $wpdb;

        if (version_compare(WC()->version, '2.6', '>=')) {
            return [$wpdb->termmeta, 'term_id'];
        }

        return ["{$wpdb->prefix}woocommerce_termmeta", 'woocommerce_term_id'];
    }
}
