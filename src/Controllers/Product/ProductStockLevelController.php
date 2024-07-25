<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Controllers\Product;

use Exception;
use Jtl\Connector\Core\Exception\TranslatableAttributeException;
use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\Product as ProductModel;
use Jtl\Connector\Core\Model\ProductStockLevel as StockLevelModel;
use JtlWooCommerceConnector\Controllers\AbstractBaseController;
use JtlWooCommerceConnector\Utilities\Util;
use WC_Product;

class ProductStockLevelController extends AbstractBaseController
{
    /**
     * @param WC_Product $product
     * @return StockLevelModel
     */
    public function pullData(WC_Product $product): StockLevelModel
    {
        $stockLevel = $product->get_stock_quantity();

        return (new StockLevelModel())
            ->setProductId(new Identity((string)$product->get_id()))
            ->setStockLevel(\is_null($stockLevel) ? 0.0 : (double)$stockLevel);
    }

    /**
     * @param ProductModel $product
     * @return void
     * @throws Exception
     */
    public function pushDataChild(ProductModel $product): void
    {
        $variationId = $product->getId()->getEndpoint();

        if (\wc_get_product($variationId) === false) {
            return;
        }

        \update_post_meta((int)$variationId, '_manage_stock', $product->getConsiderStock() ? 'yes' : 'no');

        $stockLevel = $product->getStockLevel();

        \wc_update_product_stock_status((int)$variationId, $this->util->getStockStatus(
            $stockLevel,
            $product->getPermitNegativeStock(),
            $product->getConsiderStock()
        ));

        if ($product->getConsiderStock()) {
            \update_post_meta(
                (int)$product->getId()->getEndpoint(),
                '_backorders',
                $this->getBackorderValue($product)
            );
            \wc_update_product_stock((int)$variationId, (int)\wc_stock_amount($product->getStockLevel()));
        } else {
            \delete_post_meta((int)$variationId, '_backorders');
            \delete_post_meta((int)$variationId, '_stock');
        }
    }

    /**
     * @param ProductModel $product
     * @return void
     * @throws Exception
     */
    public function pushDataParent(ProductModel $product): void
    {
        $productId = $product->getId()->getEndpoint();
        $wcProduct = \wc_get_product($productId);

        if ($wcProduct === false || $wcProduct === null) {
            return;
        }

        $stockLevel = $product->getStockLevel();

        $stockStatus = $this->util->getStockStatus(
            $stockLevel,
            $product->getPermitNegativeStock(),
            $product->getConsiderStock()
        );

        if ('yes' == \get_option('woocommerce_manage_stock')) {
            \update_post_meta(
                (int)$product->getId()->getEndpoint(),
                '_backorders',
                $this->getBackorderValue($product)
            );

            if ($product->getConsiderStock()) {
                \update_post_meta((int)$productId, '_manage_stock', 'yes');
                if (!$wcProduct->is_type('variable')) {
                    \wc_update_product_stock_status((int)$productId, $stockStatus);
                }

                \wc_update_product_stock((int)$productId, (int)\wc_stock_amount($stockLevel));
            } else {
                \update_post_meta((int)$productId, '_manage_stock', 'no');
                \update_post_meta((int)$productId, '_stock', '');

                \wc_update_product_stock_status((int)$productId, $stockStatus);
            }
        } elseif (!$wcProduct->is_type('variable')) {
            \wc_update_product_stock_status((int)$productId, $stockStatus);
        }
    }

    /**
     * @param ProductModel $product
     * @return string
     * @throws TranslatableAttributeException
     */
    protected function getBackorderValue(ProductModel $product): string
    {
        $value = $product->getPermitNegativeStock() ? 'yes' : 'no';
        if ($value === 'yes') {
            $attribute = $this->util->findAttributeI18nByName(
                ProductVaSpeAttrHandlerController::NOTIFY_CUSTOMER_ON_OVERSELLING,
                $this->util->getWooCommerceLanguage(),
                ...$product->getAttributes()
            );
            /** @var string $attributeValue */
            $attributeValue = !\is_null($attribute) ? $attribute->getValue() : '';
            if ($attributeValue !== '' && Util::isTrue($attributeValue)) {
                $value = 'notify';
            }
        }

        return $value;
    }
}
