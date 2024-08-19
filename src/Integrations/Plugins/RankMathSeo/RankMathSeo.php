<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Integrations\Plugins\RankMathSeo;

use Jtl\Connector\Core\Model\ManufacturerI18n;
use Jtl\Connector\Core\Model\ProductI18n;
use Jtl\Connector\Core\Model\CategoryI18n;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractPlugin;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;
use Psr\Log\InvalidArgumentException;

/**
 * Class RankMathSeo
 *
 * @package JtlWooCommerceConnector\Integrations\Plugins\YoastSeo
 */
class RankMathSeo extends AbstractPlugin
{
    /**
     * @return bool
     */
    public function canBeUsed(): bool
    {
        return SupportedPlugins::isActive(SupportedPlugins::PLUGIN_RANK_MATH_SEO)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_RANK_MATH_SEO_AI);
    }

    /**
     * @param int $temId
     * @return array<int, array<string, string>>|null
     * @throws InvalidArgumentException
     */
    public function findManufacturerSeoData(int $temId): array|null
    {
        /** @var array<int, array<string, string>>|null $manufacturerSeoData*/
        $manufacturerSeoData = $this->getPluginsManager()
            ->getDatabase()
            ->query(SqlHelper::pullRankMathSeoTermData($temId));

        return $manufacturerSeoData;
    }

    /**
     * @param \WC_Product $wcProduct
     * @param ProductI18n $jtlProductI18n
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    public function setProductSeoData(\WC_Product $wcProduct, ProductI18n $jtlProductI18n): void
    {
        $utils = (new Util($this->getPluginsManager()->getDatabase()));

        /** @var string $rankMathTitle */
        $rankMathTitle = $utils->getPostMeta($wcProduct->get_id(), 'rank_math_title', true);

        /** @var string $rankMathDescription */
        $rankMathDescription = $utils->getPostMeta($wcProduct->get_id(), 'rank_math_description', true);

        /** @var string $rankMathFocusKeyword */
        $rankMathFocusKeyword = $utils->getPostMeta($wcProduct->get_id(), 'rank_math_focus_keyword', true);

        $jtlProductI18n->setTitleTag($rankMathTitle)
            ->setMetaDescription($rankMathDescription)
            ->setMetaKeywords($rankMathFocusKeyword)
            ->setUrlPath($wcProduct->get_slug());
    }

    /**
     * @param int          $categoryId
     * @param CategoryI18n $categoryI18n
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    public function setCategorySeoData(int $categoryId, CategoryI18n $categoryI18n): void
    {
        $updateRankMathSeoData = [
            'rank_math_title' => $categoryI18n->getTitleTag(),
            'rank_math_description' => $categoryI18n->getMetaDescription(),
            'rank_math_focus_keyword' => $categoryI18n->getMetaKeywords()
        ];
        (new \JtlWooCommerceConnector\Utilities\Util($this->getPluginsManager()->getDatabase()))
            ->updateTermMeta($updateRankMathSeoData, $categoryId);
    }

    /**
     * @param int                           $taxonomyId
     * @param ManufacturerI18n|CategoryI18n $i18nModel
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    public function updateWpSeoTaxonomyMeta(int $taxonomyId, ManufacturerI18n|CategoryI18n $i18nModel): void
    {
        $taxonomySeo = [
            'rank_math_description' => $i18nModel->getMetaDescription(),
            'rank_math_focus_keyword' => $i18nModel->getMetaKeywords(),
            'rank_math_title' => $i18nModel->getTitleTag()
        ];

        (new \JtlWooCommerceConnector\Utilities\Util($this->getPluginsManager()->getDatabase()))
            ->updateTermMeta($taxonomySeo, $taxonomyId);
    }
}
