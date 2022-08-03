<?php

namespace JtlWooCommerceConnector\Utilities;

use Jtl\Connector\Core\Model\AbstractModel;
use JtlWooCommerceConnector\Controllers\GlobalData\CustomerGroup;

class B2BMarket extends WordpressUtils
{
    protected $util;

    public function __construct(Db $database, Util $util)
    {
        parent::__construct($database);
        $this->util = $util;
    }

    /**
     * @param array $customerGroupsIds
     * @param string $metaKey
     * @param AbstractModel ...$models
     */
    protected function setB2BCustomerGroupBlacklist(array $customerGroupsIds, string $metaKey, AbstractModel ...$models)
    {
        $newCustomerGroupBlacklist = [];
        foreach ($models as $model) {
            if (method_exists($model, 'getInvisibilities') && in_array($metaKey, ['bm_conditional_products', 'bm_conditional_categories'])) {
                foreach ($model->getInvisibilities() as $modelInvisibility) {
                    $customerGroupId = $modelInvisibility->getCustomerGroupId()->getEndpoint();
                    if (!empty($customerGroupId) && in_array($customerGroupId, $customerGroupsIds, true)) {
                        $newCustomerGroupBlacklist[$customerGroupId][] = $model->getId()->getEndpoint();
                    }
                }
            }
        }

        foreach ($customerGroupsIds as $customerGroupId) {
            $customerGroupId = (int)$customerGroupId;
            $currentCustomerGroupBlackList = $this->getPostMeta($customerGroupId, $metaKey, true);
            if (isset($newCustomerGroupBlacklist[$customerGroupId])) {
                $this->updatePostMeta($customerGroupId, $metaKey, join(',', $newCustomerGroupBlacklist[$customerGroupId]), $currentCustomerGroupBlackList);
            } else {
                $this->deletePostMeta($customerGroupId, $metaKey, $currentCustomerGroupBlackList);
            }
        }
    }

    /**
     * @param string $controller
     * @param AbstractModel ...$entities
     */
    public function handleCustomerGroupsBlacklists(string $controller, AbstractModel ...$entities)
    {
        $customerGroups = (new CustomerGroup($this->database, $this->util))->pullData();
        $customerGroupsIds = array_values(array_map(function (CustomerGroup $customerGroup) {
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
