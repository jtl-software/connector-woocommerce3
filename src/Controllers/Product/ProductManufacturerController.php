<?php

declare(strict_types=1);

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

            // If endpoint ID is empty, try to look it up from the link table using host ID
            if ($manufacturerId === '') {
                $hostId = $product->getManufacturerId()->getHost();
                if ($hostId > 0) {
                    $manufacturerId = $this->getManufacturerEndpointId($hostId);
                }
            }

            $this->removeManufacturerTerm($productId);

            if ($manufacturerId === '') {
                return;
            }

            $term = \get_term_by('id', $manufacturerId, 'pwb-brand');
            if ($term instanceof \WP_Term) {
                \wp_set_object_terms((int)$productId, $term->term_id, $term->taxonomy, true);
            }
        } else {
            $this->removeManufacturerTerm($productId);
        }
    }

    /**
     * Look up the manufacturer endpoint ID from the link table using the host ID.
     *
     * @param int $hostId
     * @return string
     */
    private function getManufacturerEndpointId(int $hostId): string
    {
        global $wpdb;
        $tableName = $wpdb->prefix . 'jtl_connector_link_manufacturer';

        $endpointId = $this->db->queryOne(
            "SELECT endpoint_id FROM {$tableName} WHERE host_id = {$hostId}"
        );

        return $endpointId !== null ? (string)$endpointId : '';
    }

    /**
     * @param string $productId
     * @return void
     */
    private function removeManufacturerTerm(string $productId): void
    {
        $terms = \wp_get_object_terms((int)$productId, 'pwb-brand');

        if (\is_array($terms) && \count($terms) > 0) {
            /** @var \WP_Term $term */
            foreach ($terms as $key => $term) {
                if ($term instanceof \WP_Term) {
                    \wp_remove_object_terms((int)$productId, $term->term_id, 'pwb-brand');
                }
            }
        }
    }

    /**
     * @param ProductModel $model
     * @return Identity|null
     * @throws \InvalidArgumentException
     */
    public function pullData(ProductModel $model): ?Identity
    {
        $productId      = $model->getId()->getEndpoint();
        $manufacturerId = null;
        if (SupportedPlugins::isPerfectWooCommerceBrandsActive()) {
            $terms = \wp_get_object_terms((int)$productId, 'pwb-brand');
        } elseif (SupportedPlugins::isGermanizedActive()) {
            $terms = \wp_get_object_terms((int)$productId, 'product_manufacturer');
        } else {
            $terms = \wp_get_object_terms((int)$productId, 'product_brand');
        }

        if (!\is_array($terms)) {
            throw new \InvalidArgumentException(
                'Array type expected. Got ' . \gettype($terms) . ' instead.'
            );
        }

        if (\count($terms) > 0) {
            /** @var \WP_Term $term */
            $term           = $terms[0];
            $manufacturerId = (new Identity())->setEndpoint((string)$term->term_id);
        }

        return $manufacturerId;
    }
}
