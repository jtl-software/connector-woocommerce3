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
use JtlWooCommerceConnector\Utilities\Util;

class MeasurementUnit extends AbstractController
{
    public function pullGermanizedData()
    {
        $measurementUnits = [];
        
        $result = $this->database->query(SqlHelper::globalDataGermanizedMeasurementUnitPull());
        
        foreach ((array)$result as $row) {
            $measurementUnits[] = (new MeasurementUnitModel())
                ->setId(new Identity($row['id']))
                ->setCode((new Germanized())->parseUnit($row['code']))
                ->setDisplayCode($row['code'])
                ->setI18ns((new MeasurementUnitI18n())
                    ->setName($row['code'])
                    ->setLanguageISO($this->util->getWooCommerceLanguage()));
        }
        
        return $measurementUnits;
    }
    
    public function pullGermanMarketData()
    {
        $measurementUnits = [];
       
        $sql = SqlHelper::globalDataGMMUPullSpecific();
        $specific = $this->database->query($sql);
        
        if (count($specific) <= 0) {
            return $measurementUnits;
        }
        
        $specific = $specific[0];
        
        $values = $this->database->query(SqlHelper::specificValuePull(sprintf(
            'pa_%s',
            $specific['attribute_name']
        )));
        
        foreach ($values as $unit) {
            $measurementUnits[] = (new MeasurementUnitModel())
                ->setId(new Identity($unit['term_id']))
                ->setCode($unit['name'])
                ->setDisplayCode($unit['name'])
                ->setI18ns([
                    (new MeasurementUnitI18n())
                        ->setMeasurementUnitId(new Identity($unit['term_id']))
                        ->setName($unit['description'])
                        ->setLanguageISO($this->util->getWooCommerceLanguage()),
                ]);
        }
        
        return $measurementUnits;
    }
    
}
