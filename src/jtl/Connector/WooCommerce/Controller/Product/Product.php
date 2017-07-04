<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller\Product;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\WooCommerce\Controller\BaseController;
use jtl\Connector\WooCommerce\Controller\Traits\DeleteTrait;
use jtl\Connector\WooCommerce\Controller\Traits\PullTrait;
use jtl\Connector\WooCommerce\Controller\Traits\PushTrait;
use jtl\Connector\WooCommerce\Controller\Traits\StatsTrait;
use jtl\Connector\WooCommerce\Logger\WpErrorLogger;
use jtl\Connector\WooCommerce\Utility\Germanized;
use jtl\Connector\WooCommerce\Utility\SQL;
use jtl\Connector\WooCommerce\Utility\Util;

class Product extends BaseController
{
    use PullTrait, PushTrait, DeleteTrait, StatsTrait;

    private static $idCache = [];

    public function pullData($limit)
    {
        $products = [];

        $ids = $this->database->queryList(SQL::productPull($limit));

        foreach ($ids as $id) {
            $product = \wc_get_product($id);

            if (!$product instanceof \WC_Product) {
                continue;
            }

            $postDate = $product->get_date_created();
            $modDate = $product->get_date_modified();

            $result = (new ProductModel())
                ->setId(new Identity($product->get_id()))
                ->setIsMasterProduct($product->is_type('variable'))
                ->setSku($product->get_sku())
                ->setVat(Util::getInstance()->getTaxRateByTaxClassAndShopLocation($product->get_tax_class()))
                ->setSort($product->get_menu_order())
                ->setIsTopProduct($product->is_featured())
                ->setProductTypeId(new Identity($product->get_type()))
                ->setKeywords(($tags = \wc_get_product_tag_list($product->get_id())) ? $tags : null)
                ->setCreationDate($postDate)
                ->setModified($modDate)
                ->setAvailableFrom($postDate <= $modDate ? null : $postDate)
                ->setHeight((double)$product->get_height())
                ->setLength((double)$product->get_length())
                ->setWidth((double)$product->get_width())
                ->setShippingWeight((double)$product->get_weight())
                ->setConsiderStock($product->managing_stock())
                ->setPermitNegativeStock($product->backorders_allowed())
                ->setShippingClassId(new Identity($product->get_shipping_class_id()));

            if ($product->get_parent_id() !== 0) {
                $result->setMasterProductId(new Identity($product->get_parent_id()));
            }

            $result
                ->setI18ns(ProductI18n::getInstance()->pullData($product, $result))
                ->setPrices(ProductPrice::getInstance()->pullData($product, $result))
                ->setSpecialPrices(ProductSpecialPrice::getInstance()->pullData($product, $result))
                ->setCategories(Product2Category::getInstance()->pullData($product, $result))
                ->setAttributes(ProductAttr::getInstance()->pullData($product, $result))
                ->setVariations(ProductVariation::getInstance()->pullData($product, $result));

            if ($product->managing_stock()) {
                $result->setStockLevel(ProductStockLevel::getInstance()->pullData($product, $result));
            }

            if (Germanized::getInstance()->isActive()) {
                $this->setGermanizedAttributes($result, $product);
            }

            $products[] = $result;
        }

        return $products;
    }

    protected function pushData(ProductModel $product, $model)
    {
        $masterProductId = $product->getMasterProductId()->getEndpoint();

        if (empty($masterProductId) && isset(self::$idCache[$product->getMasterProductId()->getHost()])) {
            $masterProductId = self::$idCache[$product->getMasterProductId()->getHost()];
            $product->getMasterProductId()->setEndpoint($masterProductId);
        }

        $productId = $product->getId()->getEndpoint();
        $endpoint = $this->mapper->toEndpoint($product);

        if (!empty($productId)) {
            $endpoint['ID'] = (int)$productId;
        }

        $result = \wp_insert_post($endpoint, true);

        if ($result instanceof \WP_Error) {
            WpErrorLogger::getInstance()->logError($result);

            return $product;
        }

        $product->getId()->setEndpoint($result);

        $this->onProductInserted($product, $endpoint);

        if (Germanized::getInstance()->isActive()) {
            $this->updateGermanizedAttributes($product, $endpoint);
        }

        return $product;
    }

