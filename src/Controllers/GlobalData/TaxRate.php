<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\GlobalData;

use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\TaxRate as TaxRateModel;
use JtlWooCommerceConnector\Controllers\AbstractController;
use JtlWooCommerceConnector\Utilities\SqlHelper;

class TaxRate extends AbstractController
{
    public function pullData()
    {
        $return = [];
        $uniqueRates = [];

        $result = $this->database->query(SqlHelper::taxRatePull());

        foreach ($result as $row) {

            $taxRate = round($row['tax_rate'], 4);

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
