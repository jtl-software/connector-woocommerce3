<?php

namespace JtlWooCommerceConnector\Controllers\Product;

use InvalidArgumentException;
use Jtl\Connector\Core\Model\Product as ProductModel;
use JtlWooCommerceConnector\Controllers\AbstractBaseController;
use JtlWooCommerceConnector\Controllers\GlobalData\CustomerGroupController;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use WC_Product;

class ProductB2BMarketFieldsController extends AbstractBaseController
{
    /**
     * @param ProductModel $product
     * @param WC_Product $wcProduct
     * @return void
     */
    public function pullData(ProductModel &$product, WC_Product $wcProduct): void
    {
        $this->setRRPProperty($product, $wcProduct);
    }

    /**
     * @param ProductModel $product
     * @param WC_Product $wcProduct
     * @return void
     */
    private function setRRPProperty(ProductModel &$product, WC_Product $wcProduct): void
    {
        $rrp = \get_post_meta($wcProduct->get_id(), 'bm_rrp', true);
        if ($rrp !== '' && !empty($rrp)) {
            $product->setRecommendedRetailPrice((float)$rrp);
        }
    }

    /**
     * @param ProductModel $product
     * @return void
     * @throws InvalidArgumentException
     */
    public function pushData(ProductModel $product): void
    {
        $wcProduct = \wc_get_product($product->getId()->getEndpoint());

        if ($wcProduct instanceof WC_Product === false) {
            return;
        }

        $this->updateRRP($product, $wcProduct);
        $this->updateMinimumOrderQuantity($product, $wcProduct);
        $this->updateSpecialPrices($product, $wcProduct);
    }

    /**
     * @param ProductModel $product
     * @param WC_Product $wcProduct
     * @return void
     * @throws InvalidArgumentException
     */
    protected function updateMinimumOrderQuantity(ProductModel $product, WC_Product $wcProduct): void
    {
        $groupController = new CustomerGroupController($this->db, $this->util);

        $updatedEndpoints = [];

        foreach ($product->getCustomerGroupPackagingQuantities() as $customerGroup) {
            $groupSlug = $groupController->getSlugById($customerGroup->getCustomerGroupId()->getEndpoint());
            if ($groupSlug !== false) {
                $this->updateMinimumQuantityMetaFields($customerGroup, $wcProduct, $groupSlug);
                $updatedEndpoints[] = $customerGroup->getCustomerGroupId()->getEndpoint();
            }
        }

        $customerGroups = $groupController->pull();
        foreach ($customerGroups as $customerGroup) {
            $customerGroupId = $customerGroup->getId()->getEndpoint();

            if (\in_array($customerGroupId, $updatedEndpoints)) {
                continue;
            }

            $groupSlug = $groupController->getSlugById($customerGroupId);
            if ($groupSlug !== false) {
                $this->updateMinimumQuantityMetaFields($product, $wcProduct, $groupSlug);
            }
        }
    }

    /**
     * @param $quantityObject
     * @param WC_Product $wcProduct
     * @param $groupSlug
     * @return void
     */
    protected function updateMinimumQuantityMetaFields($quantityObject, WC_Product $wcProduct, $groupSlug): void
    {
        $minQuantityKey = \sprintf("bm_%s_min_quantity", $groupSlug);
        \update_post_meta(
            $wcProduct->get_id(),
            $minQuantityKey,
            (float)$quantityObject->getMinimumOrderQuantity(),
            \get_post_meta($wcProduct->get_id(), $minQuantityKey, true)
        );
        $stepQuantityKey = \sprintf("bm_%s_step_quantity", $groupSlug);
        \update_post_meta(
            $wcProduct->get_id(),
            $stepQuantityKey,
            (float)$quantityObject->getPackagingQuantity(),
            \get_post_meta($wcProduct->get_id(), $stepQuantityKey, true)
        );
    }

    /**
     * @param ProductModel $product
     * @param WC_Product $wcProduct
     * @return void
     */
    private function updateRRP(ProductModel $product, WC_Product $wcProduct): void
    {
        $rrp = $product->getRecommendedRetailPrice();
        $rrp = \round($rrp, 4);

        $version     = (string)SupportedPlugins::getVersionOf(SupportedPlugins::PLUGIN_B2B_MARKET);
        $isNewFormat = (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_B2B_MARKET)
            && \version_compare($version, '1.0.4', '>='));

        $oldValue = (float)\get_post_meta($wcProduct->get_id(), 'bm_rrp', true);
        if ($rrp !== 0.) {
            if ($rrp !== $oldValue) {
                if ($product->getMasterProductId()->getHost() !== 0 && !$isNewFormat) {
                    $vKey = \sprintf('bm_%s_rrp', $wcProduct->get_id());
                    \update_post_meta(
                        $wcProduct->get_parent_id(),
                        $vKey,
                        $rrp,
                        \get_post_meta($wcProduct->get_parent_id(), $vKey, true)
                    );
                } else {
                    \update_post_meta(
                        $wcProduct->get_id(),
                        'bm_rrp',
                        $rrp,
                        \get_post_meta($wcProduct->get_id(), 'bm_rrp', true)
                    );
                }
            }
        } else {
            if ($product->getMasterProductId()->getHost() !== 0 && !$isNewFormat) {
                $vKey = \sprintf('bm_%s_rrp', $wcProduct->get_id());
                \delete_post_meta($wcProduct->get_parent_id(), $vKey);
            } else {
                \delete_post_meta($wcProduct->get_id(), 'bm_rrp');
            }
        }
    }

    /**
     * @param ProductModel $product
     * @param WC_Product $wcProduct
     * @return void
     */
    private function updateSpecialPrices(ProductModel $product, WC_Product $wcProduct): void
    {
        $jtlSpecialPrices = $product->getSpecialPrices();

        foreach ($jtlSpecialPrices as $jtlSpecialPrice) {
            $items = $jtlSpecialPrice->getItems();
            foreach ($items as $item) {
                if (\get_post($item->getCustomerGroupId()->getEndpoint()) !== null) {
                    $customerGroup = \get_post($item->getCustomerGroupId()->getEndpoint())->post_name;
                    $key           = 'bm_' . $customerGroup . '_group_prices';
                    $oldGroupPrice = \get_post_meta($wcProduct->get_id(), $key, true);
                    $oldValue      = (float)$oldGroupPrice[0]['group_price'];
                    if ($oldValue !== $item->getPriceNet()) {
                        $postMeta                   = \get_post_meta($wcProduct->get_id(), $key, true);
                        $postMeta[0]['group_price'] = (string)$item->getPriceNet();
                        \update_post_meta(
                            $wcProduct->get_id(),
                            $key,
                            $postMeta,
                        );
                    }
                }
            }
        }
    }
}
