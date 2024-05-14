<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\WooCommerce;

use Jtl\Connector\Core\Model\Specific;
use Jtl\Connector\Core\Model\SpecificI18n;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractComponent;
use JtlWooCommerceConnector\Logger\ErrorFormatter;
use JtlWooCommerceConnector\Utilities\Util;

/**
 * Class WooCommerceSpecific
 * @package JtlWooCommerceConnector\Integrations\Plugins\WooCommerce
 */
class WooCommerceSpecific extends AbstractComponent
{
    /**
     * @param Specific $specific
     * @param SpecificI18n $specificI18n
     * @return Specific|null
     */
    public function save(Specific $specific, SpecificI18n $specificI18n): ?Specific
    {
        $attrName = \wc_sanitize_taxonomy_name(Util::removeSpecialchars($specificI18n->getName()));

        //STOP here if already exists
        $existingTaxonomyId = Util::getAttributeTaxonomyIdByName($attrName);
        $endpointId         = (int)$specific->getId()->getEndpoint();

        if ($existingTaxonomyId !== 0) {
            if ($existingTaxonomyId !== $endpointId) {
                $attrId = $existingTaxonomyId;
            } else {
                $attrId = $endpointId;
            }
        } else {
            $attrId = $endpointId;
        }

        $endpoint = [
            'id' => $attrId,
            'name' => $specificI18n->getName(),
            'slug' => \wc_sanitize_taxonomy_name(\substr(\trim($specificI18n->getName()), 0, 27)),
            'type' => 'select',
            'order_by' => 'menu_order'
        ];

        if ($endpoint['id'] === 0) {
            $attributeId = \wc_create_attribute($endpoint);
        } else {
            $attributeId = \wc_update_attribute($endpoint['id'], $endpoint);
        }

        if ($attributeId instanceof \WP_Error) {
            $this->logger->error(ErrorFormatter::formatError($attributeId));
            return null;
        }

        $specific->getId()->setEndpoint($attributeId);
        return $specific;
    }
}
