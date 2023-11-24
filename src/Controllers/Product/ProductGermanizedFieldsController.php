<?php

/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Product;

use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\Product as ProductModel;
use Jtl\Connector\Core\Model\TranslatableAttribute;
use Jtl\Connector\Core\Model\TranslatableAttributeI18n as ProductAttrI18nModel;
use JtlWooCommerceConnector\Controllers\AbstractBaseController;
use JtlWooCommerceConnector\Utilities\Germanized;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;

/**
 * Class ProductGermanizedFields
 *
 * @package JtlWooCommerceConnector\Controllers\Product
 */
class ProductGermanizedFieldsController extends AbstractBaseController
{
    /**
     * @param ProductModel $product
     * @param \WC_Product $wcProduct
     * @return void
     * @throws \InvalidArgumentException
     */
    public function pullData(ProductModel &$product, \WC_Product $wcProduct): void
    {
        $this->setGermanizedAttributes($product, $wcProduct);
    }

    /**
     * @param ProductModel $product
     * @param \WC_Product $wcProduct
     * @return void
     * @throws \InvalidArgumentException
     */
    private function setGermanizedAttributes(ProductModel &$product, \WC_Product $wcProduct): void
    {
        $units           = new \WC_GZD_Units();
        $germanizedUtils = (new Germanized());
        if ($germanizedUtils->hasUnitProduct($wcProduct)) {
            $plugin = \get_plugin_data(\WP_PLUGIN_DIR . '/woocommerce-germanized/woocommerce-germanized.php');

            if (isset($plugin['Version']) && \version_compare($plugin['Version'], '1.6.0') < 0) {
                $unitObject = $units->get_unit_object($wcProduct->gzd_product->unit);
            } else {
                $unit       = $germanizedUtils->getUnit($wcProduct);
                $unitObject = \get_term_by('slug', $unit, 'product_unit');
            }

            $code            = $germanizedUtils->parseUnit($unitObject->slug);
            $productQuantity = (double)$germanizedUtils->getUnitProduct($wcProduct);
            $product->setMeasurementQuantity($productQuantity);
            $product->setMeasurementUnitId(new Identity($unitObject->term_id));
            $product->setMeasurementUnitCode($code);

            $product->setConsiderBasePrice(true);
            $baseQuantity = (double)$germanizedUtils->getUnitBase($wcProduct);

            if ($baseQuantity !== 0.0) {
                $product->setBasePriceDivisor($productQuantity / $baseQuantity);
            }

            $product->setBasePriceQuantity($baseQuantity);
            $product->setBasePriceUnitId(new Identity($unitObject->term_id));
            $product->setBasePriceUnitCode($code);
            $product->setBasePriceUnitName($unitObject->name);
        }

        if ($wcProduct->get_meta('_is_food') === 'yes') {
            $foodMetaKey = $this->getGermanizedProFoodMetaKeys();

            foreach ($wcProduct->get_meta_data() as $metaData) {
                if (
                    \in_array($metaKey = $metaData->get_data()['key'], $foodMetaKey)
                    && !empty($metaValue = $metaData->get_data()['value'])
                ) {
                    $metaKey = 'wc_gzd' . $metaKey;

                    $i18n = (new ProductAttrI18nModel())
                        ->setName($metaKey)
                        ->setValue($metaValue)
                        ->setLanguageIso($this->util->getWooCommerceLanguage());

                    $attribute = (new TranslatableAttribute())
                        ->setId(new Identity($product->getId()->getEndpoint() . '_' . $metaKey))
                        ->setI18ns($i18n);

                    $product->addAttribute($attribute);
                }
            }
        }
    }

    /**
     * @param ProductModel $product
     * @return void
     */
    public function pushData(ProductModel $product): void
    {
        $this->updateGermanizedAttributes($product);
    }

    /**
     * @param ProductModel $product
     * @return void
     */
    private function updateGermanizedAttributes(ProductModel &$product): void
    {
        $id = $product->getId()->getEndpoint();

        \update_post_meta($id, '_ts_mpn', (string)$product->getManufacturerNumber());

        $this->updateGermanizedBasePriceAndUnits($product, $id);

        if ($this->isGermanizedProFoodProduct($product)) {
            $this->updateGermanizedProFoodProductData($product);
        }
    }

