<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Controllers\Product;

use InvalidArgumentException;
use Jtl\Connector\Core\Model\AbstractModel;
use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\Product;
use Jtl\Connector\Core\Model\Product as ProductModel;
use Jtl\Connector\Core\Model\Product2Category as Product2CategoryModel;
use JtlWooCommerceConnector\Controllers\AbstractBaseController;
use JtlWooCommerceConnector\Logger\ErrorFormatter;
use JtlWooCommerceConnector\Utilities\Id;
use Psr\Log\LogLevel;
use WC_Product;

class Product2CategoryController extends AbstractBaseController
{
    /**
     * @param WC_Product $product
     * @return Product2CategoryModel[]
     * @throws InvalidArgumentException
     */
    public function pullData(WC_Product $product): array
    {
        $productCategories = [];

        if (!$product->is_type('variation')) {
            $categories = $product->get_category_ids();

            if (empty($categories)) {
                $this->logger->log(LogLevel::INFO, 'No categories for product found.');
            }

            foreach ($categories as $category) {
                $productCategory = (new Product2CategoryModel())
                    ->setId(new Identity(Id::link([$product->get_id(), $category])))
                    ->setCategoryId(new Identity((string)$category));

                $productCategories[] = $productCategory;
            }
        }

        return $productCategories;
    }

    /**
     * @param AbstractModel $model
     * @return void
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function pushData(AbstractModel $model): void
    {
        /** @var Product $model */
        $wcProduct = \wc_get_product($model->getId()->getEndpoint());

        if (!$wcProduct instanceof WC_Product) {
            $this->logger->log(
                LogLevel::INFO,
                'Product not found for given product id',
                ['product_id' => $model->getId()->getEndpoint()]
            );

            return;
        }

        $wcProduct->set_category_ids($this->getCategoryIds($model->getCategories()));
        $wcProduct->save();
    }

    /**
     * @param Product2CategoryModel[] $categories
     * @return int[]
     */
    private function getCategoryIds(array $categories): array
    {
        $productCategories = [];

        foreach ($categories as $category) {
            $categoryId = $category->getCategoryId()->getEndpoint();

            if (!empty($categoryId)) {
                $productCategories[] = (int)$categoryId;
            }
        }

        return $productCategories;
    }
}
