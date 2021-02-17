<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Utilities;

use jtl\Connector\Core\Exception\LanguageException;
use jtl\Connector\Core\Utilities\Language;
use jtl\Connector\Core\Utilities\Singleton;
use jtl\Connector\Payment\PaymentTypes;
use JtlConnectorAdmin;
use JtlWooCommerceConnector\Controllers\GlobalData\CustomerGroup;

/**
 * Class Util
 *
 * @package JtlWooCommerceConnector\Utilities
 */
final class Util extends Singleton
{
    const TO_SYNC = 'jtlconnector_master_products_to_sync';
    const TO_SYNC_COUNT = 'jtlconnector_master_products_to_sync_count';
    const TO_SYNC_MOD = 100;

    private $locale;
    private $namespaceMapping;

    public function __construct()
    {
        parent::__construct();
        $this->locale = $this->mapLanguageIso(get_locale());

        $this->namespaceMapping = [
            'CustomerOrder' => 'Order\\',
            'GlobalData'    => 'GlobalData\\',
            'Product'       => 'Product\\',
        ];
    }

    /**
     * @param $controller
     *
     * @return string
     */
    public function getControllerNamespace($controller)
    {
        if (isset($this->namespaceMapping[$controller])) {
            return Constants::CONTROLLER_NAMESPACE . $this->namespaceMapping[$controller] . $controller;
        }

        return Constants::CONTROLLER_NAMESPACE . $controller;
    }

    /**
     * @return false|int|mixed|string|null
     */
    public function getWooCommerceLanguage()
    {
        return $this->locale;
    }

    /**
     * @param $language
     *
     * @return bool
     */
    public function isWooCommerceLanguage($language)
    {
        return $language === self::getWooCommerceLanguage();
    }

    /**
     * @param                $taxClass
     * @param \WC_Order|null $order
     *
     * @return float
     */
    public function getTaxRateByTaxClass($taxClass, \WC_Order $order = null)
    {
        $countryIso = \get_option('woocommerce_default_country');

        if (!is_null($order)) {
            $option = \get_option('woocommerce_tax_based_on', 'base');

            if ($option === 'shipping') {
                $countryIso = $order->get_shipping_country();
            }

            if ($option === 'billing' || $option === 'shipping' && empty($country)) {
                $countryIso = $order->get_billing_country();
            }
        }

        $taxRates = \WC_Tax::find_rates([
            'tax_class' => $taxClass,
            'country'   => $countryIso,
        ]);

        if (!empty($taxRates)) {
            return (double)array_values($taxRates)[0]['rate'];
        }

        return 0.0;
    }

    /**
     * @param array $bulkPrices
     * @return array
     */
    public static function setBulkPricesQuantityTo(array $bulkPrices)
    {
        usort($bulkPrices, function ($a, $b) {
            return (int)$a['bulk_price_from'] > (int)$b['bulk_price_from'];
        });

        foreach ($bulkPrices as $i => &$bulkPrice) {
            if (isset($bulkPrices[$i + 1])) {
                $bulkPrice['bulk_price_to'] = $bulkPrices[$i + 1]['bulk_price_from'] - 1;
            } else {
                $bulkPrice['bulk_price_to'] = '';
            }

            $bulkPrice['bulk_price_to'] = (string)$bulkPrice['bulk_price_to'];
            $bulkPrice['bulk_price_from'] = (string)$bulkPrice['bulk_price_from'];
        }

        return $bulkPrices;
    }

    /**
     * @param      $stockLevel
     * @param      $backorders
     * @param bool $managesStock
     *
     * @return string
     */
    public function getStockStatus($stockLevel, $backorders, $managesStock = false)
    {
        $stockStatus = $stockLevel > 0;

        if (version_compare(WC()->version, '2.6', '>=')) {
            $stockStatus = $stockStatus || $backorders;
        }

        return $stockStatus || !$managesStock ? 'instock' : 'outofstock';
    }

    /**
     * @param $price
     * @param $pd
     *
     * @return bool|string
     */
    public static function getNetPriceCutted($price, $pd){
        $position = strrpos((string)$price,'.');

        if($position > 0){
            $cut = substr($price,0,$position + 1 + $pd);
            $price = $cut;
        }

        return $price;
    }

