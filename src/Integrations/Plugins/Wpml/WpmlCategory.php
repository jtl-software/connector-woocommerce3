<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\Wpml;

use Exception;
use Jtl\Connector\Core\Model\Category;
use Jtl\Connector\Core\Model\Identity;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractComponent;
use JtlWooCommerceConnector\Integrations\Plugins\RankMathSeo\RankMathSeo;
use JtlWooCommerceConnector\Integrations\Plugins\WooCommerce\WooCommerce;
use JtlWooCommerceConnector\Integrations\Plugins\WooCommerce\WooCommerceCategory;
use JtlWooCommerceConnector\Integrations\Plugins\YoastSeo\YoastSeo;
use JtlWooCommerceConnector\Utilities\Category as CategoryUtil;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use Psr\Log\InvalidArgumentException;

/**
 * Class WpmlCategory
 * @package JtlWooCommerceConnector\Integrations\Plugins\Wpml
 */
class WpmlCategory extends AbstractComponent
{
    public const
        PRODUCT_CATEGORY_TYPE = 'tax_product_cat';

    /**
     * @param Category $jtlCategory
     * @param array $wooCommerceMainCategory
     * @param Identity $parentCategoryId
     * @throws Exception
     */
    public function setCategoryTranslations(
        Category $jtlCategory,
        array $wooCommerceMainCategory,
        Identity $parentCategoryId
    ): void {
        $trid = (int)$this->getCurrentPlugin()
            ->getElementTrid((int)$wooCommerceMainCategory['term_taxonomy_id'], self::PRODUCT_CATEGORY_TYPE);

        foreach ($jtlCategory->getI18ns() as $categoryI18n) {
            $languageCode = $this->getCurrentPlugin()->convertLanguageToWpml($categoryI18n->getLanguageISO());
            if ($this->getCurrentPlugin()->getDefaultLanguage() === $languageCode) {
                continue;
            }

            $categoryTerm = $this->findCategoryTranslation((int)$trid, $languageCode);
            $categoryId   = null;
            if (isset($categoryTerm['term_id'])) {
                $categoryId = (int)$categoryTerm['term_id'];
            }

            $result = $this->getCurrentPlugin()
                ->getPluginsManager()
                ->get(WooCommerce::class)
                ->getComponent(WooCommerceCategory::class)
                ->saveWooCommerceCategory($categoryI18n, $parentCategoryId, $categoryId);

            if (!empty($result)) {
                $categoryId = (int) $result['term_id'];

                /** @var YoastSeo $yoastSeo */
                $yoastSeo = $this->getCurrentPlugin()->getPluginsManager()->get(YoastSeo::class);
                /** @var RankMathSeo $rankMathSeo */
                $rankMathSeo = $this->getCurrentPlugin()->getPluginsManager()->get(RankMathSeo::class);

                if ($yoastSeo->canBeUsed()) {
                    $yoastSeo->setCategorySeoData($categoryId, $categoryI18n);
                } elseif ($rankMathSeo->canBeUsed()) {
                    $rankMathSeo->updateWpSeoTaxonomyMeta($categoryId, $categoryI18n);
                }

                $this->getCurrentPlugin()->getSitepress()->set_element_language_details(
                    (int) $result['term_taxonomy_id'],
                    self::PRODUCT_CATEGORY_TYPE,
                    $trid,
                    $languageCode
                );
            }
        }
    }

    /**
     * @param int $trid
     * @param string $languageCode
     * @return array
     */
    protected function findCategoryTranslation(int $trid, string $languageCode): array
    {
        $categoryTranslation = $this
            ->getCurrentPlugin()
            ->getComponent(WpmlTermTranslation::class)
            ->getTranslations($trid, self::PRODUCT_CATEGORY_TYPE, false);

        $translation = [];
        if (isset($categoryTranslation[$languageCode])) {
            $translationData = $categoryTranslation[$languageCode];
            $translation     = $this->getCurrentPlugin()
                ->getComponent(WpmlTermTranslation::class)
                ->getTranslatedTerm($translationData->term_id, 'product_cat');
        }
        return $translation;
    }

