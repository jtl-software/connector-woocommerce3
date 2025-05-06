<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Utilities;

use Jtl\Connector\Core\Model\Category as CategoryModel;
use Psr\Log\InvalidArgumentException;

class Category
{
    public const TERM_TAXONOMY               = 'product_cat';
    public const LEVEL_TABLE                 = 'jtl_connector_category_level';
    public const OPTION_CATEGORY_HAS_CHANGED = 'jtlconnector_category_has_changes';

    protected Db $db;

    /**
     * @param Db $db
     */
    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    /**
     * @param int[]|null $parentIds
     * @param int        $level
     * @return void
     * @throws InvalidArgumentException
     */
    public function fillCategoryLevelTable(?array $parentIds = null, int $level = 0): void
    {
        global $wpdb;
        $where = ' AND tt.parent = 0';

        if ($parentIds === null) {
            $this->db->query(\sprintf(
                'TRUNCATE TABLE `%s%s`',
                $wpdb->prefix,
                self::LEVEL_TABLE
            ));
        } else {
            $where = 'AND tt.parent IN (' . \implode(',', $parentIds) . ')';
        }

        $parentIds = [];

        $categories = $this->db->query(SqlHelper::categoryTreeGet($where));

        if (! empty($categories)) {
            $sort    = 0;
            $parents = [];

            /** @var array<string, int|string> $category */
            foreach ($categories as $category) {
                $categoryId       = (int) $category['term_id'];
                $parentCategoryId = (int) $category['parent'];

                if (! \in_array($parentCategoryId, $parents)) {
                    $sort      = 0;
                    $parents[] = $parentCategoryId;
                }

                $parentIds[] = $categoryId;

                $this->db->query(SqlHelper::categoryTreeAddIgnore($categoryId, $level, $sort++));
            }

            self::fillCategoryLevelTable($parentIds, $level + 1);
        }
    }

    /**
     * @param int[]|string[] $parent
     * @param int            $count
     * @return void
     * @throws InvalidArgumentException
     * @throws \InvalidArgumentException
     */
    public function saveCategoryLevelsAsPreOrder(array $parent = [], int &$count = 0): void
    {
        if ($count === 0) {
            $categories = $this->db->query(SqlHelper::categoryTreePreOrderRoot());
            /** @var array<string, int|string> $category */
            foreach ((array)$categories as $category) {
                \wc_set_term_order((int)$category['category_id'], ++$count, 'product_cat');
                $this->saveCategoryLevelsAsPreOrder($category, $count);
            }
        } else {
            $query      = SqlHelper::categoryTreePreOrder((int)$parent['category_id'], (int)$parent['level'] + 1);
            $categories = $this->db->query($query);
            /** @var array<string, int|string> $category */
            foreach ((array)$categories as $category) {
                \wc_set_term_order((int)$category['category_id'], ++$count, 'product_cat');
                $this->saveCategoryLevelsAsPreOrder($category, $count);
            }
        }
        /** @var array<string, int|string> $category */
        foreach ((array) $categories as $category) {
            \wc_set_term_order((int)$category['category_id'], ++$count, 'product_cat');
            self::saveCategoryLevelsAsPreOrder($category, $count);
        }
    }

    /**
     * @param CategoryModel $category
     * @param bool          $isNew
     * @return void
     * @throws InvalidArgumentException
     */
    public function updateCategoryTree(CategoryModel $category, bool $isNew): void
    {
        $id = $category->getId()->getEndpoint();

        if ($isNew) {
            $categoryTreeQuery = SqlHelper::categoryTreeAdd((int)$id, $category->getLevel(), $category->getSort());
        } else {
            $categoryTreeQuery = SqlHelper::categoryTreeUpdate((int)$id, $category->getLevel(), $category->getSort());
        }

        $this->db->query($categoryTreeQuery);
    }

    /**
     * @return array|string[]
     */
    public static function getTermMetaData(): array
    {
        global $wpdb;

        if (\version_compare(\WC()->version, '2.6', '>=')) {
            return [ $wpdb->termmeta, 'term_id' ];
        } else {
            return [ "{$wpdb->prefix}woocommerce_termmeta", 'woocommerce_term_id' ];
        }
    }
}
