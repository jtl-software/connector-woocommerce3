<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Integrations\Plugins\Wpml;

use Jtl\Connector\Core\Model\SpecificValue;
use Jtl\Connector\Core\Model\SpecificValueI18n as SpecificValueI18nModel;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractComponent;
use JtlWooCommerceConnector\Integrations\Plugins\WooCommerce\WooCommerce;
use JtlWooCommerceConnector\Integrations\Plugins\WooCommerce\WooCommerceSpecificValue;

/**
 * Class WpmlSpecificValue
 *
 * @package JtlWooCommerceConnector\Integrations\Plugins\Wpml
 */
class WpmlSpecificValue extends AbstractComponent
{
    /**
     * @param SpecificValue $specificValue
     * @param int           $mainSpecificValueId
     * @param string        $elementType
     * @return void
     * @throws \Exception
     */
    public function getTranslations(SpecificValue $specificValue, int $mainSpecificValueId, string $elementType): void
    {
        /** @var Wpml $wpmlPlugin */
        $wpmlPlugin = $this->getCurrentPlugin();
        $trid       = $wpmlPlugin->getElementTrid($mainSpecificValueId, 'tax_' . $elementType);

        /** @var WpmlTermTranslation $wpmlTermTranslation */
        $wpmlTermTranslation = $wpmlPlugin->getComponent(WpmlTermTranslation::class);
        $translations        = $wpmlTermTranslation
            ->getTranslations((int)$trid, 'tax_' . $elementType, true);

        foreach ($translations as $languageCode => $translation) {
            $specificValue->addI18n((new SpecificValueI18nModel())
                ->setLanguageISO($wpmlPlugin->convertLanguageToWawi($languageCode))
                ->setValue($translation->name));
        }
    }

    /**
     * @param string        $taxonomy
     * @param SpecificValue $specificValue
     * @param int           $mainSpecificValueId
     * @return void
     * @throws \Exception
     */
    public function setTranslations(string $taxonomy, SpecificValue $specificValue, int $mainSpecificValueId): void
    {
        $type = 'tax_' . $taxonomy;

        /** @var Wpml $wpmlPlugin */
        $wpmlPlugin = $this->getCurrentPlugin();
        $trid       = $wpmlPlugin->getElementTrid($mainSpecificValueId, $type);

        foreach ($specificValue->getI18ns() as $specificValueI18n) {
            $languageCode = $wpmlPlugin->convertLanguageToWpml($specificValueI18n->getLanguageISO());
            if ($wpmlPlugin->getDefaultLanguage() === $languageCode) {
                continue;
            }

            $specificTranslation = $this->findSpecificValueTranslation((int)$trid, $taxonomy, $languageCode);
            if (isset($specificTranslation['term_taxonomy_id'])) {
                $specificValue->getId()->setEndpoint($specificTranslation['term_taxonomy_id']);
            } else {
                $specificValue->getId()->setEndpoint('0');
            }

            $slug = \wc_sanitize_taxonomy_name($specificValueI18n->getValue()) . '-' . $languageCode;

            /** @var WooCommerceSpecificValue $wooCommerceSpecificValue */
            $wooCommerceSpecificValue = $wpmlPlugin
                ->getPluginsManager()
                ->get(WooCommerce::class)
                ->getComponent(WooCommerceSpecificValue::class);

            $specificValueId = $wooCommerceSpecificValue
                ->save($taxonomy, $specificValue, $specificValueI18n, $slug);

            if (!\is_null($specificValueId) && $specificValueId !== 0) {
                $wpmlPlugin->getSitepress()->set_element_language_details(
                    $specificValueId,
                    $type,
                    (int)$trid,
                    $languageCode
                );
            }
        }
    }

    /**
     * @param int    $trid
     * @param string $taxonomy
     * @param string $languageCode
     * @return array<string, string>
     */
    public function findSpecificValueTranslation(int $trid, string $taxonomy, string $languageCode): array
    {
        /** @var WpmlTermTranslation $wpmlTermTranslation */
        $wpmlTermTranslation = $this->getCurrentPlugin()->getComponent(WpmlTermTranslation::class);

        $specificTranslations = $wpmlTermTranslation
            ->getTranslations($trid, 'tax_' . $taxonomy, false);

        $translation = [];
        if (isset($specificTranslations[$languageCode])) {
            $translationData = $specificTranslations[$languageCode];
            $translation     = $wpmlTermTranslation
                ->getTranslatedTerm($translationData->term_id, $taxonomy);
        }
        return $translation;
    }
}
