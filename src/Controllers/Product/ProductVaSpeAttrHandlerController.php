<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Controllers\Product;

use Jtl\Connector\Core\Exception\MustNotBeNullException;
use Jtl\Connector\Core\Exception\TranslatableAttributeException;
use Jtl\Connector\Core\Model\AbstractIdentity;
use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\Product as ProductModel;
use Jtl\Connector\Core\Model\ProductAttribute;
use Jtl\Connector\Core\Model\ProductI18n;
use Jtl\Connector\Core\Model\ProductSpecific;
use Jtl\Connector\Core\Model\ProductVariation;
use Jtl\Connector\Core\Model\ProductVariationValue;
use Jtl\Connector\Core\Model\TranslatableAttribute;
use Jtl\Connector\Core\Model\TranslatableAttribute as ProductAttrModel;
use Jtl\Connector\Core\Model\TranslatableAttributeI18n as ProductAttrI18nModel;
use Jtl\Connector\Core\Model\ProductSpecific as ProductSpecificModel;
use Jtl\Connector\Core\Model\ProductVariationValue as ProductVariationValueModel;
use JtlWooCommerceConnector\Controllers\AbstractBaseController;
use JtlWooCommerceConnector\Utilities\Config;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\SupportedPlugins as SupportedPluginsAlias;
use JtlWooCommerceConnector\Utilities\Util;
use WC_Product;
use WC_Product_Attribute;

class ProductVaSpeAttrHandlerController extends AbstractBaseController
{
    public const
        PRODUCT_TYPE_ATTR              = 'wc_product_type',
        DELIVERY_TIME_ATTR             = 'wc_dt_offset',
        DOWNLOADABLE_ATTR              = 'wc_downloadable',
        FACEBOOK_VISIBILITY_ATTR       = 'wc_fb_visibility',
        FACEBOOK_SYNC_STATUS_ATTR      = 'wc_fb_sync_status',
        PAYABLE_ATTR                   = 'wc_payable',
        NOSEARCH_ATTR                  = 'wc_nosearch',
        VISIBILITY                     = 'wc_visibility',
        VIRTUAL_ATTR                   = 'wc_virtual',
        PURCHASE_NOTE_ATTR             = 'wc_purchase_note',
        PURCHASE_ONLY_ONE_ATTR         = 'wc_sold_individually',
        NOTIFY_CUSTOMER_ON_OVERSELLING = 'wc_notify_customer_on_overselling',

        //GERMAN MARKET
        GM_DIGITAL_ATTR              = 'wc_gm_digital',
        GM_ALT_DELIVERY_NOTE_ATTR    = 'wc_gm_alt_delivery_note',
        GM_SUPPRESS_SHIPPPING_NOTICE = 'wc_gm_suppress_shipping_notice',

        //GERMANIZED
        GZD_IS_SERVICE = 'wc_gzd_is_service',
        GZD_MIN_AGE    = 'wc_minimum_age',

        //GERMANIZED PRO
        GZD_IS_FOOD = 'wc_gzd_is_food',

        //MISC
        JTL_CURRENT_PRODUCT_SPECIFICS = 'jtl_current_specifics',
        VALUE_TRUE                    = 'true',
        VALUE_FALSE                   = 'false';

    /** @var array<string, array<int, AbstractIdentity|ProductSpecific|ProductVariation|TranslatableAttribute>> $productData */
    private array $productData = [
        'productVariation'  => [],
        'productAttributes' => [],
        'productSpecifics'  => [],
    ];

    /** @var ProductVariationValue[] $values  */
    private array $values = [];

    /**
     * @param Db   $db
     * @param Util $util
     * @throws \Exception
     */
    public function __construct(Db $db, Util $util)
    {
        if (! \defined('WC_DELIMITER')) {
            \define('WC_DELIMITER', '|');
        }

        parent::__construct($db, $util);
    }

