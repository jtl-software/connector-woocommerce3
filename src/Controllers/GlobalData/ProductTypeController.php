<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Controllers\GlobalData;

use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\ProductType as ProductTypeModel;

class ProductTypeController
{
    /**
     * @return array<int, ProductTypeModel>
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