    protected function onProductInserted(ProductModel &$product, array &$endpoint)
    {
        $this->updateProductMeta($product, $endpoint);

        $this->updateProductRelations($product, $endpoint);

        if ($endpoint['post_type'] === 'product_variation') {
            $this->updateVariationCombinationChild($product, $endpoint);
        } else {
            $this->updateProduct($product, $endpoint);
            \wc_delete_product_transients($product->getId()->getEndpoint());
        }
    }

    private function updateProductMeta(ProductModel $product, $endpoint)
    {
        $wcProduct = \wc_get_product($product->getId()->getEndpoint());

        if (is_null($wcProduct)) {
            return;
        }

        $wcProduct->set_sku($product->getSku());
        $wcProduct->set_parent_id(empty($parent = $product->getMasterProductId()->getEndpoint()) ? 0 : (int)$parent);
        $wcProduct->set_menu_order($product->getSort());
        $wcProduct->set_featured($product->getIsTopProduct());
        $wcProduct->set_weight($product->getHeight());
        $wcProduct->set_length($product->getLength());
        $wcProduct->set_width($product->getWidth());
        $wcProduct->set_weight($product->getShippingWeight());

        $wcProduct->set_date_modified($product->getModified());
        $wcProduct->set_status(is_null($product->getAvailableFrom()) ? ($product->getIsActive() ? 'publish' : 'draft') : 'future');

        $wcProduct->save();

        \wp_set_object_terms($wcProduct->get_id(), $endpoint['type'], 'product_type');

        $tags = array_map('trim', explode(' ', $product->getKeywords()));
        \wp_set_post_terms($wcProduct->get_id(), implode(',', $tags), 'product_tag');

        $taxClass = $this->database->queryOne(SQL::taxClassByRate($product->getVat()));
        $wcProduct->set_tax_class(is_null($taxClass) ? '' : $taxClass);

        $shippingClass = $product->getShippingClassId()->getEndpoint();

        if (!empty($shippingClass)) {
            \wp_set_object_terms($wcProduct->get_id(), \wc_clean($shippingClass), 'product_shipping_class');
        }
    }

    private function updateVariationCombinationChild(ProductModel $product, $endpoint)
    {
        $productId = (int)$product->getId()->getEndpoint();

        $productTitle = \esc_html(\get_the_title($product->getMasterProductId()->getEndpoint()));
        $variation_post_title = sprintf(__('Variation #%s of %s', 'woocommerce'), $productId, $productTitle);
        \wp_update_post(['ID' => $productId, 'post_title' => $variation_post_title]);

        \update_post_meta($productId, '_variation_description', $endpoint['post_excerpt']);

        $productStockLevel = new ProductStockLevel();
        $productStockLevel->pushDataChild($product);
    }

    private function updateProduct(ProductModel $product, $endpoint)
    {
        $productId = (int)$product->getId()->getEndpoint();

        \update_post_meta($productId, '_visibility', 'visible');

        $productAttr = new ProductAttr();
        $productAttr->pushData($product, $endpoint);

        $productStockLevel = new ProductStockLevel();
        $productStockLevel->pushDataParent($product);

        if ($product->getIsMasterProduct()) {
            Util::getInstance()->addMasterProductToSync($productId);
        }

        self::$idCache[$product->getId()->getHost()] = $productId;
    }

    private function updateProductRelations(ProductModel $product, $endpoint)
    {
        $product2Category = new Product2Category();
        $product2Category->pushData($product, $endpoint);

        $productPrice = new ProductPrice();
        $productPrice->pushData($product, $endpoint);

        $productSpecialPrice = new ProductSpecialPrice();
        $productSpecialPrice->pushData($product, $endpoint);

        $productVariation = new ProductVariation();
        $productVariation->pushData($product, $endpoint);
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
        return count($this->database->queryList(SQL::productPull()));
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

    private function updateGermanizedAttributes(ProductModel &$product, array &$endpoint)
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
                \update_post_meta($id, '_unit_price', round((float)\get_post_meta($id, '_price', true) / $divisor, $pd));
                \update_post_meta($id, '_unit_price_regular', round((float)\get_post_meta($id, '_regular_price', true) / $divisor, $pd));
            }
            $salePrice = \get_post_meta($id, '_sale_price', true);
            if (!empty($salePrice)) {
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
            if (Util::getInstance()->isWooCommerceLanguage($deliveryStatus) && !empty($deliveryStatus)) {
                $term = $this->database->queryOne(SQL::deliveryStatusByText($deliveryStatus));
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
}
