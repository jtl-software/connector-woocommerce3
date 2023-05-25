<?php

/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\GlobalData;

use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\MeasurementUnit as MeasurementUnitModel;
use Jtl\Connector\Core\Model\MeasurementUnitI18n;
use JtlWooCommerceConnector\Controllers\AbstractController;
use JtlWooCommerceConnector\Utilities\Germanized;
use JtlWooCommerceConnector\Utilities\SqlHelper;

class MeasurementUnit extends AbstractController
{
    /**
     * @return array
     * @throws \InvalidArgumentException
     */
    public function pullGermanizedData(): array
    {
        $measurementUnits = [];

        $result = $this->db->query(SqlHelper::globalDataGermanizedMeasurementUnitPull());

        foreach ((array)$result as $row) {
            $measurementUnits[] = (new MeasurementUnitModel())
                ->setId(new Identity($row['id']))
                ->setCode((new Germanized())->parseUnit($row['code']))
                ->setDisplayCode($row['code'])
                ->setI18ns(...[
                    (new MeasurementUnitI18n())
                        ->setName($row['code'])
                        ->setLanguageISO($this->util->getWooCommerceLanguage()),
                ]);
        }

        return $measurementUnits;
    }

    /**
     * @return array
     * @throws \InvalidArgumentException
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
            $measurementUnits[] = (new MeasurementUnitModel())
                ->setId(new Identity($unit['term_id']))
                ->setCode($unit['name'])
                ->setDisplayCode($unit['name'])
                ->setI18ns(...[
                    (new MeasurementUnitI18n())
                        ->setName($unit['description'])
                        ->setLanguageISO($this->util->getWooCommerceLanguage()),
                ]);
        }

        return $measurementUnits;
    }
}