    /**
     * @param WC_Product   $product
     * @param ProductModel $model
     *
     * @return array<string, array<int, AbstractIdentity|ProductSpecific|ProductVariation|TranslatableAttribute>>
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function pullData(WC_Product $product, ProductModel $model): array
    {
        $globCurrentAttr          = $product->get_attributes();
        $isProductVariation       = $product instanceof \WC_Product_Variation;
        $isProductVariationParent = $product instanceof \WC_Product_Variable;
        $languageIso              = $this->util->getWooCommerceLanguage();

        if (! $isProductVariation) {
            /**
             * @var string               $slug
             * @var WC_Product_Attribute $attribute
             */
            foreach ($globCurrentAttr as $slug => $attribute) {
                $isVariation               = $attribute->get_variation();
                $taxonomyExistsCurrentAttr = \taxonomy_exists($slug);

                // <editor-fold defaultstate="collapsed" desc="Handling ATTR Pull">
                if (! $isVariation && ! $taxonomyExistsCurrentAttr) {
                    $this->productData['productAttributes'][] = ( new ProductAttrController($this->db, $this->util) )
                        ->pullData(
                            $product,
                            $attribute,
                            (string)$slug,
                            $languageIso
                        );
                }
                // </editor-fold>
                // <editor-fold defaultstate="collapsed" desc="Handling Specific Pull">
                if (! $isVariation && $taxonomyExistsCurrentAttr) {
                    $tmp = ( new ProductSpecificController($this->db, $this->util) )
                        ->pullData(
                            $model,
                            $product,
                            $attribute,
                            $slug
                        );
                    foreach ($tmp as $productSpecific) {
                        $this->productData['productSpecifics'][] = $productSpecific;
                    }
                }
                // </editor-fold>
                // <editor-fold defaultstate="collapsed" desc="Handling Variation Parent Pull">

                if ($isVariation && $isProductVariationParent) {
                    $tmp = ( new ProductVariationController($this->db, $this->util) )
                        ->pullDataParent(
                            $model,
                            $attribute,
                            $languageIso
                        );
                    if (\is_null($tmp)) {
                        continue;
                    }
                    $this->productData['productVariation'][] = $tmp;
                }

                // </editor-fold>
            }
        } else {
            // <editor-fold defaultstate="collapsed" desc="Handling Variation Child Pull">
            $tmp = ( new ProductVariationController($this->db, $this->util) )
                ->pullDataChild(
                    $product,
                    $model,
                    $languageIso
                );

            $this->productData['productVariation'] = $tmp;
            // </editor-fold>
        }

        // <editor-fold defaultstate="collapsed" desc="FUNC ATTR Pull">
        $this->handleCustomPropertyAttributes($product, $languageIso);
        $this->setProductFunctionAttributes($product, $languageIso);

        // </editor-fold>

        return $this->productData;
    }

    /**
     * @param WC_Product $product
     * @param string     $languageIso
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    private function handleCustomPropertyAttributes(WC_Product $product, string $languageIso = ''): void
    {
        if (! $product->is_purchasable()) {
            $isPurchasable = false;

            if ($product->has_child()) {
                $isPurchasable = true;

                foreach ($product->get_children() as $childId) {
                    $child = \wc_get_product($childId);
                    if ($child instanceof WC_Product) {
                        $isPurchasable &= $child->is_purchasable();
                    }
                }
            }

            if (! $isPurchasable) {
                $attrI18n = ( new ProductAttrI18nModel() )
                    ->setLanguageISO($languageIso)
                    ->setName(self::PAYABLE_ATTR)
                    ->setValue(self::VALUE_FALSE);

                $this->productData['productAttributes'][] = ( new ProductAttrModel() )
                    ->setId(new Identity(self::PAYABLE_ATTR))
                    ->setIsCustomProperty(false)
                    ->addI18n($attrI18n);
            }
        }
    }

    /**
     * @param WC_Product $product
     * @param string     $languageIso
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    private function setProductFunctionAttributes(
        WC_Product $product,
        string $languageIso = ''
    ): void {
        $functionAttributes = [
            $this->getDeliveryTimeFunctionAttribute(
                $product,
                $languageIso
            ),
            $this->getDownloadableFunctionAttribute(
                $product,
                $languageIso
            ),
            $this->getOnlyOneFunctionAttribute(
                $product,
                $languageIso
            ),
            $this->getPayableFunctionAttribute(
                $product,
                $languageIso
            ),
            $this->getVisibilityFunctionAttribute(
                $product,
                $languageIso
            ),
            $this->getVirtualFunctionAttribute(
                $product,
                $languageIso
            ),
            $this->getProductTypeFunctionAttribute(
                $product,
                $languageIso
            ),
            $this->getPurchaseNoteFunctionAttribute(
                $product,
                $languageIso
            ),
        ];

        if (SupportedPluginsAlias::isActive(SupportedPluginsAlias::PLUGIN_FB_FOR_WOO)) {
            // $functionAttributes[] = $this->getFacebookVisibilityFunctionAttribute($product);

            $functionAttributes[] = $this->getFacebookSyncStatusFunctionAttribute(
                $product,
                $languageIso
            );
        }
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)) {
            $gzdProduct = \wc_gzd_get_product($product);
            if ($gzdProduct instanceof \WC_GZD_Product && $product->meta_exists('_service')) {
                $functionAttributes[] = $this->getIsServiceFunctionAttribute(
                    $gzdProduct,
                    $languageIso
                );
            }
        }

        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)) {
            $gzdProduct = \wc_gzd_get_product($product);
            if ($gzdProduct instanceof \WC_GZD_Product && $product->meta_exists('_min_age')) {
                $functionAttributes[] = $this->getMinimumAgeAttribute(
                    $gzdProduct,
                    $languageIso
                );
            }
        }

        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_GERMAN_MARKET)) {
            $functionAttributes[] = $this->getDigitalFunctionAttribute(
                $product,
                $languageIso
            );

            $functionAttributes[] = $this->getSuppressShippingNoticeFunctionAttribute(
                $product,
                $languageIso
            );

            $functionAttributes[] = $this->getAltDeliveryNoteFunctionAttribute(
                $product,
                $languageIso
            );
        }

        foreach ($functionAttributes as $functionAttribute) {
            $this->productData['productAttributes'][] = $functionAttribute;
        }
    }

    // <editor-fold defaultstate="collapsed" desc="Filtered Methods">

    /**
     * @param WC_Product $product
     * @param string     $languageIso
     *
     * @return ProductAttrModel
     * @throws \InvalidArgumentException
     */
    private function getDeliveryTimeFunctionAttribute(WC_Product $product, string $languageIso = ''): ProductAttrModel
    {
        $i18n = ( new ProductAttrI18nModel() )
            ->setName(self::DELIVERY_TIME_ATTR)
            ->setValue("0")
            ->setLanguageISO($languageIso);

        $attribute = ( new ProductAttrModel() )
            ->setId(new Identity($product->get_id() . '_' . self::DELIVERY_TIME_ATTR))
            ->setIsCustomProperty(false)
            ->addI18n($i18n);

        return $attribute;
    }

    /**
     * @param WC_Product $product
     * @param string     $languageIso
     *
     * @return ProductAttrModel
     * @throws \InvalidArgumentException
     */
    private function getDownloadableFunctionAttribute(WC_Product $product, string $languageIso = ''): ProductAttrModel
    {
        $value = $product->is_downloadable() ? self::VALUE_TRUE : self::VALUE_FALSE;
        $i18n  = ( new ProductAttrI18nModel() )
            ->setName(self::DOWNLOADABLE_ATTR)
            ->setValue((string) $value)
            ->setLanguageISO($languageIso);

        return ( new ProductAttrModel() )
            ->setId(new Identity($product->get_id() . '_' . self::DOWNLOADABLE_ATTR))
            ->setIsCustomProperty(false)
            ->addI18n($i18n);
    }
    // </editor-fold>

    // <editor-fold defaultstate="collapsed" desc="GenerateData Methods">

    /**
     * @param WC_Product $product
     * @param string     $languageIso
     *
     * @return ProductAttrModel
     * @throws \InvalidArgumentException
     */
    private function getOnlyOneFunctionAttribute(WC_Product $product, string $languageIso = ''): ProductAttrModel
    {
        $value = $product->is_sold_individually() ? self::VALUE_TRUE : self::VALUE_FALSE;
        $i18n  = ( new ProductAttrI18nModel() )
            ->setName(self::PURCHASE_ONLY_ONE_ATTR)
            ->setValue((string) $value)
            ->setLanguageISO($languageIso);

        return ( new ProductAttrModel() )
            ->setId(new Identity($product->get_id() . '_' . self::PURCHASE_ONLY_ONE_ATTR))
            ->setIsCustomProperty(false)
            ->addI18n($i18n);
    }

    /**
     * @param WC_Product $product
     * @param string     $languageIso
     *
     * @return ProductAttrModel
     * @throws \InvalidArgumentException
     */
    private function getPayableFunctionAttribute(WC_Product $product, string $languageIso = ''): ProductAttrModel
    {
        $value = \strcmp((string)\get_post_status($product->get_id()), 'private') !== 0
            ? self::VALUE_TRUE
            : self::VALUE_FALSE;

        $i18n = ( new ProductAttrI18nModel() )
            ->setName(self::PAYABLE_ATTR)
            ->setValue((string) $value)
            ->setLanguageISO($languageIso);

        return ( new ProductAttrModel() )
            ->setId(new Identity($product->get_id() . '_' . self::PAYABLE_ATTR))
            ->setIsCustomProperty(false)
            ->addI18n($i18n);
    }
    // </editor-fold>

    // <editor-fold defaultstate="collapsed" desc="FuncAttr Methods">

    /**
     * @param WC_Product $product
     * @param string     $languageIso
     *
     * @return ProductAttrModel
     * @throws \InvalidArgumentException
     */
    private function getVisibilityFunctionAttribute(WC_Product $product, string $languageIso = ''): ProductAttrModel
    {
        $terms              = \get_the_terms($product->get_id(), 'product_visibility');
        $termNames          = \is_array($terms) ? \wp_list_pluck($terms, 'name') : [];
        $excludeFromSearch  = \in_array('exclude-from-search', $termNames, true);
        $excludeFromCatalog = \in_array('exclude-from-catalog', $termNames, true);

        $visibility = 'visible';
        if ($excludeFromSearch && $excludeFromCatalog) {
            $visibility = 'hidden';
        } elseif ($excludeFromSearch) {
            $visibility = 'catalog';
        } elseif ($excludeFromCatalog) {
            $visibility = 'search';
        }

        $i18n = ( new ProductAttrI18nModel() )
            ->setName(self::VISIBILITY)
            ->setValue($visibility)
            ->setLanguageISO($languageIso);

        return ( new ProductAttrModel() )
            ->setId(new Identity($product->get_id() . '_' . self::VISIBILITY))
            ->setIsCustomProperty(false)
            ->addI18n($i18n);
    }

    /**
     * @param WC_Product $product
     * @param string     $languageIso
     *
     * @return ProductAttrModel
     * @throws \InvalidArgumentException
     */
    private function getVirtualFunctionAttribute(WC_Product $product, string $languageIso = ''): ProductAttrModel
    {
        $value = $product->is_virtual() ? self::VALUE_TRUE : self::VALUE_FALSE;
        $i18n  = ( new ProductAttrI18nModel() )
            ->setName(self::VIRTUAL_ATTR)
            ->setValue((string) $value)
            ->setLanguageISO($languageIso);

        return ( new ProductAttrModel() )
            ->setId(new Identity($product->get_id() . '_' . self::VIRTUAL_ATTR))
            ->setIsCustomProperty(false)
            ->addI18n($i18n);
    }

    /**
     * @param WC_Product $product
     * @param string     $languageIso
     *
     * @return ProductAttrModel
     * @throws \InvalidArgumentException
     */
    private function getProductTypeFunctionAttribute(WC_Product $product, string $languageIso = ''): ProductAttrModel
    {
        $value = $product->get_type();

        $i18n = ( new ProductAttrI18nModel() )
            ->setName(self::PRODUCT_TYPE_ATTR)
            ->setValue((string) $value)
            ->setLanguageISO($languageIso);

        return ( new ProductAttrModel() )
            ->setId(new Identity($product->get_id() . '_' . self::PRODUCT_TYPE_ATTR))
            ->setIsCustomProperty(false)
            ->addI18n($i18n);
    }

    /**
     * @param WC_Product $product
     * @param string     $languageIso
     *
     * @return ProductAttrModel
     * @throws \InvalidArgumentException
     */
    private function getPurchaseNoteFunctionAttribute(WC_Product $product, string $languageIso = ''): ProductAttrModel
    {
        /** @var false|string $info */
        $info = \get_post_meta($product->get_id(), '_purchase_note', true);

        $i18n = ( new ProductAttrI18nModel() )
            ->setName(self::PURCHASE_NOTE_ATTR)
            ->setValue((string)$info)
            ->setLanguageISO($languageIso);

        return ( new ProductAttrModel() )
            ->setId(new Identity($product->get_id() . '_' . self::PURCHASE_NOTE_ATTR))
            ->setIsCustomProperty(false)
            ->addI18n($i18n);
    }

    /**
     * @param WC_Product $product
     * @param string     $languageIso
     *
     * @return ProductAttrModel
     * @throws \InvalidArgumentException
     */
    private function getFacebookSyncStatusFunctionAttribute(
        WC_Product $product,
        string $languageIso = ''
    ): ProductAttrModel {
        $value = self::VALUE_FALSE;
        /** @var array<int, string> $status */
        $status = \get_post_meta($product->get_id(), 'fb_sync_status');

        if (\count($status) > 0 && \strcmp($status[0], '1') === 0) {
            $value = self::VALUE_TRUE;
        }

        $i18n = ( new ProductAttrI18nModel() )
            ->setName(self::FACEBOOK_SYNC_STATUS_ATTR)
            ->setValue((string) $value)
            ->setLanguageISO($languageIso);

        return ( new ProductAttrModel() )
            ->setId(new Identity($product->get_id() . '_' . self::FACEBOOK_SYNC_STATUS_ATTR))
            ->setIsCustomProperty(false)
            ->addI18n($i18n);
    }

    /**
     * @param \WC_GZD_Product $product
     * @param string          $languageIso
     *
     * @return ProductAttrModel
     * @throws \InvalidArgumentException
     */
    private function getIsServiceFunctionAttribute(\WC_GZD_Product $product, string $languageIso = ''): ProductAttrModel
    {
        $value = $product->get_service() === true ? self::VALUE_TRUE : self::VALUE_FALSE;

        $i18n = ( new ProductAttrI18nModel() )
            ->setName(self::GZD_IS_SERVICE)
            ->setValue((string) $value)
            ->setLanguageISO($languageIso);

        return ( new ProductAttrModel() )
            ->setId(new Identity($product->get_wc_product()->get_id() . '_' . self::GZD_IS_SERVICE))
            ->setIsCustomProperty(false)
            ->addI18n($i18n);
    }

    /**
     * @param \WC_GZD_Product $product
     * @param string          $languageIso
     *
     * @return ProductAttrModel
     * @throws \InvalidArgumentException
     */
    private function getMinimumAgeAttribute(\WC_GZD_Product $product, string $languageIso = ''): ProductAttrModel
    {
        $value = $product->get_min_age();

        $i18n = ( new ProductAttrI18nModel() )
            ->setName(self::GZD_MIN_AGE)
            ->setValue($value)
            ->setLanguageISO($languageIso);

        return ( new ProductAttrModel() )
            ->setId(new Identity($product->get_wc_product()->get_id() . '_' . self::GZD_MIN_AGE))
            ->setIsCustomProperty(false)
            ->addI18n($i18n);
    }

    /**
     * @param WC_Product $product
     * @param string     $languageIso
     *
     * @return ProductAttrModel
     * @throws \InvalidArgumentException
     */
    private function getDigitalFunctionAttribute(WC_Product $product, string $languageIso = ''): ProductAttrModel
    {
        /** @var array<int, string> $digital */
        $digital = \get_post_meta($product->get_id(), '_digital');

        if (\count($digital) > 0 && \strcmp($digital[0], 'yes') === 0) {
            $value = self::VALUE_TRUE;
        } else {
            $value = self::VALUE_FALSE;
        }

        $i18n = ( new ProductAttrI18nModel() )
            ->setName(self::GM_DIGITAL_ATTR)
            ->setValue((string) $value)
            ->setLanguageISO($languageIso);

        return ( new ProductAttrModel() )
            ->setId(new Identity($product->get_id() . '_' . self::GM_DIGITAL_ATTR))
            ->setIsCustomProperty(false)
            ->addI18n($i18n);
    }

    /**
     * @param WC_Product $product
     * @param string     $languageIso
     *
     * @return ProductAttrModel
     * @throws \InvalidArgumentException
     */
    private function getSuppressShippingNoticeFunctionAttribute(
        WC_Product $product,
        string $languageIso = ''
    ): ProductAttrModel {
        /** @var string $value */
        $value = \get_post_meta($product->get_id(), '_suppress_shipping_notice', true);

        if (\strcmp($value, 'on') === 0) {
            $value = self::VALUE_TRUE;
        } else {
            $value = self::VALUE_FALSE;
        }

        $i18n = ( new ProductAttrI18nModel() )
            ->setName(self::GM_SUPPRESS_SHIPPPING_NOTICE)
            ->setValue((string) $value)
            ->setLanguageISO($languageIso);

        return ( new ProductAttrModel() )
            ->setId(new Identity($product->get_id() . '_' . self::GM_SUPPRESS_SHIPPPING_NOTICE))
            ->setIsCustomProperty(false)
            ->addI18n($i18n);
    }

    /**
     * @param WC_Product $product
     * @param string     $languageIso
     *
     * @return ProductAttrModel
     * @throws \InvalidArgumentException
     */
    private function getAltDeliveryNoteFunctionAttribute(
        WC_Product $product,
        string $languageIso = ''
    ): ProductAttrModel {
        /** @var string $info */
        $info = \get_post_meta($product->get_id(), '_alternative_shipping_information', true);

        $i18n = ( new ProductAttrI18nModel() )
            ->setName(self::GM_ALT_DELIVERY_NOTE_ATTR)
            ->setValue((string) $info)
            ->setLanguageISO($languageIso);

        return ( new ProductAttrModel() )
            ->setId(new Identity($product->get_id() . '_' . self::GM_ALT_DELIVERY_NOTE_ATTR))
            ->setIsCustomProperty(false)
            ->addI18n($i18n);
    }

    /**
     * @param ProductModel $product
     * @param WC_Product   $wcProduct
     * @param ProductI18n  $productI18n
     * @return void
     * @throws TranslatableAttributeException
     * @throws MustNotBeNullException
     * @throws \Psr\Log\InvalidArgumentException
     * @throws \TypeError
     * @throws \Exception
     */
    public function pushDataNew(ProductModel $product, WC_Product $wcProduct, ProductI18n $productI18n): void
    {
        //Identify Master = parent/simple
        $isMaster = $product->getMasterProductId()->getHost() === 0;

        $productId = $wcProduct->get_id();

        if ($isMaster) {
            $newWcProductAttributes = [];
            //Current Values
            $wcProductAttributes = $wcProduct->get_attributes();

            //Filtered
            $currentVariationsAndSpecifics = $this->getVariationAndSpecificAttributes(
                $wcProductAttributes,
                $product->getVariations()
            );

            $currentAttributes = $this->getVariationAttributes($wcProductAttributes, ...$product->getAttributes());

            //GENERATE DATA ARRAYS
            $jtlVariations = $this->generateVariationSpecificData(
                $productI18n->getLanguageIso(),
                $product->getVariations()
            );
            $jtlSpecifics  = $this->generateSpecificData($product->getSpecifics());

            //handleAttributes
            /** @var array<string, array<string, bool|int|string|null>> $productAttributes */
            $productAttributes = ( new ProductAttrController($this->db, $this->util) )->pushData(
                $productId,
                $product->getAttributes(),
                $currentVariationsAndSpecifics,
                $product
            );

            $this->mergeAttributes($newWcProductAttributes, $productAttributes);

            //Get updated WC Product after deleting removed custom attributes
            $wcProduct = \wc_get_product($product->getId()->getEndpoint());

            if (!$wcProduct instanceof \WC_Product) {
                throw new \InvalidArgumentException("WC Product not found.");
            }

            //Get updated attributes
            $wcProductAttributes = $wcProduct->get_attributes();

            // handleSpecifics
            $productSpecifics = ( new ProductSpecificController($this->db, $this->util) )->pushData(
                $productId,
                $wcProductAttributes,
                $jtlSpecifics,
                $product->getSpecifics(),
                $product->getAttributes()
            );

            $this->mergeAttributes($newWcProductAttributes, $productSpecifics);

            // handleVarSpecifics
            $productVariations = ( new ProductVariationController($this->db, $this->util) )->pushMasterData(
                (string)$productId,
                $jtlVariations,
                $currentAttributes
            );

            if (! \is_array($productVariations)) {
                $productVariations = [];
            }

            $this->mergeAttributes($newWcProductAttributes, $productVariations);

            $jtlNewProductSpecifics = \array_filter(\array_map(function (ProductSpecificModel $productSpecific) {
                return $productSpecific->getId()->getEndpoint();
            }, $product->getSpecifics()), function ($value) {
                return $value !== '';
            });

            /** @var array<int, array<int, string>> $jtlOldProductSpecifics */
            $jtlOldProductSpecifics = \get_post_meta($wcProduct->get_id(), self::JTL_CURRENT_PRODUCT_SPECIFICS);
            \update_post_meta(
                $wcProduct->get_id(),
                self::JTL_CURRENT_PRODUCT_SPECIFICS,
                $jtlNewProductSpecifics
            );

            if (! empty($jtlOldProductSpecifics)) {
                $jtlOldProductSpecifics = $jtlOldProductSpecifics[0];
                $removeSpecifics        = \array_diff($jtlOldProductSpecifics, $jtlNewProductSpecifics);

                foreach ($newWcProductAttributes as $index => $attribute) {
                    if (isset($attribute['id']) && \in_array($attribute['id'], $removeSpecifics)) {
                        unset($newWcProductAttributes[ $index ]);
                    }
                }
            }

            if (
                Config::get(
                    Config::OPTIONS_DELETE_UNKNOWN_ATTRIBUTES,
                    Config::JTLWCC_CONFIG_DEFAULTS[ Config::OPTIONS_DELETE_UNKNOWN_ATTRIBUTES ]
                )
            ) {
                $newWcProductAttributes = $this->removeUnknownAttributes(
                    $newWcProductAttributes,
                    $product->getAttributes()
                );
            }

            $old = \get_post_meta($productId, '_product_attributes', true);
            \update_post_meta($productId, '_product_attributes', $newWcProductAttributes, $old);
        } else {
            ( new ProductVariationController($this->db, $this->util) )->pushChildData(
                $productId,
                $product->getVariations()
            );
        }
        // remove the transient to renew the cache
        \delete_transient('wc_attribute_taxonomies');
    }

    /**
     * @param array<string, WC_Product_Attribute> $attributes
     * @param ProductVariation[]                  $variations
     *
     * @return array<string, array<string, bool|int|string|null>>
     */
    private function getVariationAndSpecificAttributes(array &$attributes = [], array $variations = []): array
    {
        $filteredAttributes = [];
        $jtlVariations      = [];
        foreach ($variations as $variation) {
            foreach ($variation->getI18ns() as $productVariationI18n) {
                if ($this->util->isWooCommerceLanguage($productVariationI18n->getLanguageISO())) {
                    $jtlVariations[] = $productVariationI18n->getName();
                }
            }
        }

        /**
         * @var string               $slug      The attributes unique slug.
         * @var WC_Product_Attribute $attribute The attribute.
         */
        foreach ($attributes as $slug => $attribute) {
            if ($attribute->get_variation()) {
                if ($attribute->get_taxonomy() === '' && \in_array($attribute->get_name(), $jtlVariations)) {
                    unset($attributes[ $slug ]);
                } else {
                    $filteredAttributes[ $slug ] = [
                        'id'           => $attribute->get_id(),
                        'name'         => $attribute->get_name(),
                        'value'        => \implode(' ' . \WC_DELIMITER . ' ', $attribute->get_options()),
                        'position'     => $attribute->get_position(),
                        'is_visible'   => $attribute->get_visible(),
                        'is_variation' => $attribute->get_variation(),
                        'is_taxonomy'  => $attribute->get_taxonomy(),
                    ];
                }
            } elseif (\taxonomy_exists($slug)) {
                $filteredAttributes[ $slug ] =
                    [
                        'id'           => $attribute->get_id(),
                        'name'         => $attribute->get_name(),
                        'value'        => '',
                        'position'     => $attribute->get_position(),
                        'is_visible'   => $attribute->get_visible(),
                        'is_variation' => $attribute->get_variation(),
                        'is_taxonomy'  => $attribute->get_taxonomy(),
                    ];
            }
        }

        return $filteredAttributes;
    }

    /**
     * @param array<string, WC_Product_Attribute> $curAttributes
     * @param ProductAttrModel                    ...$jtlAttributes
     * @return array<string, array<string, bool|int|string|null>>
     * @throws TranslatableAttributeException
     * @throws \InvalidArgumentException
     */
    private function getVariationAttributes(array $curAttributes, ProductAttrModel ...$jtlAttributes): array
    {
        $filteredAttributes = [];

        /**
         * @var string               $slug
         * @var WC_Product_Attribute $wcProductAttribute
         */
        foreach ($curAttributes as $slug => $wcProductAttribute) {
            if (! $wcProductAttribute->get_variation()) {
                $filteredAttributes[ $slug ] = [
                    'name'         => $wcProductAttribute->get_name(),
                    'value'        => $this->util->findAttributeValue($wcProductAttribute, ...$jtlAttributes),
                    'position'     => $wcProductAttribute->get_position(),
                    'is_visible'   => $wcProductAttribute->get_visible(),
                    'is_variation' => $wcProductAttribute->get_variation(),
                    'is_taxonomy'  => $wcProductAttribute->get_taxonomy(),
                ];
            }
        }

        return $filteredAttributes;
    }

    /**
     * @param string             $wawiIsoLanguage
     * @param ProductVariation[] $pushedVariations
     *
     * @return array<string, array<string, int|string>>
     */
    private function generateVariationSpecificData(string $wawiIsoLanguage, array $pushedVariations = []): array
    {
        $variationSpecificData = [];
        foreach ($pushedVariations as $variation) {
            foreach ($variation->getI18ns() as $variationI18n) {
                $taxonomyName = \wc_sanitize_taxonomy_name($variationI18n->getName());
                $customSort   = false;

                if ($wawiIsoLanguage !== $variationI18n->getLanguageISO()) {
                    continue;
                }

                $values = [];

                $this->values = $variation->getValues();

                foreach ($this->values as $vv) {
                    if ($vv->getSort() !== 0) {
                        $customSort = true;
                    }
                }

                if ($customSort) {
                    \usort($this->values, [
                        $this,
                        'sortI18nValues',
                    ]);
                }

                foreach ($this->values as $vv) {
                    foreach ($vv->getI18ns() as $valueI18n) {
                        if ($wawiIsoLanguage !== $valueI18n->getLanguageISO()) {
                            continue;
                        }

                        $values[] = $valueI18n->getName();
                    }
                }

                $variationSpecificData[ $taxonomyName ] = [
                    'name'         => $variationI18n->getName(),
                    'value'        => \implode(' ' . \WC_DELIMITER . ' ', $values),
                    'position'     => $variation->getSort(),
                    'is_visible'   => 0,
                    'is_variation' => 1,
                    'is_taxonomy'  => 0,
                ];
            }
        }

        if (! empty($variationSpecificData)) {
            \uasort($variationSpecificData, function ($a, $b) {
                return $a['position'] <=> $b['position'];
            });
        }

        return $variationSpecificData;
    }

    /**
     * @param ProductSpecific[] $pushedSpecifics
     *
     * @return array<int, array<string, array<int, int>>>
     */
    private function generateSpecificData(array $pushedSpecifics = []): array
    {
        $specificData = [];
        foreach ($pushedSpecifics as $specific) {
            $endpointId              = $specific->getId()->getEndpoint();
            $specificValueEndpointId = $specific->getSpecificValueId()->getEndpoint();
            if (empty($endpointId) || empty($specificValueEndpointId)) {
                continue;
            }
            $specificData[ (int) $endpointId ]['options'][] = (int) $specificValueEndpointId;
        }

        return $specificData;
    }

    // </editor-fold>

    //ALL

    /**
     * @param string $slug
     * @param string $value
     * @return Identity
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function getSpecificValueId(string $slug, string $value): Identity
    {
        /** @var array<int, array<string, int|string>> $val */
        $val = $this->db->query(SqlHelper::getSpecificValueId($slug, $value)) ?? [];

        if (\count($val) === 0) {
            $result = ( new Identity() );
        } else {
            $result = isset($val[0]['endpoint_id'])
                      && isset($val[0]['host_id'])
                ? ( new Identity() )->setEndpoint((string)$val[0]['endpoint_id'])->setHost((int)$val[0]['host_id'])
                : ( new Identity() )->setEndpoint((string)$val[0]['term_id']);
        }

        return $result;
    }

    /**
     * @param array<string, array<string, bool|int|string|null>> $newProductAttributes
     * @param array<string, array<string, bool|int|string|null>> $attributes
     * @param bool                                               $sort
     * @return void
     */
    private function mergeAttributes(array &$newProductAttributes, array $attributes, bool $sort = false): void
    {
        foreach ($attributes as $slug => $attr) {
            if (\array_key_exists($slug, $newProductAttributes)) {
                if ($attr['name'] === $slug && $attr['name'] === $newProductAttributes[ $slug ]['name']) {
                    $isVariation = $attr['is_variation'] || $newProductAttributes[ $slug ]['is_variation'];
                    $attrValues  = \explode(' ' . \WC_DELIMITER . ' ', (string)$attr['value']);
                    $oldValues   = \explode(' ' . \WC_DELIMITER . ' ', (string)$newProductAttributes[ $slug ]['value']);

                    $values = \array_merge($attrValues, $oldValues);

                    $values                                        = \array_map(
                        "unserialize",
                        \array_unique(\array_map("serialize", $values))
                    );
                    $valuesString                                  = \implode(' ' . \WC_DELIMITER . ' ', $values);
                    $newProductAttributes[ $slug ]['value']        = $valuesString;
                    $newProductAttributes[ $slug ]['is_variation'] = $isVariation;

                    if ($sort) {
                        $newProductAttributes[ $slug ]['position'] = $attributes[ $slug ]['position'];
                    }
                }
            } else {
                $newProductAttributes[ $slug ] = $attr;
            }
        }
    }

    //VARIATIONSPECIFIC && SPECIFIC

    /**
     * @param array<string, array<string, bool|int|string|null>> $newWcProductAttributes
     * @param ProductAttribute[]                                 $jtlAttributes
     *
     * @return array<string, array<string, bool|int|string|null>>
     */
    protected function removeUnknownAttributes(array $newWcProductAttributes, array $jtlAttributes): array
    {
        $defaultLanguage = $this->util->getWooCommerceLanguage();
        foreach ($newWcProductAttributes as $i => $wcAttribute) {
            if (! isset($wcAttribute['id']) && $wcAttribute['is_taxonomy'] === '') {
                /** @var string $wcAttributeName */
                $wcAttributeName = $wcAttribute['name'];
                $attributeExists = ! \is_null(
                    $this->util->findAttributeI18nByName($wcAttributeName, $defaultLanguage, ...$jtlAttributes)
                );
                if ($attributeExists === false) {
                    unset($newWcProductAttributes[ $i ]);
                }
            }
        }

        return $newWcProductAttributes;
    }

    /**
     * @param ProductVariationValueModel $a
     * @param ProductVariationValueModel $b
     * @return int
     */
    private function sortI18nValues(
        ProductVariationValueModel $a,
        ProductVariationValueModel $b
    ): int {
        return ( $a->getSort() - $b->getSort() );
    }
}
