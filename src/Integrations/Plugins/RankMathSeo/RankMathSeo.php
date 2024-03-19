<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\RankMathSeo;

use jtl\Connector\Core\Model\Model;
use jtl\Connector\Model\ProductI18n;
use jtl\Connector\Model\CategoryI18n;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractPlugin;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;

/**
 * Class RankMathSeo
 * @package JtlWooCommerceConnector\Integrations\Plugins\YoastSeo
 */
class RankMathSeo extends AbstractPlugin
{
    /**
     * @return bool
     */
    public function canBeUsed(): bool
    {
        return SupportedPlugins::isActive(SupportedPlugins::PLUGIN_RANK_MATH_SEO);
    }

    /**
     * @param $temId
     * @return array
     */
    public function findManufacturerSeoData($temId): array
    {
        return $this->getPluginsManager()->getDatabase()->query(SqlHelper::pullRankMathSeoTermData($temId));
    }

    /**
     * @param \WC_Product $wcProduct
     * @param ProductI18n $jtlProductI18n
     */
    public function setProductSeoData(\WC_Product $wcProduct, ProductI18n $jtlProductI18n)
    {
        $utils = Util::getInstance();
        $jtlProductI18n->setTitleTag($utils->getPostMeta($wcProduct->get_id(),'rank_math_title',true))
            ->setMetaDescription($utils->getPostMeta($wcProduct->get_id(),'rank_math_description',true))
            ->setMetaKeywords($utils->getPostMeta($wcProduct->get_id(),'rank_math_focus_keyword',true))
            ->setUrlPath($wcProduct->get_slug());
    }

    /**
     * @param int $categoryId
     * @param CategoryI18n $categoryI18n
     */
    public function setCategorySeoData(int $categoryId, CategoryI18n $categoryI18n)
    {
        $updateRankMathSeoData = [
            'rank_math_title' => $categoryI18n->getTitleTag(),
            'rank_math_description' => $categoryI18n->getMetaDescription(),
            'rank_math_focus_keyword' => $categoryI18n->getMetaKeywords()
        ];
        Util::updateTermMeta($updateRankMathSeoData, $categoryId);
    }

    /**
     * @param int $taxonomyId
     * @param Model $i18nModel
     */
    public function updateWpSeoTaxonomyMeta(int $taxonomyId, Model $i18nModel): void
    {
        $taxonomySeo = [
            'rank_math_description' => $i18nModel->getMetaDescription(),
            'rank_math_focus_keyword' => $i18nModel->getMetaKeywords(),
            'rank_math_title' => $i18nModel->getTitleTag()
        ];

        Util::updateTermMeta($taxonomySeo, $taxonomyId);
    }
}
