<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\GlobalData;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\ProductType as ProductTypeModel;
use JtlWooCommerceConnector\Controllers\Traits\PullTrait;

class ProductType
{
    use PullTrait;

    public function pullData()
    {
        $productTypes = [];

        foreach (\wc_get_product_types() as $slug => $name) {
            $productTypes[] = (new ProductTypeModel())
                ->setId(new Identity($slug))
                ->setName($name);
        }

        return $productTypes;
    }
}
