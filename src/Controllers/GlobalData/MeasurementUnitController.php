<?php

namespace JtlWooCommerceConnector\Controllers\GlobalData;

use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\MeasurementUnit as MeasurementUnitModel;
use Jtl\Connector\Core\Model\MeasurementUnitI18n;
use JtlWooCommerceConnector\Controllers\AbstractBaseController;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlGermanMarket;
use JtlWooCommerceConnector\Utilities\Germanized;
use JtlWooCommerceConnector\Utilities\SqlHelper;

class MeasurementUnitController extends AbstractBaseController
{
    /**
     * @return array
     * @throws \InvalidArgumentException
     */
    public function pullGermanizedData(): array
    {
        $measurementUnits = [];

        $defaultLanguage = $this->util->getWooCommerceLanguage();
        if ($this->wpml->canBeUsed()) {
            $defaultLanguage = $this->wpml->convertLanguageToWawi($this->wpml->getDefaultLanguage());
        }

        $result = $this->db->query(SqlHelper::globalDataGermanizedMeasurementUnitPull());

        foreach ((array)$result as $row) {
            $measurementUnits[] = (new MeasurementUnitModel())
                ->setId(new Identity($row['id']))
                ->setCode((new Germanized())->parseUnit($row['code']))
                ->setDisplayCode($row['code'])
                ->setI18ns(
                    (new MeasurementUnitI18n())
                        ->setName($row['code'])
                        ->setLanguageISO($defaultLanguage)
                );
        }

        return $measurementUnits;
    }

    /**
     * @return array
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function pullGermanMarketData(): array
    {
        $measurementUnits = [];

        $sql      = SqlHelper::globalDataGMMUPullSpecific();
        $specific = $this->db->query($sql);

        if (\count($specific) <= 0) {
            return $measurementUnits;
        }

        $specific = $specific[0];

        $values = $this->db->query(SqlHelper::specificValuePull(\sprintf(
            'pa_%s',
            $specific['attribute_name']
        )));

        foreach ($values as $unit) {
            $measurementUnit = (new MeasurementUnitModel())
                ->setId(new Identity($unit['term_id']))
                ->setCode($unit['name'])
                ->setDisplayCode($unit['name'])
                ->setI18ns(
                    (new MeasurementUnitI18n())
                        ->setName($unit['description'])
                        ->setLanguageISO($this->util->getWooCommerceLanguage())
                );

            if ($this->wpml->canBeUsed()) {
                $translations = $this->wpml
                    ->getComponent(WpmlGermanMarket::class)
                    ->getMeasurementUnitsTranslations($unit['term_taxonomy_id'], $specific['attribute_name']);

                foreach ($translations as $translation) {
                    $measurementUnit->addI18n($translation);
                }
            }

            $measurementUnits[] = $measurementUnit;
        }

        return $measurementUnits;
    }
}
