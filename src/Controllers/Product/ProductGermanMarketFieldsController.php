<?php

namespace JtlWooCommerceConnector\Controllers\Product;

use InvalidArgumentException;
use Jtl\Connector\Core\Model\Product as ProductModel;
use JtlWooCommerceConnector\Controllers\AbstractBaseController;
use PhpUnitsOfMeasure\Exception\NonNumericValue;
use PhpUnitsOfMeasure\Exception\NonStringUnitName;
use PhpUnitsOfMeasure\PhysicalQuantity\Area;
use PhpUnitsOfMeasure\PhysicalQuantity\Length;
use PhpUnitsOfMeasure\PhysicalQuantity\Mass;
use PhpUnitsOfMeasure\PhysicalQuantity\Volume;
use WC_Product;

class ProductGermanMarketFieldsController extends AbstractBaseController
{
    /**
     * @param ProductModel $product
     * @param WC_Product $wcProduct
     * @return void
     * @throws InvalidArgumentException
     */
    public function pullData(ProductModel &$product, WC_Product $wcProduct): void
    {
        $this->setBasePriceProperties($product, $wcProduct);
    }

    /**
     * @param ProductModel $product
     * @param WC_Product $wcProduct
     * @return void
     * @throws InvalidArgumentException
     */
    private function setBasePriceProperties(ProductModel $product, WC_Product $wcProduct): void
    {
        $metaKeys = $this->getGermanMarketMetaKeys($product->getMasterProductId()->getHost() === 0);

        if ($this->hasGermanMarketUnitPrice($wcProduct, $metaKeys)) {
            $metaData = $this->getGermanMarketMeta($wcProduct, $metaKeys);

            $basePriceDivisor
                = $metaData[$metaKeys['unitRegularAutoPPUProductQuantity']]
                / $metaData[$metaKeys['unitRegularMultiplikatorKey']];
            $basePriceFactor  = $metaData[$metaKeys['priceKey']] / $basePriceDivisor;

            $product
                ->setConsiderBasePrice(true)
                ->setMeasurementQuantity((float)$metaData[$metaKeys['unitRegularAutoPPUProductQuantity']])
                ->setMeasurementUnitCode($metaData[$metaKeys['unitRegularUnitKey']])
                ->setBasePriceQuantity((float)$metaData[$metaKeys['unitRegularMultiplikatorKey']])
                ->setBasePriceUnitCode($metaData[$metaKeys['unitRegularUnitKey']])
                ->setBasePriceUnitName($metaData[$metaKeys['unitRegularUnitKey']])
                ->setBasePriceDivisor($basePriceDivisor)
                ->setBasePriceFactor($basePriceFactor);
        }
    }

    /**
     * @param bool $isMaster
     * @return string[]
     */
    private function getGermanMarketMetaKeys(bool $isMaster = false): array
    {
        $result = [
            //Price
            'priceKey' => '_price',
            //meta keys vars
            'weightKey' => '_weight',
            'lengthKey' => '_length',
            'widthKey' => '_width',
            'heightKey' => '_height',
            'jtlwccStkKey' => '_jtlwcc_stk',
            'usedCustomPPUKey' => '_v_used_setting_ppu',
        ];

        $keys = [  //meta keys PPU vars
            'unitRegularUnitKey' => '_unit_regular_price_per_unit',
            'unitRegularMultiplikatorKey' => '_unit_regular_price_per_unit_mult',
            'unitRegularAutoPPUProductQuantity' => '_auto_ppu_complete_product_quantity',
        ];

        foreach ($keys as $key => $value) {
            if ($isMaster) {
                $result[$key] = $value;
            } else {
                $result[$key] = '_v' . $value;
            }
        }

        return $result;
    }

