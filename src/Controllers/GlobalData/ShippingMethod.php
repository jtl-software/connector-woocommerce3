<?php

/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\GlobalData;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\ShippingMethod as ShippingMethodModel;

class ShippingMethod
{
    /**
     * @return array
     * @throws \InvalidArgumentException
     */
    public function pullData(): array
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
