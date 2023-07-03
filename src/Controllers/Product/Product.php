<?php

/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Product;

use DateTime;
use Exception;
use InvalidArgumentException;
use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\Model\ProductI18n as ProductI18nModel;
use jtl\Connector\Model\TaxRate;
use JtlConnectorAdmin;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Logger\WpErrorLogger;
use JtlWooCommerceConnector\Traits\WawiProductPriceSchmuddelTrait;
use JtlWooCommerceConnector\Utilities\Config;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;
use PhpUnitsOfMeasure\Exception\NonNumericValue;
use PhpUnitsOfMeasure\Exception\NonStringUnitName;
use WC_Data_Exception;

class Product extends BaseController
{
    use WawiProductPriceSchmuddelTrait;

    public const
        TYPE_PARENT = 'parent',
        TYPE_CHILD  = 'child',
        TYPE_SINGLE = 'single';

    private static array $idCache = [];

    /**
     * @param $limit
     * @return array
     * @throws InvalidArgumentException
     */
    public function pullData($limit): array
    {
        $products = [];

        $ids = $this->database->queryList(SqlHelper::productPull($limit));

        foreach ($ids as $id) {
            $product = \wc_get_product($id);

            if (!$product instanceof \WC_Product) {
                continue;
            }

            $postDate     = $product->get_date_created();
            $modDate      = $product->get_date_modified();
            $status       = $product->get_status('view');
            $productModel = (new ProductModel())
                ->setId(new Identity($product->get_id()))
                ->setIsMasterProduct($product->is_type('variable'))
                ->setIsActive(
                    !\in_array($status, [
                        'private',
                        'draft',
                        'future',
                    ])
                )
                ->setSku($product->get_sku())
                ->setVat(Util::getInstance()->getTaxRateByTaxClass($product->get_tax_class()))
                ->setSort($product->get_menu_order())
                ->setIsTopProduct(($itp = $product->is_featured()) ? $itp : $itp == 'yes')
                ->setProductTypeId(new Identity($product->get_type()))
                ->setKeywords(
                    ($tags = \wc_get_product_tag_list($product->get_id(), ' '))
                        ? \strip_tags($tags) : ''
                )
                ->setCreationDate($postDate)
                ->setModified($modDate)
                ->setAvailableFrom($postDate <= $modDate ? null : $postDate)
                ->setHeight((double)$product->get_height())
                ->setLength((double)$product->get_length())
                ->setWidth((double)$product->get_width())
                ->setShippingWeight((double)$product->get_weight())
                ->setConsiderStock(\is_bool($ms = $product->managing_stock()) ? $ms : $ms == 'yes')
                ->setPermitNegativeStock(
                    \is_bool($pns = $product->backorders_allowed()) ? $pns : $pns == 'yes'
                )
                ->setShippingClassId(new Identity($product->get_shipping_class_id()));

            //EAN / GTIN
            if (Util::useGtinAsEanEnabled()) {
                $ean = '';

                if (
                    SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED)
                    || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)
                    || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO)
                ) {
                    $ean = \get_post_meta($product->get_id(), '_ts_gtin');

                    if (\is_array($ean) && \count($ean) > 0 && \array_key_exists(0, $ean)) {
                        $ean = $ean[0];
                    } else {
                        $ean = '';
                    }
                }

