<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Controllers;

use Jtl\Connector\Core\Controller\DeleteInterface;
use Jtl\Connector\Core\Controller\PullInterface;
use Jtl\Connector\Core\Controller\PushInterface;
use Jtl\Connector\Core\Controller\StatisticInterface;
use Jtl\Connector\Core\Model\CrossSelling;
use Jtl\Connector\Core\Model\CrossSelling as CrossSellingModel;
use Jtl\Connector\Core\Model\CrossSellingItem;
use Jtl\Connector\Core\Model\AbstractModel;
use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\QueryFilter;
use JtlWooCommerceConnector\Models\CrossSellingGroup;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use Psr\Log\InvalidArgumentException;

/**
 * Class CrossSelling
 *
 * @package JtlWooCommerceConnector\Controllers
 */
class CrossSellingController extends AbstractBaseController implements
    PullInterface,
    PushInterface,
    DeleteInterface,
    StatisticInterface
{
    public const CROSSSELLING_META_KEY = '_crosssell_ids';
    public const UPSELLING_META_KEY    = '_upsell_ids';

    /**
     * @param QueryFilter $query
     * @return CrossSellingModel[]
     * @throws InvalidArgumentException
     */
    public function pull(QueryFilter $query): array
    {
        $crossSelling = [];

        /** @var array<int, array<string, int|string>> $results */
        $results          = $this->db->query(SqlHelper::crossSellingPull($query->getLimit())) ?? [];
        $formattedResults = $this->formatResults($results);

        foreach ($formattedResults as $row) {
            $type            = $row['meta_key'];
            $relatedProducts = \unserialize((string)$row['meta_value']);

            $crossSellingGroup = CrossSellingGroup::getByWooCommerceName((string)$type, $this->util);

            if (!empty($relatedProducts)) {
                if (!isset($crossSelling[$row['post_id']])) {
                    $crossSelling[$row['post_id']] = (new CrossSellingModel());
                }

                $crossSelling[$row['post_id']]
                    ->setId(new Identity((string)$row['post_id']))
                    ->setProductId(new Identity((string)$row['post_id']));

                $crosssellingProducts = [];

                if (\is_array($relatedProducts)) {
                    foreach ($relatedProducts as $product) {
                        $crosssellingProducts[] = new Identity((string)$product);
                    }
                }

                if (!\is_bool($crossSellingGroup)) {
                    $crossSelling[$row['post_id']]->addItem(
                        (new CrossSellingItem())
                            ->setCrossSellingGroupId($crossSellingGroup->getId())
                            ->setProductIds(...$crosssellingProducts)
                    );
                }
            } else {
                $this->logger->error(\sprintf(
                    'CrossSelling values for product id %s are empty',
                    $row['post_id']
                ));
            }

            \reset($crossSelling);
        }

        return \array_values($crossSelling);
    }

    /**
     * @param array<int, array<string, int|string>> $result
     * @return array<int, array<string, int|string>>
     */
    protected function formatResults(array $result): array
    {
        $formattedResults = [];
        foreach ($result as $row) {
            /** @var string $metaKey */
            $metaKey = $row['meta_key'];
            /** @var string $metaValue */
            $metaValue = $row['meta_value'];

            $types  = \explode('||', $metaKey);
            $values = \explode('||', $metaValue);

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
     * @param AbstractModel ...$models
     * @return CrossSellingModel[]
     */
    public function push(AbstractModel ...$models): array
    {
        $returnModels = [];

        foreach ($models as $model) {
            /** @var CrossSelling $model */
            $product = \wc_get_product((int)$model->getProductId()->getEndpoint());

            if (!$product instanceof \WC_Product) {
                $returnModels[] = $model;
                continue;
            }

            $model->getId()->setEndpoint($model->getProductId()->getEndpoint());

            $crossSellingProducts = $this->getProductIds($model, CrossSellingGroup::TYPE_CROSS_SELL);
            $upSellProducts       = $this->getProductIds($model, CrossSellingGroup::TYPE_UP_SELL);

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

            $returnModels[] = $model;
        }
        return $returnModels;
    }

    /**
     * @param AbstractModel ...$models
     * @return AbstractModel[]
     */
    public function delete(AbstractModel ...$models): array
    {
        $returnModels = [];

        foreach ($models as $model) {
            /** @var CrossSelling $model */
            $product = \wc_get_product((int)$model->getProductId()->getEndpoint());

            if (!$product instanceof \WC_Product) {
                $returnModels[] = $model;
                continue;
            }

            $crossSellingProducts = $this->getProductIds($model, CrossSellingGroup::TYPE_CROSS_SELL);
            $upSellProducts       = $this->getProductIds($model, CrossSellingGroup::TYPE_UP_SELL);

            $crossSellIds =
                !empty($crossSellingProducts)
                    ? \array_diff($product->get_cross_sell_ids(), $crossSellingProducts)
                    : [];
            $upSellIds    = !empty($upSellProducts) ? \array_diff($product->get_upsell_ids(), $upSellProducts) : [];

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

            $returnModels[] = $model;
        }

        return $returnModels;
    }

    /**
     * @param QueryFilter $query
     * @return int
     * @throws \InvalidArgumentException
     */
    public function statistic(QueryFilter $query): int
    {
        return (int)$this->db->queryOne(SqlHelper::crossSellingPull());
    }

    /**
     * @param int                    $productId
     * @param string                 $key
     * @param array<int, int|string> $value
     * @return void
     */
    protected function updateMetaKey(int $productId, string $key, array $value): void
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
     * @param string            $crossSellingGroupEndpointId
     * @return int[]
     */
    private function getProductIds(CrossSellingModel $crossSelling, string $crossSellingGroupEndpointId): array
    {
        $products = [];

        foreach ($crossSelling->getItems() as $item) {
            foreach ($item->getProductIds() as $productId) {
                if ($crossSellingGroupEndpointId === $item->getCrossSellingGroupId()->getEndpoint()) {
                    $products[] = (int)$productId->getEndpoint();
                }
            }
        }

        return \array_unique($products);
    }
}