    /**
     * @param WC_Product $wcProduct
     * @param array $metaKeys
     * @return bool
     */
    private function hasGermanMarketUnitPrice(WC_Product $wcProduct, array $metaKeys): bool
    {
        $result = false;
        /** @var \WC_Meta_Data $meta */
        foreach ($wcProduct->get_meta_data() as $meta) {
            if ($result) {
                continue;
            }
            if (\count($meta->get_data()) > 0 && isset($meta->get_data()['key'])) {
                if ($meta->get_data()['key'] === $metaKeys['unitRegularMultiplikatorKey']) {
                    $value = \get_post_meta($wcProduct->get_id(), $metaKeys['unitRegularMultiplikatorKey'], true);
                    if (isset($value) && $value !== false) {
                        $value  = (float)$value;
                        $result = $value > 0.00;
                    };
                }
            }
        }

        return $result;
    }

    /**
     * @param WC_Product $wcProduct
     * @param array $metaKeys
     * @return array
     */
    private function getGermanMarketMeta(WC_Product $wcProduct, array $metaKeys): array
    {
        $result = [];

        foreach ($metaKeys as $metaKey => $meta) {
            $result[$meta] = \get_post_meta($wcProduct->get_id(), $meta, true);
        }

        return $result;
    }

    /**
     * @param string $metaIdent
     * @return string
     */
    private function identifyGermanMarketMetaGroup(string $metaIdent): string
    {
        $weight = [
            'mg',
            'g',
            'kg',
            'lbs',
            'lb',
            't',
            'oz',
        ];

        $surfaces = [
            'mm2',
            'cm2',
            'dm2',
            'm2',
            'km2',
        ];

        $length = [
            'mm',
            'cm',
            'dm',
            'm',
            'km',
            'in',
            'yd',
        ];

        $volumes = [
            'mm3',
            'cm3',
            'dm3',
            'm3',
            'dm3',
            'km3',
            'l',
            'ml',
            'cl',
            'dl',
        ];

        if (\in_array($metaIdent, $weight)) {
            return 'weight';
        } elseif (\in_array($metaIdent, $surfaces)) {
            return 'surface';
        } elseif (\in_array($metaIdent, $length)) {
            return 'length';
        } elseif (\in_array($metaIdent, $volumes)) {
            return 'volume';
        } else {
            return 'standard';
        }
    }

    /**
     * @param ProductModel $product
     * @return void
     * @throws NonNumericValue
     * @throws NonStringUnitName
     */
    public function pushData(ProductModel $product): void
    {
        $this->updateGermanMarketPPU($product);
    }

