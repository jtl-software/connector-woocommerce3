<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller\Product;

use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\WooCommerce\Controller\BaseController;
use jtl\Connector\WooCommerce\Controller\Traits\DeleteTrait;
use jtl\Connector\WooCommerce\Controller\Traits\PullTrait;
use jtl\Connector\WooCommerce\Controller\Traits\PushTrait;
use jtl\Connector\WooCommerce\Controller\Traits\StatsTrait;
use jtl\Connector\WooCommerce\Logger\WpErrorLogger;
use jtl\Connector\WooCommerce\Utility\SQLs;
use jtl\Connector\WooCommerce\Utility\Util;

class Product extends BaseController
{
    use PullTrait, PushTrait, DeleteTrait, StatsTrait;

    private static $idCache = [];

    public function pullData($limit)
    {
        $products = [];

        $ids = $this->database->queryList(SQLs::productPull($limit));

        foreach ($ids as $id) {
            $product = \wc_get_product($id);

            if (!$product instanceof \WC_Product) {
                continue;
            }

            $result = $this->mapper->toHost($product);

            if (!$result instanceof ProductModel) {
                continue;
            }

            $this->onProductMapped($result);

            $products[] = $result;
        }

        return $products;
    }

    protected function getStats()
    {
        return count($this->database->queryList(SQLs::productPull()));
    }

    protected function pushData(ProductModel $product, $model)
    {
        if ($this->isValidForWooCommerce($product)) {
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
        }

        return $product;
    }

    private function isValidForWooCommerce(ProductModel $product)
    {
        return $product->getPackagingQuantity() <= 1.0 && $product->getMinimumOrderQuantity() <= 1.0;
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
        $productId = $product->getId()->getEndpoint();

        \wp_set_object_terms($productId, $endpoint['type'], 'product_type');

        $tags = array_map('trim', explode(' ', $product->getKeywords()));
        \wp_set_post_terms($productId, implode(',', $tags), 'product_tag');

        $taxClass = $this->database->queryOne(SQLs::taxClassByRate($product->getVat()));
        \update_post_meta($productId, '_tax_class', is_null($taxClass) ? '' : $taxClass);

        \update_post_meta($productId, '_featured', $product->getIsTopProduct() ? 'yes' : 'no');
        \update_post_meta($productId, '_sku', $product->getSku());
        \update_post_meta($productId, '_weight', $endpoint['weight']);
        \update_post_meta($productId, '_height', $endpoint['height']);
        \update_post_meta($productId, '_width', $endpoint['width']);
        \update_post_meta($productId, '_length', $endpoint['length']);

        $shippingClass = $product->getShippingClassId()->getEndpoint();
        if (!empty($shippingClass)) {
            \wp_set_object_terms($productId, \wc_clean($shippingClass), 'product_shipping_class');
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

    protected function onProductMapped(ProductModel &$product)
    {
    }
}
