<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Utilities;

use InvalidArgumentException;
use Jtl\Connector\Core\Definition\PaymentType;
use Jtl\Connector\Core\Exception\TranslatableAttributeException;
use Jtl\Connector\Core\Model\CategoryI18n;
use Jtl\Connector\Core\Model\ManufacturerI18n;
use Jtl\Connector\Core\Model\TranslatableAttribute;
use Jtl\Connector\Core\Model\TranslatableAttributeI18n;
use JtlWooCommerceConnector\Controllers\CustomerOrderController;
use JtlWooCommerceConnector\Controllers\GlobalData\CustomerGroupController;
use WhiteCube\Lingua\Service;

/**
 * Class Util
 *
 * @package JtlWooCommerceConnector\Utilities
 */
class Util extends WordpressUtils
{
    public const TO_SYNC       = 'jtlconnector_master_products_to_sync';
    public const TO_SYNC_COUNT = 'jtlconnector_master_products_to_sync_count';
    public const TO_SYNC_MOD   = 100;

    private string $locale;

    /**
     * @param Db $db
     * @throws InvalidArgumentException|\Exception
     */
    public function __construct(Db $db)
    {
        parent::__construct($db);
        //phpcs:ignore SlevomatCodingStandard.Namespaces.FullyQualifiedGlobalFunctions
        $this->locale = $this->mapLanguageIso(get_locale());
    }

    /**
     * @return string
     */
    public function getWooCommerceLanguage(): string
    {
        return $this->locale;
    }

    /**
     * @param string $language
     *
     * @return bool
     */
    public function isWooCommerceLanguage(string $language): bool
    {
        return $language === $this->getWooCommerceLanguage();
    }

    /**
     * @param string         $taxClass
     * @param \WC_Order|null $order
     *
     * @return float
     * @throws \http\Exception\InvalidArgumentException
     */
    public function getTaxRateByTaxClass(string $taxClass, ?\WC_Order $order = null): float
    {
        $wcDefaultCountry = \get_option('woocommerce_default_country');

        if (!\is_string($wcDefaultCountry)) {
            throw new \InvalidArgumentException(
                "Expected wcDefaultCountry to be a string but got " . \gettype($wcDefaultCountry) . " instead."
            );
        }

        $countryIso = \explode(":", $wcDefaultCountry);
        $countryIso = $countryIso[0];

        if (! \is_null($order)) {
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
                                             'country'   => $countryIso,
                                         ]);

        if (! empty($taxRates)) {
            return (double) \array_values($taxRates)[0]['rate'];
        }

