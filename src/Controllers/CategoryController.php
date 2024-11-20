<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Controllers;

use Jtl\Connector\Core\Controller\DeleteInterface;
use Jtl\Connector\Core\Controller\PullInterface;
use Jtl\Connector\Core\Controller\PushInterface;
use Jtl\Connector\Core\Controller\StatisticInterface;
use Jtl\Connector\Core\Model\AbstractModel;
use Jtl\Connector\Core\Model\Category;
use Jtl\Connector\Core\Model\Category as CategoryModel;
use Jtl\Connector\Core\Model\CategoryI18n;
use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\QueryFilter;
use JtlWooCommerceConnector\Integrations\Plugins\WooCommerce\WooCommerce;
use JtlWooCommerceConnector\Integrations\Plugins\WooCommerce\WooCommerceCategory;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\Wpml;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlCategory;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlTermTranslation;
use JtlWooCommerceConnector\Logger\ErrorFormatter;
use JtlWooCommerceConnector\Utilities\Category as CategoryUtil;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;
use Psr\Log\InvalidArgumentException;

class CategoryController extends AbstractBaseController implements
    PullInterface,
    PushInterface,
    DeleteInterface,
    StatisticInterface
{
    /** @var array<int, string|int> */
    private static array $idCache = [];

    /**
     * @param QueryFilter $query
     * @return array<Category>
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public function pull(QueryFilter $query): array
    {
        $categories = [];

        if ($this->wpml->canBeUsed()) {
            $wpmlCategory = $this->wpml->getComponent(WpmlCategory::class);

            /** @var WpmlCategory $wpmlCategory */
            $categoryData = $wpmlCategory->getCategories($query->getLimit());
        } else {
            $categoryUtil = new CategoryUtil($this->db);
            $categoryUtil->fillCategoryLevelTable();
            $categoryData = $this->db->query(SqlHelper::categoryPull($query->getLimit()));
            $categoryData = $categoryData === null ? [] : $categoryData;
        }

        /** @var array<string, int|string> $categoryDataSet */
        foreach ($categoryData as $categoryDataSet) {
            $category = (new CategoryModel())
                ->setId(new Identity((string)$categoryDataSet['category_id']))
                ->setLevel((int)$categoryDataSet['level'])
                ->setSort((int)$categoryDataSet['sort']);

            if (!empty($categoryDataSet['parent'])) {
                $category->setParentCategoryId(new Identity((string)$categoryDataSet['parent']));
            }

            $wooCommerceCategoryComponent = $this->getPluginsManager()
                ->get(WooCommerce::class)
                ->getComponent(WooCommerceCategory::class);

            $i18n = (new CategoryI18n())
                ->setLanguageISO($this->util->getWooCommerceLanguage())
                ->setName(\html_entity_decode((string)$categoryDataSet['name']))
                ->setDescription(\html_entity_decode((string)$categoryDataSet['description']))
                ->setUrlPath((string)$categoryDataSet['slug'])
                ->setTitleTag((string)$categoryDataSet['name']);

            if (
                SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO)
                || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO_PREMIUM)
            ) {
                //['product_cat' => [catId => seoData[desc -> string, focus -> string], catId => seoData]]
                /** @var array<string, array<int, array<string, string>>|bool|string> $taxonomySeo */
                $taxonomySeo = \get_option('wpseo_taxonomy_meta');

                if (isset($taxonomySeo['product_cat']) && \is_array($taxonomySeo['product_cat'])) {
                    /** @var array<string, string> $seoData */
                    foreach ($taxonomySeo['product_cat'] as $catId => $seoData) {
                        if ($catId === (int)$categoryDataSet['category_id']) {
                            $i18n
                                ->setMetaDescription($seoData['wpseo_desc'] ?? '')
                                ->setMetaKeywords($seoData['wpseo_focuskw'] ?? (string)$categoryDataSet['name'])
                                ->setTitleTag($seoData['wpseo_title'] ?? (string)$categoryDataSet['name']);
                        }
                    }
                }
            } elseif (
                SupportedPlugins::isActive(SupportedPlugins::PLUGIN_RANK_MATH_SEO)
                || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_RANK_MATH_SEO_AI)
            ) {
                $sql = SqlHelper::pullRankMathSeoTermData((int) $categoryDataSet['category_id']);
                /** @var array<int, array<string, string>> $categorySeoData */
                $categorySeoData = $this->db->query($sql);
                if (\is_array($categorySeoData)) {
                    $this->util->setI18nRankMathSeo($i18n, $categorySeoData);
                }
            }

            $category->addI18n($i18n);

            if ($this->wpml->canBeUsed()) {
                $wpmlTaxonomyTranslations = $this->wpml->getComponent(WpmlTermTranslation::class);

                /** @var WpmlTermTranslation $wpmlTaxonomyTranslations */
                $categoryTranslations = $wpmlTaxonomyTranslations
                    ->getTranslations((int)$categoryDataSet['trid'], 'tax_product_cat');

                foreach ($categoryTranslations as $languageCode => $translation) {
                    $term = $wpmlTaxonomyTranslations->getTranslatedTerm(
                        (int)$translation->term_id,
                        'product_cat'
                    );

                    if (isset($term['term_id'])) {
                        /** @var WooCommerceCategory $wooCommerceCategoryComponent */
                        $i18n = $wooCommerceCategoryComponent
                            ->createCategoryI18n(
                                $category,
                                $this->wpml->convertLanguageToWawi($translation->language_code),
                                [
                                    'name' => \html_entity_decode($translation->name),
                                    'slug' => $term['slug'],
                                    'description' => \html_entity_decode($term['description']),
                                    'category_id' => $term['term_id']
                                ]
                            );
                        $category->addI18n($i18n);
                    }
                }
            }
            $categories[] = $category;
        }

        return $categories;
    }

    /**
     * @param AbstractModel $model
     * @return CategoryModel
     * @throws \Exception
     */
    public function push(AbstractModel $model): AbstractModel
    {
        /** @var CategoryModel $model */
        if (!$model->getIsActive()) {
            return $model;
        }

        \update_option(CategoryUtil::OPTION_CATEGORY_HAS_CHANGED, 'yes');

        $parentCategoryId = $model->getParentCategoryId();

        if (isset(self::$idCache[$parentCategoryId->getHost()])) {
            $parentCategoryId->setEndpoint((string)self::$idCache[$parentCategoryId->getHost()]);
        }

        $meta       = null;
        $categoryId = (int)$model->getId()->getEndpoint();

        foreach ($model->getI18ns() as $i18n) {
            if ($this->wpml->canBeUsed()) {
                if ($this->wpml->getDefaultLanguage() === Util::mapLanguageIso($i18n->getLanguageIso())) {
                    $meta = $i18n;
                    break;
                }
            } else {
                if ($this->util->isWooCommerceLanguage($i18n->getLanguageISO())) {
                    $meta = $i18n;
                    break;
                }
            }
        }

        if (\is_null($meta)) {
            return $model;
        }

        $urlPath = $meta->getUrlPath();

        $categoryData = [
            'description' => $meta->getDescription(),
            'parent'      => $parentCategoryId->getEndpoint(),
            'name'        => $meta->getName(),
            'taxonomy'    => \wc_sanitize_taxonomy_name(CategoryUtil::TERM_TAXONOMY),
            'slug'        => !empty($urlPath) ? $urlPath : \strtolower($meta->getName())
        ];

        \remove_filter('pre_term_description', 'wp_filter_kses');

        if (empty($categoryId)) {
            $result = \wp_insert_term($meta->getName(), CategoryUtil::TERM_TAXONOMY, $categoryData);
        } else {
            $categoryTerm = \get_term($categoryId, CategoryUtil::TERM_TAXONOMY);
            if (!$categoryTerm instanceof \WP_Term) {
                throw new \Exception(\sprintf("Cannot find category %s", $categoryId));
            }
            // WordPress does not create a unique slug itself if the given already exists
            if ($categoryTerm->slug !== $categoryData['slug']) {
                $categoryData['slug'] = \wp_unique_term_slug($categoryData['slug'], (object)$categoryData);
            }

            $wpml = $this->getPluginsManager()->get(Wpml::class);
            if ($wpml->canBeUsed()) {
                $wpmlTermTranslation = $wpml->getComponent(WpmlTermTranslation::class);

                /** @var WpmlTermTranslation $wpmlTermTranslation */
                $wpmlTermTranslation->disableGetTermAdjustId();
            }

            $result = \wp_update_term($categoryId, CategoryUtil::TERM_TAXONOMY, $categoryData);

            if ($wpml->canBeUsed()) {
                $wpmlTermTranslation = $wpml->getComponent(WpmlTermTranslation::class);

                /** @var WpmlTermTranslation $wpmlTermTranslation */
                $wpmlTermTranslation->enableGetTermAdjustId();
            }
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

            if (\is_array($taxonomySeo) && !isset($taxonomySeo['product_cat'])) {
                $taxonomySeo['product_cat'] = [];
            }
            $exists = false;

            if (\is_array($taxonomySeo) && \is_array($taxonomySeo['product_cat'])) {
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
            }

            \update_option('wpseo_taxonomy_meta', $taxonomySeo, true);
        } elseif (
            SupportedPlugins::isActive(SupportedPlugins::PLUGIN_RANK_MATH_SEO)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_RANK_MATH_SEO_AI)
        ) {
            $updateRankMathSeoData = [
                'rank_math_title' => $meta->getTitleTag(),
                'rank_math_description' => $meta->getMetaDescription(),
                'rank_math_focus_keyword' => $meta->getMetaKeywords()
            ];
            $this->util->updateTermMeta($updateRankMathSeoData, (int) $result['term_id']);
        }

        if (!empty($result)) {
            $model->getId()->setEndpoint((string)$result['term_id']);
            self::$idCache[$model->getId()->getHost()] = $result['term_id'];

            (new CategoryUtil($this->db))->updateCategoryTree($model, empty($categoryId));

            if ($this->wpml->canBeUsed()) {
                /** @var WpmlCategory $wpmlCategory */
                $wpmlCategory = $this->wpml->getComponent(WpmlCategory::class);
                $wpmlCategory->setCategoryTranslations($model, $result, $parentCategoryId);
            }
        }

        return $model;
    }

    /**
     * @param AbstractModel $model
     * @return CategoryModel
     * @throws InvalidArgumentException
     */
    public function delete(AbstractModel $model): AbstractModel
    {
        /** @var Category $model */
        $categoryId = $model->getId()->getEndpoint();

        if (!empty($categoryId)) {
            \update_option(CategoryUtil::OPTION_CATEGORY_HAS_CHANGED, 'yes');

            $result = \wp_delete_term((int)$categoryId, CategoryUtil::TERM_TAXONOMY);

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
     * @throws \Exception
     */
    public function statistic(QueryFilter $query): int
    {
        if ($this->wpml->canBeUsed()) {
            $wpmlCategory = $this->wpml->getComponent(WpmlCategory::class);

            /** @var WpmlCategory $wpmlCategory */
            $count = (int)$wpmlCategory->getStats();
        } else {
            $count = (int)$this->db->queryOne(SqlHelper::categoryStats());
        }

        return $count;
    }
}
