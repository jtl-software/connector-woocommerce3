<?php

namespace JtlWooCommerceConnector\Utilities;

use jtl\Connector\Model\DataModel;
use JtlWooCommerceConnector\Controllers\GlobalData\CustomerGroup;

class B2BMarket extends WordpressUtils
{
    /**
     * @param array $customerGroupsIds
     * @param string $metaKey
     * @param DataModel ...$models
     * @return void
     */
    protected function setB2BCustomerGroupBlacklist(
        array $customerGroupsIds,
        string $metaKey,
        DataModel ...$models
    ): void {
        $newCustomerGroupBlacklist = [];
        foreach ($models as $model) {
            if (
                \method_exists($model, 'getInvisibilities')
                && \in_array($metaKey, ['bm_conditional_products', 'bm_conditional_categories'])
            ) {
                foreach ($model->getInvisibilities() as $modelInvisibility) {
                    $customerGroupId = $modelInvisibility->getCustomerGroupId()->getEndpoint();
                    if (!empty($customerGroupId) && \in_array($customerGroupId, $customerGroupsIds, true)) {
                        $newCustomerGroupBlacklist[$customerGroupId][] = $model->getId()->getEndpoint();
                    }
                }
            }
        }

        foreach ($customerGroupsIds as $customerGroupId) {
            $customerGroupId               = (int)$customerGroupId;
            $currentCustomerGroupBlackList = $this->getPostMeta($customerGroupId, $metaKey, true);
            if (isset($newCustomerGroupBlacklist[$customerGroupId])) {
                $this->updatePostMeta(
                    $customerGroupId,
                    $metaKey,
                    \join(',', $newCustomerGroupBlacklist[$customerGroupId]),
                    $currentCustomerGroupBlackList
                );
            } else {
                $this->deletePostMeta($customerGroupId, $metaKey, $currentCustomerGroupBlackList);
            }
        }
    }

    /**
     * @param string $controller
     * @param DataModel ...$entities
     * @return void
     * @throws \InvalidArgumentException
     */
    public function handleCustomerGroupsBlacklists(string $controller, DataModel ...$entities): void
    {
        $customerGroups    = (new CustomerGroup())->pullData();
        $customerGroupsIds = \array_values(\array_map(function (\jtl\Connector\Model\CustomerGroup $customerGroup) {
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
