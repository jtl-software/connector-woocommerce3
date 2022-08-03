<?php
namespace JtlWooCommerceConnector\Models;

use Jtl\Connector\Core\Model\CrossSellingGroupI18n;
use Jtl\Connector\Core\Model\Identity;
use JtlWooCommerceConnector\Utilities\Util;

/**
 * Class CrossSelling
 * @package JtlWooCommerceConnector\Models
 */
class CrossSellingGroup
{
    const TYPE_CROSS_SELL = "1";
    const TYPE_UP_SELL = "2";

    /**
     * @var array
     */
    protected static $groups = [
        [
            'endpointId' => self::TYPE_CROSS_SELL,
            'name' => 'WooCommerce-CrossSelling',
            'woo_commerce_name' => '_crosssell_ids',
        ],
        [
            'endpointId' => self::TYPE_UP_SELL,
            'name' => 'WooCommerce-UpSell',
            'woo_commerce_name' => '_upsell_ids',
        ]
    ];

    /**
     * @return array
     */
    public static function all(Util $util)
    {
        $groups = [];
        foreach (self::$groups as $group) {
            $groups[] = self::createFromArray($group, $util);
        }
        return $groups;
    }

    /**
     * @param $name
     * @return false|\Jtl\Connector\Core\Model\CrossSellingGroup
     */
    public static function getByWooCommerceName($name)
    {
        $key = self::findKeyByColumn('woo_commerce_name', $name);

        if ($key === false) {
            return false;
        }

        $group = self::$groups[$key];

        return self::createFromArray($group);
    }

    /**
     * @param array $groupData
     * @param Util $util
     * @return \Jtl\Connector\Core\Model\CrossSellingGroup
     */
    protected static function createFromArray(array $groupData, Util $util)
    {
        $crossSellingGroup = new \Jtl\Connector\Core\Model\CrossSellingGroup();
        $crossSellingGroup->setId(new Identity($groupData['endpointId']));

        $i18n = new CrossSellingGroupI18n();
        $i18n->setLanguageISO($util->getWooCommerceLanguage());
        $i18n->setName($groupData['name']);

        $crossSellingGroup->addI18n($i18n);

        return $crossSellingGroup;
    }

    /**
     * @param $columnName
     * @param $value
     * @return false|int|string
     */
    protected static function findKeyByColumn($columnName, $value)
    {
        return array_search($value, array_column(self::$groups, $columnName));
    }
}