    /**
     * @param ProductModel $product
     * @param $id
     * @return void
     */
    private function updateGermanizedBasePriceAndUnits(ProductModel $product, $id): void
    {
        if ($product->getConsiderBasePrice()) {
            $pd = Util::getPriceDecimals();

            \update_post_meta($id, '_unit_base', $product->getBasePriceQuantity());

            if ($product->getBasePriceDivisor() != 0) {
                $divisor      = $product->getBasePriceDivisor();
                $currentPrice = (float)\get_post_meta($id, '_price', true);
                $basePrice    = \round($currentPrice / $divisor, $pd);

                \update_post_meta($id, '_unit_price', (float)$basePrice);
                \update_post_meta($id, '_unit_price_regular', (float)$basePrice);
            }

            $salePrice = \get_post_meta($id, '_sale_price', true);

            if (! empty($salePrice)) {
                if ($product->getBasePriceDivisor() !== 0) {
                    $unitSale = \round((float)$salePrice / $product->getBasePriceDivisor(), $pd);

                    \update_post_meta($id, '_unit_price_sale', (float)$unitSale);

                    if (\get_post_meta($id, '_price', true) === $salePrice) {
                        \update_post_meta($id, '_unit_price', (float)$unitSale);
                    }
                }
            }


            \update_post_meta($id, '_unit', $product->getBasePriceUnitName());

            if ($product->getMeasurementQuantity() !== 0) {
                \update_post_meta($id, '_unit_product', $product->getMeasurementQuantity());
            }
        } else {
            \delete_post_meta($id, '_unit_product');
            \delete_post_meta($id, '_unit_price');
            \delete_post_meta($id, '_unit_price_sale');
            \delete_post_meta($id, '_unit_price_regular');
            \delete_post_meta($id, '_unit_base');
        }
    }

    private function updateGermanizedProFoodProductData($product)
    {
        $id          = $product->getId()->getEndpoint();
        $foodMetaKey = $this->getGermanizedProFoodMetaKeys();

        foreach ($product->getAttributes() as $attribute) {
            foreach ($attribute->getI18ns() as $i18n) {
                if (
                    $this->util->isWooCommerceLanguage($i18n->getLanguageIso())
                    && \in_array($metaKey = \str_replace('wc_gzd', '', $i18n->getName()), $foodMetaKey)
                ) {
                    if (empty($metaValue = $i18n->getValue())) {
                        \delete_post_meta($id, $metaKey);
                        continue;
                    }

                    if ($metaKey == '_nutrient_ids' || $metaKey == '_allergen_ids') {
                        $metaValue = \json_decode($metaValue, true);
                    }

                    \update_post_meta($id, $metaKey, $metaValue);
                }
            }
        }
    }

    private function getGermanizedProFoodMetaKeys(): array
    {
        return [
            '_is_food',
            '_deposit_type',
            '_deposit_quantity',
            '_net_filling_quantity',
            '_drained_weight',
            '_alcohol_content',
            '_nutri_score',
            '_allergen_ids',
            '_ingredients',
            '_food_description',
            '_food_distributor',
            '_food_place_of_origin',
            '_nutrient_reference_value',
            '_nutrient_135',
            '_nutrient_136',
            '_nutrient_137',
            '_nutrient_138',
            '_nutrient_139',
            '_nutrient_140',
            '_nutrient_141',
            '_nutrient_142',
            '_nutrient_143',
            '_nutrient_144',
            '_nutrient_145',
            '_nutrient_146',
            '_nutrient_147',
            '_nutrient_148',
            '_nutrient_ids',
        ];
    }

    private function isGermanizedProFoodProduct($product): bool
    {
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO)) {
            foreach ($product->getAttributes() as $attribute) {
                foreach ($attribute->getI18ns() as $i18n) {
                    if ($i18n->getName() === 'wc_gzd_is_food') {
                        return true;
                    }
                }
            }
        }
        return false;
    }
}
