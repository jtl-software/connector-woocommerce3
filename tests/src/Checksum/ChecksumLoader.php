<?php

namespace JtlWooCommerceConnector\Tests\Checksum;

use Jtl\Connector\Core\Definition\IdentityType;
use JtlWooCommerceConnector\Tests\AbstractTestCase;

class ChecksumLoader extends AbstractTestCase
{
    public function testRead(): void
    {
        $checksumId = uniqid('', true);

        $db = $this->createDbMock(['queryOne']);
        $db->expects($this->once())->method('queryOne')->willReturn($checksumId);

        $checksumLoader = $this->getChecksumLoaderMock($db, ['getChecksumReadQuery']);
        $checksumLoader->expects($this->once())->method('getChecksumReadQuery');

        $result = $checksumLoader->read('c_1', IdentityType::CATEGORY);

        $this->assertEquals($checksumId, $result);
    }

    public function testWrite(): void
    {
        $checksumId = uniqid('', true);
        $db = $this->createDbMock(['query']);
        $db->expects($this->once())->method('query')->willReturn([]);

        $checksumLoader = $this->getChecksumLoaderMock($db, ['getChecksumWriteQuery']);
        $checksumLoader->expects($this->once())->method('getChecksumWriteQuery');

        $result = $checksumLoader->write('c_1', IdentityType::CATEGORY, $checksumId);
        $this->assertEquals([], $result);
    }

    public function testDelete(): void
    {
        $db = $this->createDbMock(['query']);
        $db->expects($this->once())->method('query')->willReturn([]);

        $checksumLoader = $this->getChecksumLoaderMock($db, ['getChecksumDeleteQuery']);
        $checksumLoader->expects($this->once())->method('getChecksumDeleteQuery');

        $result = $checksumLoader->delete('c_1', IdentityType::CATEGORY);
        $this->assertEquals([], $result);
    }

    protected function getChecksumLoaderMock($dbMock, array $onlyMethods = [])
    {
        return $this->getMockBuilder(\JtlWooCommerceConnector\Checksum\ChecksumLoader::class)
            ->setConstructorArgs([$dbMock])
            ->onlyMethods($onlyMethods)
            ->getMock();
    }
}