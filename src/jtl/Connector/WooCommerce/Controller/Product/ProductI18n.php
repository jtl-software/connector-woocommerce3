<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller\Product;

use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\Model\ProductI18n as ProductI18nModel;
use jtl\Connector\WooCommerce\Controller\BaseController;
use jtl\Connector\WooCommerce\Utility\Germanized;
use jtl\Connector\WooCommerce\Utility\Util;

class ProductI18n extends BaseController
{
    public function pullData(\WC_Product $product, ProductModel $model)
    {
        $i18n = (new ProductI18nModel())
            ->setProductId($model->getId())
            ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage())
            ->setName($this->name($product))
            ->setDescription($product->get_description())
            ->setShortDescription($product->get_short_description())
            ->setUrlPath($product->get_slug());

        if (Germanized::getInstance()->isActive() && $product->gzd_product->has_product_units()) {
            $i18n->setMeasurementUnitName($product->gzd_product->unit);
        }

        return $i18n;
    }

    private function name(\WC_Product $product)
    {
        if ($product instanceof \WC_Product_Variation) {
            switch (\get_option(\JtlConnectorAdmin::OPTIONS_VARIATION_NAME_FORMAT, '')) {
                case 'space':
                    return $product->get_name() . ' ' . \wc_get_formatted_variation($product, true);
                case 'brackets':
                    return sprintf('%s (%s)', $product->get_name(), \wc_get_formatted_variation($product, true));
                case 'space_parent':
                    $parent = \wc_get_product($product->get_parent_id());

                    return $parent->get_title() . ' ' . \wc_get_formatted_variation($product, true);
                case 'brackets_parent':
                    $parent = \wc_get_product($product->get_parent_id());

                    return sprintf('%s (%s)', $parent->get_title(), \wc_get_formatted_variation($product, true));
            }
        }

        return $product->get_name();
    }
}
