<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Utilities;

use Jtl\Connector\Core\Definition\PaymentType;
use Jtl\Connector\Core\Exception\TranslatableAttributeException;
use Jtl\Connector\Core\Model\AbstractI18n;
use Jtl\Connector\Core\Model\TranslatableAttribute;
use Jtl\Connector\Core\Model\TranslatableAttributeI18n;
use JtlWooCommerceConnector\Controllers\CustomerOrderController;
use JtlWooCommerceConnector\Controllers\GlobalData\CustomerGroup;
use WhiteCube\Lingua\Service;

/**
 * Class Util
 *
 * @package JtlWooCommerceConnector\Utilities
 */
final class Util extends WordpressUtils
{
    const TO_SYNC = 'jtlconnector_master_products_to_sync';
    const TO_SYNC_COUNT = 'jtlconnector_master_products_to_sync_count';
    const TO_SYNC_MOD = 100;

    private $locale;

    public function __construct(Db $database)
    {
        parent::__construct($database);
        $this->locale = self::mapLanguageIso(get_locale());
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
    public function isWooCommerceLanguage($language): bool
    {
        return $language === $this->getWooCommerceLanguage();
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

            if ($option === 'billing' || $option === 'shipping') {
                $countryIso = $order->get_billing_country();
            }
        }

        $taxRates = \WC_Tax::find_rates([
            'tax_class' => $taxClass,
            'country' => $countryIso,
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
    public static function getNetPriceCutted($price, $pd)
    {
        $position = strrpos((string)$price, '.');

        if ($position > 0) {
            $cut = substr($price, 0, $position + 1 + $pd);
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
            $customerGroups = $this->database->query(SqlHelper::customerGroupPull());
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
            $result = $this->database->query(SqlHelper::categoryProductsCount($offset, $limit));

            foreach ($result as $category) {
                $this->database->query(SqlHelper::termTaxonomyCountUpdate($category['term_taxonomy_id'],
                    $category['count']));
                $this->database->query(SqlHelper::categoryMetaCountUpdate($category['term_id'],
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
            $result = $this->database->query(SqlHelper::productTagsCount($offset, $limit));

            foreach ($result as $tag) {
                $this->database->query(SqlHelper::termTaxonomyCountUpdate($tag['term_taxonomy_id'],
                    $tag['count']));
            }

            $offset += $limit;
        }
    }

    /**
     * @param $locale
     * @return string
     * @throws \Exception
     */
    public static function mapLanguageIso($locale)
    {
        $language = Service::create($locale);
        return $language->toISO_639_1();
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
                return PaymentType::PAYPAL_PLUS;
            case 'express_checkout':
                return PaymentType::PAYPAL_EXPRESS;
            case 'paypal':
                return PaymentType::PAYPAL;
            case 'cod':
                return PaymentType::CASH_ON_DELIVERY;
            case 'bacs':
                return PaymentType::BANK_TRANSFER;
            case 'german_market_sepa_direct_debit':
            case 'direct-debit':
                return PaymentType::DIRECT_DEBIT;
            case 'invoice':
            case 'german_market_purchase_on_account':
                return PaymentType::INVOICE;
            case 'amazon_payments_advanced':
                return PaymentType::AMAPAY;
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
        return Util::canPullOrderStatus(CustomerOrderController::STATUS_COMPLETED);
    }

    /**
     * @return array
     */
    public static function getOrderStatusesToImport(): array
    {
        $defaultStatuses = Config::JTLWCC_CONFIG_DEFAULTS[Config::OPTIONS_DEFAULT_ORDER_STATUSES_TO_IMPORT];
        return Config::get(Config::OPTIONS_DEFAULT_ORDER_STATUSES_TO_IMPORT, $defaultStatuses);
    }

    /**
     * @return array
     */
    public static function getManualPaymentTypes(): array
    {
        $defaultManualPayments = Config::JTLWCC_CONFIG_DEFAULTS[Config::OPTIONS_DEFAULT_MANUAL_PAYMENT_TYPES];
        return Config::get(Config::OPTIONS_DEFAULT_MANUAL_PAYMENT_TYPES, $defaultManualPayments);
    }


    /**
     * @param string $stateName
     * @return bool
     */
    public static function canPullOrderStatus(string $stateName): bool
    {
        $orderImportStates = Config::get(Config::OPTIONS_DEFAULT_ORDER_STATUSES_TO_IMPORT);
        return is_array($orderImportStates) ? in_array(sprintf('wc-%s',$stateName), $orderImportStates) : false;
    }

    /**
     * @return int
     */
    public static function getPriceDecimals()
    {
        $pd = \wc_get_price_decimals();
        if ($pd < 4) {
            $pd = 4;
        }
        return $pd;
    }

    /**
     * @param float $number
     * @return int
     */
    public static function getDecimalPrecision(float $number): int
    {
        $explode = explode('.', (string)$number);
        $precision = isset($explode[1]) ? strlen($explode[1]) : 0;
        return $precision < 2 ? 2 : $precision;
    }


    /**
     * @param string $attributeName
     * @param string $languageIso
     * @param TranslatableAttribute ...$productAttributes
     * @return TranslatableAttributeI18n|null
     */
    public function findAttributeI18nByName(string $attributeName, string $languageIso, TranslatableAttribute ...$productAttributes): ?TranslatableAttributeI18n
    {
        $attribute = null;
        foreach ($productAttributes as $productAttribute) {
            foreach ($productAttribute->getI18ns() as $productAttributeI18n) {
                if ($productAttributeI18n->getLanguageIso() === $languageIso && $attributeName === $productAttributeI18n->getName()) {
                    $attribute = $productAttributeI18n;
                    break 2;
                }
            }
        }
        return $attribute;
    }

    /**
     * @param array $dataSet
     * @param int $termId
     */
    public function updateTermMeta(array $dataSet, int $termId)
    {
        foreach ($dataSet as $metaKey => $metaValue) {
            if (!empty($metaValue)) {
                $oldTermMeta = get_term_meta($termId, $metaKey, true);
                if (empty($oldTermMeta)) {
                    add_term_meta($oldTermMeta, $metaKey, $metaKey);
                } else {
                    update_term_meta($termId, $metaKey, $metaValue, $oldTermMeta);
                }
            }
        }
    }

    /**
     * @param AbstractI18n $i18n
     * @param array $rankMathSeoData
     * @return void
     */
    public function setI18nRankMathSeo(AbstractI18n $i18n, array $rankMathSeoData)
    {
        foreach($rankMathSeoData as $termMeta){
            switch ($termMeta['meta_key']) {
                case 'rank_math_title':
                    $i18n->setTitleTag($termMeta['rank_math_title']);
                    break;
                case 'rank_math_description':
                    $i18n->setMetaDescription($termMeta['rank_math_description']);
                    break;
                case 'rank_math_focus_keyword':
                    $i18n->setMetaKeywords($termMeta['rank_math_focus_keyword']);
                    break;
            }
        }
    }

    /**
     * @return array|false
     */
    public static function getStates()
    {
        return WC()->countries->get_states();
    }

    /**
     * @param \WC_Product_Attribute $wcProductAttribute
     * @param TranslatableAttribute ...$jtlAttributes
     * @return string
     * @throws TranslatableAttributeException
     */
    public function findAttributeValue(\WC_Product_Attribute $wcProductAttribute, TranslatableAttribute ...$jtlAttributes): string
    {
        $value = implode(' ' . WC_DELIMITER . ' ', $wcProductAttribute->get_options());
        foreach ($jtlAttributes as $productAttr) {
            foreach ($productAttr->getI18ns() as $productAttrI18n) {
                if ($productAttrI18n->getName() === $wcProductAttribute->get_name() && $this->isWooCommerceLanguage($productAttrI18n->getLanguageIso())) {
                    $value = $productAttrI18n->getValue();
                    break 2;
                }
            }
        }

        return $value;
    }

    /**
     * @param string $value
     * @return bool
     */
    public static function isTrue(string $value): bool
    {
        return !in_array(strtolower(trim($value)), ['no', '0', 'false', ''], true);
    }
}
