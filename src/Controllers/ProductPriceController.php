<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Controllers;

use Automattic\WooCommerce\Internal\DependencyManagement\ContainerException;
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
     * @param AbstractModel ...$models
     *
     * @return AbstractModel[]
     * @throws ContainerException
     * @throws InvalidArgumentException
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function push(AbstractModel ...$models): array
    {
        $returnModels = [];

        foreach ($models as $model) {
            $wcProducts = [];
            $wcProduct  = \wc_get_product($model->getId()->getEndpoint());

            if ($wcProduct !== false && $wcProduct !== null) {
                $vat = $model->getVat();

                $wcProducts[] = $wcProduct;

                if ($this->wpml->canBeUsed()) {
                    /** @var WpmlProduct $wpmlProduct */
                    $wpmlProduct = $this->wpml->getComponent(WpmlProduct::class);

                    $wcProductTranslations = $wpmlProduct
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

            $returnModels[] = $model;
        }
        return $returnModels;
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
