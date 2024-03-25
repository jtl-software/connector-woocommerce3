<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\Wpml;

use jtl\Connector\Core\Model\SpecificValue;
use jtl\Connector\Core\Model\SpecificValueI18n as SpecificValueI18nModel;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractComponent;
use JtlWooCommerceConnector\Integrations\Plugins\WooCommerce\WooCommerce;
use JtlWooCommerceConnector\Integrations\Plugins\WooCommerce\WooCommerceSpecificValue;

/**
 * Class WpmlSpecificValue
 * @package JtlWooCommerceConnector\Integrations\Plugins\Wpml
 */
class WpmlSpecificValue extends AbstractComponent
{
    /**
     * @param SpecificValue $specificValue
     * @param int $mainSpecificValueId
     * @param string $elementType
     * @throws \jtl\Connector\Core\Exception\LanguageException//TODO
     */
    public function getTranslations(SpecificValue $specificValue, int $mainSpecificValueId, string $elementType)
    {
        $trid         = $this->getCurrentPlugin()
            ->getElementTrid($mainSpecificValueId, 'tax_' . $elementType);
        $translations = $this->getCurrentPlugin()->getComponent(WpmlTermTranslation::class)
            ->getTranslations($trid, 'tax_' . $elementType, true);

        foreach ($translations as $languageCode => $translation) {
            $specificValue->addI18n((new SpecificValueI18nModel())
                ->setLanguageISO($this->getCurrentPlugin()->convertLanguageToWawi($languageCode))
                ->setValue($translation->name));
        }
    }

    /**
     * @param string $taxonomy
     * @param SpecificValue $specificValue
     * @param int $mainSpecificValueId
     * @throws \Exception
     */
    public function setTranslations(string $taxonomy, SpecificValue $specificValue, int $mainSpecificValueId)
    {
        $type = 'tax_' . $taxonomy;
        $trid = $this->getCurrentPlugin()->getElementTrid($mainSpecificValueId, $type);

        foreach ($specificValue->getI18ns() as $specificValueI18n) {
            $languageCode = $this->getCurrentPlugin()->convertLanguageToWpml($specificValueI18n->getLanguageISO());
            if ($this->getCurrentPlugin()->getDefaultLanguage() === $languageCode) {
                continue;
            }

            $specificTranslation = $this->findSpecificValueTranslation($trid, $taxonomy, $languageCode);
            if (isset($specificTranslation['term_taxonomy_id'])) {
                $specificValue->getId()->setEndpoint($specificTranslation['term_taxonomy_id']);
            } else {
                $specificValue->getId()->setEndpoint(0);
            }

            $slug = \wc_sanitize_taxonomy_name($specificValueI18n->getValue()) . '-' . $languageCode;

            $specificValueId = $this->getCurrentPlugin()
                ->getPluginsManager()
                ->get(WooCommerce::class)
                ->getComponent(WooCommerceSpecificValue::class)
                ->save($taxonomy, $specificValue, $specificValueI18n, $slug);

            if (!\is_null($specificValueId) && $specificValueId !== 0) {
                $this->getCurrentPlugin()->getSitepress()->set_element_language_details(
                    $specificValueId,
                    $type,
                    $trid,
                    $languageCode
                );
            }
        }
    }

    /**
     * @param int $trid
     * @param string $taxonomy
     * @param string $languageCode
     * @return array
     */
    public function findSpecificValueTranslation(int $trid, string $taxonomy, string $languageCode)
    {
        $specificTranslations = $this
            ->getCurrentPlugin()
            ->getComponent(WpmlTermTranslation::class)
            ->getTranslations($trid, 'tax_' . $taxonomy, false);

        $translation = [];
        if (isset($specificTranslations[$languageCode])) {
            $translationData = $specificTranslations[$languageCode];
            $translation     = $this->getCurrentPlugin()
                ->getComponent(WpmlTermTranslation::class)
                ->getTranslatedTerm($translationData->term_id, $taxonomy);
        }
        return $translation;
    }
}
