<?php

namespace FlatModel\CsvModel\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use FlatModel\CsvModel\CsvModelServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            CsvModelServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup default storage path for tests
        $app['config']->set('filesystems.default', 'local');
    }

    /**
     * Get the path to a test fixture file.
     */
    protected function fixture(string $filename): string
    {
        return __DIR__ . '/Fixtures/' . $filename;
    }

    /**
     * Create a writable copy of a fixture for testing write operations.
     */
    protected function writableFixture(string $sourceFixture, string $destination = null): string
    {
        $destination = $destination ?? str_replace('.csv', '_writable_' . uniqid() . '.csv', $sourceFixture);
        $sourcePath = $this->fixture($sourceFixture);
        $destPath = $this->fixture($destination);

        copy($sourcePath, $destPath);

        return $destPath;
    }

    /**
     * Clean up writable fixtures after tests.
     */
    protected function tearDown(): void
    {
        // Clean up any writable fixtures created during tests
        $fixturesDir = __DIR__ . '/Fixtures/';
        $files = glob($fixturesDir . '*_writable_*.csv');
        $files = array_merge($files, glob($fixturesDir . '*.bak'));
        $files = array_merge($files, glob($fixturesDir . 'temp_*.csv'));

        foreach ($files as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }

        parent::tearDown();
    }
}
