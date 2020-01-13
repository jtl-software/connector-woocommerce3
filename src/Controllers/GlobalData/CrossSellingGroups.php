<?php
namespace JtlWooCommerceConnector\Controllers\GlobalData;

use JtlWooCommerceConnector\Controllers\Traits\PullTrait;
use JtlWooCommerceConnector\Models\CrossSellingGroup;

/**
 * Class CrossSelling
 * @package JtlWooCommerceConnector\Controllers\GlobalData
 */
class CrossSellingGroups
{
    use PullTrait;

    /**
     * @return array
     */
    public function pullData()
    {
        $crossSellingGroups = CrossSellingGroup::all();

        return $crossSellingGroups;
    }

}
