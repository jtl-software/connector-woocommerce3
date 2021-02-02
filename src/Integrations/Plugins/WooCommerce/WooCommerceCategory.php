<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\WooCommerce;

use jtl\Connector\Model\Category;
use jtl\Connector\Model\CategoryI18n;
use jtl\Connector\Model\CategoryI18n as CategoryI18nModel;
use jtl\Connector\Model\Identity;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractComponent;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\Wpml;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlTermTranslation;
use JtlWooCommerceConnector\Integrations\Plugins\YoastSeo\YoastSeo;
use JtlWooCommerceConnector\Logger\WpErrorLogger;
use JtlWooCommerceConnector\Utilities\Category as CategoryUtil;
use JtlWooCommerceConnector\Utilities\SqlHelper;

/**
 * Class WooCommerceCategory
 * @package JtlWooCommerceConnector\Integrations\Plugins\WooCommerce
 */
class WooCommerceCategory extends AbstractComponent
{
    /**
     * @param Category $category
     * @param string $languageIso
     * @param array $data
     * @return CategoryI18nModel
     * @throws \Exception
     */
    public function createCategoryI18n(Category $category, string $languageIso, array $data): CategoryI18n
    {
        $i18n = (new CategoryI18nModel)
            ->setCategoryId($category->getId())
            ->setLanguageISO($languageIso)
            ->setName($data['name'])
            ->setDescription($data['description'])
            ->setUrlPath($data['slug'])
            ->setTitleTag($data['name']);

        /** @var YoastSeo $yoastSeo */
        $yoastSeo = $this->getCurrentPlugin()->getPluginsManager()->get(YoastSeo::class);
        if ($yoastSeo->canBeUsed()) {
            $seoData = $yoastSeo->findCategorySeoData($data['category_id']);
            if (!empty($seoData)) {
                $i18n->setMetaDescription(isset($seoData['wpseo_desc']) ? $seoData['wpseo_desc'] : '')
                    ->setMetaKeywords(isset($seoData['wpseo_focuskw']) ? $seoData['wpseo_focuskw'] : $data['name'])
                    ->setTitleTag(isset($seoData['wpseo_title']) ? $seoData['wpseo_title'] : $data['name']);
            }
        }

        return $i18n;
    }

    /**
     * @return int
     */
    public function getStats(): int
    {
        return (int)$this->getCurrentPlugin()->getPluginsManager()->getDatabase()->queryOne(SqlHelper::categoryStats());
    }

    /**
     * @param int $limit
     * @return array
     */
    public function getCategories(int $limit): array
    {
        CategoryUtil::fillCategoryLevelTable();
        return $this->getCurrentPlugin()->getPluginsManager()->getDatabase()->query(SqlHelper::categoryPull($limit));
    }

    /**
     * @param CategoryI18nModel $categoryI18n
     * @param Identity $parentCategoryId
     * @param int|null $categoryId
     * @return array
     * @throws \Exception
     */
    public function saveWooCommerceCategory(CategoryI18n $categoryI18n, Identity $parentCategoryId,?int $categoryId = null): array
    {
        $categoryData = [
            'description' => $categoryI18n->getDescription(),
            'parent' => $parentCategoryId->getEndpoint(),
            'name' => $categoryI18n->getName(),
            'taxonomy' => \wc_sanitize_taxonomy_name($categoryI18n->getName()),
            'slug' => !empty($categoryI18n->getUrlPath()) ? $categoryI18n->getUrlPath() : $categoryI18n->getName()
        ];

        remove_filter('pre_term_description', 'wp_filter_kses');
        if (empty($categoryId)) {
            $result = \wp_insert_term($categoryI18n->getName(), CategoryUtil::TERM_TAXONOMY, $categoryData);
        } else {
            if (isset($categoryData['slug'])) {
                $categoryData['slug'] = wp_unique_term_slug($categoryData['slug'], (object)$categoryData);
            }
            $wpml = $this->getCurrentPlugin()->getPluginsManager()->get(Wpml::class);
            if ($wpml->canBeUsed()) {
                $wpml->getComponent(WpmlTermTranslation::class)->disableGetTermAdjustId();
            }

            $result = \wp_update_term($categoryId, CategoryUtil::TERM_TAXONOMY, $categoryData);

            if ($wpml->canBeUsed()) {
                $wpml->getComponent(WpmlTermTranslation::class)->enableGetTermAdjustId();
            }
        }
        add_filter('pre_term_description', 'wp_filter_kses');

        if ($result instanceof \WP_Error) {
            WpErrorLogger::getInstance()->logError($result);
            return [];
        }

        $categoryId = (int) $result['term_id'];

        $yoastSeo = $this->getCurrentPlugin()->getPluginsManager()->get(YoastSeo::class);
        if ($yoastSeo->canBeUsed()) {
            $yoastSeo->setCategorySeoData($categoryId, $categoryI18n);
        }

        return $result;
    }
}