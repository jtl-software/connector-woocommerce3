<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Product;

use jtl\Connector\Model\Product as ProductModel;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Controllers\GlobalData\CustomerGroup;

class ProductB2BMarketFields extends BaseController
{
    /**
     * @param ProductModel $product
     * @param \WC_Product $wcProduct
     */
    public function pullData(ProductModel &$product, \WC_Product $wcProduct)
    {
        $this->setRRPProperty($product, $wcProduct);
    }

    /**
     * @param ProductModel $product
     * @param \WC_Product $wcProduct
     */
    private function setRRPProperty(ProductModel &$product, \WC_Product $wcProduct)
    {
        $rrp = get_post_meta($wcProduct->get_id(), 'bm_rrp', true);
        if ($rrp !== '' && !is_null($rrp) && !empty($rrp)) {
            $product->setRecommendedRetailPrice((float)$rrp);
        }
    }

    /**
     * @param ProductModel $product
     */
    public function pushData(ProductModel $product)
    {
        $wcProduct = \wc_get_product($product->getId()->getEndpoint());

        if ($wcProduct instanceof \WC_Product === false) {
            return;
        }

        $this->updateRRP($product, $wcProduct);
        $this->updateMinimumOrderQuantity($product, $wcProduct);
    }

    /**
     * @param ProductModel $product
     * @param \WC_Product $wcProduct
     */
    protected function updateMinimumOrderQuantity(ProductModel $product, \WC_Product $wcProduct)
    {
        $groupController = new CustomerGroup();

        $updatedEndpoints = [];

        foreach ($product->getCustomerGroupPackagingQuantities() as $customerGroup) {
            $groupSlug = $groupController->getSlugById($customerGroup->getCustomerGroupId()->getEndpoint());
            if ($groupSlug !== false) {
                $this->updateMinimumQuantityMetaFields($customerGroup, $wcProduct, $groupSlug);
                $updatedEndpoints[] = $customerGroup->getCustomerGroupId()->getEndpoint();
            }
        }

        $customerGroups = $groupController->pullData();
        foreach ($customerGroups as $customerGroup) {
            $customerGroupId = $customerGroup->getId()->getEndpoint();

            if (in_array($customerGroupId, $updatedEndpoints)) {
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
     * @param \WC_Product $wcProduct
     * @param $groupSlug
     */
    protected function updateMinimumQuantityMetaFields($quantityObject, \WC_Product $wcProduct, $groupSlug)
    {
        $minQuantityKey = sprintf("bm_%s_min_quantity", $groupSlug);
        \update_post_meta(
            $wcProduct->get_id(),
            $minQuantityKey,
            (float)$quantityObject->getMinimumOrderQuantity(),
            \get_post_meta($wcProduct->get_id(), $minQuantityKey, true)
        );
        $stepQuantityKey = sprintf("bm_%s_step_quantity", $groupSlug);
        \update_post_meta(
            $wcProduct->get_id(),
            $stepQuantityKey,
            (float)$quantityObject->getPackagingQuantity(),
            \get_post_meta($wcProduct->get_id(), $stepQuantityKey, true)
        );
    }

    /**
     * @param ProductModel $product
     * @param \WC_Product $wcProduct
     */
    private function updateRRP(ProductModel $product, \WC_Product $wcProduct)
    {
        $rrp = $product->getRecommendedRetailPrice();
        $rrp = round($rrp, 4);
        $oldValue = (float) \get_post_meta($wcProduct->get_id(), 'bm_rrp', true);
        if ($rrp !== 0.) {
            if ($rrp !== $oldValue) {
                if ($product->getMasterProductId()->getHost() !== 0) {
                    $vKey = sprintf('bm_%s_rrp', $wcProduct->get_id());
                    \update_post_meta(
                        $wcProduct->get_parent_id(),
                        $vKey,
                        $rrp,
                        \get_post_meta($wcProduct->get_parent_id(), $vKey, true)
                    );
                }else {
                    \update_post_meta(
                        $wcProduct->get_id(),
                        'bm_rrp',
                        $rrp,
                        \get_post_meta($wcProduct->get_id(), 'bm_rrp', true)
                    );
                }
            }
        } else {
            if ($product->getMasterProductId()->getHost() !== 0) {
                $vKey = sprintf('bm_%s_rrp', $wcProduct->get_id());
                \delete_post_meta($wcProduct->get_parent_id(), $vKey);
            } else {
                \delete_post_meta($wcProduct->get_id(), 'bm_rrp');
            }
        }
    }
}
