<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\Wpml;

use JtlWooCommerceConnector\Integrations\Plugins\AbstractComponent;

/**
 * Class WpmlPerfectWooCommerceBrands
 * @package JtlWooCommerceConnector\Integrations\Plugins\Wpml
 */
class WpmlPerfectWooCommerceBrands extends AbstractComponent
{
    /**
     * @param int $limit
     * @return array
     */
    public function getManufacturers(int $limit): array
    {
        $wpdb = $this->getPlugin()->getWpDb();

        $jclm = $wpdb->prefix . 'jtl_connector_link_manufacturer';
        $translations = $wpdb->prefix . 'icl_translations';

        $sql = sprintf( "
            SELECT t.term_id, tt.parent, tt.description, t.name, t.slug, tt.count, wpmlt.trid
            FROM `{$wpdb->terms}` t
            LEFT JOIN `{$wpdb->term_taxonomy}` tt ON t.term_id = tt.term_id
            LEFT JOIN `%s` l ON t.term_id = l.endpoint_id
            LEFT JOIN `%s` wpmlt ON t.term_id = wpmlt.element_id
            WHERE tt.taxonomy = '%s' AND l.host_id IS NULL
              
                AND wpmlt.element_type = 'tax_pwb-brand'
                AND wpmlt.source_language_code IS NULL                 
                AND wpmlt.language_code = '%s'
            
            ORDER BY tt.parent ASC
            LIMIT {$limit}",
            $jclm,
            $translations,
            'pwb-brand',
            $this->getPlugin()->getDefaultLanguage()
        );

        return $this->getPlugin()->getPluginsManager()->getDatabase()->query($sql);
    }
}
