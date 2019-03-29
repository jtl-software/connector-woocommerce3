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
        $return = [];
        
        $result = Db::getInstance()->query(SqlHelper::globalDataGermanizedMeasurementUnitPull());
        
        foreach ((array)$result as $row) {
            $return[] = (new MeasurementUnitModel())
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
        
        return $return;
    }
    
    public function pullGermanMarketData()
    {
        $return = [];
       
        $sql = SqlHelper::globalDataGMMUPullSpecific();
        $specific = Db::getInstance()->query($sql);
        
        if (count($specific) <= 0) {
            return $return;
        }
        
        $specific = $specific[0];
        
        $values = Db::getInstance()->query(SqlHelper::specificValuePull(sprintf(
            'pa_%s',
            $specific['attribute_name']
        )));
        
        foreach ($values as $unit) {
            $return[] = (new MeasurementUnitModel())
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
        
        return $return;
    }
    
}
