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
            if (\is_numeric($row['tax_rate']) && \is_string($row['tax_rate'])) {
                // sql might return a string, so we need to cast it to float
                $row['tax_rate'] = (float)$row['tax_rate'];
            }
            $taxRate = \round($row['tax_rate'], 4);

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
