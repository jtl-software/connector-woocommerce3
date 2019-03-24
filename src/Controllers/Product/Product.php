<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Product;

use DateTime;
use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\Model\ProductI18n as ProductI18nModel;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Controllers\Traits\DeleteTrait;
use JtlWooCommerceConnector\Controllers\Traits\PullTrait;
use JtlWooCommerceConnector\Controllers\Traits\PushTrait;
use JtlWooCommerceConnector\Controllers\Traits\StatsTrait;
use JtlWooCommerceConnector\Logger\WpErrorLogger;
use JtlWooCommerceConnector\Utilities\Germanized;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;

class Product extends BaseController
{
    use PullTrait, PushTrait, DeleteTrait, StatsTrait;
    
    private static $idCache = [];
    
    public function pullData($limit)
    {
        $products = [];
        
        $ids = $this->database->queryList(SqlHelper::productPull($limit));
        
        foreach ($ids as $id) {
            $product = \wc_get_product($id);
            
            if ( ! $product instanceof \WC_Product) {
                continue;
            }
            
            $postDate = $product->get_date_created();
            $modDate  = $product->get_date_modified();
            $status   = $product->get_status('view');
            $result   = (new ProductModel())
                ->setId(new Identity($product->get_id()))
                ->setIsMasterProduct($product->is_type('variable'))
                ->setIsActive(in_array($status, ['private', 'draft', 'future']) ? false : true)
                ->setSku($product->get_sku())
                ->setVat(Util::getInstance()->getTaxRateByTaxClass($product->get_tax_class()))
                ->setSort($product->get_menu_order())
                ->setIsTopProduct(($itp = $product->is_featured()) ? $itp : $itp === 'yes')
                ->setProductTypeId(new Identity($product->get_type()))
                ->setKeywords(($tags = \wc_get_product_tag_list($product->get_id())) ? strip_tags($tags) : '')
                ->setCreationDate($postDate)
                ->setModified($modDate)
                ->setAvailableFrom($postDate <= $modDate ? null : $postDate)
                ->setHeight((double)$product->get_height())
                ->setLength((double)$product->get_length())
                ->setWidth((double)$product->get_width())
                ->setShippingWeight((double)$product->get_weight())
                ->setConsiderStock(is_bool($ms = $product->managing_stock()) ? $ms : $ms === 'yes')
                ->setPermitNegativeStock(is_bool($pns = $product->backorders_allowed()) ? $pns : $pns === 'yes')
                ->setShippingClassId(new Identity($product->get_shipping_class_id()));
            
            //EAN / GTIN
            if (Util::useGtinAsEanEnabled()) {
                $ean = get_post_meta($product->get_id(), '_ts_gtin');
                
                if (is_array($ean) && count($ean) > 0 && array_key_exists(0, $ean)) {
                    $ean = $ean[0];
                } else {
                    $ean = '';
                }
                
                $result->setEan($ean);
            }
            
            if ($product->get_parent_id() !== 0) {
                $result->setMasterProductId(new Identity($product->get_parent_id()));
            }
            
            $result
                ->addI18n(ProductI18n::getInstance()->pullData($product, $result))
                ->addPrice(ProductPrice::getInstance()->pullData($product))
                ->setSpecialPrices(ProductSpecialPrice::getInstance()->pullData($product))
                ->setCategories(Product2Category::getInstance()->pullData($product))
                ->setVariations(ProductVariation::getInstance()->pullData($product, $result));
            //->setAttributes(ProductAttr::getInstance()->pullData($product))
            
            // Simple or father articles
            if ($product->is_type('variable') || $product->is_type('simple')) {
                $result->setAttributes(ProductAttr::getInstance()->pullData($product))
                       ->setSpecifics(ProductSpecific::getInstance()->pullData($product, $result));
            }
            
            if ($product->managing_stock()) {
                $result->setStockLevel(ProductStockLevel::getInstance()->pullData($product));
            }
            
            if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED)) {
                $this->setGermanizedAttributes($result, $product);
            }
            
