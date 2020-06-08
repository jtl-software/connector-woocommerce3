<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\GlobalData;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\MeasurementUnit as MeasurementUnitModel;
use jtl\Connector\Model\MeasurementUnitI18n;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlGermanMarket;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\Germanized;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\Util;

/**
 * Class MeasurementUnit
 * @package JtlWooCommerceConnector\Controllers\GlobalData
 */
class MeasurementUnit extends BaseController
{
    /**
     * @return array
     * @throws \jtl\Connector\Core\Exception\LanguageException
     */
    public function pullGermanizedData()
    {
        $measurementUnits = [];

        $defaultLanguage = Util::getInstance()->getWooCommerceLanguage();
        if ($this->wpml->canBeUsed()) {
            $defaultLanguage = $this->wpml->convertLanguageToWawi($this->wpml->getDefaultLanguage());
        }

        $result = Db::getInstance()->query(SqlHelper::globalDataGermanizedMeasurementUnitPull());

        foreach ((array)$result as $row) {
            $measurementUnit = (new MeasurementUnitModel())
                ->setId(new Identity($row['id']))
                ->setCode(Germanized::getInstance()->parseUnit($row['code']))
                ->setDisplayCode($row['code'])
                ->setI18ns([
                    (new MeasurementUnitI18n())
                        ->setMeasurementUnitId(new Identity($row['id']))
                        ->setName($row['code'])
                        ->setLanguageISO($defaultLanguage),
                ]);
            $measurementUnits[] = $measurementUnit;
        }

        return $measurementUnits;
    }

    /**
     * @return array
     * @throws \Exception
     */
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
            $measurementUnit = (new MeasurementUnitModel())
                ->setId(new Identity($unit['term_id']))
                ->setCode($unit['name'])
                ->setDisplayCode($unit['name'])
                ->setI18ns([
                    (new MeasurementUnitI18n())
                        ->setMeasurementUnitId(new Identity($unit['term_id']))
                        ->setName($unit['description'])
                        ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage()),
                ]);

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
