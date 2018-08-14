<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller\GlobalData;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\ShippingMethod as ShippingMethodModel;
use jtl\Connector\WooCommerce\Controller\Traits\PullTrait;

class ShippingMethod
{
    use PullTrait;

    public function pullData()
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