            if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_PERFECT_WOO_BRANDS)) {
                $tmpManId = ProductManufacturer::getInstance()->pullData($product, $result);
                if ( ! is_null($tmpManId) && $tmpManId instanceof Identity) {
                    $result->setManufacturerId($tmpManId);
                }
            }
            
            $products[] = $result;
        }
        
        return $products;
    }
    
    protected function pushData(ProductModel $product)
    {
        $tmpI18n         = null;
        $masterProductId = $product->getMasterProductId()->getEndpoint();
        
        if (empty($masterProductId) && isset(self::$idCache[$product->getMasterProductId()->getHost()])) {
            $masterProductId = self::$idCache[$product->getMasterProductId()->getHost()];
            $product->getMasterProductId()->setEndpoint($masterProductId);
        }
        
        foreach ($product->getI18ns() as $i18n) {
            if (Util::getInstance()->isWooCommerceLanguage($i18n->getLanguageISO())) {
                $tmpI18n = $i18n;
                break;
            }
        }
        
        if (is_null($tmpI18n)) {
            return $product;
        }
        
        $creationDate = is_null($product->getAvailableFrom()) ? $product->getCreationDate() : $product->getAvailableFrom();
        
        if ( ! $creationDate instanceof DateTime) {
            $creationDate = new DateTime();
        }
        
        $isMasterProduct = empty($masterProductId);
        
        /** @var ProductI18nModel $tmpI18n */
        $endpoint = [
            'ID'           => (int)$product->getId()->getEndpoint(),
            'post_type'    => $isMasterProduct ? 'product' : 'product_variation',
            'post_title'   => $tmpI18n->getName(),
            'post_name'    => $tmpI18n->getUrlPath(),
            'post_content' => $tmpI18n->getDescription(),
            'post_excerpt' => $tmpI18n->getShortDescription(),
            'post_date'    => $this->getCreationDate($creationDate),
            //'post_date_gmt' => $this->getCreationDate($creationDate, true),
            'post_status'  => is_null($product->getAvailableFrom()) ? ($product->getIsActive() ? 'publish' : 'draft') : 'future',
        ];
        
        if ($endpoint['ID'] !== 0) {
            // Needs to be set for existing products otherwise commenting is disabled
            $endpoint['comment_status'] = \get_post_field('comment_status', $endpoint['ID']);
        } else {
            // Update existing products by SKU
            $productId = \wc_get_product_id_by_sku($product->getSku());
            
            if ($productId !== 0) {
                $endpoint['ID'] = $productId;
            }
        }
        // Post filtering
        remove_filter('content_save_pre', 'wp_filter_post_kses');
        remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
        $newPostId = \wp_insert_post($endpoint, true);
        // Post filtering
        add_filter('content_save_pre', 'wp_filter_post_kses');
        add_filter('content_filtered_save_pre', 'wp_filter_post_kses');
        
        if ($newPostId instanceof \WP_Error) {
            WpErrorLogger::getInstance()->logError($newPostId);
            
            return $product;
        }
        
        $product->getId()->setEndpoint($newPostId);
        
        $this->onProductInserted($product, $tmpI18n);
        
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED)) {
            $this->updateGermanizedAttributes($product);
        }
        
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO_PREMIUM)) {
            ProductMetaSeo::getInstance()->pushData($product, $newPostId, $tmpI18n);
        }
        
        return $product;
    }
    
    protected function deleteData(ProductModel $product)
    {
        $productId = (int)$product->getId()->getEndpoint();
        
        \wp_delete_post($productId, true);
        \wc_delete_product_transients($productId);
        
        unset(self::$idCache[$product->getId()->getHost()]);
        
        return $product;
    }
    
    protected function getStats()
    {
        return count($this->database->queryList(SqlHelper::productPull()));
    }
    
    protected function onProductInserted(ProductModel &$product, &$meta)
    {
        $wcProduct = \wc_get_product($product->getId()->getEndpoint());
        
        if (is_null($wcProduct)) {
            return;
        }
        
        $this->updateProductMeta($product, $wcProduct);
        
        $this->updateProductRelations($product);
        
        if ($this->getType($product) !== 'product_variation') {
            $this->updateProduct($product);
            \wc_delete_product_transients($product->getId()->getEndpoint());
        }
        
        //variations
        (new ProductVariation)->pushData($product);
        if ($this->getType($product) === 'product_variation') {
            $this->updateVariationCombinationChild($product, $wcProduct, $meta);
        }
    }
    
    private function updateProductMeta(ProductModel $product, \WC_Product $wcProduct)
    {
        $parent = $parent = $product->getMasterProductId()->getEndpoint();
        
        $wcProduct->set_sku($product->getSku());
        $wcProduct->set_parent_id(empty($parent) ? 0 : (int)$parent);
        $wcProduct->set_menu_order($product->getSort());
        $wcProduct->set_featured($product->getIsTopProduct());
        $wcProduct->set_height($product->getHeight());
        $wcProduct->set_length($product->getLength());
        $wcProduct->set_width($product->getWidth());
        $wcProduct->set_weight($product->getShippingWeight());
        
        if (Util::useGtinAsEanEnabled()) {
            \update_post_meta($product->getId()->getEndpoint(), '_ts_gtin', (string)$product->getEan());
        } else {
            \update_post_meta($product->getId()->getEndpoint(), '_ts_gtin', '');
        }
        
        if ( ! is_null($product->getModified())) {
            $wcProduct->set_date_modified($product->getModified()->getTimestamp());
        }
        
        $taxClass = $this->database->queryOne(SqlHelper::taxClassByRate($product->getVat()));
        $wcProduct->set_tax_class(is_null($taxClass) ? '' : $taxClass);
        
        $wcProduct->save();
        
        \wp_set_object_terms($wcProduct->get_id(), $this->getType($product), 'product_type');
        
        $tags = array_map('trim', explode(' ', $product->getKeywords()));
        \wp_set_post_terms($wcProduct->get_id(), implode(',', $tags), 'product_tag');
        
        $shippingClass = get_term_by(
            'id',
            \wc_clean($product->getShippingClassId()->getEndpoint()),
            'product_shipping_class'
        );
        
        if ( ! empty($shippingClass)) {
            \wp_set_object_terms(
                $wcProduct->get_id(),
                $shippingClass->term_id,
                'product_shipping_class',
                false
            );
        }
        //DELIVERYTIME
        (new ProductDeliveryTime())->pushData($product, $wcProduct);
        (new ProductManufacturer())->pushData($product, $wcProduct);
    }
    
    private function updateProductRelations(ProductModel $product)
    {
        (new Product2Category)->pushData($product);
        (new ProductPrice)->pushData($product);
        (new ProductSpecialPrice)->pushData($product);
    }
    
    private function updateVariationCombinationChild(ProductModel $product, \WC_Product $wcProduct, $meta)
    {
        $productId = (int)$product->getId()->getEndpoint();
        
        $productTitle         = \esc_html(\get_the_title($product->getMasterProductId()->getEndpoint()));
        $variation_post_title = sprintf(__('Variation #%s of %s', 'woocommerce'), $productId, $productTitle);
        \wp_update_post(['ID' => $productId, 'post_title' => $variation_post_title]);
        \update_post_meta($productId, '_variation_description', $meta->getDescription());
        \update_post_meta($productId, '_mini_dec', $meta->getShortDescription());
        
        $productStockLevel = new ProductStockLevel();
        $productStockLevel->pushDataChild($product);
    }
    
    private function updateProduct(ProductModel $product)
    {
        $productId = (int)$product->getId()->getEndpoint();
        
        \update_post_meta($productId, '_visibility', 'visible');
        
        (new ProductAttr)->pushData($product);
        (new ProductSpecific)->pushData($product);
        (new ProductStockLevel)->pushDataParent($product);
        
        if ($product->getIsMasterProduct()) {
            Util::getInstance()->addMasterProductToSync($productId);
        }
        
        self::$idCache[$product->getId()->getHost()] = $productId;
    }
    
    private function setGermanizedAttributes(ProductModel &$product, \WC_Product $wcProduct)
    {
        $units = new \WC_GZD_Units();
        
        if ($wcProduct->gzd_product->has_product_units()) {
            $plugin = \get_plugin_data(WP_PLUGIN_DIR . '/woocommerce-germanized/woocommerce-germanized.php');
            
            if (isset($plugin['Version']) && version_compare($plugin['Version'], '1.6.0') < 0) {
                $unitObject = $units->get_unit_object($wcProduct->gzd_product->unit);
            } else {
                $unitObject = \get_term_by('slug', $wcProduct->gzd_product->unit, 'product_unit');
            }
            
            $code = Germanized::getInstance()->parseUnit($unitObject->slug);
            
            $productQuantity = (double)$wcProduct->gzd_product->unit_product;
            $product->setMeasurementQuantity($productQuantity);
            $product->setMeasurementUnitId(new Identity($unitObject->term_id));
            $product->setMeasurementUnitCode($code);
            
            $product->setConsiderBasePrice(true);
            
            $baseQuantity = (double)$wcProduct->gzd_product->unit_base;
            
            if ($baseQuantity !== 0.0) {
                $product->setBasePriceDivisor($productQuantity / $baseQuantity);
            }
            
            $product->setBasePriceQuantity($baseQuantity);
            $product->setBasePriceUnitId(new Identity($unitObject->term_id));
            $product->setBasePriceUnitCode($code);
            $product->setBasePriceUnitName($unitObject->name);
        }
    }
    
    private function updateGermanizedAttributes(ProductModel &$product)
    {
        $id = $product->getId()->getEndpoint();
        $this->updateBasePriceAndUnits($product, $id);
        $this->updateDeliveryStatus($product, $id);
    }
    
    private function updateBasePriceAndUnits(ProductModel $product, $id)
    {
        if ($product->getConsiderBasePrice()) {
            $pd = \wc_get_price_decimals();
            \update_post_meta($id, '_unit_base', $product->getBasePriceQuantity());
            
            if ($product->getBasePriceDivisor() != 0) {
                $divisor = $product->getBasePriceDivisor();
                \update_post_meta($id, '_unit_price',
                    round((float)\get_post_meta($id, '_price', true) / $divisor, $pd));
                \update_post_meta($id, '_unit_price_regular',
                    round((float)\get_post_meta($id, '_regular_price', true) / $divisor, $pd));
            }
            
            $salePrice = \get_post_meta($id, '_sale_price', true);
            
            if ( ! empty($salePrice)) {
                if ($product->getBasePriceDivisor() !== 0) {
                    $unitSale = (float)$salePrice / $product->getBasePriceDivisor();
                    \update_post_meta($id, '_unit_price_sale', round($unitSale, $pd));
                    
                    if (\get_post_meta($id, '_price', true) === $salePrice) {
                        \update_post_meta($id, '_unit_price', round($unitSale, $pd));
                    }
                }
            }
        }
        
        \update_post_meta($id, '_unit', $product->getBasePriceUnitName());
        
        if ($product->getMeasurementQuantity() !== 0) {
            \update_post_meta($id, '_unit_product', $product->getMeasurementQuantity());
        }
    }
    
    private function updateDeliveryStatus(ProductModel $product, $id)
    {
        foreach ($product->getI18ns() as $i18n) {
            $deliveryStatus = $i18n->getDeliveryStatus();
            
            if (Util::getInstance()->isWooCommerceLanguage($deliveryStatus) && ! empty($deliveryStatus)) {
                $term = $this->database->queryOne(SqlHelper::deliveryStatusByText($deliveryStatus));
                
                if (empty($term)) {
                    $result = \wp_insert_term($i18n->getDeliveryStatus(), 'product_delivery_time');
                    
                    if ($result instanceof \WP_Error) {
                        WpErrorLogger::getInstance()->logError($result);
                        break;
                    }
                    
                    $term = $result['term_id'];
                }
                
                $result = \wp_set_object_terms($id, (int)$term, 'product_delivery_time');
                
                if ($result instanceof \WP_Error) {
                    WpErrorLogger::getInstance()->logError($result);
                }
                
                break;
            }
        }
    }
    
    private function getType(ProductModel $product)
    {
        $variations = $product->getVariations();
        $productId  = (int)$product->getId()->getEndpoint();
        $type       = \get_post_field('post_type', $productId);
        
        $allowedTypes                      = \wc_get_product_types();
        $allowedTypes['product_variation'] = 'Variables Kind Produkt.';
        
        if ( ! empty($variations) && $type === 'product') {
            return 'variable';
        } elseif (array_key_exists($type, $allowedTypes)) {
            return $type;
        }
        
        return 'simple';
    }
    
    private function getCreationDate(DateTime $creationDate, $gmt = false)
    {
        if (is_null($creationDate)) {
            return null;
        }
        
        if ($gmt) {
            $shopTimeZone = new \DateTimeZone(\wc_timezone_string());
            $creationDate->sub(date_interval_create_from_date_string($shopTimeZone->getOffset($creationDate) / 3600 . ' hours'));
        }
        
        return $creationDate->format('Y-m-d H:i:s');
    }
}
