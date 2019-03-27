<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\GlobalData;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\MeasurementUnit as MeasurementUnitModel;
use jtl\Connector\Model\MeasurementUnitI18n;
use JtlWooCommerceConnector\Controllers\Traits\PullTrait;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\Germanized;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\Util;

class MeasurementUnit
{
    use PullTrait;

    public function pullData()
    {
        $return = [];

        $result = Db::getInstance()->query(SqlHelper::globalDataMeasurementUnitPull());

        foreach ((array)$result as $row) {
            $return[] = (new MeasurementUnitModel())
                ->setId(new Identity($row['id']))
                ->setCode(Germanized::getInstance()->parseUnit($row['code']))
                ->setDisplayCode($row['code'])
                ->setI18ns([(new MeasurementUnitI18n())
                    ->setMeasurementUnitId(new Identity($row['id']))
                    ->setName($row['code'])
                    ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage())]);
        }

        return $return;
    }
}
