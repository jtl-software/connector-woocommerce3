<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Integrations\Plugins\Wpml;

use JtlWooCommerceConnector\Integrations\Plugins\AbstractComponent;
use JtlWooCommerceConnector\Integrations\Plugins\PluginInterface;
use stdClass;

/**
 * Class WpmlTermTranslation
 *
 * @package JtlWooCommerceConnector\Integrations\Plugins\Wpml
 */
class WpmlTermTranslation extends AbstractComponent
{
    /**
     * @param int    $trid
     * @param string $elementType
     * @param bool   $withoutDefaultTranslation
     * @return stdClass[]
     */
    public function getTranslations(int $trid, string $elementType, bool $withoutDefaultTranslation = true): array
    {
        /** @var Wpml $wpmlPlugin */
        $wpmlPlugin   = $this->getCurrentPlugin();
        $translations = $wpmlPlugin->getSitepress()->get_element_translations($trid, $elementType);

        if (
            $withoutDefaultTranslation === true
            && isset($translations[$wpmlPlugin->getDefaultLanguage()])
        ) {
            unset($translations[$wpmlPlugin->getDefaultLanguage()]);
        }
        return $translations;
    }

    /**
     * @param int    $translatedTermId
     * @param string $taxonomy
     * @return array<string, string>
     */
    public function getTranslatedTerm(int $translatedTermId, string $taxonomy): array
    {
        $this->disableGetTermAdjustId();
        $term = $this->getTermById($translatedTermId, $taxonomy);
        $this->enableGetTermAdjustId();

        return ($term instanceof \WP_Term) ? $term->to_array() : [];
    }

    /**
     * @param int    $translatedTermId
     * @param string $taxonomy
     * @return false|\WP_Term
     */
    protected function getTermById(int $translatedTermId, string $taxonomy): \WP_Term|bool
    {
        /** @var false|\WP_Term $term */
        $term = \get_term_by('id', $translatedTermId, $taxonomy);
        return $term;
    }

    /**
     * @return bool
     */
    public function disableGetTermAdjustId(): bool
    {
        /** @var Wpml $wpmlPlugin */
        $wpmlPlugin = $this->getCurrentPlugin();

        return \remove_filter('get_terms_args', [$wpmlPlugin->getSitepress(), 'get_terms_args_filter']) &&
            \remove_filter('get_term', [$wpmlPlugin->getSitepress(), 'get_term_adjust_id'], 1) &&
            \remove_filter('terms_clauses', [$wpmlPlugin->getSitepress(), 'terms_clauses']);
    }

    /**
     * @return void
     */
    public function enableGetTermAdjustId(): void
    {
        /** @var Wpml $wpmlPlugin */
        $wpmlPlugin = $this->getCurrentPlugin();

        \add_filter('terms_clauses', [$wpmlPlugin->getSitepress(), 'terms_clauses'], 10, 3);
        \add_filter('get_term', [$wpmlPlugin->getSitepress(), 'get_term_adjust_id'], 1, 1);
        \add_filter('get_terms_args', [$wpmlPlugin->getSitepress(), 'get_terms_args_filter'], 10, 2);
    }
}
