<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\Wpml;

use jtl\Connector\Model\Currency;
use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Language;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractComponent;
use JtlWooCommerceConnector\Utilities\Util;

/**
 * Class WpmlTaxonomyTranslation
 * @package JtlWooCommerceConnector\Integrations\Plugins\Wpml
 */
class WpmlTaxonomyTranslation extends AbstractComponent
{
    /**
     *
     */
    public const
        TAX_PRODUCT_CAT = 'tax_product_cat';

    /**
     * @param int $trid
     * @param string $elementType
     * @return array
     */
    public function getTranslations(int $trid, string $elementType): array
    {
        return $this->getPlugin()->getSitepress()->get_element_translations($trid, $elementType);
    }

    /**
     * @param int $translatedTermId
     * @param string $taxonomy
     * @return array|false|\WP_Term
     */
    public function getTranslatedTerm(int $translatedTermId, string $taxonomy)
    {
        $this->disableGetTermAdjustId();
        $term = get_term_by('id', $translatedTermId, $taxonomy);
        $this->enableGetTermAdjustId();

        return $term;
    }

    /**
     *
     */
    public function disableGetTermAdjustId()
    {
        remove_filter('get_term', array($this->getPlugin()->getSitepress(), 'get_term_adjust_id'), 1);
    }

    /**
     *
     */
    public function enableGetTermAdjustId()
    {
        add_filter('get_term', array($this->getPlugin()->getSitepress(), 'get_term_adjust_id'), 1, 1);
    }
}