    /**
     * @param $id
     * @param array $vatPluginsPriority
     * @param callable $getMetaFieldValueFunction
     * @return string
     */
    public static function findVatId($id, array $vatPluginsPriority, callable $getMetaFieldValueFunction): string
    {
        $uid = '';
        foreach ($vatPluginsPriority as $metaKey => $pluginName) {
            if (SupportedPlugins::isActive($pluginName) === true) {
                $uid = $getMetaFieldValueFunction($id, $metaKey);
                if (!empty($uid)) {
                    break;
                }
            }
        }

        if (is_bool($uid)) {
            $uid = '';
        }

        return (string) $uid;
    }

    /**
     * @param $customerId
     * @return string
     */
    public static function getVatIdFromCustomer($customerId): string
    {
        $vatIdPlugins = [
            'b2b_uid' => SupportedPlugins::PLUGIN_B2B_MARKET,
            'billing_vat' => SupportedPlugins::PLUGIN_GERMAN_MARKET,
            '_billing_vat_id' => SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO,
            '_shipping_vat_id' => SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO,
        ];

        return Util::findVatId($customerId, $vatIdPlugins, function ($id, $metaKey) {
            return \get_user_meta($id, $metaKey, true);
        });
    }

    /**
     * @param $orderId
     * @return string
     */
    public static function getVatIdFromOrder($orderId): string
    {
        $vatIdPlugins = [
            'billing_vat' => SupportedPlugins::PLUGIN_GERMAN_MARKET,
            '_shipping_vat_id' => SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO,
            '_billing_vat_id' => SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO,
            '_vat_id' => SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO,
        ];

        return Util::findVatId($orderId, $vatIdPlugins, function ($id, $metaKey) {
            return \get_post_meta($id, $metaKey, true);
        });
    }