    /**
     * @param int $limit
     * @return array
     * @throws InvalidArgumentException
     */
    public function getCategories(int $limit): array
    {
        $this->fillCategoryLevelTable();

        $tablePrefix = $this->getCurrentPlugin()->getWpDb()->prefix;

        $sql = \sprintf("SELECT 
            tt.term_id as category_id, cl.sort, cl.level, tt.parent, tt.description, t.name, t.slug, tt.count, wpmlt.*
            FROM `{$this->getCurrentPlugin()->getWpDb()->terms}` t
                LEFT JOIN 
            `{$this->getCurrentPlugin()->getWpDb()->term_taxonomy}` tt ON t.term_id = tt.term_id
                LEFT JOIN
            `%sjtl_connector_category_level` cl ON tt.term_taxonomy_id = cl.category_id
                LEFT JOIN
            `%sjtl_connector_link_category` l ON t.term_id = l.endpoint_id
                LEFT JOIN
            `%sicl_translations` wpmlt ON tt.term_taxonomy_id = wpmlt.element_id
        WHERE
            tt.taxonomy = 'product_cat'
                AND wpmlt.element_type = 'tax_product_cat'
                AND wpmlt.source_language_code IS NULL
                AND l.host_id IS NULL
                AND wpmlt.language_code = '%s'
        ORDER BY cl.level ASC , tt.parent ASC , cl.sort ASC
        LIMIT %s", $tablePrefix, $tablePrefix, $tablePrefix, $this->getCurrentPlugin()->getDefaultLanguage(), $limit);


        return $this->getCurrentPlugin()->getPluginsManager()->getDatabase()->query($sql);
    }

    /**
     * @return int
     * @throws InvalidArgumentException
     */
    public function getStats(): int
    {
        $tablePrefix = $this->getCurrentPlugin()->getWpDb()->prefix;

        $sql = \sprintf("SELECT 
                COUNT(tt.term_id)        
                FROM `{$this->getCurrentPlugin()->getWpDb()->terms}` t
                    LEFT JOIN 
                `{$this->getCurrentPlugin()->getWpDb()->term_taxonomy}` tt ON t.term_id = tt.term_id
                    LEFT JOIN
                `%sjtl_connector_category_level` cl ON tt.term_id = cl.category_id
                    LEFT JOIN
                `%sjtl_connector_link_category` l ON t.term_id = l.endpoint_id
                    LEFT JOIN
                `%sicl_translations` wpmlt ON tt.term_taxonomy_id = wpmlt.element_id
            WHERE
                tt.taxonomy = 'product_cat'
                    AND wpmlt.element_type = 'tax_product_cat'
                    AND wpmlt.source_language_code IS NULL
                    AND l.host_id IS NULL
                    AND wpmlt.language_code = '%s'
            ORDER BY cl.level ASC , tt.parent ASC , cl.sort ASC
            ", $tablePrefix, $tablePrefix, $tablePrefix, $this->getCurrentPlugin()->getDefaultLanguage());

        return (int)$this->getCurrentPlugin()->getPluginsManager()->getDatabase()->queryOne($sql);
    }

    /**
     * @param array|null $parentIds
     * @param int $level
     * @throws InvalidArgumentException
     */
    protected function fillCategoryLevelTable(array $parentIds = null, int $level = 0): void
    {
        $where = ' AND tt.parent = 0';
        $db    = $this->getPluginsManager()->getDatabase();

        if ($parentIds === null) {
            $db->query(\sprintf(
                'TRUNCATE TABLE `%s%s`',
                $this->getCurrentPlugin()->getWpDb()->prefix,
                CategoryUtil::LEVEL_TABLE
            ));
        } else {
            $where = 'AND tt.parent IN (' . \implode(',', $parentIds) . ')';
        }

        $parentIds = [];

        list($table, $column) = CategoryUtil::getTermMetaData();

        $categories = $db->query(
            \sprintf(
                "
            SELECT tt.term_taxonomy_id, tt.term_id, tt.parent, IF(tm.meta_key IS NULL, 0, tm.meta_value) as sort
            FROM `{$this->getCurrentPlugin()->getWpDb()->term_taxonomy}` tt
            LEFT JOIN `{$this->getCurrentPlugin()->getWpDb()->terms}` t ON tt.term_id = t.term_id
            LEFT JOIN `{$table}` tm ON tm.{$column} = tt.term_id AND tm.meta_key = 'order'
            LEFT JOIN `%sicl_translations` wpmlt ON tt.term_taxonomy_id = wpmlt.element_id
            WHERE tt.taxonomy = '%s' {$where}
              AND wpmlt.element_type = 'tax_product_cat'
              AND wpmlt.source_language_code IS NULL
              AND wpmlt.language_code = '%s'
            ORDER BY tt.parent ASC, sort ASC, t.name ASC",
                $this->getCurrentPlugin()->getWpDb()->prefix,
                CategoryUtil::TERM_TAXONOMY,
                $this->getCurrentPlugin()->getDefaultLanguage()
            )
        );

        if (!empty($categories)) {
            $sort    = 0;
            $parents = [];

            foreach ($categories as $category) {
                $categoryId       = (int)$category['term_id'];
                $parentCategoryId = (int)$category['parent'];

                if (!\in_array($parentCategoryId, $parents)) {
                    $sort      = 0;
                    $parents[] = $parentCategoryId;
                }

                $parentIds[] = $categoryId;

                $db->query(SqlHelper::categoryTreeAddIgnore($categoryId, $level, $sort++));
            }

            $this->fillCategoryLevelTable($parentIds, $level + 1);
        }
    }
}
