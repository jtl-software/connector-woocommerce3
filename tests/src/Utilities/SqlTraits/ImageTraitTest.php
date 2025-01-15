<?php

namespace src\Utilities\SqlTraits;

use JtlWooCommerceConnector\Utilities\SqlHelper;
use PHPUnit\Framework\TestCase;

class ImageTraitTest extends TestCase
{
    protected $getLocale;

    /**
     * @return void
     * @throws \PHPUnit\Framework\InvalidArgumentException
     * @throws \PHPUnit\Framework\MockObject\ClassAlreadyExistsException
     * @throws \PHPUnit\Framework\MockObject\ClassIsFinalException
     * @throws \PHPUnit\Framework\MockObject\ClassIsReadonlyException
     * @throws \PHPUnit\Framework\MockObject\DuplicateMethodException
     * @throws \PHPUnit\Framework\MockObject\InvalidMethodNameException
     * @throws \PHPUnit\Framework\MockObject\OriginalConstructorInvocationRequiredException
     * @throws \PHPUnit\Framework\MockObject\ReflectionException
     * @throws \PHPUnit\Framework\MockObject\RuntimeException
     * @throws \PHPUnit\Framework\MockObject\UnknownTypeException
     */
    protected function setUp(): void
    {
        global $wpdb;

        $wpdb = $this->getMockBuilder(\wpdb::class)
            ->setMethods(['prefix'])
            ->getMock();

        $wpdb->prefix = 'wp_';
    }

    /**
     * @param int $productId
     * @param string $expectedSqlQuery
     * @return void
     *
     * @dataProvider getImageDeleteLinksDataProvider
     *
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \PHPUnit\Framework\InvalidArgumentException
     * @throws \PHPUnit\Framework\MockObject\ClassAlreadyExistsException
     * @throws \PHPUnit\Framework\MockObject\ClassIsFinalException
     * @throws \PHPUnit\Framework\MockObject\ClassIsReadonlyException
     * @throws \PHPUnit\Framework\MockObject\DuplicateMethodException
     * @throws \PHPUnit\Framework\MockObject\InvalidMethodNameException
     * @throws \PHPUnit\Framework\MockObject\OriginalConstructorInvocationRequiredException
     * @throws \PHPUnit\Framework\MockObject\ReflectionException
     * @throws \PHPUnit\Framework\MockObject\RuntimeException
     * @throws \PHPUnit\Framework\MockObject\UnknownTypeException
     * @throws \ReflectionException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testGetImageDeleteLinks(int $productId, string $expectedSqlQuery): void
    {
        $queryResult = SqlHelper::imageDeleteLinks($productId);
        $queryResult = \preg_replace('/\s+/', ' ', $queryResult);
        $this->assertSame($expectedSqlQuery, $queryResult);
    }

    /**
     * @return array<array<int, int|string>>
     */
    public function getImageDeleteLinksDataProvider(): array
    {
        return [
            [
                1111,
                " DELETE FROM wp_jtl_connector_link_image" .
                " WHERE (`type` = 42" .
                " OR `type` = 64)" .
                " AND endpoint_id" .
                " LIKE '%_1111'"
            ]
        ];
    }
}
