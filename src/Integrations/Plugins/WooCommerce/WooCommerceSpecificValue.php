<?php

declare(strict_types=1);

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
 *
 * @package JtlWooCommerceConnector\Integrations\Plugins\WooCommerce
 */
class WooCommerceSpecificValue extends AbstractComponent
{
    /**
     * @param string            $taxonomy
     * @param SpecificValue     $specificValue
     * @param SpecificValueI18n $specificValueI18n
     * @param string            $slug
     * @return int|null
     * @throws \Exception
     */
    public function save(
        string $taxonomy,
        SpecificValue $specificValue,
        SpecificValueI18n $specificValueI18n,
        ?string $slug = null
    ): ?int {
        $endpointValue = [
            'name' => $specificValueI18n->getValue(),
            'slug' => $slug ?? \wc_sanitize_taxonomy_name($specificValueI18n->getValue()),
        ];

        /** @var array<int, array<string, int|string>> $exValId */
        $exValId = $this->getCurrentPlugin()->getPluginsManager()->getDatabase()->query(
            SqlHelper::getSpecificValueIdBySlug(
                $taxonomy,
                $endpointValue['slug']
            )
        ) ?? [];

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
            /** @var array<string, int|string>|\WP_Error $newTerm */
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

            /** @var WpmlTermTranslation $wpmlTermTranslation */
            $wpmlTermTranslation = $wpml->getComponent(WpmlTermTranslation::class);

            if ($wpml->canBeUsed()) {
                $wpmlTermTranslation->disableGetTermAdjustId();
            }

            /** @var array<string, int|string>|\WP_Error $termId */
            $termId = \wp_update_term($endValId, $taxonomy, $endpointValue);

            if ($wpml->canBeUsed()) {
                $wpmlTermTranslation->enableGetTermAdjustId();
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
