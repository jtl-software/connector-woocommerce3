<?php

namespace JtlWooCommerceConnector\Controllers;

use DateTime;
use Exception;
use InvalidArgumentException;
use Jtl\Connector\Core\Controller\DeleteInterface;
use Jtl\Connector\Core\Controller\PullInterface;
use Jtl\Connector\Core\Controller\PushInterface;
use Jtl\Connector\Core\Controller\StatisticInterface;
use Jtl\Connector\Core\Exception\TranslatableAttributeException;
use Jtl\Connector\Core\Model\AbstractModel;
use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\Product as ProductModel;
use Jtl\Connector\Core\Model\ProductI18n as ProductI18nModel;
use Jtl\Connector\Core\Model\QueryFilter;
use Jtl\Connector\Core\Model\TaxRate;
use JtlWooCommerceConnector\Controllers\Product\Product2CategoryController;
use JtlWooCommerceConnector\Controllers\Product\ProductAdvancedCustomFieldsController;
use JtlWooCommerceConnector\Controllers\Product\ProductB2BMarketFieldsController;
use JtlWooCommerceConnector\Controllers\Product\ProductDeliveryTimeController;
use JtlWooCommerceConnector\Controllers\Product\ProductGermanizedFieldsController;
use JtlWooCommerceConnector\Controllers\Product\ProductGermanMarketFieldsController;
use JtlWooCommerceConnector\Controllers\Product\ProductI18nController;
use JtlWooCommerceConnector\Controllers\Product\ProductManufacturerController;
use JtlWooCommerceConnector\Controllers\Product\ProductMetaSeoController;
use JtlWooCommerceConnector\Controllers\Product\ProductPrice;
use JtlWooCommerceConnector\Controllers\Product\ProductSpecialPriceController;
use JtlWooCommerceConnector\Controllers\Product\ProductVaSpeAttrHandlerController;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlProduct;
use JtlWooCommerceConnector\Logger\ErrorFormatter;
use JtlWooCommerceConnector\Traits\WawiProductPriceSchmuddelTrait;
use JtlWooCommerceConnector\Utilities\Config;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;
use PhpUnitsOfMeasure\Exception\NonNumericValue;
use PhpUnitsOfMeasure\Exception\NonStringUnitName;
use WC_Data_Exception;
use WC_Product;

