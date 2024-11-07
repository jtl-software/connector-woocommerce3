<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Utilities;

use InvalidArgumentException;
use Jtl\Connector\Core\Model\AbstractModel;
use JtlWooCommerceConnector\Controllers\GlobalData\CustomerGroupController;

class B2BMarket extends WordpressUtils
{
    protected Util $util;

    public function __construct(Db $database, Util $util)
    {
        parent::__construct($database);
        $this->util = $util;
    }

    /**
     * @param array     $customerGroupIds
     * @param string    $metaKey
     * @param AbstractModel ...$models
     *
     * @return void
     * @noinspection PhpPossiblePolymorphicInvocationInspection
     */
    protected function setB2BCustomerGroupBlacklist(
        array $customerGroupIds,
        string $metaKey,
        AbstractModel ...$models
    ): void {
        foreach ($models as $model) {
            $modelId = $model->getId()->getEndpoint();
            if (\method_exists($model, 'getInvisibilities') === false) {
                continue;
            }
            $newCustomerGroupBlacklist = \array_map(
                static fn(AbstractModel $invisibility): string => $invisibility->getCustomerGroupId()->getEndpoint(),
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
     * @param AbstractModel ...$entities
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function handleCustomerGroupsBlacklists(string $controller, AbstractModel ...$entities): void
    {
        $customerGroups    = ( new CustomerGroupController($this->db, $this->util) )->pull();
        $customerGroupsIds = \array_values(
            \array_map(static function (\jtl\Connector\Core\Model\CustomerGroup $customerGroup) {
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
