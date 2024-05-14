<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\WooCommerce;

use Jtl\Connector\Core\Model\SpecificValue;
use Jtl\Connector\Core\Model\SpecificValueI18n;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractComponent;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\Wpml;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlTermTranslation;
use JtlWooCommerceConnector\Logger\ErrorFormatter;
use JtlWooCommerceConnector\Utilities\SqlHelper;

/**
 * Class WooCommerceSpecificValue
 * @package JtlWooCommerceConnector\Integrations\Plugins\WooCommerce
 */
class WooCommerceSpecificValue extends AbstractComponent
{
    /**
     * @param string $taxonomy
     * @param SpecificValue $specificValue
     * @param SpecificValueI18n $specificValueI18n
     * @param null $slug
     * @return SpecificValue|null
     * @throws \Exception
     */
    public function save(
        string $taxonomy,
        SpecificValue $specificValue,
        SpecificValueI18n $specificValueI18n,
        $slug = null
    ): ?int {
        $endpointValue = [
            'name' => $specificValueI18n->getValue(),
            'slug' => $slug ?? \wc_sanitize_taxonomy_name($specificValueI18n->getValue()),
        ];

        $exValId = $this->getCurrentPlugin()->getPluginsManager()->getDatabase()->query(
            SqlHelper::getSpecificValueIdBySlug(
                $taxonomy,
                $endpointValue['slug']
            )
        );

        if (\count($exValId) >= 1) {
            if (isset($exValId[0]['term_id'])) {
                $exValId = $exValId[0]['term_id'];
            } else {
                $exValId = null;
            }
        } else {
            $exValId = null;
        }

        $endValId = (int)$specificValue->getId()->getEndpoint();

        if (\is_null($exValId) && $endValId === 0) {
            $newTerm = \wp_insert_term(
                $endpointValue['name'],
                $taxonomy
            );

            if ($newTerm instanceof \WP_Error) {
                $this->logger->error(ErrorFormatter::formatError($newTerm));
                return null;
            }

            $termId = $newTerm['term_id'];
        } elseif (\is_null($exValId) && $endValId !== 0) {
            $wpml = $this->getPluginsManager()->get(Wpml::class);
            if ($wpml->canBeUsed()) {
                $wpml->getComponent(WpmlTermTranslation::class)->disableGetTermAdjustId();
            }

            $termId = \wp_update_term($endValId, $taxonomy, $endpointValue);

            if ($wpml->canBeUsed()) {
                $wpml->getComponent(WpmlTermTranslation::class)->enableGetTermAdjustId();
            }
        } else {
            $termId = $exValId;
        }

        if ($termId instanceof \WP_Error) {
            $this->logger->error(ErrorFormatter::formatError($termId));
            return null;
        }

        if (\is_array($termId)) {
            $termId = $termId['term_id'];
        }

        return (int) $termId;
    }
}
