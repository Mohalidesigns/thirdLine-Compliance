<?php

declare(strict_types=1);

namespace Tests\Integration;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Base test case for integration tests that require a live PostgreSQL connection.
 *
 * Uses DatabaseTransactions (wraps each test in a transaction, then rolls back)
 * rather than RefreshDatabase. The table structure must already exist — run
 * `php artisan migrate` before executing integration tests.
 *
 * Uses the 'pgsql_integration' connection defined in config/database.php, which
 * reads PGSQL_* env vars (not the DB_* vars that phpunit.xml overrides to sqlite).
 *
 * Requirements:
 *   - PostgreSQL 17 running with the atheris_compliance database.
 *   - All migrations applied: php artisan migrate.
 *   - PGSQL_* env vars set (defaults to the local dev values: postgres/admin/atheris_compliance).
 */
abstract class PostgresTestCase extends TestCase
{
    use DatabaseTransactions;

    /**
     * The connections to wrap in a transaction for each test.
     * Must reference the 'pgsql_integration' connection from config/database.php.
     */
    protected array $connectionsToTransact = ['pgsql_integration'];

    protected function setUp(): void
    {
        parent::setUp();

        // Point all DB::table() calls in the test to the integration connection.
        $this->app['db']->setDefaultConnection('pgsql_integration');
    }
}
