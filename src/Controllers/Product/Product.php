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
use JtlConnectorAdmin;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Controllers\Traits\DeleteTrait;
use JtlWooCommerceConnector\Controllers\Traits\PullTrait;
use JtlWooCommerceConnector\Controllers\Traits\PushTrait;
use JtlWooCommerceConnector\Controllers\Traits\StatsTrait;
use JtlWooCommerceConnector\Integrations\Plugins\Germanized\Germanized;
use JtlWooCommerceConnector\Integrations\Plugins\PerfectWooCommerceBrands\PerfectWooCommerceBrands;
use JtlWooCommerceConnector\Integrations\Plugins\WooCommerce\WooCommerce;
use JtlWooCommerceConnector\Integrations\Plugins\WooCommerce\WooCommerceProduct;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlProduct;
use JtlWooCommerceConnector\Logger\WpErrorLogger;
use JtlWooCommerceConnector\Traits\WawiProductPriceSchmuddelTrait;
use JtlWooCommerceConnector\Utilities\Config;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;

/**
 * Class Product
 * @package JtlWooCommerceConnector\Controllers\Product
 */
class Product extends BaseController
{
    use PullTrait, PushTrait, DeleteTrait, StatsTrait, WawiProductPriceSchmuddelTrait;

    /**
     * @var array
     */
    private static $idCache = [];

    /**
     * @param int $limit
     * @return array
     * @throws \Exception
     */
    protected function getProductsIds(int $limit)
    {
        if ($this->wpml->canBeUsed()) {
            $ids = $this->wpml->getComponent(WpmlProduct::class)->getProducts($limit);
        } else {
            $ids = $this->database->queryList(SqlHelper::productPull($limit));
        }

        return $ids;
    }

