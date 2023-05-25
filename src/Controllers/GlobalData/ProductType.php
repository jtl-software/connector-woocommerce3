<?php

/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\GlobalData;

use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\ProductType as ProductTypeModel;

class ProductType
{
    /**
     * @return array
     * @throws \InvalidArgumentException
     */
    public function pull(): array
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
