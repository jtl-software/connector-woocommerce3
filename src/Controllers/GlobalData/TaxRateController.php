<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Controllers\GlobalData;

use InvalidArgumentException;
use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\TaxRate as TaxRateModel;
use JtlWooCommerceConnector\Controllers\AbstractController;
use JtlWooCommerceConnector\Utilities\SqlHelper;

class TaxRateController extends AbstractController
{
    /**
     * @return array<int, TaxRateModel>
     * @throws InvalidArgumentException
     */
    public function pull(): array
    {
        $taxRates    = [];
        $uniqueRates = [];

        $result = $this->db->query(SqlHelper::taxRatePull()) ?? [];

        /** @var array<string, int|float|string> $row */
        foreach ($result as $row) {
            if (\is_numeric((float)$row['tax_rate']) && \is_string($row['tax_rate'])) {
                // sql might return a string, so we need to cast it to float
                $row['tax_rate'] = (float)$row['tax_rate'];
            }
            $taxRate = \round((float)$row['tax_rate'], 4);

            if (\in_array($taxRate, $uniqueRates)) {
                continue;
            }
            $uniqueRates[] = $taxRate;

            $taxRates[] = (new TaxRateModel())
                ->setId(new Identity((string)$row['tax_rate_id']))
                ->setRate($taxRate);
        }

        return $taxRates;
    }
}
