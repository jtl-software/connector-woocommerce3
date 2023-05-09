<?php

/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\GlobalData;

use jtl\Connector\Model\CustomerGroup as CustomerGroupModel;
use jtl\Connector\Model\CustomerGroupI18n;
use jtl\Connector\Model\DataModel;
use jtl\Connector\Model\Identity;
use JtlWooCommerceConnector\Utilities\Config;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;

class CustomerGroup
{
    public const DEFAULT_GROUP = 'customer';

    /**
     * @return array<int, DataModel>
     * @throws \InvalidArgumentException
     */
    public function pullData(): array
    {
        $customerGroups    = [];
        $isDefaultGroupSet = false;
        $langIso           = Util::getInstance()->getWooCommerceLanguage();
        $version           = (string)SupportedPlugins::getVersionOf(SupportedPlugins::PLUGIN_B2B_MARKET);

        if (
            !SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)
            || (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)
            && \version_compare($version, '1.0.3', '<='))
        ) {
            //Default
            $defaultGroup = (new CustomerGroupModel())
                ->setId(new Identity(self::DEFAULT_GROUP))
                ->setIsDefault(true);

            $defaultI18n = (new CustomerGroupI18n())
                ->setCustomerGroupId($defaultGroup->getId())
                ->setName(\__('Customer', 'woocommerce'))
                ->setLanguageISO($langIso);

            $isDefaultGroupSet = true;
            $defaultGroup->addI18n($defaultI18n);
            $customerGroups[] = $defaultGroup;
        }

        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)) {
            $sql    = SqlHelper::customerGroupPull();
            $result = Db::getInstance()->query($sql);

            if (\count($result) > 0) {
                foreach ($result as $group) {
                    if (Config::get(Config::OPTIONS_AUTO_B2B_MARKET_OPTIONS, true)) {
                        $allProductsKey = 'bm_all_products';
                        \update_post_meta(
                            $group['ID'],
                            $allProductsKey,
                            'on',
                            \get_post_meta($group['ID'], $allProductsKey, true)
                        );
                    }

                    $meta = \get_post_meta($group['ID']);

                    $isDefaultGroup = $isDefaultGroupSet === false &&
                        (string) $group['ID'] === Config::get('jtlconnector_default_customer_group');

                    $customerGroup = (new CustomerGroupModel())
                        ->setApplyNetPrice(
                            isset($meta['bm_vat_type'][0]) && $meta['bm_vat_type'][0] === 'off'
                        )
                        ->setId(new Identity($group['ID']))
                        ->setIsDefault($isDefaultGroup);

                    $i18n = (new CustomerGroupI18n())
                        ->setCustomerGroupId($customerGroup->getId())
                        ->setName($group['post_title'])
                        ->setLanguageISO($langIso);

                    $customerGroup->addI18n($i18n);
                    $customerGroups[] = $customerGroup;
                }
            }
        }

        return $customerGroups;
    }

    /**
     * @param $customerId
     */
    public function getSlugById($customerId)
    {
        $group = \get_post((int)$customerId);
        if ($group instanceof \WP_Post) {
            return $group->post_name;
        }

        return false;
    }
}
