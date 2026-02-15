<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'pgsql_testing',
            'database.connections.pgsql_testing' => [
                'driver' => 'pgsql',
                'host' => env('DB_TEST_HOST', 'db_test'),
                'port' => env('DB_TEST_PORT', '5432'),
                'database' => env('DB_TEST_DATABASE', 'news_aggregator_testing'),
                'username' => env('DB_TEST_USERNAME', 'postgres'),
                'password' => env('DB_TEST_PASSWORD', 'secret'),
                'charset' => 'utf8',
                'prefix' => '',
                'prefix_indexes' => true,
                'search_path' => 'public',
                'sslmode' => 'prefer',
            ],
        ]);

        $this->artisan('migrate:fresh', [
            '--database' => 'pgsql_testing',
            '--force' => true,
        ]);
    }
}
