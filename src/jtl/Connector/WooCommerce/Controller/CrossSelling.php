<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller;

use jtl\Connector\Model\CrossSelling as CrossSellingModel;
use jtl\Connector\Model\CrossSellingItem;
use jtl\Connector\Model\Identity;
use jtl\Connector\WooCommerce\Controller\Traits\DeleteTrait;
use jtl\Connector\WooCommerce\Controller\Traits\PullTrait;
use jtl\Connector\WooCommerce\Controller\Traits\PushTrait;
use jtl\Connector\WooCommerce\Controller\Traits\StatsTrait;
use jtl\Connector\WooCommerce\Utility\SQL;

class CrossSelling extends BaseController
{
    use PullTrait, PushTrait, DeleteTrait, StatsTrait;

    protected function pullData($limit)
    {
        $return = [];

        $result = $this->database->query(SQL::crossSellingPull($limit));

        foreach ($result as $row) {
            if (!isset($row['meta_value'])) {
                continue;
            }

            $relatedProducts = unserialize($row['meta_value']);

            if (!empty($relatedProducts)) {
                $crossSelling = (new CrossSellingModel())
                    ->setId(new Identity($row['post_id']))
                    ->setProductId(new Identity($row['post_id']));

                foreach ($relatedProducts as $product) {
                    $crossSelling->addItem((new CrossSellingItem())
                        ->addProductId(new Identity($product)));
                }

                $return[] = $crossSelling;
            }
        }

        return $return;
    }

    protected function pushData(CrossSellingModel $crossSelling)
    {
        $product = \wc_get_product((int)$crossSelling->getProductId()->getEndpoint());

        if (!$product instanceof \WC_Product) {
            return $crossSelling;
        }

        $crossSelling->getId()->setEndpoint($crossSelling->getProductId()->getEndpoint());

        $crossSells = $this->getProductIds($crossSelling);

        foreach ($product->get_cross_sell_ids() as $crossSell) {
            $crossSells[] = (int)$crossSell;
        }

        $product->set_cross_sell_ids(array_unique($crossSells));
        $product->save();

        return $crossSelling;
    }

    protected function deleteData(CrossSellingModel $crossSelling)
    {
        $product = \wc_get_product((int)$crossSelling->getProductId()->getEndpoint());

        if (!$product instanceof \WC_Product) {
            return $crossSelling;
        }

        $product->set_cross_sell_ids(array_diff($product->get_cross_sell_ids(), $this->getProductIds($crossSelling)));
        $product->save();

        return $crossSelling;
    }

    protected function getStats()
    {
        $count = 0;

        $result = $this->database->query(SQL::crossSellingPull());

        foreach ($result as $row) {
            if (!isset($row['meta_value'])) {
                continue;
            }

            $relatedProducts = unserialize($row['meta_value']);

            if (!empty($relatedProducts)) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Return an array of unique product ids linked as cross selling.
     *
     * @param CrossSellingModel $crossSelling The cross selling.
     * @return array The product ids.
     */
    private function getProductIds(CrossSellingModel $crossSelling)
    {
        $products = [];

        foreach ($crossSelling->getItems() as $item) {
            foreach ($item->getProductIds() as $productId) {
                $products[] = (int)$productId->getEndpoint();
            }
        }

        return array_unique($products);
    }
}
