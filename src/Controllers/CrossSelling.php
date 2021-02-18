<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers;

use jtl\Connector\Model\CrossSelling as CrossSellingModel;
use jtl\Connector\Model\CrossSellingItem;
use jtl\Connector\Model\Identity;
use JtlWooCommerceConnector\Controllers\Traits\DeleteTrait;
use JtlWooCommerceConnector\Controllers\Traits\PullTrait;
use JtlWooCommerceConnector\Controllers\Traits\PushTrait;
use JtlWooCommerceConnector\Controllers\Traits\StatsTrait;
use JtlWooCommerceConnector\Logger\WpErrorLogger;
use JtlWooCommerceConnector\Models\CrossSellingGroup;
use JtlWooCommerceConnector\Utilities\SqlHelper;

/**
 * Class CrossSelling
 * @package JtlWooCommerceConnector\Controllers
 */
class CrossSelling extends BaseController
{
    use PullTrait, PushTrait, DeleteTrait, StatsTrait;

    const CROSSSELLING_META_KEY = '_crosssell_ids';
    const UPSELLING_META_KEY = '_upsell_ids';

    /**
     * @param $limit
     * @return array
     */
    protected function pullData($limit)
    {
        $crossSelling = [];

        $results = $this->database->query(SqlHelper::crossSellingPull($limit));
        $formattedResults = $this->formatResults($results);

        foreach ($formattedResults as $row) {
            $type = $row['meta_key'];
            $relatedProducts = unserialize($row['meta_value']);

            $crossSellingGroup = CrossSellingGroup::getByWooCommerceName($type);

            if (!empty($relatedProducts)) {

                if (!isset($crossSelling[$row['post_id']])) {
                    $crossSelling[$row['post_id']] = (new CrossSellingModel());
                }

                $crossSelling[$row['post_id']]
                    ->setId(new Identity($row['post_id']))
                    ->setProductId(new Identity($row['post_id']));

                $crosssellingProducts = [];
                foreach ($relatedProducts as $product) {
                    $crosssellingProducts[] = new Identity($product);
                }

                $crossSelling[$row['post_id']]->addItem(
                    (new CrossSellingItem())
                        ->setCrossSellingGroupId($crossSellingGroup->getId())
                        ->setProductIds($crosssellingProducts)
                );

            } else {
                WpErrorLogger::getInstance()->logError(sprintf('CrossSelling values for product id %s are empty', $row['post_id']));
            }

            reset($crossSelling);
        }

        return $crossSelling;
    }

    /**
     * @param array $result
     * @return array
     */
    protected function formatResults(array $result): array
    {
        $formattedResults = [];
        foreach ($result as $row) {
            $types = explode('||', $row['meta_key']);
            $values = explode('||', $row['meta_value']);

            foreach ($types as $i => $type) {
                if (empty($type) || !isset($values[$i])) {
                    continue;
                }
                $formattedResults[] = [
                    'meta_value' => $values[$i],
                    'meta_key' => $type,
                    'post_id' => $row['post_id'],
                ];
            }
        }
        return $formattedResults;
    }

    /**
     * @param CrossSellingModel $crossSelling
     * @return CrossSellingModel
     */
    protected function pushData(CrossSellingModel $crossSelling)
    {
        $product = \wc_get_product((int)$crossSelling->getProductId()->getEndpoint());

        if (!$product instanceof \WC_Product) {
            return $crossSelling;
        }

        $crossSelling->getId()->setEndpoint($crossSelling->getProductId()->getEndpoint());

        $crossSellingProducts = $this->getProductIds($crossSelling, CrossSellingGroup::TYPE_CROSS_SELL);
        $upSellProducts = $this->getProductIds($crossSelling, CrossSellingGroup::TYPE_UP_SELL);

        $this->updateMetaKey(
            $product->get_id(),
            self::CROSSSELLING_META_KEY,
            $crossSellingProducts
        );

        $this->updateMetaKey(
            $product->get_id(),
            self::UPSELLING_META_KEY,
            $upSellProducts
        );

        return $crossSelling;
    }

    /**
     * @param CrossSellingModel $crossSelling
     * @return CrossSellingModel
     */
    protected function deleteData(CrossSellingModel $crossSelling)
    {
        $product = \wc_get_product((int)$crossSelling->getProductId()->getEndpoint());

        if (!$product instanceof \WC_Product) {
            return $crossSelling;
        }

        $crossSellingProducts = $this->getProductIds($crossSelling, CrossSellingGroup::TYPE_CROSS_SELL);
        $upSellProducts = $this->getProductIds($crossSelling, CrossSellingGroup::TYPE_UP_SELL);

        $crossSellIds = !empty($crossSellingProducts) ? array_diff($product->get_cross_sell_ids(), $crossSellingProducts) : [];
        $upSellIds = !empty($upSellProducts) ? array_diff($product->get_upsell_ids(), $upSellProducts) : [];

        $this->updateMetaKey(
            $product->get_id(),
            self::CROSSSELLING_META_KEY,
            $crossSellIds
        );

        $this->updateMetaKey(
            $product->get_id(),
            self::UPSELLING_META_KEY,
            $upSellIds
        );

        return $crossSelling;
    }

    /**
     * @return int
     */
    protected function getStats()
    {
        return (int)$this->database->queryOne(SqlHelper::crossSellingPull());
    }

    /**
     * @param $productId
     * @param $key
     * @param $value
     */
    protected function updateMetaKey($productId, $key, $value)
    {
        \update_post_meta(
            $productId,
            $key,
            $value,
            \get_post_meta($productId, $key, true)
        );
    }

    /**
     * @param CrossSellingModel $crossSelling
     * @param $crossSellingGroupEndpointId
     * @return array
     */
    private function getProductIds(CrossSellingModel $crossSelling, $crossSellingGroupEndpointId)
    {
        $products = [];

        foreach ($crossSelling->getItems() as $item) {
            foreach ($item->getProductIds() as $productId) {
                if ($crossSellingGroupEndpointId === $item->getCrossSellingGroupId()->getEndpoint()) {
                    $products[] = (int)$productId->getEndpoint();
                }
            }
        }

        return array_unique($products);
    }
}
