<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\RankMathSeo;

use Jtl\Connector\Core\Model\AbstractI18n;
use jtl\Connector\Core\Model\Model;
use jtl\Connector\Core\Model\ProductI18n;
use jtl\Connector\Core\Model\CategoryI18n;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractPlugin;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;
use Psr\Log\InvalidArgumentException;

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
        return SupportedPlugins::isActive(SupportedPlugins::PLUGIN_RANK_MATH_SEO)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_RANK_MATH_SEO_AI);
    }

    /**
     * @param $temId
     * @return array
     * @throws InvalidArgumentException
     */
    public function findManufacturerSeoData($temId): array
    {
        return $this->getPluginsManager()->getDatabase()->query(SqlHelper::pullRankMathSeoTermData($temId));
    }

    /**
     * @param \WC_Product $wcProduct
     * @param ProductI18n $jtlProductI18n
     * @throws \InvalidArgumentException
     */
    public function setProductSeoData(\WC_Product $wcProduct, ProductI18n $jtlProductI18n): void
    {
        $utils = (new Util($this->getPluginsManager()->getDatabase()));
        $jtlProductI18n->setTitleTag($utils->getPostMeta($wcProduct->get_id(), 'rank_math_title', true))
            ->setMetaDescription($utils->getPostMeta($wcProduct->get_id(), 'rank_math_description', true))
            ->setMetaKeywords($utils->getPostMeta($wcProduct->get_id(), 'rank_math_focus_keyword', true))
            ->setUrlPath($wcProduct->get_slug());
    }

    /**
     * @param int $categoryId
     * @param CategoryI18n $categoryI18n
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
     * @param int $taxonomyId
     * @param AbstractI18n $i18nModel
     * @throws \InvalidArgumentException
     */
    public function updateWpSeoTaxonomyMeta(int $taxonomyId, AbstractI18n $i18nModel): void
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
