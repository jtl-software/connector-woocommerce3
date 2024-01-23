<?php

namespace JtlWooCommerceConnector\Controllers;

use Jtl\Connector\Core\Controller\DeleteInterface;
use Jtl\Connector\Core\Controller\PullInterface;
use Jtl\Connector\Core\Controller\PushInterface;
use Jtl\Connector\Core\Controller\StatisticInterface;
use Jtl\Connector\Core\Model\AbstractModel;
use Jtl\Connector\Core\Model\Category as CategoryModel;
use Jtl\Connector\Core\Model\CategoryI18n;
use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\QueryFilter;
use JtlWooCommerceConnector\Logger\ErrorFormatter;
use JtlWooCommerceConnector\Utilities\Category as CategoryUtil;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use Psr\Log\InvalidArgumentException;

class CategoryController extends AbstractBaseController implements
    PullInterface,
    PushInterface,
    DeleteInterface,
    StatisticInterface
{
    private static array $idCache = [];

    /**
     * @param QueryFilter $query
     * @return array
     * @throws InvalidArgumentException
     */
    public function pull(QueryFilter $query): array
    {
        $categories = [];

        $categoryUtil = new CategoryUtil($this->db, $this->util);
        $categoryUtil->fillCategoryLevelTable();
        $categoryData = $this->db->query(SqlHelper::categoryPull($query->getLimit()));

        foreach ($categoryData as $categoryDataSet) {
            $category = (new CategoryModel())
                ->setId(new Identity($categoryDataSet['category_id']))
                ->setLevel((int)$categoryDataSet['level'])
                ->setSort((int)$categoryDataSet['sort']);

            if (!empty($categoryDataSet['parent'])) {
                $category->setParentCategoryId(new Identity($categoryDataSet['parent']));
            }

            $i18n = (new CategoryI18n())
                ->setLanguageISO($this->util->getWooCommerceLanguage())
                ->setName(\html_entity_decode($categoryDataSet['name']))
                ->setDescription(\html_entity_decode($categoryDataSet['description']))
                ->setUrlPath($categoryDataSet['slug'])
                ->setTitleTag($categoryDataSet['name']);

            if (
                SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO)
                || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO_PREMIUM)
            ) {
                $taxonomySeo = \get_option('wpseo_taxonomy_meta');

                if (isset($taxonomySeo['product_cat'])) {
                    foreach ($taxonomySeo['product_cat'] as $catId => $seoData) {
                        if ($catId === (int)$categoryDataSet['category_id']) {
                            $i18n
                                ->setMetaDescription($seoData['wpseo_desc'] ?? '')
                                ->setMetaKeywords($seoData['wpseo_focuskw'] ?? $categoryDataSet['name'])
                                ->setTitleTag($seoData['wpseo_title'] ?? $categoryDataSet['name']);
                        }
                    }
                }
            } elseif (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_RANK_MATH_SEO)) {
                $sql             = SqlHelper::pullRankMathSeoTermData((int) $categoryDataSet['category_id']);
                $categorySeoData = $this->db->query($sql);
                if (\is_array($categorySeoData)) {
                    $this->util->setI18nRankMathSeo($i18n, $categorySeoData);
                }
            }

            $categories[] = $category->addI18n($i18n);
        }

        return $categories;
    }

    /**
     * @param CategoryModel $model
     * @return CategoryModel
     * @throws \Exception
     */
    public function push(AbstractModel $model): AbstractModel
    {
        if (!$model->getIsActive()) {
            return $model;
        }

        \update_option(CategoryUtil::OPTION_CATEGORY_HAS_CHANGED, 'yes');

        $parentCategoryId = $model->getParentCategoryId();

        if (isset(self::$idCache[$parentCategoryId->getHost()])) {
            $parentCategoryId->setEndpoint(self::$idCache[$parentCategoryId->getHost()]);
        }

        $meta       = null;
        $categoryId = (int)$model->getId()->getEndpoint();

        foreach ($model->getI18ns() as $i18n) {
            if ($this->util->isWooCommerceLanguage($i18n->getLanguageISO())) {
                $meta = $i18n;
                break;
            }
        }

        if (\is_null($meta)) {
            return $model;
        }

        $categoryData = [
            'description' => $meta->getDescription(),
            'parent'      => $parentCategoryId->getEndpoint(),
            'name'        => $meta->getName(),
            'taxonomy'    => \wc_sanitize_taxonomy_name(CategoryUtil::TERM_TAXONOMY),
        ];

        $urlPath = $meta->getUrlPath();

        $categoryData['slug'] = \strtolower($meta->getName());
        if (!empty($urlPath)) {
            $categoryData['slug'] = $urlPath;
        }

        \remove_filter('pre_term_description', 'wp_filter_kses');

        if (empty($categoryId)) {
            $result = \wp_insert_term($meta->getName(), CategoryUtil::TERM_TAXONOMY, $categoryData);
        } else {
            $categoryTerm = \get_term($categoryId, CategoryUtil::TERM_TAXONOMY);
            if ($categoryTerm instanceof \WP_Error) {
                throw new \Exception(\sprintf("Cannot find category %s", $categoryId));
            }
            // WordPress does not create a unique slug itself if the given already exists
            if ($categoryTerm->slug !== $categoryData['slug']) {
                $categoryData['slug'] = \wp_unique_term_slug($categoryData['slug'], (object)$categoryData);
            }

            $result = \wp_update_term($categoryId, CategoryUtil::TERM_TAXONOMY, $categoryData);
        }

        \add_filter('pre_term_description', 'wp_filter_kses');

        if ($result instanceof \WP_Error) {
            $this->logger->error(ErrorFormatter::formatError($result));

            return $model;
        }

        if (
            SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO_PREMIUM)
        ) {
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
                    $exists                                               = true;
                    $taxonomySeo['product_cat'][$catKey]['wpseo_desc']    = $meta->getMetaDescription();
                    $taxonomySeo['product_cat'][$catKey]['wpseo_focuskw'] = $meta->getMetaKeywords();
                    $taxonomySeo['product_cat'][$catKey]['wpseo_title']   = \strcmp(
                        $meta->getTitleTag(),
                        ''
                    ) === 0 ? $meta->getName() : $meta->getTitleTag();
                }
            }
            if ($exists === false) {
                $taxonomySeo['product_cat'][(int)$result['term_id']] = [
                    'wpseo_desc'    => $meta->getMetaDescription(),
                    'wpseo_focuskw' => $meta->getMetaKeywords(),
                    'wpseo_title'   => \strcmp(
                        $meta->getTitleTag(),
                        ''
                    ) === 0 ? $meta->getName() : $meta->getTitleTag(),
                ];
            }

            \update_option('wpseo_taxonomy_meta', $taxonomySeo, true);
        } elseif (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_RANK_MATH_SEO)) {
            $updateRankMathSeoData = [
                'rank_math_title' => $i18n->getTitleTag(),
                'rank_math_description' => $i18n->getMetaDescription(),
                'rank_math_focus_keyword' => $i18n->getMetaKeywords()
            ];
            $this->util->updateTermMeta($updateRankMathSeoData, (int) $result['term_id']);
        }

        $model->getId()->setEndpoint($result['term_id']);
        self::$idCache[$model->getId()->getHost()] = $result['term_id'];

        (new CategoryUtil($this->db))->updateCategoryTree($model, empty($categoryId));

        return $model;
    }

    /**
     * @param CategoryModel $model
     * @return CategoryModel
     * @throws InvalidArgumentException
     */
    public function delete(AbstractModel $model): AbstractModel
    {
        $categoryId = $model->getId()->getEndpoint();

        if (!empty($categoryId)) {
            \update_option(CategoryUtil::OPTION_CATEGORY_HAS_CHANGED, 'yes');

            $result = \wp_delete_term($categoryId, CategoryUtil::TERM_TAXONOMY);

            if ($result instanceof \WP_Error) {
                $this->logger->error(ErrorFormatter::formatError($result));

                return $model;
            }

            unset(self::$idCache[$model->getId()->getHost()]);
        }

        return $model;
    }

    /**
     * @param QueryFilter $query
     * @return int
     * @throws InvalidArgumentException
     */
    public function statistic(QueryFilter $query): int
    {
        return $this->db->queryOne(SqlHelper::categoryStats());
    }
}
