<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\Wpml;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\MeasurementUnitI18n;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractComponent;

/**
 * Class WpmlGermanMarket
 * @package JtlWooCommerceConnector\Integrations\Plugins\Wpml
 */
class WpmlGermanMarket extends AbstractComponent
{
    /**
     * @param int $termTaxonomyId
     * @param string $taxonomyName
     * @return array
     */
    public function getMeasurementUnitsTranslations(int $termTaxonomyId, string $taxonomyName): array
    {
        $measurementUnitTranslations = [];

        $trid = $this->getCurrentPlugin()->getElementTrid($termTaxonomyId, 'tax_' . $taxonomyName);

        $translations = $this->getCurrentPlugin()
            ->getComponent(WpmlTermTranslation::class)
            ->getTranslations($trid, 'tax_' . $taxonomyName, true);

        foreach ($translations as $languageCode => $translation) {
            $translated = $this->getCurrentPlugin()
                ->getComponent(WpmlTermTranslation::class)
                ->getTranslatedTerm($translation->element_id, 'pa_' . $taxonomyName);

            if (!empty($translated)) {
                $measurementUnitTranslations[] = (new MeasurementUnitI18n())
                    ->setMeasurementUnitId(new Identity($termTaxonomyId))
                    ->setName($translated['description'])
                    ->setLanguageISO(
                        $this->getCurrentPlugin()->convertLanguageToWawi($languageCode)
                    );
            }
        }

        return $measurementUnitTranslations;
    }
}