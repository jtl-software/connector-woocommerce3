<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller\GlobalData;

use jtl\Connector\Model\CustomerGroup as CustomerGroupModel;
use jtl\Connector\Model\CustomerGroupI18n;
use jtl\Connector\Model\Identity;
use jtl\Connector\WooCommerce\Controller\BaseController;
use jtl\Connector\WooCommerce\Utility\Util;

class CustomerGroup extends BaseController
{
    const DEFAULT_GROUP = 'customer';

    public function pullData()
    {
        global $wp_roles;

        $customerGroupDescription = (new CustomerGroupI18n())
            ->setName(__($wp_roles->role_names[self::DEFAULT_GROUP], 'woocommerce'))
            ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage());

        $customerGroup = (new CustomerGroupModel())
            ->setId(new Identity(self::DEFAULT_GROUP))
            ->setIsDefault(true)
            ->addI18n($customerGroupDescription);

        return $customerGroup;
    }
}
