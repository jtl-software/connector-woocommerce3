<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Integrations\Plugins\Wpml;

use Jtl\Connector\Core\Model\MeasurementUnitI18n;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractComponent;

/**
 * Class WpmlGermanMarket
 *
 * @package JtlWooCommerceConnector\Integrations\Plugins\Wpml
 */
class WpmlGermanMarket extends AbstractComponent
{
    /**
     * @param int    $termTaxonomyId
     * @param string $taxonomyName
     * @return MeasurementUnitI18n[]
     * @throws \Exception
     */
    public function getMeasurementUnitsTranslations(int $termTaxonomyId, string $taxonomyName): array
    {
        $measurementUnitTranslations = [];

        /** @var Wpml $wpmlPlugin */
        $wpmlPlugin = $this->getCurrentPlugin();
        $trid       = $wpmlPlugin->getElementTrid($termTaxonomyId, 'tax_' . $taxonomyName);

        /** @var WpmlTermTranslation $wpmlTermTranslation */
        $wpmlTermTranslation = $wpmlPlugin->getComponent(WpmlTermTranslation::class);
        $translations        = $wpmlTermTranslation
            ->getTranslations((int)$trid, 'tax_' . $taxonomyName, true);

        foreach ($translations as $languageCode => $translation) {
            $translated = $wpmlTermTranslation
                ->getTranslatedTerm($translation->element_id, 'pa_' . $taxonomyName);

            if (!empty($translated)) {
                $measurementUnitTranslations[] = (new MeasurementUnitI18n())
                    ->setName($translated['description'])
                    ->setLanguageISO(
                        $wpmlPlugin->convertLanguageToWawi($languageCode)
                    );
            }
        }

        return $measurementUnitTranslations;
    }
}
