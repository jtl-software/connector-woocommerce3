<?php

namespace JtlWooCommerceConnector\Controllers\Product;

use InvalidArgumentException;
use Jtl\Connector\Core\Model\CustomerGroup as CustomerGroupModel;
use Jtl\Connector\Core\Model\CustomerGroupI18n as CustomerGroupI18nModel;
use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\Product as ProductModel;
use Jtl\Connector\Core\Model\ProductSpecialPrice as ProductSpecialPriceModel;
use Jtl\Connector\Core\Model\ProductSpecialPriceItem as ProductSpecialPriceItemModel;
use JtlWooCommerceConnector\Controllers\AbstractBaseController;
use JtlWooCommerceConnector\Controllers\GlobalData\CustomerGroupController;
use JtlWooCommerceConnector\Controllers\ProductController;
use JtlWooCommerceConnector\Utilities\Config;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;
use WC_Product;

class ProductSpecialPriceController extends AbstractBaseController
{
    /**
     * @param WC_Product $product
     * @param ProductModel $model
     * @return array
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public function pullData(WC_Product $product, ProductModel $model): array
    {
        $specialPrices   = [];
        $groupController = (new CustomerGroupController($this->db, $this->util));

        if (!SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)) {
            $salePrice = $product->get_sale_price();

            if (!empty($salePrice)) {
                $specialPrices[] = (new ProductSpecialPriceModel())
                    ->setId(new Identity($product->get_id()))
                    ->setIsActive($product->is_on_sale())
                    ->setConsiderDateLimit(!\is_null($product->get_date_on_sale_to()))
                    ->setActiveFromDate($product->get_date_on_sale_from())
                    ->setActiveUntilDate($product->get_date_on_sale_to())
                    ->addItem((new ProductSpecialPriceItemModel())
                        ->setCustomerGroupId(new Identity(CustomerGroupController::DEFAULT_GROUP))
                        ->setPriceNet((float)$this->getPriceNet($product->get_sale_price(), $product)));
            }
        } else {
            $customerGroups = $groupController->pull();

            /** @var CustomerGroupModel $customerGroup */
            foreach ($customerGroups as $cKey => $customerGroup) {
                $items = [];

                $customerGroupEndpointId = $customerGroup->getId()->getEndpoint();

                if (
                    $customerGroupEndpointId === CustomerGroupController::DEFAULT_GROUP
                    && !SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)
                    || (
                        $customerGroupEndpointId === CustomerGroupController::DEFAULT_GROUP
                        && SupportedPlugins::comparePluginVersion(
                            SupportedPlugins::PLUGIN_B2B_MARKET,
                            '<=',
                            '1.0.3'
                        )
                    )
                ) {
                    $salePrice = $product->get_sale_price();

                    if (!empty($salePrice)) {
                        $items [] = (new ProductSpecialPriceItemModel())
                            ->setCustomerGroupId(new Identity(CustomerGroupController::DEFAULT_GROUP))
                            ->setPriceNet((float)$this->getPriceNet($salePrice, $product));
                    }
                } else {
                    $groupSlug           = $groupController->getSlugById($customerGroupEndpointId);
                    $defaultSpecialPrice = false;
                    $salePrice           = $product->get_sale_price();

                    if (!empty($salePrice)) {
                        $defaultSpecialPrice = true;
                    }

                    if ($model->getIsMasterProduct()) {
                        $productIdForMeta = $product->get_id();
                        $priceKeyForMeta  = \sprintf('_jtlwcc_bm_%s_sale_price', $groupSlug);
                    } else {
                        $productIdForMeta = $product->get_parent_id();
                        $priceKeyForMeta  = \sprintf(
                            '_jtlwcc_bm_%s_%s_sale_price',
                            $groupSlug,
                            $product->get_id()
                        );
                    }
                    $specialPrice = \get_post_meta($productIdForMeta, $priceKeyForMeta, true);

                    if (!empty($specialPrice)) {
                        $specialPrice = $this->getPriceNet($specialPrice, $product);
                    } elseif ($defaultSpecialPrice) {
                        $specialPrice = $this->getPriceNet($salePrice, $product);
                    } else {
                        continue;
                    }

                    $items [] = (new ProductSpecialPriceItemModel())
                        ->setCustomerGroupId($customerGroup->getId())
                        ->setPriceNet((float)$specialPrice);
                }

