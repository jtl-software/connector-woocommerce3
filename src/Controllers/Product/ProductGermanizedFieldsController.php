<?php

namespace JtlWooCommerceConnector\Controllers\Product;

use http\Exception\InvalidArgumentException;
use Jtl\Connector\Core\Exception\TranslatableAttributeException;
use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\Product as ProductModel;
use Jtl\Connector\Core\Model\ProductAttribute;
use Jtl\Connector\Core\Model\TranslatableAttributeI18n as ProductAttrI18nModel;
use JtlWooCommerceConnector\Controllers\AbstractBaseController;
use JtlWooCommerceConnector\Utilities\Germanized;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;
use WC_Product;

/**
 * Class ProductGermanizedFields
 *
 * @package JtlWooCommerceConnector\Controllers\Product
 */
class ProductGermanizedFieldsController extends AbstractBaseController
{
    /**
     * @param ProductModel $product
     * @param WC_Product $wcProduct
     * @return void
     * @throws InvalidArgumentException
     * @throws \InvalidArgumentException
     */
    public function pullData(ProductModel &$product, \WC_Product $wcProduct): void
    {
        $this->setGermanizedAttributes($product, $wcProduct);
    }

    /**
     * @param ProductModel $product
     * @param WC_Product $wcProduct
     * @return void
     * @throws \InvalidArgumentException
     * @throws TranslatableAttributeException
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
                $metaKey   = $metaData->get_data()['key'];
                $metaValue = $metaData->get_data()['value'];

                if (\in_array($metaKey, $foodMetaKey) && !empty($metaValue)) {
                    $this->setProductAttribute(
                        $product,
                        $metaValue,
                        'wc_gzd_pro' . $metaKey
                    );
                } elseif ($metaKey === '_allergen_ids' && !empty($metaValue)) {
                    $allergens = [];

                    foreach ($metaValue as $allergenId) {
                        $allergens[] = $this->getNutrientTermData($allergenId, 'getSlug');
                    }

                    $this->setProductAttribute(
                        $product,
                        \implode(',', $allergens),
                        'wc_gzd_pro_allergens'
                    );
                } elseif ($metaKey === '_nutrient_ids' && !empty($metaValue)) {
                    foreach ($metaData->get_data()['value'] as $nutrientId => $values) {
                        $nutrientSlug = $this->getNutrientTermData($nutrientId, 'getSlug');

                        $this->setProductAttribute(
                            $product,
                            $values['value'],
                            'wc_gzd_pro_' . $nutrientSlug
                        );

                        if (!empty($values['ref_value'])) {
                            $this->setProductAttribute(
                                $product,
                                $values['ref_value'],
                                'wc_gzd_pro_ref_' . $nutrientSlug
                            );
                        }
                    }
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
                if ($product->getBasePriceDivisor() !== 0.0) {
                    $unitSale = \round((float)$salePrice / $product->getBasePriceDivisor(), $pd);

                    \update_post_meta($id, '_unit_price_sale', (float)$unitSale);

                    if (\get_post_meta($id, '_price', true) === $salePrice) {
                        \update_post_meta($id, '_unit_price', (float)$unitSale);
                    }
                }
            }

            \update_post_meta($id, '_unit', $product->getBasePriceUnitName());

            if ($product->getMeasurementQuantity() !== 0.0) {
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

    private function updateGermanizedProFoodProductData($product): void
    {
        $id          = $product->getId()->getEndpoint();
        $foodMetaKey = $this->getGermanizedProFoodMetaKeys();
        $allergens   = [];
        $nutrients   = [];

        foreach ($product->getAttributes() as $attribute) {
            foreach ($attribute->getI18ns() as $i18n) {
                if (
                    $this->util->isWooCommerceLanguage($i18n->getLanguageIso())
                    && (
                    \in_array($metaKey = \str_replace('wc_gzd_pro', '', $i18n->getName()), $foodMetaKey)
                    )
                ) {
                    if (empty($metaValue = $i18n->getValue())) {
                        \delete_post_meta($id, $metaKey);
                        continue;
                    }

                    \update_post_meta($id, $metaKey, $metaValue);
                } elseif (
                    $this->util->isWooCommerceLanguage($i18n->getLanguageIso())
                    && \str_contains($i18n->getName(), 'wc_gzd_pro')
                ) {
                    $metaKey = \str_replace('wc_gzd_pro_', '', $i18n->getName());

                    if ($metaKey === 'allergens') {
                        foreach (\explode(',', $i18n->getValue()) as $allergen) {
                            $termId      = $this->getNutrientTermData($allergen, 'getTermId');
                            $allergens[] = $termId;
                        }
                    } elseif (\str_contains($metaKey, 'ref')) {
                        $metaKey = \str_replace('ref_', '', $metaKey);
                        $termId  = $this->getNutrientTermData($metaKey, 'getTermId');
                        if (!\array_key_exists($termId, $nutrients)) {
                            $nutrients[$termId] = [
                                'value' => '',
                            ];
                        }
                        $nutrients[$termId]['ref_value'] = $i18n->getValue();
                    } else {
                        $termId                      = $this->getNutrientTermData($metaKey, 'getTermId');
                        $nutrients[$termId]['value'] = $i18n->getValue();
                    }
                }
            }
        }
        \update_post_meta($id, '_allergen_ids', $allergens);
        \update_post_meta($id, '_nutrient_ids', $nutrients);
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
            '_ingredients',
            '_food_description',
            '_food_distributor',
            '_food_place_of_origin',
            '_nutrient_reference_value',
        ];
    }

    /**
     * @param $product ProductModel
     * @param $value
     * @param $wawiAttributeKey
     * @return void
     * @throws TranslatableAttributeException
     * @throws \JsonException
     */
    private function setProductAttribute($product, $value, $wawiAttributeKey): void
    {
        $i18n = (new ProductAttrI18nModel())
            ->setName($wawiAttributeKey)
            ->setValue($value)
            ->setLanguageIso($this->util->getWooCommerceLanguage());

        $attribute = (new ProductAttribute())
            ->setId(new Identity($product->getId()->getEndpoint() . '_' . $wawiAttributeKey))
            ->setI18ns($i18n);

        $product->addAttribute($attribute);
    }

    private function isGermanizedProFoodProduct($product): bool
    {
        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO)) {
            foreach ($product->getAttributes() as $attribute) {
                foreach ($attribute->getI18ns() as $i18n) {
                    if ($i18n->getName() === 'wc_gzd_pro_is_food' && $i18n->getValue() == 'yes') {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Psr\Log\InvalidArgumentException
     */
    private function getNutrientTermData($nutrientData, $flag): string
    {
        if (!\in_array($flag, ['getSlug', 'getTermId'])) {
            throw new InvalidArgumentException('Invalid nutrient flag argument');
        }

        $tableName = $this->db->getWpDb()->prefix . 'terms';

        $selectColumn = $flag == 'getSlug' ? 'slug' : 'term_id';
        $whereColumn  = $selectColumn == 'slug' ? 'term_id' : 'slug';

        return $this->db->queryOne(
            \sprintf('SELECT %s FROM %s WHERE %s = \'%s\'', $selectColumn, $tableName, $whereColumn, $nutrientData)
        );
    }
}
