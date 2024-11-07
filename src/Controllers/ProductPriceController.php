<?php

namespace JtlWooCommerceConnector\Controllers;

use Exception;
use InvalidArgumentException;
use Jtl\Connector\Core\Controller\PushInterface;
use Jtl\Connector\Core\Model\AbstractModel;
use Jtl\Connector\Core\Model\Product;
use JtlWooCommerceConnector\Controllers\Product\ProductPrice;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlProduct;

class ProductPriceController extends ProductPrice implements PushInterface
{
    /**
     * @param Product $model
     * @return AbstractModel
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function push(AbstractModel $model): AbstractModel
    {
        $wcProduct = \wc_get_product($model->getId()->getEndpoint());

        if ($wcProduct !== false) {
            $vat = $model->getVat();
            if (\is_null($vat)) {
                $vat = $this->util->getTaxRateByTaxClass($wcProduct->get_tax_class());
            }

            $wcProducts[] = $wcProduct;

            if ($this->wpml->canBeUsed()) {
                $wcProductTranslations = $this->wpml->getComponent(WpmlProduct::class)
                    ->getWooCommerceProductTranslations($wcProduct);
                $wcProducts            = \array_merge($wcProducts, $wcProductTranslations);
            }

            foreach ($wcProducts as $wcProduct) {
                $this->savePrices(
                    $wcProduct,
                    $vat,
                    $this->getJtlProductType($wcProduct),
                    ...$model->getPrices()
                );

                // Update the max and min prices for the parent product
                if ($wcProduct->is_type('variation')) {
                    \WC_Product_Variable::sync($wcProduct->get_id());
                }

                \wc_delete_product_transients($wcProduct->get_id());
            }
        }

        return $model;
    }

    /**
     * @param \WC_Product $wcProduct
     * @return string
     */
    protected function getJtlProductType(\WC_Product $wcProduct): string
    {
        switch ($wcProduct->get_type()) {
            case 'variable':
                $type = ProductController::TYPE_PARENT;
                break;
            case 'variation':
                $type = ProductController::TYPE_CHILD;
                break;
            case 'simple':
            default:
                $type = ProductController::TYPE_SINGLE;
                break;
        }

        return $type;
    }
}
