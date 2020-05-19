<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\Wpml;

use jtl\Connector\Core\Utilities\Language;
use jtl\Connector\Model\SpecificValue;
use jtl\Connector\Model\SpecificValueI18n as SpecificValueI18nModel;
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
     * @throws \jtl\Connector\Core\Exception\LanguageException
     */
    public function getTranslations(SpecificValue $specificValue, int $mainSpecificValueId, string $elementType)
    {
        $trid = (int)$this->getPlugin()->getElementTrid($mainSpecificValueId, 'tax_'.$elementType);
        $translations = $this->getPlugin()->getComponent(WpmlTermTranslation::class)->getTranslations($trid, 'tax_'.$elementType, true);

        foreach ($translations as $languageCode => $translation) {
            $specificValue->addI18n((new SpecificValueI18nModel)
                ->setLanguageISO(Language::convert($languageCode))
                ->setSpecificValueId($specificValue->getId())
                ->setValue($translation->name));
        }
    }

    /**
     * @param string $taxonomy
     * @param SpecificValue $specificValue
     * @throws \jtl\Connector\Core\Exception\LanguageException
     */
    public function setTranslations(string $taxonomy, SpecificValue $specificValue)
    {
        $type = 'tax_' . $taxonomy;
        $trid = (int)$this->getPlugin()->getElementTrid((int)$specificValue->getId()->getEndpoint(), $type);

        foreach ($specificValue->getI18ns() as $specificValueI18n) {
            $languageCode = Language::convert(null, $specificValueI18n->getLanguageISO());
            if ($this->getPlugin()->getDefaultLanguage() === $languageCode) {
                continue;
            }

            $specificTranslation = $this->findSpecificValueTranslation((int)$trid, $taxonomy, $languageCode);
            $specificId = null;
            if (isset($specificTranslation['term_taxonomy_id'])) {
                $specificValue->getId()->setEndpoint($specificTranslation['term_taxonomy_id']);
            }

            $slug = wc_sanitize_taxonomy_name($specificValueI18n->getValue()) . '-' . $languageCode;

            /** @var SpecificValue $specificValue |null */
            $specificValueSaved = $this->getPlugin()
                ->getPluginsManager()
                ->get(WooCommerce::class)
                ->getComponent(WooCommerceSpecificValue::class)
                ->save($taxonomy, $specificValue, $specificValueI18n, $slug);

            if (!is_null($specificValueSaved) && !empty($specificValueSaved->getId()->getEndpoint())) {
                $this->getPlugin()->getSitepress()->set_element_language_details(
                    $specificValueSaved->getId()->getEndpoint(),
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
            ->getPlugin()
            ->getComponent(WpmlTermTranslation::class)
            ->getTranslations($trid, 'tax_' . $taxonomy, false);

        $translation = [];
        if (isset($specificTranslations[$languageCode])) {
            $translationData = $specificTranslations[$languageCode];
            $translation = $this->getPlugin()
                ->getComponent(WpmlTermTranslation::class)
                ->getTranslatedTerm($translationData->term_id, $taxonomy);
        }
        return $translation;
    }
}