<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\GlobalData;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\MeasurementUnit as MeasurementUnitModel;
use jtl\Connector\Model\MeasurementUnitI18n;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\Germanized;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\Util;

class MeasurementUnit
{
    public function pullGermanizedData()
    {
        $measurementUnits = [];
        
        $result = Db::getInstance()->query(SqlHelper::globalDataGermanizedMeasurementUnitPull());
        
        foreach ((array)$result as $row) {
            $measurementUnits[] = (new MeasurementUnitModel())
                ->setId(new Identity($row['id']))
                ->setCode(Germanized::getInstance()->parseUnit($row['code']))
                ->setDisplayCode($row['code'])
                ->setI18ns([
                    (new MeasurementUnitI18n())
                        ->setMeasurementUnitId(new Identity($row['id']))
                        ->setName($row['code'])
                        ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage()),
                ]);
        }
        
        return $measurementUnits;
    }
    
    public function pullGermanMarketData()
    {
        $measurementUnits = [];
       
        $sql = SqlHelper::globalDataGMMUPullSpecific();
        $specific = Db::getInstance()->query($sql);
        
        if (count($specific) <= 0) {
            return $measurementUnits;
        }
        
        $specific = $specific[0];
        
        $values = Db::getInstance()->query(SqlHelper::specificValuePull(sprintf(
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
                        ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage()),
                ]);
        }
        
        return $measurementUnits;
    }
    
}
