<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Controllers\Product;

use Jtl\Connector\Core\Exception\TranslatableAttributeException;
use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\Product as ProductModel;
use Jtl\Connector\Core\Model\ProductAttribute;
use Jtl\Connector\Core\Model\TranslatableAttributeI18n as ProductAttrI18nModel;
use JtlWooCommerceConnector\Controllers\AbstractBaseController;
use JtlWooCommerceConnector\Utilities\Germanized;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\TaxonomyOverride;
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
     * @param WC_Product   $wcProduct
     * @return void
     * @throws \InvalidArgumentException
     */
    public function pullData(ProductModel &$product, WC_Product $wcProduct): void
    {
        $this->setGermanizedAttributes($product, $wcProduct);
    }

    /**
     * @param ProductModel $product
     * @param WC_Product   $wcProduct
     * @return void
     * @throws \InvalidArgumentException
     * @throws TranslatableAttributeException
     * @throws \JsonException
     */
    private function setGermanizedAttributes(ProductModel &$product, WC_Product $wcProduct): void
    {
        $units           = new \WC_GZD_Units();
        $germanizedUtils = (new Germanized());
        if ($germanizedUtils->hasUnitProduct($wcProduct)) {
            $plugin = \get_plugin_data(\WP_PLUGIN_DIR . '/woocommerce-germanized/woocommerce-germanized.php');

            if (\version_compare($plugin['Version'], '1.6.0') < 0) {
                $unitObject = $units->get_unit_object($wcProduct->gzd_product->unit); /** @phpstan-ignore-line */
            } else {
                $unit       = $germanizedUtils->getUnit($wcProduct);
                $unitObject = \get_term_by('slug', (string)$unit, 'product_unit');
            }

            $code            = $germanizedUtils->parseUnit($unitObject->slug);
            $productQuantity = (double)$germanizedUtils->getUnitProduct($wcProduct);
            $product->setMeasurementQuantity($productQuantity);
            $product->setMeasurementUnitId(new Identity((string)$unitObject->term_id));
            $product->setMeasurementUnitCode($code);

            $product->setConsiderBasePrice(true);
            $baseQuantity = (double)$germanizedUtils->getUnitBase($wcProduct);

            if ($baseQuantity !== 0.0) {
                $product->setBasePriceDivisor($productQuantity / $baseQuantity);
            }

            $product->setBasePriceQuantity($baseQuantity);
            $product->setBasePriceUnitId(new Identity((string)$unitObject->term_id));
            $product->setBasePriceUnitCode($code);
            $product->setBasePriceUnitName($unitObject->name);
        }

        if ($wcProduct->get_meta('_is_food') === 'yes') {
            $foodMetaKey = $this->getGermanizedProFoodMetaKeys();

            foreach ($wcProduct->get_meta_data() as $metaData) {
                $metaKey = $metaData->get_data()['key'];
                /** @var array<int, int|string> $metaValue */
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
                    /** @var array<string, string> $values */
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
     * @throws TranslatableAttributeException
     */
    public function pushData(ProductModel $product): void
    {
        $this->updateGermanizedAttributes($product);
    }

    /**
     * @param ProductModel $product
     * @return void
     * @throws TranslatableAttributeException
     */
    private function updateGermanizedAttributes(ProductModel &$product): void
    {
        $id = $product->getId()->getEndpoint();

        \update_post_meta((int)$id, '_ts_mpn', (string)$product->getManufacturerNumber());

        $this->updateGermanizedBasePriceAndUnits($product, (int)$id);
        $this->updateGermanizedGpsrData($product);

        if ($this->isGermanizedProFoodProduct($product)) {
            $this->updateGermanizedProFoodProductData($product);
        }
    }

    /**
     * @param ProductModel $product
     * @param int          $id
     * @return void
     */
    private function updateGermanizedBasePriceAndUnits(ProductModel $product, int $id): void
    {
        if ($product->getConsiderBasePrice()) {
            $pd = Util::getPriceDecimals();

            \update_post_meta($id, '_unit_base', $product->getBasePriceQuantity());

            if ($product->getBasePriceDivisor() != 0) {
                $divisor = $product->getBasePriceDivisor();
                /** @var false|string $currentPrice */
                $currentPrice = \get_post_meta($id, '_price', true);
                $basePrice    = \round((float)$currentPrice / $divisor, $pd);

                \update_post_meta($id, '_unit_price', (float)$basePrice);
                \update_post_meta($id, '_unit_price_regular', (float)$basePrice);
            }
            /** @var false|string $salePrice */
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

    /**
     * @param ProductModel $product
     * @return void
     * @throws \InvalidArgumentException
     * @throws TranslatableAttributeException
     * @throws \Psr\Log\InvalidArgumentException
     */
    private function updateGermanizedProFoodProductData(ProductModel $product): void
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
                        \delete_post_meta((int)$id, $metaKey);
                        continue;
                    }

                    \update_post_meta((int)$id, $metaKey, $metaValue);
                } elseif (
                    $this->util->isWooCommerceLanguage($i18n->getLanguageIso())
                    && \str_contains($i18n->getName(), 'wc_gzd_pro')
                ) {
                    $metaKey = \str_replace('wc_gzd_pro_', '', $i18n->getName());

                    if ($metaKey === 'allergens') {
                        /** @var string $i18nValue */
                        $i18nValue = $i18n->getValue();
                        foreach (\explode(',', $i18nValue) as $allergen) {
                            $termId      = $this->getNutrientTermData($allergen, 'getTermId');
                            $allergens[] = $termId;
                        }
                    } elseif (\str_contains($metaKey, 'ref')) {
                        $metaKey = \str_replace('ref_', '', $metaKey);
                        $termId  = $this->getNutrientTermData($metaKey, 'getTermId') ?? '';
                        if ($termId !== '' && !\array_key_exists($termId, $nutrients)) {
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
        \update_post_meta((int)$id, '_allergen_ids', $allergens);
        \update_post_meta((int)$id, '_nutrient_ids', $nutrients);
    }

    /**
     * @return string[]
     */
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
     * @param ProductModel $product
     * @return void
     * @throws TranslatableAttributeException
     */
    private function updateGermanizedGpsrData(ProductModel $product): void
    {
        $gpsrManufacturerName      = '';
        $gpsrManufacturerTitleform = '';

        $manufacturerData = [
            'name' => '',
            'street' => '',
            'housenumber' => '',
            'postalcode' => '',
            'city' => '',
            'state' => '',
            'country' => '',
            'email' => '',
            'homepage' => ''
        ];

        $responsiblePersonData = [
            'name' => '',
            'street' => '',
            'housenumber' => '',
            'postalcode' => '',
            'city' => '',
            'state' => '',
            'country' => '',
            'email' => '',
            'homepage' => ''
        ];

        foreach ($product->getAttributes() as $attribute) {
            foreach ($attribute->getI18ns() as $i18n) {
                if ($this->util->isWooCommerceLanguage($i18n->getLanguageIso())) {
                    switch ($i18n->getName()) {
                        case 'gpsr_manufacturer_name':
                            /** @var string $gpsrManufacturerName */
                            $gpsrManufacturerName      = $i18n->getValue();
                            $manufacturerData['name']  = $gpsrManufacturerName;
                            $gpsrManufacturerTitleform = \strtolower(
                                \str_replace(' ', '', $gpsrManufacturerName)
                            ) . '-gpsr';
                            break;
                        case 'gpsr_manufacturer_street':
                            $manufacturerData['street'] = $i18n->getValue();
                            break;
                        case 'gpsr_manufacturer_housenumber':
                            $manufacturerData['housenumber'] = $i18n->getValue();
                            break;
                        case 'gpsr_manufacturer_postalcode':
                            $manufacturerData['postalcode'] = $i18n->getValue();
                            break;
                        case 'gpsr_manufacturer_city':
                            $manufacturerData['city'] = $i18n->getValue();
                            break;
                        case 'gpsr_manufacturer_state':
                            $manufacturerData['state'] = $i18n->getValue();
                            break;
                        case 'gpsr_manufacturer_country':
                            $manufacturerData['country'] = $i18n->getValue();
                            break;
                        case 'gpsr_manufacturer_email':
                            $manufacturerData['email'] = $i18n->getValue();
                            break;
                        case 'gpsr_manufacturer_homepage':
                            $manufacturerData['homepage'] = $i18n->getValue();
                            break;
                        case 'gpsr_responsibleperson_name':
                            $responsiblePersonData['name'] = $i18n->getValue();
                            break;
                        case 'gpsr_responsibleperson_street':
                            $responsiblePersonData['street'] = $i18n->getValue();
                            break;
                        case 'gpsr_responsibleperson_housenumber':
                            $responsiblePersonData['housenumber'] = $i18n->getValue();
                            break;
                        case 'gpsr_responsibleperson_postalcode':
                            $responsiblePersonData['postalcode'] = $i18n->getValue();
                            break;
                        case 'gpsr_responsibleperson_city':
                            $responsiblePersonData['city'] = $i18n->getValue();
                            break;
                        case 'gpsr_responsibleperson_state':
                            $responsiblePersonData['state'] = $i18n->getValue();
                            break;
                        case 'gpsr_responsibleperson_country':
                            $responsiblePersonData['country'] = $i18n->getValue();
                            break;
                        case 'gpsr_responsibleperson_email':
                            $responsiblePersonData['email'] = $i18n->getValue();
                            break;
                        case 'gpsr_responsibleperson_homepage':
                            $responsiblePersonData['homepage'] = $i18n->getValue();
                            break;
                    }
                }
            }
        }

        if ($gpsrManufacturerName === '') {
            TaxonomyOverride::wp_delete_object_term_relationships(
                (int)$product->getId()->getEndpoint(),
                'product_manufacturer'
            );
            \update_post_meta((int)$product->getId()->getEndpoint(), '_manufacturer_slug', '');

            return;
        }

        /** @var \WP_Term|false $existingTerm */
        $existingTerm = \get_term_by('slug', $gpsrManufacturerTitleform, 'product_manufacturer');
        if (!$existingTerm) {
            /** @var array<string, int|string> $newTerm */
            $newTerm = \wp_insert_term(
                $gpsrManufacturerName,
                'product_manufacturer',
                [
                    'description' => '',
                    'slug' => $gpsrManufacturerTitleform,
                    ]
            );

            /** @var int $termId */
            $termId = $newTerm['term_id'];
        } else {
            $termId = $existingTerm->term_id;
        }

        /** @var array<string, string> $manufacturerData */
        /** @var array<string, string> $responsiblePersonData */
        $concatenatedAddresses        = $this->getConcatenatedAddresses($manufacturerData, $responsiblePersonData);
        $gpsrManufacturerAddress      = $concatenatedAddresses[0];
        $gpsrResponsiblePersonAddress = $concatenatedAddresses[1];

        if (!empty(\str_replace([' ', "\n"], '', $gpsrManufacturerAddress))) {
            \update_term_meta($termId, 'formatted_address', $gpsrManufacturerAddress);
        }

        if (!empty(\str_replace([' ', "\n"], '', $gpsrResponsiblePersonAddress))) {
            \update_term_meta($termId, 'formatted_eu_address', $gpsrResponsiblePersonAddress);
        }

        // remove existing product to gpsr manufacturer link
        TaxonomyOverride::wp_delete_object_term_relationships(
            (int)$product->getId()->getEndpoint(),
            'product_manufacturer'
        );

        // link product to gpsr manufacturer
        \wp_set_object_terms((int)$product->getId()->getEndpoint(), $termId, 'product_manufacturer');
        \update_post_meta((int)$product->getId()->getEndpoint(), '_manufacturer_slug', $gpsrManufacturerTitleform);
    }

    /**
     * @param ProductModel                  $product
     * @param array<int, int|string>|string $value
     * @param string                        $wawiAttributeKey
     * @return void
     * @throws TranslatableAttributeException
     * @throws \JsonException
     */
    private function setProductAttribute(ProductModel $product, array|string $value, string $wawiAttributeKey): void
    {
        $i18n = (new ProductAttrI18nModel())
            ->setName($wawiAttributeKey)
            ->setValue($value)
            ->setLanguageIso($this->util->getWooCommerceLanguage());

        /** @var ProductAttribute $attribute */
        $attribute = (new ProductAttribute())
            ->setId(new Identity($product->getId()->getEndpoint() . '_' . $wawiAttributeKey))
            ->setI18ns($i18n);

        $product->addAttribute($attribute);
    }

    /**
     * @param ProductModel $product
     * @return bool
     * @throws TranslatableAttributeException
     */
    private function isGermanizedProFoodProduct(ProductModel $product): bool
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
     * @param int|string $nutrientData
     * @param string     $flag
     * @return string|null
     * @throws \InvalidArgumentException
     * @throws \Psr\Log\InvalidArgumentException
     */
    private function getNutrientTermData(int|string $nutrientData, string $flag): ?string
    {
        if (!\in_array($flag, ['getSlug', 'getTermId'])) {
            throw new \InvalidArgumentException('Invalid nutrient flag argument');
        }

        $tableName = $this->db->getWpDb()->prefix . 'terms';

        $selectColumn = $flag == 'getSlug' ? 'slug' : 'term_id';
        $whereColumn  = $selectColumn == 'slug' ? 'term_id' : 'slug';

        return $this->db->queryOne(
            \sprintf(
                'SELECT %s FROM %s WHERE %s = \'%s\'',
                $selectColumn,
                $tableName,
                $whereColumn,
                \esc_sql((string)$nutrientData)
            )
        );
    }

    /**
     * @param string[] $manufacturerData
     * @param string[] $responsiblePersonData
     * @return string[]
     */
    public function getConcatenatedAddresses(array $manufacturerData, array $responsiblePersonData): array
    {
        $gpsrManufacturerAddress = $manufacturerData['name'] . "\n"
            . $manufacturerData['street'] . ' ' . $manufacturerData['housenumber'] . "\n"
            . $manufacturerData['postalcode'] . ' ' . $manufacturerData['city'] . "\n"
            . $manufacturerData['state'] . ' ' . $manufacturerData['country'] . "\n"
            . $manufacturerData['email'] . "\n"
            . $manufacturerData['homepage'];

        $gpsrResponsiblePersonAddress = $responsiblePersonData['name'] . "\n"
            . $responsiblePersonData['street'] . ' ' . $responsiblePersonData['housenumber'] . "\n"
            . $responsiblePersonData['postalcode'] . ' ' . $responsiblePersonData['city'] . "\n"
            . $responsiblePersonData['state'] . ' ' . $responsiblePersonData['country'] . "\n"
            . $responsiblePersonData['email'] . "\n"
            . $responsiblePersonData['homepage'];

        return [$gpsrManufacturerAddress, $gpsrResponsiblePersonAddress];
    }
}