    /**
     * @param $limit
     * @return array
     * @throws \PhpUnitsOfMeasure\Exception\NonNumericValue
     * @throws \PhpUnitsOfMeasure\Exception\NonStringUnitName
     */
    public function pullData($limit)
    {
        $products = [];

        $ids = $this->getProductsIds($limit);

        foreach ($ids as $id) {
            $wcProduct = \wc_get_product($id);

            if (!$wcProduct instanceof \WC_Product) {
                continue;
            }

            $postDate = $wcProduct->get_date_created();
            $modDate = $wcProduct->get_date_modified();
            $status = $wcProduct->get_status('view');
            $jtlProduct = (new ProductModel())
                ->setId(new Identity($wcProduct->get_id()))
                ->setIsMasterProduct($wcProduct->is_type('variable'))
                ->setIsActive(in_array($status, [
                    'private',
                    'draft',
                    'future',
                ]) ? false : true)
                ->setSku($wcProduct->get_sku())
                ->setVat(Util::getInstance()->getTaxRateByTaxClass($wcProduct->get_tax_class()))
                ->setSort($wcProduct->get_menu_order())
                ->setIsTopProduct(($itp = $wcProduct->is_featured()) ? $itp : $itp === 'yes')
                ->setProductTypeId(new Identity($wcProduct->get_type()))
                ->setKeywords(($tags = \wc_get_product_tag_list($wcProduct->get_id())) ? strip_tags($tags) : '')
                ->setCreationDate($postDate)
                ->setModified($modDate)
                ->setAvailableFrom($postDate <= $modDate ? null : $postDate)
                ->setHeight((double)$wcProduct->get_height())
                ->setLength((double)$wcProduct->get_length())
                ->setWidth((double)$wcProduct->get_width())
                ->setShippingWeight((double)$wcProduct->get_weight())
                ->setConsiderStock(is_bool($ms = $wcProduct->managing_stock()) ? $ms : $ms === 'yes')
                ->setPermitNegativeStock(is_bool($pns = $wcProduct->backorders_allowed()) ? $pns : $pns === 'yes')
                ->setShippingClassId(new Identity($wcProduct->get_shipping_class_id()));

            //EAN / GTIN
            if (Util::useGtinAsEanEnabled()) {
                $ean = '';

                if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED)
                    || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)
                    || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO)) {
                    $ean = get_post_meta($wcProduct->get_id(), '_ts_gtin');

                    if (is_array($ean) && count($ean) > 0 && array_key_exists(0, $ean)) {
                        $ean = $ean[0];
                    } else {
                        $ean = '';
                    }
                }

                if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_GERMAN_MARKET)) {
                    $ean = get_post_meta($wcProduct->get_id(), '_gm_gtin');

                    if (is_array($ean) && count($ean) > 0 && array_key_exists(0, $ean)) {
                        $ean = $ean[0];
                    } else {
                        $ean = '';
                    }
                }

                $jtlProduct->setEan($ean);
            }

            if ($wcProduct->get_parent_id() !== 0) {
                $jtlProduct->setMasterProductId(new Identity($wcProduct->get_parent_id()));
            }

            if ($this->wpml->canBeUsed()) {
                $this->wpml->getComponent(WpmlProduct::class)->getTranslations($wcProduct, $jtlProduct);
            }

            $productI18n = $this->getPluginsManager()
                ->get(WooCommerce::class)
                ->getComponent(WooCommerceProduct::class)
                ->getI18ns($wcProduct, $jtlProduct, Util::getInstance()->getWooCommerceLanguage());

            $jtlProduct->addI18n($productI18n)
                ->setPrices(ProductPrice::getInstance()->pullData($wcProduct, $jtlProduct))
                ->setSpecialPrices(ProductSpecialPrice::getInstance()->pullData($wcProduct, $jtlProduct))
                ->setCategories(Product2Category::getInstance()->pullData($wcProduct));

            $productVariationSpecificAttribute = (new ProductVaSpeAttrHandler)->pullData($wcProduct, $jtlProduct);

            // Var parent or child articles
            if ($wcProduct instanceof \WC_Product_Variable || $wcProduct instanceof \WC_Product_Variation) {
                $jtlProduct->setVariations($productVariationSpecificAttribute['productVariation']);
            }

            $jtlProduct->setAttributes($productVariationSpecificAttribute['productAttributes'])
                ->setSpecifics($productVariationSpecificAttribute['productSpecifics']);
            if ($wcProduct->managing_stock()) {
                $jtlProduct->setStockLevel((new ProductStockLevel)->pullData($wcProduct));
            }

            if ($this->getPluginsManager()->get(Germanized::class)->canBeUsed()) {
                (new ProductGermanizedFields)->pullData($jtlProduct, $wcProduct);
            }

            if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_GERMAN_MARKET)) {
                (new ProductGermanMarketFields)->pullData($jtlProduct, $wcProduct);
            }

            if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)) {
                (new ProductB2BMarketFields)->pullData($jtlProduct, $wcProduct);
            }

            if ($this->getPluginsManager()->get(PerfectWooCommerceBrands::class)->canBeUsed()) {
                $tmpManId = ProductManufacturer::getInstance()->pullData($jtlProduct);
                if (!is_null($tmpManId) && $tmpManId instanceof Identity) {
                    $jtlProduct->setManufacturerId($tmpManId);
                }
            }

            $products[] = $jtlProduct;
        }

        return $products;
    }

    /**
     * @param ProductModel $product
     * @return ProductModel
     * @throws \PhpUnitsOfMeasure\Exception\NonNumericValue
     * @throws \PhpUnitsOfMeasure\Exception\NonStringUnitName
     * @throws \WC_Data_Exception
     */
    protected function pushData(ProductModel $product)
    {
        if (Config::get(JtlConnectorAdmin::OPTIONS_AUTO_WOOCOMMERCE_OPTIONS)) {
            //Wawi überträgt Netto
            \update_option('woocommerce_prices_include_tax', 'no', true);
            //Preise im Shop mit hinterlegter Steuer
            \update_option('woocommerce_tax_display_shop', 'incl', true);
            //Preise im Cart mit hinterlegter Steuer
            \update_option('woocommerce_tax_display_cart', 'incl', true);
        }

        $tmpI18n = null;
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

        if (!$creationDate instanceof DateTime) {
            $creationDate = new DateTime();
        }

        $isMasterProduct = empty($masterProductId);

        /** @var ProductI18nModel $tmpI18n */
        $endpoint = [
            'ID' => (int)$product->getId()->getEndpoint(),
            'post_type' => $isMasterProduct ? 'product' : 'product_variation',
            'post_title' => $tmpI18n->getName(),
            'post_name' => $tmpI18n->getUrlPath(),
            'post_content' => $tmpI18n->getDescription(),
            'post_excerpt' => $tmpI18n->getShortDescription(),
            'post_date' => $this->getCreationDate($creationDate),
            //'post_date_gmt' => $this->getCreationDate($creationDate, true),
            'post_status' => is_null($product->getAvailableFrom()) ? ($product->getIsActive() ? 'publish' : 'draft') : 'future',
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

        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO)) {
            (new ProductGermanizedFields)->pushData($product);
        }

        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_GERMAN_MARKET)) {
            (new ProductGermanMarketFields)->pushData($product);
        }

        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)) {
            (new ProductB2BMarketFields)->pushData($product);
        }

        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO_PREMIUM)) {
            (new ProductMetaSeo)->pushData($product, $newPostId, $tmpI18n);
        }

        return $product;
    }

    /**
     * @param ProductModel $product
     * @return ProductModel
     */
    protected function deleteData(ProductModel $product)
    {
        $productId = (int)$product->getId()->getEndpoint();

        \wp_delete_post($productId, true);
        \wc_delete_product_transients($productId);

        unset(self::$idCache[$product->getId()->getHost()]);

        return $product;
    }

    /**
     * @return int
     * @throws \Exception
     */
    protected function getStats()
    {
        if ($this->wpml->canBeUsed()) {
            $ids = $this->wpml->getComponent(WpmlProduct::class)->getProducts();
        } else {
            $ids = $this->database->queryList(SqlHelper::productPull());
        }
        return count($ids);
    }

    /**
     * @param ProductModel $product
     * @param $meta
     * @throws \WC_Data_Exception
     */
    protected function onProductInserted(ProductModel &$product, &$meta)
    {
        $wcProduct = \wc_get_product($product->getId()->getEndpoint());
        $productType = $this->getType($product);

        if (is_null($wcProduct)) {
            return;
        }

        $this->updateProductMeta($product, $wcProduct);

        $this->updateProductRelations($product, $wcProduct);

        (new ProductVaSpeAttrHandler)->pushDataNew($product, $wcProduct);


        if ($productType !== 'product_variation') {
            $this->updateProduct($product);
            \wc_delete_product_transients($product->getId()->getEndpoint());
        }

        //variations
        if ($productType === 'product_variation') {
            $this->updateVariationCombinationChild($product, $wcProduct, $meta);
        }

        $productTypeTerm = \get_term_by('slug', $productType, 'product_type');
        $currentProductType = \wp_get_object_terms($wcProduct->get_id(), 'product_type');

        $removeTerm = null;
        foreach ($currentProductType as $term) {
            if ($term instanceof \WP_Term) {
                $removeTerm = $term->term_id;
            }
        }

        if (!is_null($removeTerm) && is_int($removeTerm)) {
            \wp_remove_object_terms($wcProduct->get_id(), $removeTerm, 'product_type');
        }

        if ($productTypeTerm instanceof \WP_Term) {
            \wp_set_object_terms($wcProduct->get_id(), $productTypeTerm->term_id, 'product_type', false);
        } else {
            \wp_set_object_terms($wcProduct->get_id(), $productType, 'product_type', false);
        }
    }

    /**
     * @param ProductModel $product
     * @param \WC_Product $wcProduct
     * @throws \WC_Data_Exception
     */
    private function updateProductMeta(ProductModel $product, \WC_Product $wcProduct)
    {
        $parent = $product->getMasterProductId()->getEndpoint();

        $wcProduct->set_sku($product->getSku());
        $wcProduct->set_parent_id(empty($parent) ? 0 : (int)$parent);
        $wcProduct->set_menu_order($product->getSort());
        $wcProduct->set_featured($product->getIsTopProduct());
        $wcProduct->set_height($product->getHeight());
        $wcProduct->set_length($product->getLength());
        $wcProduct->set_width($product->getWidth());
        $wcProduct->set_weight($product->getShippingWeight());

        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO)) {
            $productId = $product->getId()->getEndpoint();
            if (Util::useGtinAsEanEnabled()) {
                \update_post_meta(
                    $productId,
                    '_ts_gtin',
                    (string)$product->getEan(),
                    \get_post_meta($productId, '_ts_gtin', true)
                );
            } else {
                \update_post_meta(
                    $productId,
                    '_ts_gtin',
                    '',
                    \get_post_meta($productId, '_ts_gtin', true)
                );
            }
        }

        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_GERMAN_MARKET)) {
            $productId = $product->getId()->getEndpoint();

            if (Util::useGtinAsEanEnabled()) {
                \update_post_meta(
                    $productId,
                    '_gm_gtin',
                    (string)$product->getEan(),
                    \get_post_meta($productId, '_gm_gtin', true)
                );
            } else {
                \update_post_meta(
                    $productId,
                    '_gm_gtin',
                    '',
                    \get_post_meta($productId, '_gm_gtin', true)
                );
            }
        }

        if (!is_null($product->getModified())) {
            $wcProduct->set_date_modified($product->getModified()->getTimestamp());
        }

        $taxClass = $this->database->queryOne(SqlHelper::taxClassByRate($product->getVat()));
        $wcProduct->set_tax_class(is_null($taxClass) ? '' : $taxClass);

        $wcProduct->save();

        $tags = array_map('trim', explode(' ', $product->getKeywords()));
        \wp_set_post_terms($wcProduct->get_id(), implode(',', $tags), 'product_tag', false);

        $shippingClass = get_term_by(
            'id',
            \wc_clean($product->getShippingClassId()->getEndpoint()),
            'product_shipping_class'
        );

        if (!empty($shippingClass)) {
            \wp_set_object_terms(
                $wcProduct->get_id(),
                $shippingClass->term_id,
                'product_shipping_class',
                false
            );
        }
        //Map to Delivery-time
        (new ProductDeliveryTime())->pushData($product, $wcProduct);
        //Map to Manufacturer
        (new ProductManufacturer())->pushData($product);
    }

    /**
     * @param ProductModel $product
     * @param \WC_Product $wcProduct
     */
    private function updateProductRelations(ProductModel $product, \WC_Product $wcProduct)
    {
        (new Product2Category)->pushData($product);
        $this->fixProductPriceForCustomerGroups($product, $wcProduct);
        (new ProductPrice)->pushData($product);
        (new ProductSpecialPrice)->pushData($product, $wcProduct);
    }

    /**
     * @param ProductModel $product
     * @param \WC_Product $wcProduct
     * @param $meta
     */
    private function updateVariationCombinationChild(ProductModel $product, \WC_Product $wcProduct, $meta)
    {
        $productId = (int)$product->getId()->getEndpoint();

        $productTitle = \esc_html(\get_the_title($product->getMasterProductId()->getEndpoint()));
        $variation_post_title = sprintf(__('Variation #%s of %s', 'woocommerce'), $productId, $productTitle);
        \wp_update_post([
            'ID' => $productId,
            'post_title' => $variation_post_title,
        ]);
        \update_post_meta($productId, '_variation_description', $meta->getDescription());
        \update_post_meta($productId, '_mini_dec', $meta->getShortDescription());

        (new ProductStockLevel)->pushDataChild($product);
    }

    /**
     * @param ProductModel $product
     */
    private function updateProduct(ProductModel $product)
    {
        $productId = (int)$product->getId()->getEndpoint();

        \update_post_meta($productId, '_visibility', 'visible');

        (new ProductStockLevel)->pushDataParent($product);

        if ($product->getIsMasterProduct()) {
            Util::getInstance()->addMasterProductToSync($productId);
        }

        self::$idCache[$product->getId()->getHost()] = $productId;
    }

    /**
     * @param ProductModel $product
     * @return string
     */
    public function getType(ProductModel $product)
    {
        $variations = $product->getVariations();
        $productId = (int)$product->getId()->getEndpoint();
        $type = \get_post_field('post_type', $productId);

        $allowedTypes = \wc_get_product_types();
        $allowedTypes['product_variation'] = 'Variables Kind Produkt.';

        if (!empty($variations) && $type === 'product') {
            return 'variable';
        } elseif (array_key_exists($type, $allowedTypes)) {
            return $type;
        }

        return 'simple';
    }

    /**
     * @param DateTime $creationDate
     * @param bool $gmt
     * @return string|null
     */
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
