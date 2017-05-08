<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
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
use jtl\Connector\WooCommerce\Utility\SQLs;

class CrossSelling extends BaseController
{
    use PullTrait, PushTrait, DeleteTrait, StatsTrait;

    public function pullData($limit)
    {
        $return = [];

        $result = $this->database->query(SQLs::crossSellingPull($limit));

        foreach ($result as $row) {
            $relatedProducts = unserialize($row['meta_value']);

            if (!empty($relatedProducts)) {
                $crossSelling = $this->mapper->toHost($row);

                if ($crossSelling instanceof CrossSellingModel) {
                    foreach ($relatedProducts as $product) {
                        $crossSelling->addItem((new CrossSellingItem())
                            ->addProductId(new Identity($product)));
                    }

                    $return[] = $crossSelling;
                }
            }
        }

        return $return;
    }

    protected function getStats()
    {
        $count = 0;

        $result = $this->database->query(SQLs::crossSellingPull());

        foreach ($result as $row) {
            $relatedProducts = unserialize($row['meta_value']);

            if (!empty($relatedProducts)) {
                ++$count;
            }
        }

        return $count;
    }

    protected function pushData(CrossSellingModel $crossSelling)
    {
        $product = \wc_get_product((int)$crossSelling->getProductId()->getEndpoint());

        if ($product instanceof \WC_Product) {
            $crossSells = [];

            foreach ($product->get_cross_sell_ids() as $crossSell) {
                $crossSells[] = (int)$crossSell;
            }

            foreach ($crossSelling->getItems() as $item) {
                foreach ($item->getProductIds() as $productId) {
                    $crossSells[] = (int)$productId->getEndpoint();
                }
            }

            $product->set_cross_sell_ids(array_unique($crossSells));
            $product->save();
        }

        $crossSelling->getId()->setEndpoint($crossSelling->getProductId()->getEndpoint());

        return $crossSelling;
    }

    protected function deleteData(CrossSellingModel $crossSelling)
    {
        $product = \wc_get_product((int)$crossSelling->getProductId()->getEndpoint());

        if ($product instanceof \WC_Product) {
            $product->set_cross_sell_ids([]);
            $product->save();
        }

        return $crossSelling;
    }
}
