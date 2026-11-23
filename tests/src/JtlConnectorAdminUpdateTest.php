<?php

declare(strict_types=1);

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

namespace JtlWooCommerceConnector\Tests;

use JtlWooCommerceConnector\Utilities\Config;
use JtlWooCommerceConnector\Utilities\Db;
use Mockery;
use Mockery\MockInterface;
use WP_Mock;

require_once __DIR__ . '/../../includes/JtlConnectorAdmin.php';

if (!\defined('JTLWCC_CONNECTOR_DIR')) {
    \define('JTLWCC_CONNECTOR_DIR', \dirname(__DIR__, 2));
}
if (!\defined('DB_NAME')) {
    \define('DB_NAME', 'wordpress_test');
}
if (!\defined('CONNECTOR_DIR')) {
    $connectorDir = \sys_get_temp_dir() . '/jtl-connector-admin-update-test';
    if (!\is_dir($connectorDir . '/config')) {
        \mkdir($connectorDir . '/config', 0777, true);
    }
    \define('CONNECTOR_DIR', $connectorDir);
}

/**
 * @covers \JtlConnectorAdmin
 */
class JtlConnectorAdminUpdateTest extends AbstractTestCase
{
    private const string SITE_PREFIX = 'wp_14_';

    private const array LINK_FK_TABLES = ['product', 'order', 'payment', 'crossselling', 'category', 'specific'];

    private const array LINK_TABLES = [
        'category', 'crossselling', 'crossselling_group', 'currency', 'customer', 'customer_group',
        'image', 'language', 'manufacturer', 'measurement_unit', 'order', 'payment', 'product',
        'shipping_class', 'shipping_method', 'specific', 'specific_value', 'tax_class',
    ];

    /** @var list<string> */
    private array $queries = [];

    /** @var list<string> */
    private array $showTablesCalls = [];

    /** @var list<string> */
    private array $existingTables = [];

    /** @var list<string> */
    private array $existingForeignKeys = [];

    private MockInterface $wpdb;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        WP_Mock::setUp();

        $this->queries             = [];
        $this->showTablesCalls     = [];
        $this->existingTables      = $this->fullyInstalledTables();
        $this->existingForeignKeys = $this->fullyInstalledForeignKeys();

        $this->wpdb         = $this->createWpdbFake();
        $GLOBALS['wpdb']    = $this->wpdb;
        $this->wpdb->prefix = self::SITE_PREFIX;

