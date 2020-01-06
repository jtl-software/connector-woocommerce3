<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\GlobalData;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\TaxRate as TaxRateModel;
use JtlWooCommerceConnector\Controllers\Traits\PullTrait;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\SqlHelper;

class TaxRate
{
    use PullTrait;

    public function pullData()
    {
        $return = [];
        $uniqueRates = [];

        $result = Db::getInstance()->query(SqlHelper::taxRatePull());

        foreach ($result as $row) {

            $taxRate = (float)$row['tax_rate'];

            if (in_array($taxRate, $uniqueRates)) {
                continue;
            }
            $uniqueRates[] = $taxRate;

            $return[] = (new TaxRateModel)
                ->setId(new Identity($row['tax_rate_id']))
                ->setRate($taxRate);
        }

        return $return;
    }
}
