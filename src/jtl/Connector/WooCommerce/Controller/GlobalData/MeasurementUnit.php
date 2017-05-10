<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller\GlobalData;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\MeasurementUnit as MeasurementUnitModel;
use jtl\Connector\Model\MeasurementUnitI18n;
use jtl\Connector\WooCommerce\Controller\Traits\PullTrait;
use jtl\Connector\WooCommerce\Utility\Db;
use jtl\Connector\WooCommerce\Utility\SQLsGermanized;
use jtl\Connector\WooCommerce\Utility\Util;
use jtl\Connector\WooCommerce\Utility\UtilGermanized;

class MeasurementUnit
{
    use PullTrait;

    public function pullData()
    {
        $return = [];

        $result = Db::getInstance()->query(SQLsGermanized::globalDataMeasurementUnitPull());

        foreach ((array)$result as $row) {
            $return[] = (new MeasurementUnitModel())
                ->setId(new Identity($row['id']))
                ->setCode(UtilGermanized::getInstance()->parseUnit($row['code']))
                ->setDisplayCode($row['code'])
                ->setI18ns([(new MeasurementUnitI18n())
                    ->setMeasurementUnitId(new Identity($row['id']))
                    ->setName($row['code'])
                    ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage())]);
        }

        return $return;
    }
}
