<?php

namespace JtlWooCommerceConnector\Controllers\GlobalData;

use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\ShippingMethod as ShippingMethodModel;

class ShippingMethodController
{
    /**
     * @return array
     */
    public function pull(): array
    {
        $shippingMethods = [];

        foreach (\WC()->shipping()->get_shipping_methods() as $shippingMethod) {
            if ($shippingMethod->enabled === 'yes') {
                $shippingMethods[] = (new ShippingMethodModel())
                    ->setId(new Identity($shippingMethod->id))
                    ->setName($shippingMethod->method_title);
            }
        }

        return $shippingMethods;
    }
}
