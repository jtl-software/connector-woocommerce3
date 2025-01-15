<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Faker;

use JtlWooCommerceConnector\Utilities\Db;

class DbFaker extends Db
{
    /**
     * @var string[]
     */
    public array $givenQueries = [];

    /**
     * @param string $query
     * @param bool $shouldLog
     * @return array<string, bool|int|string|null>|null Faker Database query results
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function query(string $query, bool $shouldLog = true): ?array
    {
        $this->givenQueries[] = $query;
        return parent::query($query, $shouldLog);
    }
}