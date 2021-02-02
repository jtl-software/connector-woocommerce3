<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Product;

use jtl\Connector\Core\Utilities\Language;
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
use JtlWooCommerceConnector\Integrations\Plugins\GermanMarket\GermanMarket;
use JtlWooCommerceConnector\Integrations\Plugins\PerfectWooCommerceBrands\PerfectWooCommerceBrands;
use JtlWooCommerceConnector\Integrations\Plugins\WooCommerce\WooCommerce;
use JtlWooCommerceConnector\Integrations\Plugins\WooCommerce\WooCommerceProduct;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlProduct;
use JtlWooCommerceConnector\Integrations\Plugins\YoastSeo\YoastSeo;
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
    public const
        TYPE_PARENT = 'parent',
        TYPE_CHILD = 'child',
        TYPE_SINGLE = 'single';

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

                if ($this->getPluginsManager()->get(Germanized::class)->canBeUsed()) {
                    $ean = get_post_meta($wcProduct->get_id(), '_ts_gtin');

                    if (is_array($ean) && count($ean) > 0 && array_key_exists(0, $ean)) {
                        $ean = $ean[0];
                    } else {
                        $ean = '';
                    }
                }

                if ($this->getPluginsManager()->get(GermanMarket::class)->canBeUsed()) {
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
     * @throws \jtl\Connector\Core\Exception\LanguageException
     */
    protected function pushData(ProductModel $product)
    {
        if (Config::get(Config::OPTIONS_AUTO_WOOCOMMERCE_OPTIONS)) {
            //Wawi überträgt Netto
            \update_option('woocommerce_prices_include_tax', 'no', true);
            //Preise im Shop mit hinterlegter Steuer
            \update_option('woocommerce_tax_display_shop', 'incl', true);
            //Preise im Cart mit hinterlegter Steuer
            \update_option('woocommerce_tax_display_cart', 'incl', true);
        }

        $defaultI18n = null;
        $masterProductId = $product->getMasterProductId()->getEndpoint();

        if (empty($masterProductId) && isset(self::$idCache[$product->getMasterProductId()->getHost()])) {
            $masterProductId = self::$idCache[$product->getMasterProductId()->getHost()];
            $product->getMasterProductId()->setEndpoint($masterProductId);
        }

        foreach ($product->getI18ns() as $i18n) {
            if ($this->wpml->canBeUsed()) {
                if ($this->wpml->getDefaultLanguage() === Language::convert(null, $i18n->getLanguageISO())) {
                    $defaultI18n = $i18n;
                    break;
                }
            } else {
                if (Util::getInstance()->isWooCommerceLanguage($i18n->getLanguageISO())) {
                    $defaultI18n = $i18n;
                    break;
                }
            }
        }

        if (is_null($defaultI18n)) {
            return $product;
        }

        $wcProductId = (int)$product->getId()->getEndpoint();
        $existingProductId = \wc_get_product_id_by_sku($product->getSku());
        if ($existingProductId !== 0) {
            $wcProductId = $existingProductId;
        }

        $newPostId = $this->getPluginsManager()
            ->get(WooCommerce::class)
            ->getComponent(WooCommerceProduct::class)
            ->saveProduct($wcProductId, $masterProductId, $product, $defaultI18n);

        if (is_null($newPostId)) {
            return $product;
        }

        $product->getId()->setEndpoint($newPostId);

        $wcProduct = \wc_get_product($newPostId);
        $this->onProductInserted($wcProduct, $product, $defaultI18n);

        if ($this->wpml->canBeUsed()) {
            $this->wpml->getComponent(WpmlProduct::class)->setProductTranslations(
                $newPostId,
                $masterProductId,
                $product
            );
        }

        if ($this->getPluginsManager()->get(Germanized::class)->canBeUsed()) {
            (new ProductGermanizedFields)->pushData($product);
        }

        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_GERMAN_MARKET)) {
            (new ProductGermanMarketFields)->pushData($product);
        }

        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)) {
            (new ProductB2BMarketFields)->pushData($product);
        }

        if ($this->getPluginsManager()->get(YoastSeo::class)->canBeUsed()) {
            (new ProductMetaSeo)->pushData($newPostId, $defaultI18n);
        }

        remove_filter('content_save_pre', 'wp_filter_post_kses');
        remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');

        wp_update_post([
            'ID' => $newPostId,
            'post_content' => $defaultI18n->getDescription(),
            'post_excerpt' => $defaultI18n->getShortDescription()
        ]);

        add_filter('content_save_pre', 'wp_filter_post_kses');
        add_filter('content_filtered_save_pre', 'wp_filter_post_kses');

        return $product;
    }

    /**
     * @param ProductModel $product
     * @return ProductModel
     * @throws \Exception
     */
    protected function deleteData(ProductModel $product)
    {
        $productId = (int)$product->getId()->getEndpoint();

        $wcProduct = wc_get_product($productId);

        if ($wcProduct instanceof \WC_Product) {
            \wp_delete_post($productId, true);
            \wc_delete_product_transients($productId);

            if ($this->wpml->canBeUsed()) {
                $this->wpml->getComponent(WpmlProduct::class)->deleteTranslations($wcProduct);
            }

            unset(self::$idCache[$product->getId()->getHost()]);
        }

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
     * @param \WC_Product $wcProduct
     * @param ProductModel $jtlProduct
     * @param ProductI18nModel $jtlProductDefaultI18n
     * @throws \WC_Data_Exception
     * @throws \jtl\Connector\Core\Exception\LanguageException
     * @throws \Exception
     */
    public function onProductInserted(
        \WC_Product $wcProduct,
        ProductModel $jtlProduct,
        ProductI18nModel $jtlProductDefaultI18n
    ) {
        $productType = $this->getType($jtlProduct);

        if (is_null($wcProduct)) {
            return;
        }

        $this->updateProductMeta($jtlProduct, $wcProduct);

        (new Product2Category)->pushData($jtlProduct);

        $this->fixProductPriceForCustomerGroups($jtlProduct, $wcProduct);

        (new ProductPrice)->pushData($wcProduct, $jtlProduct->getVat(), $productType, ...$jtlProduct->getPrices());

        (new ProductSpecialPrice)->pushData($jtlProduct, $wcProduct, $productType);

        (new ProductVaSpeAttrHandler)->pushDataNew($jtlProduct, $wcProduct, $jtlProductDefaultI18n);

        if ($productType !== Product::TYPE_CHILD) {
            $this->updateProduct($wcProduct, $jtlProduct);
            \wc_delete_product_transients($jtlProduct->getId()->getEndpoint());
        }

        //variations
        if ($productType === Product::TYPE_CHILD) {
            $this->updateVariationCombinationChild($wcProduct, $jtlProduct, $jtlProductDefaultI18n);
            (new ProductStockLevel)->pushDataChild($wcProduct, $jtlProduct);
        }

        $this->updateProductType($jtlProduct, $wcProduct);
    }

    /**
     * @param ProductModel $jtlProduct
     * @param \WC_Product $wcProduct
     */
    public function updateProductType(ProductModel $jtlProduct, \WC_Product $wcProduct)
    {
        $productId = $wcProduct->get_id();
        $customProductTypeSet = false;

        foreach ($jtlProduct->getAttributes() as $key => $pushedAttribute) {
            foreach ($pushedAttribute->getI18ns() as $i18n) {
                if (!Util::getInstance()->isWooCommerceLanguage($i18n->getLanguageISO())) {
                    continue;
                }

                $attrName = strtolower(trim($i18n->getName()));

                if (strcmp($attrName, ProductVaSpeAttrHandler::PRODUCT_TYPE_ATTR) === 0) {
                    $value = $i18n->getValue();

                    $allowedTypes = \wc_get_product_types();

                    if (in_array($value, array_keys($allowedTypes))) {
                        $term = get_term_by('slug', wc_sanitize_taxonomy_name(
                            $value
                        ), 'product_type');

                        if ($term instanceof \WP_Term) {
                            $productTypeTerms = wc_get_object_terms($productId, 'product_type');
                            if (is_array($productTypeTerms) && count($productTypeTerms) === 1) {
                                $oldProductTypeTerm = end($productTypeTerms);
                                if ($oldProductTypeTerm->term_id !== $term->term_id) {
                                    $removeObjTermsResult = wp_remove_object_terms($productId,
                                        [$oldProductTypeTerm->term_id],
                                        'product_type');
                                    if ($removeObjTermsResult === true) {
                                        $result = wp_add_object_terms($productId, [$term->term_id], 'product_type');
                                        if (($result instanceof \WP_Error === false) && is_array($result)) {
                                            $customProductTypeSet = true;
                                        }
                                    }
                                } else {
                                    $customProductTypeSet = true;
                                }
                            }
                        }
                    }
                    break;
                }
            }
        }

        if ($customProductTypeSet === false) {
            $oldWcProductType = $this->getWcProductType($jtlProduct);

            $productTypeTerm = \get_term_by('slug', $oldWcProductType, 'product_type');
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
                \wp_set_object_terms($wcProduct->get_id(), $oldWcProductType, 'product_type', false);
            }
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

        if ($this->getPluginsManager()->get(Germanized::class)->canBeUsed()) {
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

        if ($this->getPluginsManager()->get(GermanMarket::class)->canBeUsed()) {
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
     * @param \WC_Product $wcProduct
     * @param ProductModel $product
     * @param ProductI18nModel $jtlProductDefaultI18n
     * @throws \Exception
     */
    public function updateVariationCombinationChild(
        \WC_Product $wcProduct,
        ProductModel $product,
        ProductI18nModel $jtlProductDefaultI18n
    ) {
        $productId = (int)$wcProduct->get_id();

        $productTitle = \esc_html(\get_the_title($wcProduct->get_parent_id()));
        $variation_post_title = sprintf(__('Variation #%s of %s', 'woocommerce'), $productId, $productTitle);
        \wp_update_post([
            'ID' => $productId,
            'post_title' => $variation_post_title,
        ]);
        \update_post_meta($productId, '_variation_description', $jtlProductDefaultI18n->getDescription());
        \update_post_meta($productId, '_mini_dec', $jtlProductDefaultI18n->getShortDescription());
    }

    /**
     * @param \WC_Product $wcProduct
     * @param ProductModel $product
     */
    private function updateProduct(\WC_Product $wcProduct, ProductModel $product)
    {
        $productId = (int)$wcProduct->get_id();

        \update_post_meta($productId, '_visibility', 'visible');

        (new ProductStockLevel)->pushDataParent($wcProduct, $product);

        if ($product->getIsMasterProduct()) {
            Util::getInstance()->addMasterProductToSync($productId);
        }

        self::$idCache[$product->getId()->getHost()] = $productId;
    }

    /**
     * @param ProductModel $product
     * @return string
     */
    protected function getWcProductType(ProductModel $product): string
    {
        switch ($this->getType($product)) {
            case self::TYPE_PARENT:
                $type = 'variable';
                break;
            case self::TYPE_CHILD:
                $type = 'product_variation';
                break;
            case self::TYPE_SINGLE:
            default:
                $type = 'simple';
                break;
        }

        return $type;
    }

    /**
     * @param ProductModel $product
     * @return string
     */
    public function getType(ProductModel $product): string
    {
        if($product->getIsMasterProduct() === true){
            return self::TYPE_PARENT;
        }
        if ($product->getMasterProductId()->getHost() > 0) {
            return self::TYPE_CHILD;
        }
        return self::TYPE_SINGLE;
    }
}
