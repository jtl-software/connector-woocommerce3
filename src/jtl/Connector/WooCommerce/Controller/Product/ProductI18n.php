<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Controller\Product;

use jtl\Connector\Model\Product as ProductModel;
use jtl\Connector\Model\ProductI18n as ProductI18nModel;
use jtl\Connector\WooCommerce\Controller\BaseController;
use jtl\Connector\WooCommerce\Utility\Util;

class ProductI18n extends BaseController
{
    public function pullData(\WC_Product $product, $model)
    {
        $i18n = $this->mapper->toHost($product);

        if ($i18n instanceof ProductI18nModel) {
            $this->onProductI18nMapped($i18n, $product);
        }

        return [$i18n];
    }

    public function pushData(ProductModel $product, array &$model)
    {
        foreach ($product->getI18ns() as $i18n) {
            if (Util::getInstance()->isWooCommerceLanguage($i18n->getLanguageISO())) {
                $model = array_merge($model, $this->mapper->toEndpoint($i18n));
                break;
            }
        }
    }

    protected function onProductI18nMapped(ProductI18nModel &$i18n, \WC_Product $product)
    {
        if ($product instanceof \WC_Product_Variation) {
            switch (\get_option(\JtlConnectorAdmin::OPTIONS_VARIATION_NAME_FORMAT, '')) {
                case 'space':
                    $i18n->setName($i18n->getName() . ' ' . $product->get_formatted_variation_attributes(true));
                    break;
                case 'brackets':
                    $i18n->setName(sprintf('%s (%s)', $i18n->getName(), $product->get_formatted_variation_attributes(true)));
                    break;
                case 'space_parent':
                    $i18n->setName($product->parent->get_title() . ' ' . $product->get_formatted_variation_attributes(true));
                    break;
                case 'brackets_parent':
                    $i18n->setName(sprintf('%s (%s)', $product->parent->get_title(), $product->get_formatted_variation_attributes(true)));
                    break;
            }
        }
    }
}
