<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller\GlobalData;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\TaxRate as TaxRateModel;
use jtl\Connector\WooCommerce\Controller\Traits\PullTrait;
use jtl\Connector\WooCommerce\Utility\Db;
use jtl\Connector\WooCommerce\Utility\SQLs;

class TaxRate
{
    use PullTrait;

    public function pullData()
    {
        $return = [];

        $result = Db::getInstance()->query(SQLs::taxRatePull());

        foreach ($result as $row) {
            $return[] = (new TaxRateModel)
                ->setId(new Identity($row['tax_rate_id']))
                ->setRate((float)$row['tax_rate']);
        }

        return $return;
    }
}