        WP_Mock::userFunction('esc_sql', ['return' => static fn (string $value): string => $value]);
        WP_Mock::userFunction('is_multisite', ['return' => false]);
        WP_Mock::userFunction('wp_json_encode', ['return' => '{}']);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        unset($GLOBALS['wpdb']);
        Mockery::close();
        WP_Mock::tearDown();
        parent::tearDown();
    }

    /**
     * @covers \JtlConnectorAdmin::update
     * @return void
     * @throws \ReflectionException
     */
    public function testUpdateDoesNotTouchTheDatabaseWhenInstalledVersionEqualsBuildVersion(): void
    {
        $this->stubInstalledVersion(Config::getBuildVersion());
        WP_Mock::userFunction('update_option', ['times' => 0, 'return' => true]);

        $this->invokeUpdate();

        $this->assertSame([], $this->showTablesCalls, 'activate_linking() must not run');
        $this->assertSame([], $this->queries, 'no DDL/DML may be executed');
    }

    /**
     * @covers \JtlConnectorAdmin::update
     * @return void
     * @throws \ReflectionException
     */
    public function testUpdateDoesNotTouchTheDatabaseWhenInstalledVersionIsNewerThanBuildVersion(): void
    {
        $this->stubInstalledVersion('99.0.0');
        WP_Mock::userFunction('update_option', ['times' => 0, 'return' => true]);

        $this->invokeUpdate();

        $this->assertSame([], $this->showTablesCalls);
        $this->assertSame([], $this->queries);
    }

    /**
     * @covers \JtlConnectorAdmin::update
     * @dataProvider olderVersionsProvider
     * @param string $installedVersion
     * @return void
     * @throws \ReflectionException
     */
    public function testUpdateRunsMigrationOnceAndStoresBuildVersionWhenInstalledVersionIsOlder(
        string $installedVersion
    ): void {
        $this->stubInstalledVersion($installedVersion);
        WP_Mock::userFunction('update_option', [
            'times'  => 1,
            'args'   => [Config::OPTIONS_INSTALLED_VERSION, Config::getBuildVersion(), true],
            'return' => true,
        ]);

        $this->invokeUpdate();

        $this->assertCount(1, $this->showTablesCalls, 'activate_linking() must run exactly once');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function olderVersionsProvider(): array
    {
        return [
            'last case of the old switch' => ['2.4.1'],
            'version missing in switch'   => ['2.4.2'],
            'previous release'            => ['2.4.3'],
        ];
    }

    /**
     * @covers \JtlConnectorAdmin::activate_linking
     * @return void
     * @throws \ReflectionException
     */
    public function testActivateLinkingExecutesNoQueryOnFullyInstalledMultisiteSite(): void
    {
        $this->invokeStatic('activate_linking');

        $this->assertSame([], $this->queries);
    }

    /**
     * @covers \JtlConnectorAdmin::activate_linking
     * @return void
     * @throws \ReflectionException
     */
    public function testActivateLinkingAddsForeignKeysOnlyToLinkTablesOfCurrentSite(): void
    {
        $this->existingForeignKeys = [];

        $this->invokeStatic('activate_linking');

        $alterQueries = \array_values(\array_filter(
            $this->queries,
            static fn (string $query): bool => \str_contains($query, 'ADD CONSTRAINT')
        ));
        $this->assertCount(\count(self::LINK_FK_TABLES), $alterQueries);
        foreach (self::LINK_FK_TABLES as $table) {
            $expected = '`' . self::SITE_PREFIX . 'jtl_connector_link_' . $table . '`';
            $matching = \array_filter(
                $alterQueries,
                static fn (string $query): bool => \str_contains($query, 'ALTER TABLE ' . $expected)
            );
            $this->assertCount(1, $matching, 'FK for ' . $table . ' must target ' . $expected);
        }
    }

    /**
     * @covers \JtlConnectorAdmin::activate_linking
     * @return void
     * @throws \ReflectionException
     */
    public function testActivateLinkingDoesNotCreateTablesWithTruncatedPrefix(): void
    {
        $this->invokeStatic('activate_linking');

        $created = \array_filter(
            $this->queries,
            static fn (string $query): bool => \preg_match(
                '/CREATE TABLE IF NOT EXISTS `' . self::SITE_PREFIX . 'jtl_connector_(?!link_)[a-z_]*`/',
                $query
            ) === 1 && !\str_contains($query, 'category_level') && !\str_contains($query, 'product_checksum')
        );
        $this->assertSame([], \array_values($created));
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    private function invokeUpdate(): void
    {
        $this->invokeStatic('update', new Db($this->wpdb));
    }

    /**
     * @param string $method
     * @param mixed  ...$arguments
     * @return mixed
     * @throws \ReflectionException
     */
    private function invokeStatic(string $method, mixed ...$arguments): mixed
    {
        $reflectionMethod = new \ReflectionMethod(\JtlConnectorAdmin::class, $method);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invoke(null, ...$arguments);
    }

    /**
     * @param string $installedVersion
     * @return void
     */
    private function stubInstalledVersion(string $installedVersion): void
    {
        WP_Mock::userFunction('get_option', [
            'return' => static fn (string $name, mixed $default = false): mixed =>
                $name === Config::OPTIONS_INSTALLED_VERSION ? $installedVersion : $default,
        ]);
    }

    /**
     * @return list<string>
     */
    private function fullyInstalledTables(): array
    {
        $tables = [];
        foreach (self::LINK_TABLES as $table) {
            $tables[] = self::SITE_PREFIX . 'jtl_connector_link_' . $table;
        }
        $tables[] = self::SITE_PREFIX . 'jtl_connector_category_level';
        $tables[] = self::SITE_PREFIX . 'jtl_connector_product_checksum';
        $tables[] = self::SITE_PREFIX . 'posts';

        return $tables;
    }

    /**
     * @return list<string>
     */
    private function fullyInstalledForeignKeys(): array
    {
        $keys = [];
        foreach (self::LINK_FK_TABLES as $table) {
            $keys[] = self::SITE_PREFIX . 'jtl_connector_link_' . $table . '|jtl_connector_link_' . $table . '_1';
        }
        $keys[] = self::SITE_PREFIX . 'jtl_connector_category_level|jtl_connector_category_level1';
        $keys[] = self::SITE_PREFIX . 'jtl_connector_product_checksum|jtl_connector_product_checksum1';

        return $keys;
    }

    /**
     * @return MockInterface
     */
    private function createWpdbFake(): MockInterface
    {
        $wpdb = Mockery::mock('wpdb');

        $wpdb->posts = self::SITE_PREFIX . 'posts';
        $wpdb->terms = self::SITE_PREFIX . 'terms';

        $wpdb->shouldReceive('prepare')->andReturnUsing(
            static fn (string $query, mixed ...$args): string => \vsprintf(
                \str_replace('%s', "'%s'", $query),
                $args
            )
        );
        $wpdb->shouldReceive('get_results')->andReturnUsing(function (string $query): array {
            if ($query === 'SHOW TABLES') {
                $this->showTablesCalls[] = $query;

                return \array_map(
                    static fn (string $table): object => (object) ['Tables_in_db' => $table],
                    $this->existingTables
                );
            }

            return [];
        });
        $wpdb->shouldReceive('get_var')->andReturnUsing(function (string $query): ?string {
            if (\str_contains($query, 'information_schema.TABLES')) {
                return 'InnoDB';
            }
            if (\preg_match("/TABLE_NAME = '([^']+)'\s+AND CONSTRAINT_NAME = '([^']+)'/", $query, $matches) === 1) {
                return \in_array($matches[1] . '|' . $matches[2], $this->existingForeignKeys, true) ? '1' : '0';
            }

            return null;
        });
        $wpdb->shouldReceive('query')->andReturnUsing(function (string $query): int {
            $this->queries[] = \trim((string) \preg_replace('/\s+/', ' ', $query));

            return 0;
        });

        return $wpdb;
    }
}
