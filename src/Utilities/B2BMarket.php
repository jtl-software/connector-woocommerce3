<?php

namespace JtlWooCommerceConnector\Utilities;

use jtl\Connector\Model\DataModel;
use jtl\Connector\Model\ProductInvisibility;
use JtlWooCommerceConnector\Controllers\GlobalData\CustomerGroup;

class B2BMarket extends WordpressUtils
{
    /**
     * @param array $customerGroupIds
     * @param string $metaKey
     * @param DataModel ...$models
     * @return void
     */
    protected function setB2BCustomerGroupBlacklist(array $customerGroupIds, string $metaKey, DataModel ...$models): void
    {
        foreach ($models as $model) {
            $modelId = $model->getId()->getEndpoint();
            $newCustomerGroupBlacklist = \array_map(
                fn(ProductInvisibility $invisibility): string => $invisibility->getCustomerGroupId()->getEndpoint(),
                $model->getInvisibilities()
            );

            foreach ($customerGroupIds as $customerGroupId) {
                $postMeta = get_post_meta($customerGroupId, $metaKey)[0];
                $currentItems = !empty($postMeta) ? \explode(',', $postMeta) : [];

                if (\in_array($customerGroupId, $newCustomerGroupBlacklist)) {
                    $currentItems[] = $modelId;
                    update_post_meta($customerGroupId, $metaKey, \implode(',', \array_unique($currentItems)));
                } elseif (($key = \array_search($modelId, $currentItems)) !== false) {
                    unset($currentItems[$key]);
                    update_post_meta($customerGroupId, $metaKey, \implode(',', $currentItems));
                }

                if (empty(get_post_meta($customerGroupId, $metaKey)[0])) {
                    delete_post_meta($customerGroupId, $metaKey);
                }
            }
        }
    }

    /**
     * @param string $controller
     * @param DataModel ...$entities
     */
    public function handleCustomerGroupsBlacklists(string $controller, DataModel ...$entities)
    {
        $customerGroups = (new CustomerGroup())->pullData();
        $customerGroupsIds = array_values(array_map(function (\jtl\Connector\Model\CustomerGroup $customerGroup) {
            return $customerGroup->getId()->getEndpoint();
        }, $customerGroups));

        $metaKey = '';
        switch ($controller) {
            case 'product':
                $metaKey = 'bm_conditional_products';
                break;
            case 'category':
                $metaKey = 'bm_conditional_categories';
                break;
        }

        if (!empty($metaKey)) {
            $this->setB2BCustomerGroupBlacklist($customerGroupsIds, $metaKey, ...$entities);
        }
    }
}
