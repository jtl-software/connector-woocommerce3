<?php

namespace JtlWooCommerceConnector\Controllers\GlobalData;

use InvalidArgumentException;
use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\TaxRate as TaxRateModel;
use JtlWooCommerceConnector\Controllers\AbstractController;
use JtlWooCommerceConnector\Utilities\SqlHelper;

class TaxRateController extends AbstractController
{
    /**
     * @return array
     * @throws InvalidArgumentException
     */
    public function pull(): array
    {
        $return      = [];
        $uniqueRates = [];

        $result = $this->db->query(SqlHelper::taxRatePull());

        foreach ($result as $row) {
            $taxRate = (float)\round($row['tax_rate'], 4);

            if (\in_array($taxRate, $uniqueRates)) {
                continue;
            }
            $uniqueRates[] = $taxRate;

            $return[] = (new TaxRateModel())
                ->setId(new Identity($row['tax_rate_id']))
                ->setRate($taxRate);
        }

        return $return;
    }
}
