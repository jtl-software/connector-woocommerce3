<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\GlobalData;

use jtl\Connector\Model\CustomerGroup as CustomerGroupModel;
use jtl\Connector\Model\CustomerGroupI18n;
use jtl\Connector\Model\Identity;
use JtlWooCommerceConnector\Controllers\Traits\PullTrait;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;

class CustomerGroup
{
    use PullTrait;
    
    const DEFAULT_GROUP = 'customer';
    
    public function pullData()
    {
        $customerGroups = [];
        $langIso = Util::getInstance()->getWooCommerceLanguage();
        
        //Default
        $defaultGroup = (new CustomerGroupModel)
            ->setId(new Identity(self::DEFAULT_GROUP))
            ->setIsDefault(true);
        
        $defaultI18n = (new CustomerGroupI18n)
            ->setCustomerGroupId($defaultGroup->getId())
            ->setName(__('Customer', 'woocommerce'))
            ->setLanguageISO($langIso);
        
        $defaultGroup->addI18n($defaultI18n);
        $customerGroups[] = $defaultGroup;
        
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)) {
            $result = Db::getInstance()->query(SqlHelper::customerGroupPull());
            
            if (count($result) > 0) {
                foreach ($result as $group) {
                    $allProductsKey = 'bm_all_products';
                    $allConditionalProductsKey = 'bm_conditional_all_products';
                  
                    \update_post_meta(
                        $group['ID'],
                        $allProductsKey,
                        'on',
                        \get_post_meta($group['ID'], $allProductsKey, true)
                    );
                    
                    \update_post_meta(
                        $group['ID'],
                        $allConditionalProductsKey,
                        'on',
                        \get_post_meta($group['ID'], $allConditionalProductsKey, true)
                    );
                    
                    $meta = \get_post_meta($group['ID']);
                    
                    $customerGroup = (new CustomerGroupModel)
                        ->setApplyNetPrice(
                            isset($meta['bm_vat_type'])
                            && isset($meta['bm_vat_type'][0])
                            && $meta['bm_vat_type'][0] === 'off'
                                ? true
                                : false
                        )
                        ->setId(new Identity($group['ID']))
                        ->setIsDefault(false);
                    
                    $i18n = (new CustomerGroupI18n)
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
}
