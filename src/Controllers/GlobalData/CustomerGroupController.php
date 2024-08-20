<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Controllers\GlobalData;

use http\Exception\InvalidArgumentException;
use Jtl\Connector\Core\Model\CustomerGroup as CustomerGroupModel;
use Jtl\Connector\Core\Model\CustomerGroupI18n;
use Jtl\Connector\Core\Model\Identity;
use JtlWooCommerceConnector\Controllers\AbstractBaseController;
use JtlWooCommerceConnector\Utilities\Config;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\Util;

class CustomerGroupController extends AbstractBaseController
{
    public const DEFAULT_GROUP = 'customer';

    protected Db $db;
    protected Util $util;

    /**
     * @return array<int, CustomerGroupModel>
     * @throws \InvalidArgumentException
     */
    public function pull(): array
    {
        $customerGroups    = [];
        $isDefaultGroupSet = false;
        $langIso           = $this->util->getWooCommerceLanguage();
        $version           = (string)SupportedPlugins::getVersionOf(SupportedPlugins::PLUGIN_B2B_MARKET);

        if (
            !SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)
            || \version_compare($version, '1.0.3', '<=')
        ) {
            //Default
            $defaultGroup = (new CustomerGroupModel())
                ->setId(new Identity(self::DEFAULT_GROUP))
                ->setIsDefault(true);

            $defaultI18n = (new CustomerGroupI18n())
                ->setName(\__('Customer', 'woocommerce'))
                ->setLanguageISO($langIso);

            $isDefaultGroupSet = true;
            $defaultGroup->addI18n($defaultI18n);
            $customerGroups[] = $defaultGroup;
        }

        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)) {
            $sql    = SqlHelper::customerGroupPull();
            $result = $this->db->query($sql) ?? [];

            if (\count($result) > 0) {
                /** @var array<string, int|string> $group */
                foreach ($result as $group) {
                    if (Config::get(Config::OPTIONS_AUTO_B2B_MARKET_OPTIONS, true)) {
                        $allProductsKey = 'bm_all_products';
                        \update_post_meta(
                            (int)$group['ID'],
                            $allProductsKey,
                            'on',
                            \get_post_meta((int)$group['ID'], $allProductsKey, true)
                        );
                    }

                    $meta = \get_post_meta((int)$group['ID']);

                    if (!\is_array($meta)) {
                        throw new InvalidArgumentException(
                            "meta expected to be an array but got " . \gettype($meta) . " instead"
                        );
                    }

                    $isDefaultGroup = $isDefaultGroupSet === false &&
                        (string) $group['ID'] === Config::get('jtlconnector_default_customer_group');

                    $customerGroup = (new CustomerGroupModel())
                        ->setApplyNetPrice(
                            isset($meta['bm_vat_type'][0]) && $meta['bm_vat_type'][0] === 'off'
                        )
                        ->setId(new Identity((string)$group['ID']))
                        ->setIsDefault($isDefaultGroup);

                    $i18n = (new CustomerGroupI18n())
                        ->setName((string)$group['post_title'])
                        ->setLanguageISO($langIso);

                    $customerGroup->addI18n($i18n);
                    $customerGroups[] = $customerGroup;
                }
            }
        }

        return $customerGroups;
    }

    /**
     * @param string $customerId
     * @return false|string
     */
    public function getSlugById(string $customerId): bool|string
    {
        $group = \get_post((int)$customerId);
        if ($group instanceof \WP_Post) {
            return $group->post_name;
        }

        return false;
    }
}