                $specialPrices[] = (new ProductSpecialPriceModel())
                    ->setId(new Identity($product->get_id()))
                    ->setIsActive($product->is_on_sale())
                    ->setConsiderDateLimit(!\is_null($product->get_date_on_sale_to()))
                    ->setActiveFromDate($product->get_date_on_sale_from())
                    ->setActiveUntilDate($product->get_date_on_sale_to())
                    ->setItems(...$items);
            }
        }

        return $specialPrices;
    }

    /**
     * @param $priceNet
     * @param WC_Product $product
     * @return float
     */
    protected function getPriceNet($priceNet, WC_Product $product): float
    {
        $taxRate = $this->util->getTaxRateByTaxClass($product->get_tax_class());
        $pd      = $this->util->getPriceDecimals();

        if (\wc_prices_include_tax() && $taxRate != 0) {
            $netPrice = \round(\round(((float)$priceNet) / ($taxRate + 100), $pd) * 100);
        } else {
            $netPrice = \round((float)$priceNet, $pd);
        }

        return $netPrice;
    }

    /**
     * @param ProductModel $product
     * @param WC_Product $wcProduct
     * @param string $productType
     * @return void
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public function pushData(ProductModel $product, WC_Product $wcProduct, string $productType): void
    {
        $pd = Util::getPriceDecimals();

        $productId       = $product->getId()->getEndpoint();
        $masterProductId = $product->getMasterProductId();
        $specialPrices   = $product->getSpecialPrices();

        if (
            SupportedPlugins::comparePluginVersion(
                SupportedPlugins::PLUGIN_B2B_MARKET,
                '>',
                '1.0.3'
            )
        ) {
            foreach ($specialPrices as $specialPrice) {
                foreach ($specialPrice->getItems() as $item) {
                    $endpoint = $item->getCustomerGroupId()->getEndpoint();
                    if ($endpoint === Config::get('jtlconnector_default_customer_group')) {
                        $specialPrice->addItem((new ProductSpecialPriceItemModel())
                            ->setCustomerGroupId(new Identity(CustomerGroupController::DEFAULT_GROUP))
                            ->setPriceNet((float)$item->getPriceNet()));
                    }
                }
            }
        }

        if (\count($specialPrices) > 0) {
            if (
                SupportedPlugins::comparePluginVersion(
                    SupportedPlugins::PLUGIN_B2B_MARKET,
                    '>',
                    '1.0.3'
                )
            ) {
                foreach ($specialPrices as $specialPrice) {
                    foreach ($specialPrice->getItems() as $item) {
                        $endpoint = $item->getCustomerGroupId()->getEndpoint();
                        if ($endpoint === Config::get('jtlconnector_default_customer_group')) {
                            $specialPrice->addItem((new ProductSpecialPriceItemModel())
                                ->setCustomerGroupId(new Identity(CustomerGroupController::DEFAULT_GROUP))
                                ->setPriceNet((float)$item->getPriceNet()));
                        }
                    }
                }
            }

            foreach ($specialPrices as $specialPrice) {
                foreach ($specialPrice->getItems() as $item) {
                    $endpoint     = $item->getCustomerGroupId()->getEndpoint();
                    $current_time = \time();

                    if ($specialPrice->getConsiderDateLimit()) {
                        $dateTo   = \is_null($end = $specialPrice->getActiveUntilDate()) ? null : $end->getTimestamp();
                        $dateFrom = \is_null($start = $specialPrice->getActiveFromDate())
                            ? null
                            : $start->getTimestamp();
                    } else {
                        $dateTo   = '';
                        $dateFrom = \is_null($start = $specialPrice->getActiveFromDate()) ? '' : $start->getTimestamp();
                    }

                    if (\wc_prices_include_tax()) {
                        $salePrice = \round($item->getPriceNet() * (1 + $product->getVat() / 100), $pd);
                    } else {
                        $salePrice = \round($item->getPriceNet(), $pd);
                    }

                    if (!$this->util->isValidCustomerGroup((string)$endpoint)) {
                        continue;
                    }

                    if ((CustomerGroupController::DEFAULT_GROUP === $endpoint)) {
                        $salePriceMetaKey      = '_sale_price';
                        $salePriceDatesToKey   = '_sale_price_dates_to';
                        $salePriceDatesFromKey = '_sale_price_dates_from';
                        $priceMetaKey          = '_price';
                        $regularPriceKey       = '_regular_price';

                        \update_post_meta(
                            $productId,
                            $salePriceMetaKey,
                            \wc_format_decimal($salePrice, $pd),
                            \get_post_meta($productId, $salePriceMetaKey, true)
                        );

                        \update_post_meta(
                            $productId,
                            $salePriceDatesToKey,
                            $dateTo,
                            \get_post_meta($productId, $salePriceDatesToKey, true)
                        );

                        \update_post_meta(
                            $productId,
                            $salePriceDatesFromKey,
                            $dateFrom,
                            \get_post_meta($productId, $salePriceDatesFromKey, true)
                        );

                        if ('' !== $salePrice && '' == $dateTo && '' == $dateFrom) {
                            \update_post_meta(
                                $productId,
                                $priceMetaKey,
                                \wc_format_decimal($salePrice, $pd),
                                \get_post_meta($productId, $priceMetaKey, true)
                            );
                        } elseif (
                            '' !== $salePrice
                            && $dateFrom <= $current_time
                            && ($current_time <= $dateTo || $dateTo == '')
                        ) {
                            \update_post_meta(
                                $productId,
                                $priceMetaKey,
                                \wc_format_decimal($salePrice, $pd),
                                \get_post_meta($productId, $priceMetaKey, true)
                            );
                        } else {
                            $regularPrice = (float)\get_post_meta($productId, $regularPriceKey, true);
                            \update_post_meta(
                                $productId,
                                $priceMetaKey,
                                \wc_format_decimal($regularPrice, $pd),
                                \get_post_meta($productId, $priceMetaKey, true)
                            );
                        }
                    } elseif (\is_int((int)$endpoint)) {
                        if ($productType !== ProductController::TYPE_PARENT) {
                            $customerGroup       = \get_post($endpoint);
                            $priceMetaKey        = \sprintf(
                                'bm_%s_price',
                                $customerGroup->post_name
                            );
                            $regularPriceMetaKey = \sprintf(
                                '_jtlwcc_bm_%s_regular_price',
                                $customerGroup->post_name
                            );

                            $metaKeyForCustomerGroupPriceType = $priceMetaKey . '_type';
                            \update_post_meta(
                                $productId,
                                $metaKeyForCustomerGroupPriceType,
                                'fix',
                                \get_post_meta($productId, $metaKeyForCustomerGroupPriceType, true)
                            );

                            if ($productType === ProductController::TYPE_CHILD) {
                                $COPpriceMetaKey          = \sprintf(
                                    'bm_%s_%s_price',
                                    $customerGroup->post_name,
                                    $productId
                                );
                                $COPpriceTypeMetaKey      = \sprintf(
                                    'bm_%s_%s_price_type',
                                    $customerGroup->post_name,
                                    $productId
                                );
                                $COPsalePriceMetaKey      = \sprintf(
                                    '_jtlwcc_bm_%s_%s_sale_price',
                                    $customerGroup->post_name,
                                    $productId
                                );
                                $COPsalePriceDatesToKey   = \sprintf(
                                    '_jtlwcc_bm_%s_%s_sale_price_dates_to',
                                    $customerGroup->post_name,
                                    $productId
                                );
                                $COPsalePriceDatesFromKey = \sprintf(
                                    '_jtlwcc_bm_%s_%s_sale_price_dates_from',
                                    $customerGroup->post_name,
                                    $productId
                                );
                            } else {
                                $salePriceMetaKey      = \sprintf(
                                    '_jtlwcc_bm_%s_sale_price',
                                    $customerGroup->post_name
                                );
                                $salePriceDatesToKey   = \sprintf(
                                    '_jtlwcc_bm_%s_sale_price_dates_to',
                                    $customerGroup->post_name
                                );
                                $salePriceDatesFromKey = \sprintf(
                                    '_jtlwcc_bm_%s_sale_price_dates_from',
                                    $customerGroup->post_name
                                );
                            }

                            if ('' !== $salePrice && '' == $dateTo && '' == $dateFrom) {
                                \update_post_meta(
                                    $productId,
                                    $priceMetaKey,
                                    \wc_format_decimal($salePrice, $pd),
                                    \get_post_meta($productId, $priceMetaKey, true)
                                );

                                if (
                                    $productType === ProductController::TYPE_CHILD
                                    && isset($COPpriceMetaKey)
                                    && isset($COPpriceTypeMetaKey)
                                    && isset($COPsalePriceMetaKey)
                                ) {
                                    //Update price on parent
                                    \update_post_meta(
                                        $masterProductId->getEndpoint(),
                                        $COPpriceMetaKey,
                                        \wc_format_decimal($salePrice, $pd),
                                        \get_post_meta(
                                            $masterProductId->getEndpoint(),
                                            $COPpriceMetaKey,
                                            true
                                        )
                                    );
                                    //Update price type on parent
                                    \update_post_meta(
                                        $masterProductId->getEndpoint(),
                                        $COPpriceTypeMetaKey,
                                        'fix',
                                        \get_post_meta(
                                            $masterProductId->getEndpoint(),
                                            $COPpriceTypeMetaKey,
                                            true
                                        )
                                    );
                                    //Update sale_price on parent
                                    \update_post_meta(
                                        $masterProductId->getEndpoint(),
                                        $COPsalePriceMetaKey,
                                        \wc_format_decimal($salePrice, $pd),
                                        \get_post_meta(
                                            $masterProductId->getEndpoint(),
                                            $COPsalePriceMetaKey,
                                            true
                                        )
                                    );
                                } else {
                                    if (isset($salePriceMetaKey)) {
                                        //Update sale_price on product
                                        \update_post_meta(
                                            $productId,
                                            $salePriceMetaKey,
                                            \wc_format_decimal($salePrice, $pd),
                                            \get_post_meta(
                                                $productId,
                                                $salePriceMetaKey,
                                                true
                                            )
                                        );
                                    }
                                }
                            } elseif (
                                '' !== $salePrice
                                && $dateFrom <= $current_time
                                && ($current_time <= $dateTo || $dateTo == '')
                            ) {
                                \update_post_meta(
                                    $productId,
                                    $priceMetaKey,
                                    \wc_format_decimal($salePrice, $pd),
                                    \get_post_meta($productId, $priceMetaKey, true)
                                );

                                if (
                                    $productType === ProductController::TYPE_CHILD
                                    && isset($COPpriceMetaKey)
                                    && isset($COPpriceTypeMetaKey)
                                    && isset($COPsalePriceMetaKey)
                                    && isset($COPsalePriceDatesToKey)
                                    && isset($COPsalePriceDatesFromKey)
                                ) {
                                    //Update price on parent
                                    \update_post_meta(
                                        $masterProductId->getEndpoint(),
                                        $COPpriceMetaKey,
                                        \wc_format_decimal($salePrice, $pd),
                                        \get_post_meta(
                                            $masterProductId->getEndpoint(),
                                            $COPpriceMetaKey,
                                            true
                                        )
                                    );
                                    //Update price type on parent
                                    \update_post_meta(
                                        $masterProductId->getEndpoint(),
                                        $COPpriceTypeMetaKey,
                                        'fix',
                                        \get_post_meta(
                                            $masterProductId->getEndpoint(),
                                            $COPpriceTypeMetaKey,
                                            true
                                        )
                                    );
                                    //Update sale_price on parent
                                    \update_post_meta(
                                        $masterProductId->getEndpoint(),
                                        $COPsalePriceMetaKey,
                                        \wc_format_decimal($salePrice, $pd),
                                        \get_post_meta(
                                            $masterProductId->getEndpoint(),
                                            $COPsalePriceMetaKey,
                                            true
                                        )
                                    );
                                    //Update sale_price_date_to on parent
                                    \update_post_meta(
                                        $masterProductId->getEndpoint(),
                                        $COPsalePriceDatesToKey,
                                        $dateTo,
                                        \get_post_meta(
                                            $masterProductId->getEndpoint(),
                                            $COPsalePriceDatesToKey,
                                            true
                                        )
                                    );
                                    //Update sale_price_date_from on parent
                                    \update_post_meta(
                                        $masterProductId->getEndpoint(),
                                        $COPsalePriceDatesFromKey,
                                        $dateFrom,
                                        \get_post_meta(
                                            $masterProductId->getEndpoint(),
                                            $COPsalePriceDatesFromKey,
                                            true
                                        )
                                    );
                                } else {
                                    if (
                                        isset($salePriceMetaKey)
                                        && isset($salePriceDatesToKey)
                                        && isset($salePriceDatesFromKey)
                                    ) {
                                        //Update sale_price on product
                                        \update_post_meta(
                                            $productId,
                                            $salePriceMetaKey,
                                            \wc_format_decimal($salePrice, $pd),
                                            \get_post_meta(
                                                $productId,
                                                $salePriceMetaKey,
                                                true
                                            )
                                        );
                                        //Update sale_price_date_to on product
                                        \update_post_meta(
                                            $productId,
                                            $salePriceDatesToKey,
                                            $dateTo,
                                            \get_post_meta(
                                                $productId,
                                                $salePriceDatesToKey,
                                                true
                                            )
                                        );
                                        //Update sale_price_date_from on product
                                        \update_post_meta(
                                            $productId,
                                            $salePriceDatesFromKey,
                                            $dateFrom,
                                            \get_post_meta(
                                                $productId,
                                                $salePriceDatesFromKey,
                                                true
                                            )
                                        );
                                    }
                                }
                            } else {
                                $regularPrice = (float)\get_post_meta($productId, $regularPriceMetaKey, true);
                                \update_post_meta(
                                    $productId,
                                    $priceMetaKey,
                                    \wc_format_decimal($regularPrice, $pd),
                                    \get_post_meta($productId, $priceMetaKey, true)
                                );
                            }
                        }
                    } else {
                        continue;
                    }
                }
            }
        } else {
            $customerGroups = (new CustomerGroupController($this->db, $this->util))->pull();

            if (
                SupportedPlugins::comparePluginVersion(
                    SupportedPlugins::PLUGIN_B2B_MARKET,
                    '>',
                    '1.0.3'
                )
            ) {
                foreach ($customerGroups as $customerGroup) {
                    $endpoint = $customerGroup->getId()->getEndpoint();
                    if ($endpoint === Config::get('jtlconnector_default_customer_group')) {
                        $customerGroups[] = (new CustomerGroupModel())
                            ->setId(new Identity(CustomerGroupController::DEFAULT_GROUP))
                            ->addI18n((new CustomerGroupI18nModel())
                                ->setName('Customer'));
                    }
                }
            }

            /** @var CustomerGroupModel $customerGroup */
            foreach ($customerGroups as $groupKey => $customerGroup) {
                $customerGroupId = $customerGroup->getId()->getEndpoint();
                $post            = \get_post($customerGroupId);
                if ($post instanceof \WP_Post && \is_int((int)$customerGroupId)) {
                    //$post = \get_post($customerGroupId);
                    $priceMetaKey        = \sprintf(
                        'bm_%s_price',
                        $post->post_name
                    );
                    $regularPriceMetaKey = \sprintf(
                        '_jtlwcc_bm_%s_regular_price',
                        $post->post_name
                    );

                    $metaKeyForCustomerGroupPriceType = $priceMetaKey . '_type';
                    \update_post_meta(
                        $productId,
                        $metaKeyForCustomerGroupPriceType,
                        'fix',
                        \get_post_meta($productId, $metaKeyForCustomerGroupPriceType, true)
                    );

                    if ($productType === ProductController::TYPE_CHILD) {
                        $COPpriceMetaKey          = \sprintf(
                            'bm_%s_%s_price',
                            $post->post_name,
                            $productId
                        );
                        $COPpriceTypeMetaKey      = \sprintf(
                            'bm_%s_%s_price_type',
                            $post->post_name,
                            $productId
                        );
                        $COPsalePriceMetaKey      = \sprintf(
                            '_jtlwcc_bm_%s_%s_sale_price',
                            $post->post_name,
                            $productId
                        );
                        $COPsalePriceDatesToKey   = \sprintf(
                            '_jtlwcc_bm_%s_%s_sale_price_dates_to',
                            $post->post_name,
                            $productId
                        );
                        $COPsalePriceDatesFromKey = \sprintf(
                            '_jtlwcc_bm_%s_%s_sale_price_dates_from',
                            $post->post_name,
                            $productId
                        );

                        \delete_post_meta(
                            $masterProductId->getEndpoint(),
                            $COPsalePriceMetaKey,
                            \get_post_meta($masterProductId->getEndpoint(), $COPsalePriceMetaKey, true)
                        );
                        \delete_post_meta(
                            $masterProductId->getEndpoint(),
                            $COPsalePriceDatesToKey,
                            \get_post_meta($masterProductId->getEndpoint(), $COPsalePriceDatesToKey, true)
                        );
                        \delete_post_meta(
                            $masterProductId->getEndpoint(),
                            $COPsalePriceDatesFromKey,
                            \get_post_meta($masterProductId->getEndpoint(), $COPsalePriceDatesFromKey, true)
                        );
                    } else {
                        $salePriceMetaKey      = \sprintf(
                            '_jtlwcc_bm_%s_sale_price',
                            $post->post_name
                        );
                        $salePriceDatesToKey   = \sprintf(
                            '_jtlwcc_bm_%s_sale_price_dates_to',
                            $post->post_name
                        );
                        $salePriceDatesFromKey = \sprintf(
                            '_jtlwcc_bm_%s_sale_price_dates_from',
                            $post->post_name
                        );
                        \delete_post_meta(
                            $productId,
                            $salePriceMetaKey,
                            \get_post_meta($productId, $salePriceMetaKey, true)
                        );
                        \delete_post_meta(
                            $productId,
                            $salePriceDatesToKey,
                            \get_post_meta($productId, $salePriceDatesToKey, true)
                        );
                        \delete_post_meta(
                            $productId,
                            $salePriceDatesFromKey,
                            \get_post_meta($productId, $salePriceDatesFromKey, true)
                        );
                    }

                    $regularPrice = (float)\get_post_meta($productId, $regularPriceMetaKey, true);
                } elseif (\is_null($post) && $customerGroupId === CustomerGroupController::DEFAULT_GROUP) {
                    $salePriceMetaKey      = '_sale_price';
                    $salePriceDatesToKey   = '_sale_price_dates_to';
                    $salePriceDatesFromKey = '_sale_price_dates_from';
                    $priceMetaKey          = '_price';
                    $regularPriceKey       = '_regular_price';
                    $regularPrice          = (float)\get_post_meta($productId, $regularPriceKey, true);

                    \update_post_meta(
                        $productId,
                        $salePriceMetaKey,
                        '',
                        \get_post_meta($productId, $salePriceMetaKey, true)
                    );
                    \update_post_meta(
                        $productId,
                        $salePriceDatesToKey,
                        '',
                        \get_post_meta($productId, $salePriceDatesToKey, true)
                    );
                    \update_post_meta(
                        $productId,
                        $salePriceDatesFromKey,
                        '',
                        \get_post_meta($productId, $salePriceDatesFromKey, true)
                    );
                } else {
                    continue;
                }

                \update_post_meta(
                    $productId,
                    $priceMetaKey,
                    \wc_format_decimal($regularPrice, $pd),
                    \get_post_meta($productId, $priceMetaKey, true)
                );

                if (
                    $productType === ProductController::TYPE_CHILD
                    & isset($COPpriceTypeMetaKey)
                    && isset($COPpriceMetaKey)
                ) {
                    \update_post_meta(
                        $masterProductId->getEndpoint(),
                        $COPpriceMetaKey,
                        \wc_format_decimal($regularPrice, $pd),
                        \get_post_meta($masterProductId->getEndpoint(), $COPpriceMetaKey, true)
                    );
                    \update_post_meta(
                        $masterProductId->getEndpoint(),
                        $COPpriceTypeMetaKey,
                        'fix',
                        \get_post_meta($masterProductId->getEndpoint(), $COPpriceTypeMetaKey, true)
                    );
                }
            }
        }
    }
}
