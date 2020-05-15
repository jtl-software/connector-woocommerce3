<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\Wpml;

use JtlWooCommerceConnector\Integrations\Plugins\AbstractComponent;

/**
 * Class WpmlTermTranslation
 * @package JtlWooCommerceConnector\Integrations\Plugins\Wpml
 */
class WpmlTermTranslation extends AbstractComponent
{
    /**
     * @param int $trid
     * @param string $elementType
     * @param bool $withoutDefaultTranslation
     * @return array
     */
    public function getTranslations(int $trid, string $elementType, bool $withoutDefaultTranslation = true): array
    {
        $translations = $this->getPlugin()->getSitepress()->get_element_translations($trid, $elementType);
        if ($withoutDefaultTranslation === true && isset($translations[$this->getPlugin()->getDefaultLanguage()])) {
            unset($translations[$this->getPlugin()->getDefaultLanguage()]);
        }
        return $translations;
    }

    /**
     * @param int $translatedTermId
     * @param string $taxonomy
     * @return array
     */
    public function getTranslatedTerm(int $translatedTermId, string $taxonomy): array
    {
        $this->disableGetTermAdjustId();
        $term = $this->getTermById($translatedTermId, $taxonomy);
        $this->enableGetTermAdjustId();

        return ($term instanceof \WP_Term) ? $term->to_array() : (is_array($term) ? $term : []);
    }

    /**
     * @param int $translatedTermId
     * @param string $taxonomy
     * @return array|false|\WP_Term
     */
    protected function getTermById(int $translatedTermId, string $taxonomy)
    {
        return get_term_by('id', $translatedTermId, $taxonomy);
    }

    /**
     * @return bool
     */
    public function disableGetTermAdjustId(): bool
    {
        return remove_filter('get_term', array($this->getPlugin()->getSitepress(), 'get_term_adjust_id'), 1);
    }

    /**
     * @return true
     */
    public function enableGetTermAdjustId(): bool
    {
        return add_filter('get_term', array($this->getPlugin()->getSitepress(), 'get_term_adjust_id'), 1, 1);
    }
}