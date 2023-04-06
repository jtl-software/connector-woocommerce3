<?php

namespace src\Utilities;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Product;
use jtl\Connector\Model\ProductInvisibility;
use JtlWooCommerceConnector\Tests\AbstractTestCase;
use PHPUnit\Framework\MockObject\RuntimeException;

/**
 * Class B2BMarket
 * @package src\Utilities
 */
class B2BMarket extends AbstractTestCase
{
    /**
     * @throws \ReflectionException
     * @throws RuntimeException
     */
    public function testSetB2BCustomerGroupBlacklist()
    {
        $b2bMock = $this->getMockBuilder(\JtlWooCommerceConnector\Utilities\B2BMarket::class)
            ->addMethods(['getPostMeta', 'updatePostMeta', 'deletePostMeta'])
            ->getMock();

        $b2bMock->expects($this->exactly(2))
            ->method('getPostMeta')
            ->withConsecutive(['10', 'bm_conditional_products'], ['11', 'bm_conditional_products'])
            ->willReturnOnConsecutiveCalls([], []);

        $b2bMock->expects($this->once())
            ->method('deletePostMeta')
            ->with('11', 'bm_conditional_products', []);

        $b2bMock->expects($this->once())
            ->method('updatePostMeta')
            ->with('10', 'bm_conditional_products', \join(',', ['1']), []);

        $products = [
            (new Product())->setId(new Identity("1", 1))->setInvisibilities([
                (new ProductInvisibility())->setCustomerGroupId(new Identity("10", 1))
            ])
        ];

        $reflection = new \ReflectionClass($b2bMock);
        $method     = $reflection->getMethod('setB2BCustomerGroupBlacklist');
        $method->setAccessible(true);

        $method->invoke($b2bMock, ['10', '11'], 'bm_conditional_products', ...$products);
    }
}