    /**
     * @param $group
     *
     * @return bool
     */
    public function isValidCustomerGroup($group)
    {
        $result = empty($group) || $group === CustomerGroup::DEFAULT_GROUP;

        if ($result) {
            return $result;
        }

        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)) {
            $customerGroups = Db::getInstance()->query(SqlHelper::customerGroupPull());
            foreach ($customerGroups as $cKey => $customerGroup) {
                if (isset($customerGroup['ID']) && $customerGroup['ID'] === $group) {
                    $result = true;
                }
            }
        }

        return $result;
    }

    /**
     * @param $productId
     */
    public function addMasterProductToSync($productId)
    {
        $masterProductsToSyncCount = (int)\get_option(self::TO_SYNC_COUNT, 0);
        $page = ($masterProductsToSyncCount + 1) % self::TO_SYNC_MOD + 1;
        $masterProductsToSync = \get_option(self::TO_SYNC . '_' . $page, []);
        $masterProductsToSync[] = $productId;

        \update_option(self::TO_SYNC . '_' . $page, array_unique($masterProductsToSync));
    }

    /**
     *
     */
    public function syncMasterProducts()
    {
        $masterProductsToSyncCount = (int)\get_option(self::TO_SYNC_COUNT, 0);

        if ($masterProductsToSyncCount > 0) {
            $page = ($masterProductsToSyncCount + 1) % self::TO_SYNC_MOD + 1;

            for ($i = 1; $i <= $page; $i++) {
                $masterProductsToSync = \get_option(self::TO_SYNC . '_' . $page, []);

                if (!empty($masterProductsToSync)) {
                    foreach ($masterProductsToSync as $productId) {
                        \WC_Product_Variable::sync($productId);
                    }

                    \delete_option(self::TO_SYNC . '_' . $page);
                }
            }

            \update_option(self::TO_SYNC_COUNT, 0);
        }
    }

    /**
     *
     */
    public function countCategories()
    {
        $offset = 0;
        $limit = 100;

        while (!empty($result)) {
            $result = Db::getInstance()->query(SqlHelper::categoryProductsCount($offset, $limit));

            foreach ($result as $category) {
                Db::getInstance()->query(SqlHelper::termTaxonomyCountUpdate($category['term_taxonomy_id'],
                    $category['count']));
                Db::getInstance()->query(SqlHelper::categoryMetaCountUpdate($category['term_id'],
                    $category['count']));
            }

            $offset += $limit;
        }
    }

    /**
     * ???
     */
    public function countProductTags()
    {
        $offset = 0;
        $limit = 100;

        while (!empty($result)) {
            $result = Db::getInstance()->query(SqlHelper::productTagsCount($offset, $limit));

            foreach ($result as $tag) {
                Db::getInstance()->query(SqlHelper::termTaxonomyCountUpdate($tag['term_taxonomy_id'],
                    $tag['count']));
            }

            $offset += $limit;
        }
    }

    /**
     * @param $locale
     *
     * @return false|int|mixed|string|null
     */
    public function mapLanguageIso($locale)
    {
        $result = null;
        try {
            if (strpos($locale, '_')) {
                $result = Language::map(substr($locale, 0, 5));
            } else {
                if (count($locale) === 2) {
                    $result = is_null(Language::convert($locale)) ? null : $locale;
                } elseif (count($locale) === 3) {
                    $result = Language::convert(null, $locale);
                }
            }
        } catch (LanguageException $exception) {
            //
        }

        if (empty($result)) {
            return $this->getWooCommerceLanguage();
        } else {
            return $result;
        }
    }

    /**
     * @param \WC_Order $order
     *
     * @return string
     */
    public function mapPaymentModuleCode(\WC_Order $order)
    {
        switch ($order->get_payment_method()) {
            case 'paypal_plus':
                return PaymentTypes::TYPE_PAYPAL_PLUS;
            case 'express_checkout':
                return PaymentTypes::TYPE_PAYPAL_EXPRESS;
            case 'paypal':
                return PaymentTypes::TYPE_PAYPAL;
            case 'cod':
                return PaymentTypes::TYPE_CASH_ON_DELIVERY;
            case 'bacs':
                return PaymentTypes::TYPE_BANK_TRANSFER;
            case 'german_market_sepa_direct_debit':
            case 'direct-debit':
                return PaymentTypes::TYPE_DIRECT_DEBIT;
            case 'invoice':
            case 'german_market_purchase_on_account':
                return PaymentTypes::TYPE_INVOICE;
            default:
                return $order->get_payment_method_title();
        }
    }

    /**
     * @param $name
     *
     * @return int
     */
    public static function getAttributeTaxonomyIdByName($name)
    {
        $name = str_replace('pa_', '', $name);
        $taxonomies = \wp_list_pluck(\wc_get_attribute_taxonomies(), 'attribute_id', 'attribute_name');

        return isset($taxonomies[$name]) ? (int)$taxonomies[$name] : 0;
    }

    /**
     *
     */
    public static function deleteB2Bcache()
    {
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET) &&
            is_callable(['BM_Helper', 'delete_b2b_transients'])
        ) {
            \BM_Helper::delete_b2b_transients();
        }
    }

    /**
     * @param $str
     *
     * @return string
     */
    public static function removeSpecialchars($str)
    {
        return strtr($str, [
            "Ä" => "AE",
            "Ö" => "OE",
            "Ü" => "UE",
            "ä" => "ae",
            "ö" => "oe",
            "ü" => "ue",
        ]);
    }

    /**
     * @return bool
     */
    public static function sendCustomPropertiesEnabled()
    {
        if (Config::has(Config::OPTIONS_SEND_CUSTOM_PROPERTIES)) {
            $result = (boolean)Config::get(Config::OPTIONS_SEND_CUSTOM_PROPERTIES);
        } else {
            Config::set(Config::OPTIONS_SEND_CUSTOM_PROPERTIES, true);
            $result = true;
        }

        return $result;
    }

    /**
     * @return bool
     */
    public static function useGtinAsEanEnabled()
    {
        if (Config::has(Config::OPTIONS_USE_GTIN_FOR_EAN)) {
            $result = (boolean)Config::get(Config::OPTIONS_USE_GTIN_FOR_EAN);
        } else {
            Config::set(Config::OPTIONS_USE_GTIN_FOR_EAN, true);
            $result = true;
        }

        return $result;
    }

    /**
     * @return bool
     */
    public static function showVariationSpecificsOnProductPageEnabled()
    {
        return (bool)Config::get(Config::OPTIONS_SHOW_VARIATION_SPECIFICS_ON_PRODUCT_PAGE);
    }

    /**
     * @return bool
     */
    public static function includeCompletedOrders()
    {
        $includeCompletedOrdersOption = Config::get(Config::OPTIONS_COMPLETED_ORDERS, 'yes');
        return in_array($includeCompletedOrdersOption, ['yes', '1'], true);
    }

    /**
     * @return Singleton|$this
     */
    public static function getInstance()
    {
        return parent::getInstance();
    }

    /**
     * @return int
     */
    public static function getPriceDecimals(): int
    {
        $pd = \wc_get_price_decimals();
        if ($pd < 4) {
            $pd = 4;
        }
        return $pd;
    }

    /**
     * @param $name
     * @return string
     */
    public static function createVariantTaxonomyName($name): string
    {
        return 'attribute_pa_'.wc_sanitize_taxonomy_name(
            substr(
                trim(
                    $name
                ),
                0,
                27
            )
        );
    }
}
