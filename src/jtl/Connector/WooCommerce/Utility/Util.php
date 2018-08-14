<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Utility;

use jtl\Connector\Core\Utilities\Language;
use jtl\Connector\Core\Utilities\Singleton;
use jtl\Connector\Model\ProductPrice as ProductPriceModel;
use jtl\Connector\Payment\PaymentTypes;
use jtl\Connector\WooCommerce\Controller\GlobalData\CustomerGroup;

final class Util extends Singleton
{
    const TO_SYNC = 'jtlconnector_master_products_to_sync';
    const TO_SYNC_COUNT = 'jtlconnector_master_products_to_sync_count';
    const TO_SYNC_MOD = 100;
    
    private $locale;
    private $namespaceMapping;
    
    public function __construct()
    {
        $this->locale = $this->mapLanguageIso(\get_locale());
        
        $this->namespaceMapping = [
            'CustomerOrder' => 'Order\\',
            'GlobalData'    => 'GlobalData\\',
            'Product'       => 'Product\\',
        ];
    }
    
    public function getControllerNamespace($controller)
    {
        if (isset($this->namespaceMapping[$controller])) {
            return Constants::CONTROLLER_NAMESPACE . $this->namespaceMapping[$controller] . $controller;
        }
        
        return Constants::CONTROLLER_NAMESPACE . $controller;
    }
    
    public function getWooCommerceLanguage()
    {
        return $this->locale;
    }
    
    public function isWooCommerceLanguage($language)
    {
        return $language === self::getWooCommerceLanguage();
    }
    
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
    
    public function getStockStatus($stockLevel, $backorders, $managesStock = false)
    {
        $stockStatus = $stockLevel > 0;
        
        if (version_compare(WC()->version, '2.6', '>=')) {
            $stockStatus = $stockStatus || $backorders;
        }
        
        return $stockStatus || !$managesStock ? 'instock' : 'outofstock';
    }
    
    public function updateProductPrice(ProductPriceModel $productPrice, $vat)
    {
        if (!$this->isValidCustomerGroup($productPrice->getCustomerGroupId()->getEndpoint())) {
            return;
        }
        
        $productId = $productPrice->getProductId()->getEndpoint();
        $product = \wc_get_product($productId);
        
        if ($product !== false) {
            foreach ($productPrice->getItems() as $item) {
                if ($item->getQuantity() === 0) {
                    if (\wc_prices_include_tax()) {
                        $regularPrice = $item->getNetPrice() * (1 + $vat / 100);
                    } else {
                        $regularPrice = $item->getNetPrice();
                    }
                    
                    $pd = \wc_get_price_decimals();
                    $salePrice = \get_post_meta($productId, '_sale_price', true);
                    
                    if (empty($salePrice) || $salePrice !== \get_post_meta($productId, '_price', true)) {
                        \update_post_meta($productId, '_price', \wc_format_decimal($regularPrice, $pd));
                    }
                    
                    \update_post_meta($productId, '_regular_price', \wc_format_decimal($regularPrice, $pd));
                }
            }
        }
    }
    
    public function isValidCustomerGroup($group)
    {
        return empty($group) || $group === CustomerGroup::DEFAULT_GROUP;
    }
    
    public function addMasterProductToSync($productId)
    {
        $masterProductsToSyncCount = (int)\get_option(self::TO_SYNC_COUNT, 0);
        $page = ($masterProductsToSyncCount + 1) % self::TO_SYNC_MOD + 1;
        $masterProductsToSync = \get_option(self::TO_SYNC . '_' . $page, []);
        $masterProductsToSync[] = $productId;
        
        \update_option(self::TO_SYNC . '_' . $page, array_unique($masterProductsToSync));
    }
    
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
    
    public function countCategories()
    {
        $offset = 0;
        $limit = 100;
        
        while (!empty($result)) {
            $result = Db::getInstance()->query(SQL::categoryProductsCount($offset, $limit));
            
            foreach ($result as $category) {
                Db::getInstance()->query(SQL::termTaxonomyCountUpdate($category['term_taxonomy_id'],
                    $category['count']));
                Db::getInstance()->query(SQL::categoryMetaCountUpdate($category['term_id'], $category['count']));
            }
            
            $offset += $limit;
        }
    }
    
    public function countProductTags()
    {
        $offset = 0;
        $limit = 100;
        
        while (!empty($result)) {
            $result = Db::getInstance()->query(SQL::productTagsCount($offset, $limit));
            
            foreach ($result as $tag) {
                Db::getInstance()->query(SQL::termTaxonomyCountUpdate($tag['term_taxonomy_id'], $tag['count']));
            }
            
            $offset += $limit;
        }
    }
    
    public function mapLanguageIso($locale)
    {
        $result = null;
        
        if (strpos($locale, '_')) {
            $result = Language::map(substr($locale, 0, 5));
        } else {
            if (count($locale) === 2) {
                $result = is_null(Language::convert($locale)) ? null : $locale;
            } elseif (count($locale) === 3) {
                $result = Language::convert(null, $locale);
            }
        }
        
        if (empty($result)) {
            return $this->getWooCommerceLanguage();
        } else {
            return $result;
        }
    }
    
    public function mapPaymentModuleCode(\WC_Order $order)
    {
        switch ($order->get_payment_method()) {
            case 'paypal':
                return PaymentTypes::TYPE_PAYPAL_EXPRESS;
            case 'express_checkout':
                return PaymentTypes::TYPE_PAYPAL_EXPRESS;
            case 'cod':
                return PaymentTypes::TYPE_CASH_ON_DELIVERY;
            case 'bacs':
                return PaymentTypes::TYPE_BANK_TRANSFER;
            case 'direct-debit':
                return PaymentTypes::TYPE_DIRECT_DEBIT;
            case 'invoice':
                return PaymentTypes::TYPE_INVOICE;
            default:
                return $order->get_payment_method_title();
        }
    }

    public static function getAttributeTaxonomyIdByName($name)
    {
        $name = str_replace('pa_', '', $name);
        $taxonomies = \wp_list_pluck(\wc_get_attribute_taxonomies(), 'attribute_id', 'attribute_name');
        
        return isset($taxonomies[$name]) ? (int)$taxonomies[$name] : 0;
    }
    
    public static function removeSpecialchars($str)
    {
        return strtr($str, ["Ä" => "AE", "Ö" => "OE", "Ü" => "UE", "ä" => "ae", "ö" => "oe", "ü" => "ue"]);
    }
    
    /**
     * @return $this
     */
    public static function getInstance()
    {
        return parent::getInstance();
    }
}
