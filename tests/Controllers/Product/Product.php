<?php

namespace JtlWooCommerceConnector\Tests\Controllers\Product;

use phpmock\MockBuilder;
use phpmock\MockEnabledException;
use PHPUnit\Framework\TestCase;
use \jtl\Connector\Model\Product as JtlProduct;

/**
 * Class Product
 * @package JtlWooCommerceConnector\Tests\Controllers\Product
 */
class Product extends TestCase
{
    /**
     * @dataProvider getProductTypeDataProvider
     * @param array $objectTerms
     * @param array $getProductTypesFunction
     * @param string $expectedType
     * @param string $postType
     * @param JtlProduct|null $model
     * @throws MockEnabledException
     */
    public function testGetProductType(
        array $objectTerms,
        array $getProductTypesFunction,
        string $expectedType,
        string $postType = 'product',
        ?JtlProduct $model = null
    ) {
        if($model === null){
            $model = new JtlProduct();
        }

        $builder = new MockBuilder();
        $getObjectTermsFunction = $builder->setNamespace('JtlWooCommerceConnector\Controllers\Product')
            ->setName('wc_get_object_terms')
            ->setFunction(function () use ($objectTerms) {
                return $objectTerms;
            })->build();
        $getObjectTermsFunction->enable();

        $postTypeFunction = $builder->setNamespace('JtlWooCommerceConnector\Controllers\Product')
            ->setName('get_post_field')
            ->setFunction(function () use ($postType) {
                return $postType;
            })->build();
        $postTypeFunction->enable();

        $getProductTypesFunction = $builder->setNamespace('JtlWooCommerceConnector\Controllers\Product')
            ->setName('wc_get_product_types')
            ->setFunction(function () use ($getProductTypesFunction) {
                return $getProductTypesFunction;
            })->build();
        $getProductTypesFunction->enable();

        $productController = new \JtlWooCommerceConnector\Controllers\Product\Product();
        $type = $productController->getType($model);

        $this->assertEquals($expectedType, $type);

        $getObjectTermsFunction->disable();
        $getProductTypesFunction->disable();
        $postTypeFunction->disable();
    }

    /**
     * @return array
     */
    public function getProductTypeDataProvider(): array
    {
        $defaultAllowedGroups = [
            'simple' => 'Simple',
            'grouped' => 'Grouped',
            'external' => 'External',
            'variable' => 'Variable'
        ];

        return [
            [[], [], 'simple'],
            [[], $defaultAllowedGroups, 'simple'],
            [[self::makeProductTypeTerm('grouped')], $defaultAllowedGroups, 'grouped'],
            [[self::makeProductTypeTerm('other')], $defaultAllowedGroups, 'simple'],
            [[self::makeProductTypeTerm('product_variation')], [], 'product_variation'],
            [
                [],
                [],
                'variable',
                'product',
                (new JtlProduct())->setVariations(['foo'])
            ],
        ];
    }

    /**
     * @param $type
     * @return \stdClass
     */
    protected static function makeProductTypeTerm($type)
    {
        $productTypeTerm = new \stdClass();
        $productTypeTerm->slug = $type;

        return $productTypeTerm;
    }
}