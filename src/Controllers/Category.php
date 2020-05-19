<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers;

use jtl\Connector\Core\Exception\LanguageException;
use jtl\Connector\Core\Utilities\Language;
use jtl\Connector\Model\Category as CategoryModel;
use jtl\Connector\Model\Identity;
use JtlWooCommerceConnector\Controllers\Traits\DeleteTrait;
use JtlWooCommerceConnector\Controllers\Traits\PullTrait;
use JtlWooCommerceConnector\Controllers\Traits\PushTrait;
use JtlWooCommerceConnector\Controllers\Traits\StatsTrait;
use JtlWooCommerceConnector\Integrations\Plugins\WooCommerce\WooCommerce;
use JtlWooCommerceConnector\Integrations\Plugins\WooCommerce\WooCommerceCategory;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlCategory;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlTermTranslation;
use JtlWooCommerceConnector\Logger\WpErrorLogger;
use JtlWooCommerceConnector\Utilities\Category as CategoryUtil;
use JtlWooCommerceConnector\Utilities\Util;

class Category extends BaseController
{
    use PullTrait, PushTrait, DeleteTrait, StatsTrait;

    private static $idCache = [];

    /**
     * @param int $limit
     * @return mixed
     * @throws \Exception
     */
    protected function getCategoryData(int $limit)
    {
        if ($this->wpml->canBeUsed()) {
            $categoryData = $this->wpml
                ->getComponent(WpmlCategory::class)
                ->getCategories($limit);
        } else {
            $categoryData = $this->getPluginsManager()
                ->get(WooCommerce::class)
                ->getComponent(WooCommerceCategory::class)
                ->getCategories($limit);
        }

        return $categoryData;
    }

    /**
     * @param $limit
     * @return array
     * @throws \Exception
     */
    protected function pullData($limit)
    {
        $categories = [];

        $categoryData = $this->getCategoryData((int)$limit);

        $wooCommerceCategoryComponent = $this->getPluginsManager()->get(WooCommerce::class)->getComponent(WooCommerceCategory::class);

        foreach ($categoryData as $categoryDataSet) {
            $category = (new CategoryModel)
                ->setId(new Identity($categoryDataSet['category_id']))
                ->setLevel((int)$categoryDataSet['level'])
                ->setSort((int)$categoryDataSet['sort']);

            if (!empty($categoryDataSet['parent'])) {
                $category->setParentCategoryId(new Identity($categoryDataSet['parent']));
            }
            $i18n = $wooCommerceCategoryComponent->createCategoryI18n(
                $category,
                Util::getInstance()->getWooCommerceLanguage(),
                $categoryDataSet
            );
            $category->addI18n($i18n);

            if ($this->wpml->canBeUsed()) {

                $wpmlTaxonomyTranslations = $this->wpml
                    ->getComponent(WpmlTermTranslation::class);

                $categoryTranslations = $wpmlTaxonomyTranslations
                    ->getTranslations((int)$categoryDataSet['trid'], 'tax_product_cat');

                foreach ($categoryTranslations as $languageCode => $translation) {

                    $term = $wpmlTaxonomyTranslations->getTranslatedTerm(
                        (int)$translation->term_id,
                        'product_cat'
                    );

                    if (isset($term['term_id'])) {
                        $i18n = $wooCommerceCategoryComponent
                            ->createCategoryI18n(
                                $category,
                                Language::convert($translation->language_code),
                                [
                                    'name' => $translation->name,
                                    'slug' => $term['slug'],
                                    'description' => $term['description'],
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
     * @param CategoryModel $category
     * @return CategoryModel
     * @throws LanguageException
     * @throws \Exception
     */
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

        $defaultLanguageI18n = null;
        $categoryId = (int)$category->getId()->getEndpoint();

        foreach ($category->getI18ns() as $i18n) {
            if ($this->wpml->canBeUsed()) {
                if ($this->wpml->getDefaultLanguage() === Language::convert(null, $i18n->getLanguageISO())) {
                    $defaultLanguageI18n = $i18n;
                    break;
                }
            } else {
                if (Util::getInstance()->isWooCommerceLanguage($i18n->getLanguageISO())) {
                    $defaultLanguageI18n = $i18n;
                    break;
                }
            }
        }

        if (!is_null($defaultLanguageI18n)) {
            $result = $this->getPluginsManager()
                ->get(WooCommerce::class)
                ->getComponent(WooCommerceCategory::class)
                ->saveWooCommerceCategory($defaultLanguageI18n, $parentCategoryId, $categoryId);

            if (!empty($result)) {
                $category->getId()->setEndpoint($result['term_id']);
                self::$idCache[$category->getId()->getHost()] = $result['term_id'];

                CategoryUtil::updateCategoryTree($category, empty($categoryId));

                if ($this->wpml->canBeUsed()) {
                    $this->wpml
                        ->getComponent(WpmlCategory::class)
                        ->setCategoryTranslations($category, $result, $parentCategoryId);
                }
            }
        }

        return $category;
    }

    /**
     * @param CategoryModel $specific
     * @return CategoryModel
     */
    protected function deleteData(CategoryModel $specific)
    {
        $categoryId = $specific->getId()->getEndpoint();

        if (!empty($categoryId)) {
            update_option(CategoryUtil::OPTION_CATEGORY_HAS_CHANGED, 'yes');

            $result = \wp_delete_term($categoryId, CategoryUtil::TERM_TAXONOMY);

            if ($result instanceof \WP_Error) {
                WpErrorLogger::getInstance()->logError($result);

                return $specific;
            }

            unset(self::$idCache[$specific->getId()->getHost()]);
        }

        return $specific;
    }

    /**
     * @return int
     * @throws \Exception
     */
    protected function getStats(): int
    {
        if ($this->wpml->canBeUsed()) {
            $count = $this->wpml->getComponent(WpmlCategory::class)->getStats();
        } else {
            $count = $this->getPluginsManager()
                ->get(WooCommerce::class)
                ->getComponent(WooCommerceCategory::class)
                ->getStats();
        }

        return $count;
    }
}
