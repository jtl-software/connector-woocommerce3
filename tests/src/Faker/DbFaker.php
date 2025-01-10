<?php

namespace src\Faker;

use JtlWooCommerceConnector\Utilities\Db;

class DbFaker extends Db
{
    /**
     * @var string[]
     */
    public array $givenQueries = [];
    public function query(string $query, bool $shouldLog = true): ?array
    {
        $this->givenQueries[] = $query;
        return parent::query($query, $shouldLog);
    }

}