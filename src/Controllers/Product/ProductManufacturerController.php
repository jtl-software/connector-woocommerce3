<?php

namespace JtlWooCommerceConnector\Controllers\Product;

use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\Product as ProductModel;
use JtlWooCommerceConnector\Controllers\AbstractBaseController;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;

class ProductManufacturerController extends AbstractBaseController
{
    /**
     * @param ProductModel $product
     * @return void
     */
    public function pushData(ProductModel $product): void
    {
        $productId = $product->getId()->getEndpoint();

        if (SupportedPlugins::isPerfectWooCommerceBrandsActive()) {
            $manufacturerId = $product->getManufacturerId()->getEndpoint();
            $this->removeManufacturerTerm($productId);
            $term = \get_term_by('id', $manufacturerId, 'pwb-brand');
            if ($manufacturerId === '') {
                return;
            }
            if ($term instanceof \WP_Term) {
                \wp_set_object_terms($productId, $term->term_id, $term->taxonomy, true);
            }
        } else {
            $this->removeManufacturerTerm($productId);
        }
    }

    /**
     * @param string $productId
     * @return void
     */
    private function removeManufacturerTerm(string $productId): void
    {
        $terms = \wp_get_object_terms($productId, 'pwb-brand');

        if (\is_array($terms) && \count($terms) > 0) {
            /** @var \WP_Term $term */
            foreach ($terms as $key => $term) {
                if ($term instanceof \WP_Term) {
                    \wp_remove_object_terms($productId, $term->term_id, 'pwb-brand');
                }
            }
        }
    }

    /**
     * @param ProductModel $model
     * @return Identity|null
     */
    public function pullData(ProductModel $model): ?Identity
    {
        $productId      = $model->getId()->getEndpoint();
        $manufacturerId = null;
        if (SupportedPlugins::isPerfectWooCommerceBrandsActive()) {
            $terms = \wp_get_object_terms($productId, 'pwb-brand');

            if (\count($terms) > 0) {
                /** @var \WP_Term $term */
                $term           = $terms[0];
                $manufacturerId = (new Identity())->setEndpoint($term->term_id);
            }
        }

        return $manufacturerId;
    }
}