                if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_GERMAN_MARKET)) {
                    $ean = \get_post_meta($product->get_id(), '_gm_gtin');

                    if (\is_array($ean) && \count($ean) > 0 && \array_key_exists(0, $ean)) {
                        $ean = $ean[0];
                    } else {
                        $ean = '';
                    }
                }

                $productModel->setEan($ean);
            }

            if ($product->get_parent_id() !== 0) {
                $productModel->setMasterProductId(new Identity($product->get_parent_id()));
            }

            $specialPrices = ProductSpecialPrice::getInstance()->pullData($product, $productModel);
            $prices        = ProductPrice::getInstance()->pullData($product, $productModel);

            $productModel
                ->addI18n(ProductI18n::getInstance()->pullData($product, $productModel))
                ->setPrices($prices)
                ->setSpecialPrices($specialPrices)
                ->setCategories(Product2Category::getInstance()->pullData($product));

            $productVariationSpecificAttribute = (new ProductVaSpeAttrHandler())
                ->pullData($product, $productModel);

            // Var parent or child articles
            if (
                $product instanceof \WC_Product_Variable
                || $product instanceof \WC_Product_Variation
            ) {
                $productModel->setVariations($productVariationSpecificAttribute['productVariation']);
            }

            $productModel->setAttributes($productVariationSpecificAttribute['productAttributes'])
                ->setSpecifics($productVariationSpecificAttribute['productSpecifics']);
            if ($product->managing_stock()) {
                $productModel->setStockLevel((new ProductStockLevel())->pullData($product));
            }

            if (
                SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED)
                || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)
                || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO)
            ) {
                (new ProductGermanizedFields())->pullData($productModel, $product);
            }

            if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_GERMAN_MARKET)) {
                (new ProductGermanMarketFields())->pullData($productModel, $product);
            }

            if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)) {
                (new ProductB2BMarketFields())->pullData($productModel, $product);
            }

            if (SupportedPlugins::isPerfectWooCommerceBrandsActive()) {
                $tmpManId = ProductManufacturer::getInstance()->pullData($productModel);
                if ($tmpManId instanceof Identity) {
                    $productModel->setManufacturerId($tmpManId);
                }
            }

            $products[] = $productModel;
        }

        return $products;
    }

    /**
     * @param ProductModel $product
     * @return ProductModel
     * @throws InvalidArgumentException
     * @throws NonNumericValue
     * @throws NonStringUnitName
     * @throws WC_Data_Exception
     * @throws Exception
     */
    protected function pushData(ProductModel $product): ProductModel
    {
        if (Config::get(Config::OPTIONS_AUTO_WOOCOMMERCE_OPTIONS)) {
            //Wawi überträgt Netto
            \update_option('woocommerce_prices_include_tax', 'no', true);
            //Preise im Shop mit hinterlegter Steuer
            \update_option('woocommerce_tax_display_shop', 'incl', true);
            //Preise im Cart mit hinterlegter Steuer
            \update_option('woocommerce_tax_display_cart', 'incl', true);
        }

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

        if (\is_null($tmpI18n)) {
            return $product;
        }

        $creationDate = \is_null($product->getAvailableFrom())
            ? $product->getCreationDate()
            : $product->getAvailableFrom();

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
            'post_status' => \is_null($product->getAvailableFrom())
                ? ($product->getIsActive() ? 'publish' : 'draft')
                : 'future',
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
        \remove_filter('content_save_pre', 'wp_filter_post_kses');
        \remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
        $newPostId = \wp_insert_post($endpoint, true);
        // Post filtering
        \add_filter('content_save_pre', 'wp_filter_post_kses');
        \add_filter('content_filtered_save_pre', 'wp_filter_post_kses');

        if ($newPostId instanceof \WP_Error) {
            WpErrorLogger::getInstance()->logError($newPostId);

            return $product;
        }

        $product->getId()->setEndpoint($newPostId);

        $this->onProductInserted($product, $tmpI18n);

        if (
            SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO)
        ) {
            (new ProductGermanizedFields())->pushData($product);
        }

        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_GERMAN_MARKET)) {
            (new ProductGermanMarketFields())->pushData($product);
        }

        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)) {
            (new ProductB2BMarketFields())->pushData($product);
        }

        if (
            SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO_PREMIUM)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_RANK_MATH_SEO)
        ) {
            (new ProductMetaSeo())->pushData((int)$newPostId, $tmpI18n);
        }

        \remove_filter('content_save_pre', 'wp_filter_post_kses');
        \remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');

        \wp_update_post([
            'ID' => $newPostId,
            'post_content' => $tmpI18n->getDescription(),
            'post_excerpt' => $tmpI18n->getShortDescription()
        ]);

        \add_filter('content_save_pre', 'wp_filter_post_kses');
        \add_filter('content_filtered_save_pre', 'wp_filter_post_kses');

        return $product;
    }

    /**
     * @param ProductModel $product
     * @return ProductModel
     */
    protected function deleteData(ProductModel $product): ProductModel
    {
        $productId = (int)$product->getId()->getEndpoint();

        \wp_delete_post($productId, true);
        \wc_delete_product_transients($productId);

        unset(self::$idCache[$product->getId()->getHost()]);

        return $product;
    }

    /**
     * @return int|null
     */
    protected function getStats(): ?int
    {
        return \count($this->database->queryList(SqlHelper::productPull()));
    }

    /**
     * @param ProductModel $product
     * @param $meta
     * @return void
     * @throws InvalidArgumentException
     * @throws WC_Data_Exception
     * @throws Exception
     */
    protected function onProductInserted(ProductModel &$product, &$meta): void
    {
        $wcProduct   = \wc_get_product($product->getId()->getEndpoint());
        $productType = $this->getType($product);

        if (\is_null($wcProduct)) {
            return;
        }

        $this->updateProductMeta($product, $wcProduct);

        $this->updateProductRelations($product, $wcProduct, $productType);

        (new ProductVaSpeAttrHandler())->pushDataNew($product, $wcProduct);


        if ($productType !== Product::TYPE_CHILD) {
            $this->updateProduct($product);
            \wc_delete_product_transients($product->getId()->getEndpoint());
        }

        //variations
        if ($productType === Product::TYPE_CHILD) {
            $this->updateVariationCombinationChild($product, $wcProduct, $meta);
        }

        $this->updateProductType($product, $wcProduct);
    }

    /**
     * @param ProductModel $jtlProduct
     * @param \WC_Product $wcProduct
     * @return void
     */
    private function updateProductType(ProductModel $jtlProduct, \WC_Product $wcProduct): void
    {
        $productId            = $wcProduct->get_id();
        $customProductTypeSet = false;

        foreach ($jtlProduct->getAttributes() as $key => $pushedAttribute) {
            foreach ($pushedAttribute->getI18ns() as $i18n) {
                if (!Util::getInstance()->isWooCommerceLanguage($i18n->getLanguageISO())) {
                    continue;
                }

                $attrName = \strtolower(\trim($i18n->getName()));

                if (\strcmp($attrName, ProductVaSpeAttrHandler::PRODUCT_TYPE_ATTR) === 0) {
                    $value = $i18n->getValue();

                    $allowedTypes = \wc_get_product_types();

                    if (\in_array($value, \array_keys($allowedTypes))) {
                        $term = \get_term_by('slug', \wc_sanitize_taxonomy_name(
                            $value
                        ), 'product_type');

                        if ($term instanceof \WP_Term) {
                            $productTypeTerms = \wc_get_object_terms($productId, 'product_type');
                            if (\is_array($productTypeTerms) && \count($productTypeTerms) === 1) {
                                $oldProductTypeTerm = \end($productTypeTerms);
                                if ($oldProductTypeTerm->term_id !== $term->term_id) {
                                    $removeObjTermsResult = \wp_remove_object_terms(
                                        $productId,
                                        [$oldProductTypeTerm->term_id],
                                        'product_type'
                                    );
                                    if ($removeObjTermsResult === true) {
                                        $result = \wp_add_object_terms(
                                            $productId,
                                            [$term->term_id],
                                            'product_type'
                                        );
                                        if (($result instanceof \WP_Error === false) && \is_array($result)) {
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

            $productTypeTerm    = \get_term_by('slug', $oldWcProductType, 'product_type');
            $currentProductType = \wp_get_object_terms($wcProduct->get_id(), 'product_type');

            $removeTerm = null;
            foreach ($currentProductType as $term) {
                if ($term instanceof \WP_Term) {
                    $removeTerm = $term->term_id;
                }
            }

            if (\is_int($removeTerm)) {
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
     * @return void
     * @throws WC_Data_Exception
     * @throws Exception
     */
    private function updateProductMeta(ProductModel $product, \WC_Product $wcProduct): void
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

        if (
            SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO)
        ) {
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

        if (!\is_null($product->getModified())) {
            $wcProduct->set_date_modified($product->getModified()->getTimestamp());
        }

        if (!\is_null($product->getTaxClassId()) && !empty($product->getTaxClassId()->getEndpoint())) {
            $taxClassName = $product->getTaxClassId()->getEndpoint();
        } else {
            $taxClassName = $this->database->queryOne(SqlHelper::taxClassByRate($product->getVat())) ?? '';
            if (\count($product->getTaxRates()) > 0 && !\is_null($product->getTaxClassId())) {
                $taxClassName = $this->findTaxClassName(...$product->getTaxRates()) ?? $taxClassName;
                //$product->getTaxClassId()->setEndpoint($taxClassName === '' ? 'default' : $taxClassName);
            }
        }

        $wcProduct->set_tax_class($taxClassName === 'default' ? '' : $taxClassName);
        $wcProduct->save();

        $tags = \array_map('trim', \explode(' ', $product->getKeywords()));
        \wp_set_post_terms($wcProduct->get_id(), \implode(',', $tags), 'product_tag', false);

        $shippingClass = \get_term_by(
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
     * @param string $productType
     * @return void
     * @throws InvalidArgumentException
     */
    private function updateProductRelations(ProductModel $product, \WC_Product $wcProduct, string $productType): void
    {
        (new Product2Category())->pushData($product);
        $this->fixProductPriceForCustomerGroups($product, $wcProduct);

        (new ProductPrice())->savePrices($wcProduct, $product->getVat(), $productType, ...$product->getPrices());

        (new ProductSpecialPrice())->pushData($product, $wcProduct, $productType);
    }

    /**
     * @param ProductModel $product
     * @param \WC_Product $wcProduct
     * @param $meta
     * @return void
     * @throws Exception
     */
    private function updateVariationCombinationChild(ProductModel $product, \WC_Product $wcProduct, $meta): void
    {
        $productId = (int)$product->getId()->getEndpoint();

        $productTitle         = \esc_html(\get_the_title($product->getMasterProductId()->getEndpoint()));
        $variation_post_title = \sprintf(\__('Variation #%s of %s', 'woocommerce'), $productId, $productTitle);
        \wp_update_post([
            'ID' => $productId,
            'post_title' => $variation_post_title,
        ]);
        \update_post_meta($productId, '_variation_description', $meta->getDescription());
        \update_post_meta($productId, '_mini_dec', $meta->getShortDescription());

        (new ProductStockLevel())->pushDataChild($product);
    }

    /**
     * @param ProductModel $product
     * @return void
     * @throws Exception
     */
    private function updateProduct(ProductModel $product): void
    {
        $productId = (int)$product->getId()->getEndpoint();

        \update_post_meta($productId, '_visibility', 'visible');

        (new ProductStockLevel())->pushDataParent($product);

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
        if ($product->getIsMasterProduct() === true) {
            return self::TYPE_PARENT;
        }
        if ($product->getMasterProductId()->getHost() > 0) {
            return self::TYPE_CHILD;
        }
        return self::TYPE_SINGLE;
    }

    /**
     * @param DateTime $creationDate
     * @param bool $gmt
     * @return string|null
     * @throws Exception
     */
    private function getCreationDate(DateTime $creationDate, bool $gmt = false): ?string
    {
        if (\is_null($creationDate)) {
            return null;
        }

        if ($gmt) {
            $shopTimeZone = new \DateTimeZone(\wc_timezone_string());
            $creationDate->sub(
                \date_interval_create_from_date_string(
                    $shopTimeZone->getOffset($creationDate) / 3600 . ' hours'
                )
            );
        }

        return $creationDate->format('Y-m-d H:i:s');
    }

    /**
     * @param TaxRate ...$jtlTaxRates
     * @return string|null
     */
    public function findTaxClassName(TaxRate ...$jtlTaxRates): ?string
    {
        $foundTaxClasses = $this->database->query(SqlHelper::getTaxClassByTaxRates(...$jtlTaxRates));
        return $foundTaxClasses[0]['taxClassName'] ?? null;
    }
}
