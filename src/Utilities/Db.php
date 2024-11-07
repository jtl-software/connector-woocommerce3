<?php

namespace JtlWooCommerceConnector\Utilities;

use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use wpdb;

class Db implements LoggerAwareInterface
{
    /**
     * @var wpdb
     */
    protected wpdb $wpDb;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface|NullLogger $logger;

    /**
     * @param wpdb $wpdb
     */
    public function __construct(wpdb $wpdb)
    {
        $this->wpDb   = $wpdb;
        $this->logger = new NullLogger();
    }

    /**
     * @param LoggerInterface $logger
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }


    /**
     * Run a plain SQL query on the database.
     *
     * @param string $query SQL query.
     * @param bool $shouldLog Query should be written to log files.
     *
     * @return array|null Database query results
     * @throws InvalidArgumentException
     */
    public function query(string $query, bool $shouldLog = true): ?array
    {
        $wpdb = $this->getWpDb();

        if ($shouldLog) {
            $this->logger->debug($query);
        }

        return $wpdb->get_results($query, \ARRAY_A);
    }

    /**
     * Run a SQL query which should only return one value.
     *
     * @param string $query SQL query.
     * @param bool $shouldLog Query should be written to log files.
     *
     * @return null|string Found value or null.
     * @throws InvalidArgumentException
     */
    public function queryOne(string $query, bool $shouldLog = true): ?string
    {
        $wpdb = $this->getWpDb();

        if ($shouldLog) {
            $this->logger->debug($query);
        }

        return $wpdb->get_var($query);
    }

    /**
     * Run a SQL query which should return a list of single values.
     *
     * @param string $query SQL query.
     * @param bool $shouldLog Query should be written to log files.
     *
     * @return array The array of values
     * @throws InvalidArgumentException
     */
    public function queryList(string $query, bool $shouldLog = true): array
    {
        $wpdb = $this->getWpDb();

        $return = [];

        if ($shouldLog) {
            $this->logger->debug($query);
        }

        $result = $wpdb->get_results($query, \ARRAY_N);

        if (!empty($result)) {
            foreach ($result as $row) {
                $return[] = $row[0];
            }
        } else {
            return [];
        }

        return $return;
    }

    /**
     * @return wpdb
     */
    public function getWpDb(): wpdb
    {
        return $this->wpDb;
    }


    /**
     * @param $table
     * @param $constraint
     * @return bool
     * @throws InvalidArgumentException
     */
    public function checkIfFKExists($table, $constraint): bool
    {
        $sql  = "
               SELECT COUNT(*)
                  FROM information_schema.TABLE_CONSTRAINTS
                  WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = '{$table}'
                    AND CONSTRAINT_NAME = '{$constraint}';";
        $test = $this->queryOne($sql);

        return (bool)$test;
    }
}
