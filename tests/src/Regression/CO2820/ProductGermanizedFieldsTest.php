<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Regression\CO2820;

use InvalidArgumentException;
use Jtl\Connector\Core\Model\ProductImage;
use JtlWooCommerceConnector\Controllers\Product\ProductGermanizedFieldsController;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\Util;
use phpmock\MockBuilder;
use phpmock\MockEnabledException;
use PHPUnit\Framework\MockObject\ClassAlreadyExistsException;
use PHPUnit\Framework\MockObject\ClassIsFinalException;
use PHPUnit\Framework\MockObject\ClassIsReadonlyException;
use PHPUnit\Framework\MockObject\DuplicateMethodException;
use PHPUnit\Framework\MockObject\InvalidMethodNameException;
use PHPUnit\Framework\MockObject\OriginalConstructorInvocationRequiredException;
use PHPUnit\Framework\MockObject\RuntimeException;
use PHPUnit\Framework\MockObject\UnknownTypeException;
use PHPUnit\Framework\TestCase;

class ProductGermanizedFieldsTest extends TestCase
{
    protected \phpmock\Mock $getLocale;

    /**
     * @return void
     * @throws InvalidArgumentException
     * @throws MockEnabledException
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->getLocale = (new MockBuilder())->setNamespace('JtlWooCommerceConnector\Utilities')
            ->setName('get_locale')
            ->setFunction(function () {
                return 'de_DE';
            })->build();

        $this->getLocale->enable();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->getLocale->disable();
    }

    /**
     * @dataProvider gpsrDataProvider
     * @param array<string, string> $manufacturerData
     * @param array<string, string> $responsiblePersonData
     * @param array<string, string> $expectedResult
     * @return void
     * @throws ClassAlreadyExistsException
     * @throws ClassIsFinalException
     * @throws ClassIsReadonlyException
     * @throws DuplicateMethodException
     * @throws InvalidMethodNameException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws RuntimeException
     * @throws UnknownTypeException
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \PHPUnit\Framework\InvalidArgumentException
     * @throws \PHPUnit\Framework\MockObject\ReflectionException
     * @throws \ReflectionException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws \Exception
     * @covers \JtlWooCommerceConnector\Controllers\Product\ProductGermanizedFieldsController::getConcatenatedAddresses
     */
    public function testGetConcatenatedAddresses(
        array $manufacturerData,
        array $responsiblePersonData,
        array $expectedResult
    ): void {
        $db   = $this->getMockBuilder(Db::class)->disableOriginalConstructor()->getMock();
        $util = $this->getMockBuilder(Util::class)->disableOriginalConstructor()->getMock();

        $germanizedController = new ProductGermanizedFieldsController($db, $util);

        $controller   = new \ReflectionClass($germanizedController);
        $getAddresses = $controller->getMethod('getConcatenatedAddresses');

        $results = $getAddresses->invoke($germanizedController, $manufacturerData, $responsiblePersonData);
        $this->assertSame($expectedResult[0], $results[0]);
        $this->assertSame($expectedResult[1], $results[1]);
    }

    /**
     * @return array<int, array<int, ProductImage|string>>
     */
    public function gpsrDataProvider(): array
    {
        return [
            [
                [
                    'name' => 'Manufacturer ABC',
                    'street' => 'Manufacturer street',
                    'housenumber' => 'Manufacturer housenumber',
                    'postalcode' => 'Manufacturer postalcode',
                    'city' => 'Manufacturer city',
                    'state' => 'Manufacturer state',
                    'country' => 'Manufacturer country',
                    'email' => 'Manufacturer email',
                    'homepage' => 'Manufacturer homepage'
                ],
                [
                    'name' => 'John Doe',
                    'street' => 'Responsible street',
                    'housenumber' => 'Responsible housenumber',
                    'postalcode' => 'Responsible postalcode',
                    'city' => 'Responsible city',
                    'state' => 'Responsible state',
                    'country' => 'Responsible country',
                    'email' => 'Responsible email',
                    'homepage' => 'Responsible homepage'
                ],
                [
                    'Manufacturer ABC' . "\n" .
                    'Manufacturer street Manufacturer housenumber' . "\n" .
                    'Manufacturer postalcode Manufacturer city' . "\n" .
                    'Manufacturer state Manufacturer country' . "\n" .
                    'Manufacturer email' . "\n" .
                    'Manufacturer homepage',
                    'John Doe' . "\n" .
                    'Responsible street Responsible housenumber' . "\n" .
                    'Responsible postalcode Responsible city' . "\n" .
                    'Responsible state Responsible country' . "\n" .
                    'Responsible email' . "\n" .
                    'Responsible homepage'
                ]
            ]
        ];
    }
}