        return 0.0;
    }

    /**
     * @param array<int, array<string, int|float|string>> $bulkPrices
     *
     * @return array<int, array<string, float|int|string>>
     */
    public static function setBulkPricesQuantityTo(array $bulkPrices): array
    {
        \usort($bulkPrices, function ($a, $b) {
            return (float) $a['bulk_price_from'] > (float) $b['bulk_price_from'] ? 1 : 0;
        });

        foreach ($bulkPrices as $i => &$bulkPrice) {
            if (isset($bulkPrices[ $i + 1 ])) {
                $bulkPrice['bulk_price_to'] = (float)$bulkPrices[ $i + 1 ]['bulk_price_from'] - 1;
            } else {
                $bulkPrice['bulk_price_to'] = '';
            }

            $bulkPrice['bulk_price_to']   = (string) $bulkPrice['bulk_price_to'];
            $bulkPrice['bulk_price_from'] = (string) $bulkPrice['bulk_price_from'];
        }

        return $bulkPrices;
    }

    /**
     * @param float|int $stockLevel
     * @param bool      $backorders
     * @param bool      $managesStock
     *
     * @return string
     */
    public function getStockStatus(float|int $stockLevel, bool $backorders, bool $managesStock = false): string
    {
        $stockStatus = $stockLevel > 0;

        if (\version_compare(\WC()->version, '2.6', '>=')) {
            $stockStatus = $stockStatus || $backorders;
        }

        return $stockStatus || ! $managesStock ? 'instock' : 'outofstock';
    }

    /**
     * @param string $price
     * @param int    $pd
     * @return int|string
     */
    public static function getNetPriceCutted(string $price, int $pd): mixed
    {
        $position = \strrpos($price, '.');

        if ($position > 0) {
            $cut   = \substr($price, 0, $position + 1 + $pd);
            $price = $cut;
        }

        return $price;
    }

    /**
     * @param int                   $id
     * @param array<string, string> $vatPluginsPriority
     * @param callable              $getMetaFieldValueFunction
     *
     * @return string
     */
    public static function findVatId(int $id, array $vatPluginsPriority, callable $getMetaFieldValueFunction): string
    {
        $uid = '';
        foreach ($vatPluginsPriority as $metaKey => $pluginName) {
            if (SupportedPlugins::isActive($pluginName) === true) {
                $uid = $getMetaFieldValueFunction($id, $metaKey);
                if (! empty($uid)) {
                    break;
                }
            }
        }

        if (\is_bool($uid)) {
            $uid = '';
        }

        return (string) $uid;
    }

    /**
     * @param int $customerId
     *
     * @return string
     */
    public static function getVatIdFromCustomer(int $customerId): string
    {
        $vatIdPlugins = [
            'b2b_uid'          => SupportedPlugins::PLUGIN_B2B_MARKET,
            'billing_vat'      => SupportedPlugins::PLUGIN_GERMAN_MARKET,
            '_billing_vat_id'  => SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO,
            '_shipping_vat_id' => SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO,
        ];

        return Util::findVatId($customerId, $vatIdPlugins, function ($id, $metaKey) {
            return \get_user_meta($id, $metaKey, true);
        });
    }

    /**
     * @param int $orderId
     *
     * @return string
     */
    public static function getVatIdFromOrder(int $orderId): string
    {
        $vatIdPlugins = [
            'billing_vat'      => SupportedPlugins::PLUGIN_GERMAN_MARKET,
            '_shipping_vat_id' => SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO,
            '_billing_vat_id'  => SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO,
            '_vat_id'          => SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO,
        ];

        return Util::findVatId($orderId, $vatIdPlugins, function ($id, $metaKey) {
            return \get_post_meta($id, $metaKey, true);
        });
    }

    /**
     * @param string $group
     *
     * @return bool
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function isValidCustomerGroup(string $group): bool
    {
        $result = empty($group) || $group === CustomerGroupController::DEFAULT_GROUP;

        if ($result) {
            return $result;
        }

        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)) {
            $customerGroups = $this->db->query(SqlHelper::customerGroupPull());
            $customerGroups = $customerGroups ?? [];
            foreach ($customerGroups as $cKey => $customerGroup) {
                if (isset($customerGroup['ID']) && $customerGroup['ID'] === $group) {
                    $result = true;
                }
            }
        }

        return $result;
    }

    /**
     * @param int $productId
     * @return void
     * @throws \http\Exception\InvalidArgumentException
     */
    public function addMasterProductToSync(int $productId): void
    {
        $masterProductsToSyncCount = \get_option(self::TO_SYNC_COUNT, 0);

        if (!\is_int($masterProductsToSyncCount)) {
            throw new \InvalidArgumentException(
                "Expected masterProductsToSyncCount to be an integer but got " .
                \gettype($masterProductsToSyncCount) . " instead."
            );
        }

        $page                 = ( $masterProductsToSyncCount + 1 ) % self::TO_SYNC_MOD + 1;
        $masterProductsToSync = \get_option(self::TO_SYNC . '_' . $page, []);

        if (!\is_array($masterProductsToSync)) {
            throw new \InvalidArgumentException(
                "Expected masterProductsToSync to be an array but got " .
                \gettype($masterProductsToSync) . " instead."
            );
        }

        $masterProductsToSync[] = $productId;

        \update_option(self::TO_SYNC . '_' . $page, \array_unique($masterProductsToSync));
    }

    /**
     * @return void
     * @throws \http\Exception\InvalidArgumentException
     * @throws \Exception
     */
    public function syncMasterProducts(): void
    {
        $masterProductsToSyncCount = \get_option(self::TO_SYNC_COUNT, 0);

        if ($masterProductsToSyncCount > 0) {
            $page = ( $masterProductsToSyncCount + 1 ) % self::TO_SYNC_MOD + 1;

            for ($i = 1; $i <= $page; $i++) {
                $masterProductsToSync = \get_option(self::TO_SYNC . '_' . $page, []);

                if (!\is_array($masterProductsToSync)) {
                    throw new \InvalidArgumentException(
                        "Expected masterProductsToSync to be an array but got " .
                        \gettype($masterProductsToSync) . " instead."
                    );
                }

                if (! empty($masterProductsToSync)) {
                    foreach ($masterProductsToSync as $productId) {
                        \WC_Product_Variable::sync((int)$productId);
                    }

                    \delete_option(self::TO_SYNC . '_' . $page);
                }
            }

            \update_option(self::TO_SYNC_COUNT, 0);
        }
    }

    /**
     * @return void
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function countCategories(): void
    {
        $offset = 0;
        $limit  = 100;

        while (! empty($result)) {
            $result = $this->db->query(SqlHelper::categoryProductsCount($offset, $limit));
            $result = $result ?? [];

            /** @var array<string, int|string> $category */
            foreach ($result as $category) {
                $this->db->query(SqlHelper::termTaxonomyCountUpdate(
                    (int)$category['term_taxonomy_id'],
                    (int)$category['count']
                ));
                $this->db->query(SqlHelper::categoryMetaCountUpdate(
                    (int)$category['term_id'],
                    (int)$category['count']
                ));
            }

            $offset += $limit;
        }
    }

    /**
     * @return void
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function countProductTags(): void
    {
        $offset = 0;
        $limit  = 100;

        while (! empty($result)) {
            $result = $this->db->query(SqlHelper::productTagsCount($offset, $limit));
            $result = $result ?? [];

            /** @var array<string, int|string> $tag */
            foreach ($result as $tag) {
                $this->db->query(SqlHelper::termTaxonomyCountUpdate(
                    (int)$tag['term_taxonomy_id'],
                    (int)$tag['count']
                ));
            }

            $offset += $limit;
        }
    }

    /**
     * @param string $locale
     *
     * @return string
     * @throws \Exception
     */
    public static function mapLanguageIso(string $locale): string
    {
        if (\strpos($locale, '_') !== false) {
            $strPos = (\strpos($locale, '_', 4) !== false)
                ? \strpos($locale, '_', 4)
                : null;
        } else {
            $strPos = null;
        }

        if (\substr_count($locale, '_') == 2) {
            $locale = \substr(
                $locale,
                0,
                $strPos
            );
        }

        $language = Service::create($locale);
        return $language->toISO_639_1();
    }

    /**
     * @param \WC_Order $order
     *
     * @return string
     */
    public function mapPaymentModuleCode(\WC_Order $order): string
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
     * @param string $name
     *
     * @return int
     */
    public static function getAttributeTaxonomyIdByName(string $name): int
    {
        $name       = \str_replace('pa_', '', $name);
        $taxonomies = \wp_list_pluck(\wc_get_attribute_taxonomies(), 'attribute_id', 'attribute_name');

        /** @param $name string */
        return isset($taxonomies[ $name ]) ? (int) $taxonomies[ $name ] : 0;
    }

    /**
     * @return void
     */
    public static function deleteB2Bcache(): void
    {
        if (
            SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET) &&
            \is_callable([ 'BM_Helper', 'delete_b2b_transients' ])
        ) {
            /** @phpstan-ignore staticMethod.notFound */
            \BM_Helper::delete_b2b_transients();
        }
    }

    /**
     * @param string $str
     *
     * @return string
     */
    public static function removeSpecialchars(string $str): string
    {
        return \strtr($str, [
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
    public static function sendCustomPropertiesEnabled(): bool
    {
        if (Config::has(Config::OPTIONS_SEND_CUSTOM_PROPERTIES)) {
            $result = (bool) Config::get(Config::OPTIONS_SEND_CUSTOM_PROPERTIES);
        } else {
            Config::set(Config::OPTIONS_SEND_CUSTOM_PROPERTIES, true);
            $result = true;
        }

        return $result;
    }

    /**
     * @return bool
     */
    public static function useGtinAsEanEnabled(): bool
    {
        if (Config::has(Config::OPTIONS_USE_GTIN_FOR_EAN)) {
            $result = (bool) Config::get(Config::OPTIONS_USE_GTIN_FOR_EAN);
        } else {
            Config::set(Config::OPTIONS_USE_GTIN_FOR_EAN, true);
            $result = true;
        }

        return $result;
    }

    /**
     * @return bool
     */
    public static function showVariationSpecificsOnProductPageEnabled(): bool
    {
        return (bool) Config::get(Config::OPTIONS_SHOW_VARIATION_SPECIFICS_ON_PRODUCT_PAGE);
    }

    /**
     * @return bool
     */
    public static function includeCompletedOrders(): bool
    {
        return Util::canPullOrderStatus(CustomerOrderController::STATUS_COMPLETED);
    }

    /**
     * @return string[]
     * @throws \http\Exception\InvalidArgumentException
     */
    public static function getOrderStatusesToImport(): array
    {
        $defaultStatuses = Config::JTLWCC_CONFIG_DEFAULTS[ Config::OPTIONS_DEFAULT_ORDER_STATUSES_TO_IMPORT ];

        $orderImportStatuses = Config::get(Config::OPTIONS_DEFAULT_ORDER_STATUSES_TO_IMPORT, $defaultStatuses);

        if (!\is_array($orderImportStatuses)) {
            throw new \InvalidArgumentException(
                "Expected orderImportStatuses to be an array but got "
                . \gettype($orderImportStatuses) . " instead."
            );
        }

        return $orderImportStatuses;
    }

    /**
     * @return string[]
     * @throws \http\Exception\InvalidArgumentException
     */
    public static function getManualPaymentTypes(): array
    {
        $defaultManualPayments = Config::JTLWCC_CONFIG_DEFAULTS[ Config::OPTIONS_DEFAULT_MANUAL_PAYMENT_TYPES ];

        $manualPaymentTypes = Config::get(Config::OPTIONS_DEFAULT_MANUAL_PAYMENT_TYPES, $defaultManualPayments);

        if (!\is_array($manualPaymentTypes)) {
            throw new \InvalidArgumentException(
                "Expected manualPaymentTypes to be an array but got "
                . \gettype($manualPaymentTypes) . " instead."
            );
        }

        return $manualPaymentTypes;
    }


    /**
     * @param string $stateName
     *
     * @return bool
     */
    public static function canPullOrderStatus(string $stateName): bool
    {
        $orderImportStates = Config::get(Config::OPTIONS_DEFAULT_ORDER_STATUSES_TO_IMPORT);

        return \is_array($orderImportStates) && \in_array(\sprintf('wc-%s', $stateName), $orderImportStates);
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
     * @param float $number
     *
     * @return int
     */
    public static function getDecimalPrecision(float $number): int
    {
        $explode   = \explode('.', (string) $number);
        $precision = isset($explode[1]) ? \strlen($explode[1]) : 0;

        return \max($precision, 2);
    }

    /**
     * @param string $name
     * @return string
     */
    public function createVariantTaxonomyName(string $name): string
    {
        return 'attribute_pa_' . \wc_sanitize_taxonomy_name(
            \substr(
                \trim(
                    $name
                ),
                0,
                27
            )
        );
    }


    /**
     * @param string                $attributeName
     * @param string                $languageIso
     * @param TranslatableAttribute ...$productAttributes
     *
     * @return TranslatableAttributeI18n|null
     */
    public function findAttributeI18nByName(
        string $attributeName,
        string $languageIso,
        TranslatableAttribute ...$productAttributes
    ): ?TranslatableAttributeI18n {
        $attribute = null;
        foreach ($productAttributes as $productAttribute) {
            foreach ($productAttribute->getI18ns() as $productAttributeI18n) {
                if (
                    $productAttributeI18n->getLanguageIso() === $languageIso
                    && $attributeName === $productAttributeI18n->getName()
                ) {
                    $attribute = $productAttributeI18n;
                    break 2;
                }
            }
        }

        return $attribute;
    }

    /**
     * @param array<string, string> $dataSet
     * @param int                   $termId
     * @return void
     */
    public function updateTermMeta(array $dataSet, int $termId): void
    {
        foreach ($dataSet as $metaKey => $metaValue) {
            if (! empty($metaValue)) {
                $oldTermMeta = \get_term_meta($termId, $metaKey, true);
                if (empty($oldTermMeta)) {
                    \add_term_meta($termId, $metaKey, $metaValue);
                } else {
                    \update_term_meta($termId, $metaKey, $metaValue, $oldTermMeta);
                }
            }
        }
    }

    /**
     * @param CategoryI18n|ManufacturerI18n     $i18n
     * @param array<int, array<string, string>> $rankMathSeoData
     *
     * @return void
     */
    public function setI18nRankMathSeo(ManufacturerI18n|CategoryI18n $i18n, array $rankMathSeoData): void
    {
        foreach ($rankMathSeoData as $termMeta) {
            switch ($termMeta['meta_key']) {
                case 'rank_math_title':
                    $i18n->setTitleTag((string) $termMeta['rank_math_title']);
                    break;
                case 'rank_math_description':
                    $i18n->setMetaDescription((string) $termMeta['rank_math_description']);
                    break;
                case 'rank_math_focus_keyword':
                    $i18n->setMetaKeywords((string) $termMeta['rank_math_focus_keyword']);
                    break;
            }
        }
    }

    /**
     * @return bool|array<string, array<string, string>>
     */
    public function getStates(): bool|array
    {
        return \WC()->countries->get_states();
    }

    /**
     * @param \WC_Product_Attribute $wcProductAttribute
     * @param TranslatableAttribute ...$jtlAttributes
     *
     * @return string
     * @throws TranslatableAttributeException
     * @throws \http\Exception\InvalidArgumentException
     */
    public function findAttributeValue(
        \WC_Product_Attribute $wcProductAttribute,
        TranslatableAttribute ...$jtlAttributes
    ): string {
        $value = \implode(' ' . \WC_DELIMITER . ' ', $wcProductAttribute->get_options());
        foreach ($jtlAttributes as $productAttr) {
            foreach ($productAttr->getI18ns() as $productAttrI18n) {
                if (
                    $productAttrI18n->getName() === $wcProductAttribute->get_name()
                    && $this->isWooCommerceLanguage($productAttrI18n->getLanguageIso())
                ) {
                    $value = $productAttrI18n->getValue();
                    break 2;
                }
            }
        }

        if (!\is_string($value)) {
            throw new \InvalidArgumentException(
                "Expected value to be a string but got " . \gettype($value) . " instead."
            );
        }

        return $value;
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    public static function isTrue(string $value): bool
    {
        return ! \in_array(\strtolower(\trim($value)), [ 'no', '0', 'false', '' ], true);
    }
}
