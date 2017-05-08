<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller\Product;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\Model\Product2Category as Product2CategoryModel;
use jtl\Connector\WooCommerce\Controller\BaseController;
use jtl\Connector\WooCommerce\Logger\WpErrorLogger;
use jtl\Connector\WooCommerce\Utility\IdConcatenation;

class Product2Category extends BaseController
{
    const TAXONOMY = 'product_cat';

    public function pullData(\WC_Product $product)
    {
        $productCategories = [];

        if (!$product->is_type('variation')) {
            $categories = \wp_get_post_terms($product->get_id(), self::TAXONOMY, ['fields' => 'ids']);

            if ($categories instanceof \WP_Error) {
                WpErrorLogger::getInstance()->logError($categories);

                return [];
            }

            foreach ($categories as $category) {
                $productCategory = (new Product2CategoryModel())
                    ->setId(new Identity(IdConcatenation::link([$product->get_id(), $category])))
                    ->setProductId(new Identity($product->get_id()))
                    ->setCategoryId(new Identity($category));

                $productCategories[] = $productCategory;
            }
        }

        return $productCategories;
    }

    public function pushData(ProductModel $product, $model)
    {
        $func = function (Product2CategoryModel $category) {
            $categoryId = $category->getCategoryId()->getEndpoint();

            if (!empty($categoryId)) {
                $productCategories[] = (int)$categoryId;
            }
        };

        \wp_set_object_terms($product->getId()->getEndpoint(), array_map($func, $product->getCategories()), self::TAXONOMY);
    }
}
