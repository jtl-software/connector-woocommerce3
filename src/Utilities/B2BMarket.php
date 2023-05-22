<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Utilities;

use jtl\Connector\Model\DataModel;
use JtlWooCommerceConnector\Controllers\GlobalData\CustomerGroup;

class B2BMarket extends WordpressUtils
{
    /**
     * @param array     $customerGroupIds
     * @param string    $metaKey
     * @param DataModel ...$models
     *
     * @return void
     * @noinspection PhpPossiblePolymorphicInvocationInspection
     */
    protected function setB2BCustomerGroupBlacklist(
        array $customerGroupIds,
        string $metaKey,
        DataModel ...$models
    ): void {
        foreach ($models as $model) {
            $modelId = $model->getId()->getEndpoint();
            if (\method_exists($model, 'getInvisibilities') === false) {
                continue;
            }
            $newCustomerGroupBlacklist = \array_map(
                static fn(DataModel $invisibility): string => $invisibility->getCustomerGroupId()->getEndpoint(),
                $model->getInvisibilities()
            );

            foreach ($customerGroupIds as $customerGroupId) {
                $postMeta     = \get_post_meta($customerGroupId, $metaKey)[0];
                $currentItems = ! empty($postMeta) ? \explode(',', $postMeta) : [];

                if (\in_array($customerGroupId, $newCustomerGroupBlacklist, true)) {
                    $currentItems[] = $modelId;
                    \update_post_meta($customerGroupId, $metaKey, \implode(',', \array_unique($currentItems)));
                } elseif (( $key = \array_search($modelId, $currentItems, true) ) !== false) {
                    unset($currentItems[ $key ]);
                    \update_post_meta($customerGroupId, $metaKey, \implode(',', $currentItems));
                }

                if (empty(\get_post_meta($customerGroupId, $metaKey)[0])) {
                    \delete_post_meta($customerGroupId, $metaKey);
                }
            }
        }
    }

    /**
     * @param string    $controller
     * @param DataModel ...$entities
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    public function handleCustomerGroupsBlacklists(string $controller, DataModel ...$entities): void
    {
        $customerGroups    = ( new CustomerGroup() )->pullData();
        $customerGroupsIds = \array_values(
            \array_map(static function (\jtl\Connector\Model\CustomerGroup $customerGroup) {
                if ($customerGroup->getId() === null) {
                    return '';
                }

                return $customerGroup->getId()->getEndpoint();
            }, $customerGroups)
        );

        $metaKey = '';
        switch ($controller) {
            case 'product':
                $metaKey = 'bm_conditional_products';
                break;
            case 'category':
                $metaKey = 'bm_conditional_categories';
                break;
        }

        if (! empty($metaKey)) {
            $this->setB2BCustomerGroupBlacklist($customerGroupsIds, $metaKey, ...$entities);
        }
    }
}