class ProductController extends AbstractBaseController implements
    PullInterface,
    PushInterface,
    DeleteInterface,
    StatisticInterface
{
    use WawiProductPriceSchmuddelTrait;

    public const
        TYPE_PARENT = 'parent',
        TYPE_CHILD  = 'child',
        TYPE_SINGLE = 'single';

    private static array $idCache = [];

    /**
     * @throws \Psr\Log\InvalidArgumentException
     * @throws Exception
     */
    protected function getProductsIds(int $limit)
    {
        if ($this->wpml->canBeUsed()) {
            $ids = $this->wpml->getComponent(WpmlProduct::class)->getProducts($limit);
        } else {
            $ids = $this->db->queryList(SqlHelper::productPull($limit));
        }

        return $ids;
    }

    /**
     * @param QueryFilter $query
     * @return array
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function pull(QueryFilter $query): array
    {
        $products = [];

        $ids = $this->getProductsIds($query->getLimit());

        foreach ($ids as $id) {
            $product = \wc_get_product($id);

            if (!$product instanceof WC_Product) {
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
                ->setVat($this->util->getTaxRateByTaxClass($product->get_tax_class()))
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

            //EAN / GTIN / MPN
            if ($this->util->useGtinAsEanEnabled()) {
                $manufacturerNumber = '';
                $ean                = '';

                if (
                    SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED)
                    || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)
                    || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO)
                ) {
                    $manufacturerNumber = \get_post_meta($product->get_id(), '_ts_mpn', true);
                    $ean                = \get_post_meta($product->get_id(), '_ts_gtin');

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
                $productModel->setManufacturerNumber($manufacturerNumber);
            }

            if ($product->get_parent_id() !== 0) {
                $productModel->setMasterProductId(new Identity($product->get_parent_id()));
            }

            $specialPrices = (new ProductSpecialPriceController($this->db, $this->util))
                ->pullData($product, $productModel);
            $prices        = (new ProductPrice($this->db, $this->util))
                ->pullData($product, $productModel);

            if ($this->wpml->canBeUsed()) {
                $this->wpml->getComponent(WpmlProduct::class)->getTranslations($product, $productModel);
            }

            $productModel
                ->addI18n((new ProductI18nController($this->db, $this->util))->pullData($product, $productModel))
                ->setPrices(...$prices)
                ->setSpecialPrices(...$specialPrices)
                ->setCategories(...(new Product2CategoryController($this->db, $this->util))->pullData($product));

            $productVariationSpecificAttribute = (new ProductVaSpeAttrHandlerController($this->db, $this->util))
                ->pullData($product, $productModel);

            // Var parent or child articles
            if (
                $product instanceof \WC_Product_Variable
                || $product instanceof \WC_Product_Variation
            ) {
                $productModel->setVariations(...$productVariationSpecificAttribute['productVariation']);
            }

            $productModel->setAttributes(...$productVariationSpecificAttribute['productAttributes'])
                ->setSpecifics(...$productVariationSpecificAttribute['productSpecifics']);
            if ($product->managing_stock()) {
                $productModel->setStockLevel(
                    (new \JtlWooCommerceConnector\Controllers\Product\ProductStockLevelController(
                        $this->db,
                        $this->util
                    ))->pullData($product)->getStockLevel()
                );
            }

            if (
                SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED)
                || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)
                || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO)
            ) {
                (new ProductGermanizedFieldsController($this->db, $this->util))->pullData($productModel, $product);
            }

            if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_GERMAN_MARKET)) {
                (new ProductGermanMarketFieldsController($this->db, $this->util))->pullData($productModel, $product);
            }

            if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)) {
                (new ProductB2BMarketFieldsController($this->db, $this->util))->pullData($productModel, $product);
            }

            if (SupportedPlugins::isPerfectWooCommerceBrandsActive()) {
                $tmpManId = (new ProductManufacturerController($this->db, $this->util))->pullData($productModel);
                if ($tmpManId instanceof Identity) {
                    $productModel->setManufacturerId($tmpManId);
                }
            }

            if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_ADVANCED_CUSTOM_FIELDS)) {
                (new ProductAdvancedCustomFieldsController($this->db, $this->util))->pullData($productModel, $product);
            }

            $products[] = $productModel;
        }

        return $products;
    }

    /**
     * @param ProductModel $model
     * @return ProductModel
     * @throws InvalidArgumentException
     * @throws NonNumericValue
     * @throws NonStringUnitName
     * @throws WC_Data_Exception
     * @throws Exception
     */
    public function push(AbstractModel $model): AbstractModel
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
        $masterProductId = $model->getMasterProductId()->getEndpoint();

        if (empty($masterProductId) && isset(self::$idCache[$model->getMasterProductId()->getHost()])) {
            $masterProductId = self::$idCache[$model->getMasterProductId()->getHost()];
            $model->getMasterProductId()->setEndpoint($masterProductId);
        }

        foreach ($model->getI18ns() as $i18n) {
            if ($this->wpml->canBeUsed()) {
                if ($this->wpml->getDefaultLanguage() === Util::mapLanguageIso($i18n->getLanguageIso())) {
                    $tmpI18n = $i18n;
                    break;
                }
            } else {
                if ($this->util->isWooCommerceLanguage($i18n->getLanguageISO())) {
                    $tmpI18n = $i18n;
                    break;
                }
            }
        }

        if (\is_null($tmpI18n)) {
            return $model;
        }

        $wcProductId       = (int)$model->getId()->getEndpoint();
        $existingProductId = \wc_get_product_id_by_sku($model->getSku());
        if ($existingProductId !== 0) {
            $wcProductId = $existingProductId;
        }

        $creationDate = \is_null($model->getAvailableFrom())
            ? $model->getCreationDate()
            : $model->getAvailableFrom();

        if (!$creationDate instanceof DateTime) {
            $creationDate = new DateTime();
        }

        $isMasterProduct = empty($masterProductId);

        /** @var ProductI18nModel $tmpI18n */
        $endpoint = [
            'ID' => $wcProductId,
            'post_type' => $isMasterProduct ? 'product' : 'product_variation',
            'post_title' => $tmpI18n->getName(),
            'post_name' => $tmpI18n->getUrlPath(),
            'post_content' => $tmpI18n->getDescription(),
            'post_excerpt' => $tmpI18n->getShortDescription(),
            'post_date' => $this->getCreationDate($creationDate),
            'post_status' => \is_null($model->getAvailableFrom())
                ? ($model->getIsActive() ? 'publish' : 'draft')
                : 'future',
        ];

        if ($endpoint['ID'] !== 0) {
            // Needs to be set for existing products otherwise commenting is disabled
            $endpoint['comment_status'] = \get_post_field('comment_status', $endpoint['ID']);
        }

        // Post filtering
        \remove_filter('content_save_pre', 'wp_filter_post_kses');
        \remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
        $newPostId = \wp_insert_post($endpoint, true);
        // Post filtering
        \add_filter('content_save_pre', 'wp_filter_post_kses');
        \add_filter('content_filtered_save_pre', 'wp_filter_post_kses');

        if ($newPostId instanceof \WP_Error) {
            $this->logger->error(ErrorFormatter::formatError($newPostId));
            return $model;
        }

        if (\is_null($newPostId)) {
            return $model;
        }

        $model->getId()->setEndpoint($newPostId);

        $wcProduct = \wc_get_product($newPostId);
        $this->onProductInserted($model, $tmpI18n);

        if ($this->wpml->canBeUsed()) {
            $this->wpml->getComponent(WpmlProduct::class)->setProductTranslations(
                $newPostId,
                $masterProductId,
                $model
            );
        }

        if (
            SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO)
        ) {
            (new ProductGermanizedFieldsController($this->db, $this->util))->pushData($model);
        }

        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_GERMAN_MARKET)) {
            (new ProductGermanMarketFieldsController($this->db, $this->util))->pushData($model);
        }

        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)) {
            (new ProductB2BMarketFieldsController($this->db, $this->util))->pushData($model);
        }

        if (
            SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO_PREMIUM)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_RANK_MATH_SEO)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_RANK_MATH_SEO_AI)
        ) {
            (new ProductMetaSeoController($this->db, $this->util))->pushData((int)$newPostId, $tmpI18n);
        }

        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_ADVANCED_CUSTOM_FIELDS)) {
            (new ProductAdvancedCustomFieldsController($this->db, $this->util))->pushData($model);
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

        return $model;
    }

    /**
     * @param ProductModel $model
     * @return ProductModel
     * @throws Exception
     */
    public function delete(AbstractModel $model): AbstractModel
    {
        $productId = (int)$model->getId()->getEndpoint();

        $wcProduct = \wc_get_product($productId);

        if ($wcProduct instanceof \WC_Product) {
            \wp_delete_post($productId, true);
            \wc_delete_product_transients($productId);

            if ($this->wpml->canBeUsed()) {
                $this->wpml->getComponent(WpmlProduct::class)->deleteTranslations($wcProduct);
            }

            unset(self::$idCache[$model->getId()->getHost()]);
        }

        return $model;
    }

    /**
     * @param QueryFilter $query
     * @return int
     * @throws \Psr\Log\InvalidArgumentException
     * @throws Exception
     */
    public function statistic(QueryFilter $query): int
    {
        if ($this->wpml->canBeUsed()) {
            $ids = $this->wpml->getComponent(WpmlProduct::class)->getProducts();
        } else {
            $ids = $this->db->queryList(SqlHelper::productPull());
        }
        return \count($ids);
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

        (new ProductVaSpeAttrHandlerController($this->db, $this->util))->pushDataNew($product, $wcProduct, $meta);

        if ($productType !== ProductController::TYPE_CHILD) {
            $this->updateProduct($product, $wcProduct);
            \wc_delete_product_transients($product->getId()->getEndpoint());
        }

        //variations
        if ($productType === ProductController::TYPE_CHILD) {
            $this->updateVariationCombinationChild($product, $wcProduct, $meta);
        }

        $this->updateProductType($product, $wcProduct);
    }

    /**
     * @param ProductModel $jtlProduct
     * @param WC_Product $wcProduct
     * @return void
     * @throws TranslatableAttributeException
     */
    public function updateProductType(ProductModel $jtlProduct, WC_Product $wcProduct): void
    {
        $productId            = $wcProduct->get_id();
        $customProductTypeSet = false;

        foreach ($jtlProduct->getAttributes() as $key => $pushedAttribute) {
            foreach ($pushedAttribute->getI18ns() as $i18n) {
                if (!$this->util->isWooCommerceLanguage($i18n->getLanguageISO())) {
                    continue;
                }

                $attrName = \strtolower(\trim($i18n->getName()));

                if (\strcmp($attrName, ProductVaSpeAttrHandlerController::PRODUCT_TYPE_ATTR) === 0) {
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
     * @param WC_Product $wcProduct
     * @return void
     * @throws WC_Data_Exception
     * @throws Exception
     */
    private function updateProductMeta(ProductModel $product, WC_Product $wcProduct): void
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
            if ($this->util->useGtinAsEanEnabled()) {
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

            if ($this->util->useGtinAsEanEnabled()) {
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
            $taxClassName = $this->db->queryOne(SqlHelper::taxClassByRate($product->getVat())) ?? '';
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
        (new ProductDeliveryTimeController($this->db, $this->util))->pushData($product, $wcProduct);
        //Map to Manufacturer
        (new ProductManufacturerController($this->db, $this->util))->pushData($product);
    }

    /**
     * @param ProductModel $product
     * @param WC_Product $wcProduct
     * @param string $productType
     * @return void
     * @throws InvalidArgumentException
     * @throws Exception
     */
    private function updateProductRelations(ProductModel $product, WC_Product $wcProduct, string $productType): void
    {
        (new Product2CategoryController($this->db, $this->util))->pushData($product);
        $this->fixProductPriceForCustomerGroups($product, $wcProduct);

        (new ProductPrice($this->db, $this->util))
            ->savePrices($wcProduct, $product->getVat(), $productType, ...$product->getPrices());

        (new ProductSpecialPriceController($this->db, $this->util))->pushData($product, $wcProduct, $productType);
    }

    /**
     * @param ProductModel $product
     * @param WC_Product $wcProduct
     * @param $meta
     * @return void
     * @throws Exception
     */
    public function updateVariationCombinationChild(ProductModel $product, WC_Product $wcProduct, $meta): void
    {
        $productId = (int)$wcProduct->get_id();

        $productTitle         = \esc_html(\get_the_title($product->getMasterProductId()->getEndpoint()));
        $variation_post_title = \sprintf(\__('Variation #%s of %s', 'woocommerce'), $productId, $productTitle);
        \wp_update_post([
            'ID' => $productId,
            'post_title' => $variation_post_title,
        ]);
        \update_post_meta($productId, '_variation_description', $meta->getDescription());
        \update_post_meta($productId, '_mini_dec', $meta->getShortDescription());

        (new \JtlWooCommerceConnector\Controllers\Product\ProductStockLevelController($this->db, $this->util))
            ->pushDataChild($product);
    }

    /**
     * @param ProductModel $product
     * @param WC_Product $wcProduct
     * @return void
     * @throws Exception
     */
    private function updateProduct(ProductModel $product, $wcProduct): void
    {
        $productId = (int)$wcProduct->get_id();

        \update_post_meta($productId, '_visibility', 'visible');

        (new \JtlWooCommerceConnector\Controllers\Product\ProductStockLevelController($this->db, $this->util))
            ->pushDataParent($product);

        if ($product->getIsMasterProduct()) {
            $this->util->addMasterProductToSync($productId);
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
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function findTaxClassName(TaxRate ...$jtlTaxRates): ?string
    {
        $foundTaxClasses = $this->db->query(SqlHelper::getTaxClassByTaxRates(...$jtlTaxRates));
        return $foundTaxClasses[0]['taxClassName'] ?? null;
    }
}
