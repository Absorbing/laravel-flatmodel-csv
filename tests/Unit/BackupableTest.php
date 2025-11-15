<?php

namespace FlatModel\CsvModel\Tests\Unit;

use FlatModel\CsvModel\Models\Model;
use FlatModel\CsvModel\Traits\Writable;
use FlatModel\CsvModel\Traits\Backupable;
use FlatModel\CsvModel\Tests\TestCase;

class BackupableTest extends TestCase
{
    /** @test */
    public function it_creates_backup_before_flush()
    {
        $writablePath = $this->writableFixture('users.csv');

        $model = new class($writablePath) extends Model {
            use Writable, Backupable;

            protected string $path;
            protected bool $writable = true;
            protected bool $enableBackup = true;

            public function __construct(string $csvPath) {
                $this->path = $csvPath;
                parent::__construct();
            }

            protected function resolvePath($path = ''): string {
                return $this->path;
            }

            // Make backup() method public for testing
            public function createBackup() {
                $this->backup();
            }
        };

        // Get initial content
        $originalContent = file_get_contents($writablePath);

        // Create backup
        $model->createBackup();

        // Check that a backup file was created
        $backupFiles = glob(dirname($writablePath) . '/*.bak');
        $this->assertNotEmpty($backupFiles);

        // Verify backup content matches original
        $backupContent = file_get_contents($backupFiles[0]);
        $this->assertEquals($originalContent, $backupContent);
    }

    /** @test */
    public function it_does_not_create_backup_when_disabled()
    {
        $writablePath = $this->writableFixture('users.csv');

        $model = new class($writablePath) extends Model {
            use Writable, Backupable;

            protected string $path;
            protected bool $writable = true;
            protected bool $enableBackup = false; // Disabled

            public function __construct(string $csvPath) {
                $this->path = $csvPath;
                parent::__construct();
            }

            protected function resolvePath($path = ''): string {
                return $this->path;
            }

            public function createBackup() {
                $this->backup();
            }
        };

        // Create backup (should be skipped)
        $model->createBackup();

        // Check that no backup file was created
        $backupFiles = glob(dirname($writablePath) . '/*.bak');
        $this->assertEmpty($backupFiles);
    }

    /** @test */
    public function it_creates_timestamped_backup_filename()
    {
        $writablePath = $this->writableFixture('users.csv');

        $model = new class($writablePath) extends Model {
            use Writable, Backupable;

            protected string $path;
            protected bool $writable = true;
            protected bool $enableBackup = true;

            public function __construct(string $csvPath) {
                $this->path = $csvPath;
                parent::__construct();
            }

            protected function resolvePath($path = ''): string {
                return $this->path;
            }

            public function createBackup() {
                $this->backup();
            }
        };

        $model->createBackup();

        $backupFiles = glob(dirname($writablePath) . '/*.bak');
        $this->assertNotEmpty($backupFiles);

        // Verify backup filename contains a timestamp pattern (YYYY-MM-DD-HH-II-SS)
        $backupFilename = basename($backupFiles[0]);
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2}\.bak$/', $backupFilename);
    }
}