    /**
     * @param ProductModel $product
     * @return void
     * @throws NonNumericValue
     * @throws NonStringUnitName
     * @throws \Exception
     */
    private function updateGermanMarketPPU(ProductModel $product): void
    {
        $metaKeys = $this->getGermanMarketMetaKeys($product->getMasterProductId()->getHost() === 0);

        if ($product->getConsiderBasePrice()) {
            if ($product->getBasePriceQuantity() === 0.0 || $product->getMeasurementQuantity() === 0.0) {
                throw new \Exception(
                    'basePriceQuantity or measurementQuantity cannot be 0 when Base price calculation is active'
                );
            }

            $productId = $product->getId()->getEndpoint();
            $metaData  = $this->getGermanMarketMeta(\wc_get_product($productId), $metaKeys);

            $basePriceUnitCode = \strtolower($product->getBasePriceUnitCode());
            $basePriceQuantity = $product->getBasePriceQuantity();

            $measurementUnitCode = \strtolower($product->getMeasurementUnitCode());
            $measurementQuantity = $product->getMeasurementQuantity();

            $ppuType = $this->identifyGermanMarketMetaGroup($basePriceUnitCode);

            $basePrice    = null;
            $currentPrice = \get_post_meta($productId, '_price', true);
            $baseUnit     = null;

            switch ($ppuType) {
                case 'weight':
                    if ($basePriceUnitCode !== $measurementUnitCode) {
                        $mass                = new Mass($measurementQuantity, $measurementUnitCode);
                        $measurementQuantity = $mass->toUnit($basePriceUnitCode);
                    }

                    $divisor = $measurementQuantity / $basePriceQuantity;

                    $basePrice = $currentPrice / $divisor;
                    $baseUnit  = $basePriceUnitCode;
                    break;
                case 'length':
                    if ($basePriceUnitCode !== $measurementUnitCode) {
                        $length              = new Length($measurementQuantity, $measurementUnitCode);
                        $measurementQuantity = $length->toUnit($basePriceUnitCode);
                    }

                    $divisor = $measurementQuantity / $basePriceQuantity;

                    $basePrice = $currentPrice / $divisor;
                    $baseUnit  = $basePriceUnitCode;
                    break;
                case 'volume':
                    if ($basePriceUnitCode !== $measurementUnitCode) {
                        $vol                 = new Volume($measurementQuantity, $measurementUnitCode);
                        $measurementQuantity = $vol->toUnit($basePriceUnitCode);
                    }

                    $divisor = $measurementQuantity / $basePriceQuantity;

                    $basePrice = $currentPrice / $divisor;
                    $baseUnit  = $basePriceUnitCode;

                    //German Market expects capital letter only for liter
                    if ($baseUnit === 'l') {
                        $baseUnit = \strtoupper($baseUnit);
                    }
                    break;
                case 'surface':
                    $basePriceUnitCode   = \str_replace('2', '^2', $basePriceUnitCode);
                    $measurementUnitCode = \str_replace('2', '^2', $measurementUnitCode);

                    if ($basePriceUnitCode !== $measurementUnitCode) {
                        $sur                 = new Area($measurementQuantity, $measurementUnitCode);
                        $measurementQuantity = $sur->toUnit($basePriceUnitCode);
                    }

                    $divisor = $measurementQuantity / $basePriceQuantity;

                    $basePrice = $currentPrice / $divisor;
                    $baseUnit  = \str_replace('^2', '²', $basePriceUnitCode);
                    break;
                case 'standard':
                    $divisor = $measurementQuantity / $basePriceQuantity;

                    $basePrice = $currentPrice / $divisor;
                    $baseUnit  = 'Stück';
                    break;
                default:
                    $this->clearPPU($product, $metaKeys);
                    break;
            }

            if (\is_null($basePrice) || \is_null($baseUnit)) {
                $this->clearPPU($product, $metaKeys);
            } else {
                $unitCodeKey                       = $metaKeys['unitRegularUnitKey'];
                $unitMultiplikatorKey              = $metaKeys['unitRegularMultiplikatorKey'];
                $unitRegularAutoPPUProductQuantity = $metaKeys['unitRegularAutoPPUProductQuantity'];
                $usedCustomPPU                     = $metaKeys['usedCustomPPUKey'];

                \update_post_meta(
                    $productId,
                    $unitCodeKey,
                    $baseUnit,
                    $metaData[$unitCodeKey]
                );
                \update_post_meta(
                    $productId,
                    $unitMultiplikatorKey,
                    $basePriceQuantity,
                    $metaData[$unitMultiplikatorKey]
                );
                \update_post_meta(
                    $productId,
                    $unitRegularAutoPPUProductQuantity,
                    $measurementQuantity,
                    $metaData[$unitRegularAutoPPUProductQuantity]
                );
                \update_post_meta(
                    $productId,
                    $usedCustomPPU,
                    1,
                    $metaData[$usedCustomPPU]
                );
            }
        } else {
            $this->clearPPU($product, $metaKeys);
        }
    }

    /**
     * @param ProductModel $product
     * @param array $metaKeys
     * @return void
     */
    private function clearPPU(ProductModel $product, array $metaKeys): void
    {
        $productId = $product->getId()->getEndpoint();
        $metaData  = $this->getGermanMarketMeta(
            \wc_get_product($productId),
            $metaKeys
        );
        \update_post_meta(
            $productId,
            $metaKeys['unitRegularUnitKey'],
            '',
            $metaData[$metaKeys['unitRegularUnitKey']]
        );
        \update_post_meta(
            $productId,
            $metaKeys['unitRegularMultiplikatorKey'],
            '',
            $metaData[$metaKeys['unitRegularMultiplikatorKey']]
        );
        \update_post_meta(
            $productId,
            $metaKeys['unitRegularAutoPPUProductQuantity'],
            '',
            $metaData[$metaKeys['unitRegularAutoPPUProductQuantity']]
        );
        \update_post_meta(
            $productId,
            $metaKeys['usedCustomPPUKey'],
            0,
            $metaData[$metaKeys['usedCustomPPUKey']]
        );
    }
}
