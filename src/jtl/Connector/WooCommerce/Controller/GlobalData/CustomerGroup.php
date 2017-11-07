<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller\GlobalData;

use jtl\Connector\Model\CustomerGroup as CustomerGroupModel;
use jtl\Connector\Model\CustomerGroupI18n;
use jtl\Connector\Model\Identity;
use jtl\Connector\WooCommerce\Controller\Traits\PullTrait;
use jtl\Connector\WooCommerce\Utility\Util;

class CustomerGroup
{
    use PullTrait;

    const DEFAULT_GROUP = 'customer';

    public function pullData()
    {
        $customerGroup = (new CustomerGroupModel())
            ->setId(new Identity(self::DEFAULT_GROUP))
            ->setIsDefault(true)
            ->addI18n((new CustomerGroupI18n())
                ->setName(__('Customer', 'woocommerce'))
                ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage()));

        return $customerGroup;
    }
}
