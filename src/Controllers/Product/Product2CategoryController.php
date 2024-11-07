<?php

namespace JtlWooCommerceConnector\Controllers\Product;

use InvalidArgumentException;
use Jtl\Connector\Core\Model\AbstractModel;
use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\Product as ProductModel;
use Jtl\Connector\Core\Model\Product2Category as Product2CategoryModel;
use JtlWooCommerceConnector\Controllers\AbstractBaseController;
use JtlWooCommerceConnector\Logger\ErrorFormatter;
use JtlWooCommerceConnector\Utilities\Id;
use WC_Product;

class Product2CategoryController extends AbstractBaseController
{
    /**
     * @param WC_Product $product
     * @return array
     * @throws InvalidArgumentException
     */
    public function pullData(WC_Product $product): array
    {
        $productCategories = [];

        if (!$product->is_type('variation')) {
            $categories = $product->get_category_ids();

            if ($categories instanceof \WP_Error) {
                $this->logger->error(ErrorFormatter::formatError($categories));

                return [];
            }

            foreach ($categories as $category) {
                $productCategory = (new Product2CategoryModel())
                    ->setId(new Identity(Id::link([$product->get_id(), $category])))
                    ->setCategoryId(new Identity($category));

                $productCategories[] = $productCategory;
            }
        }

        return $productCategories;
    }

    /**
     * @param ProductModel $model
     * @return void
     */
    public function pushData(AbstractModel $model): void
    {
        $wcProduct = \wc_get_product($model->getId()->getEndpoint());
        $wcProduct->set_category_ids($this->getCategoryIds($model->getCategories()));
        $wcProduct->save();
    }

    /**
     * @param array $categories
     * @return array
     */
    private function getCategoryIds(array $categories): array
    {
        $productCategories = [];

        /** @var Product2CategoryModel $category */
        foreach ($categories as $category) {
            $categoryId = $category->getCategoryId()->getEndpoint();

            if (!empty($categoryId)) {
                $productCategories[] = (int)$categoryId;
            }
        }

        return $productCategories;
    }
}
