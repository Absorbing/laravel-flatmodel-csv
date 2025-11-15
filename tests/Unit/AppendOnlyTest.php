<?php

namespace FlatModel\CsvModel\Tests\Unit;

use FlatModel\CsvModel\Models\Model;
use FlatModel\CsvModel\Traits\Writable;
use FlatModel\CsvModel\Traits\AppendOnly;
use FlatModel\CsvModel\Tests\TestCase;
use FlatModel\CsvModel\Exceptions\AppendOnlyViolationException;

class AppendOnlyTest extends TestCase
{
    /** @test */
    public function it_allows_insert_on_append_only_model()
    {
        $writablePath = $this->writableFixture('users.csv');

        $model = new class($writablePath) extends Model {
            use Writable, AppendOnly;

            protected string $path;
            protected bool $writable = true;
            protected bool $appendOnly = true;

            public function __construct(string $csvPath) {
                $this->path = $csvPath;
                parent::__construct();
            }

            protected function resolvePath($path = ''): string {
                return $this->path;
            }
        };

        // Insert should work
        $model->insert(['id' => '6', 'name' => 'New User', 'email' => 'new@example.com', 'active' => 'true']);

        $this->assertTrue(true); // If we get here, insert worked
    }

    /** @test */
    public function it_throws_exception_on_update_for_append_only_model()
    {
        $writablePath = $this->writableFixture('users.csv');

        $model = new class($writablePath) extends Model {
            use Writable, AppendOnly;

            protected string $path;
            protected bool $writable = true;
            protected bool $appendOnly = true;

            public function __construct(string $csvPath) {
                $this->path = $csvPath;
                parent::__construct();
            }

            protected function resolvePath($path = ''): string {
                return $this->path;
            }
        };

        // Update should throw exception
        $this->expectException(AppendOnlyViolationException::class);
        $this->expectExceptionMessage('is append-only and does not support mutation');

        $model->update(['id' => '1'], ['name' => 'Updated Name']);
    }

    /** @test */
    public function it_throws_exception_on_upsert_for_append_only_model()
    {
        $writablePath = $this->writableFixture('users.csv');

        $model = new class($writablePath) extends Model {
            use Writable, AppendOnly;

            protected string $path;
            protected bool $writable = true;
            protected bool $appendOnly = true;

            public function __construct(string $csvPath) {
                $this->path = $csvPath;
                parent::__construct();
            }

            protected function resolvePath($path = ''): string {
                return $this->path;
            }
        };

        // Upsert should throw exception
        $this->expectException(AppendOnlyViolationException::class);

        $model->upsert(['id' => '1'], ['name' => 'Updated Name']);
    }

    /** @test */
    public function it_throws_exception_on_delete_for_append_only_model()
    {
        $writablePath = $this->writableFixture('users.csv');

        $model = new class($writablePath) extends Model {
            use Writable, AppendOnly;

            protected string $path;
            protected bool $writable = true;
            protected bool $appendOnly = true;

            public function __construct(string $csvPath) {
                $this->path = $csvPath;
                parent::__construct();
            }

            protected function resolvePath($path = ''): string {
                return $this->path;
            }
        };

        // Delete should throw exception
        $this->expectException(AppendOnlyViolationException::class);

        $model->delete(['id' => '1']);
    }

    /** @test */
    public function it_allows_all_operations_when_append_only_is_false()
    {
        $writablePath = $this->writableFixture('users.csv');

        $model = new class($writablePath) extends Model {
            use Writable, AppendOnly;

            protected string $path;
            protected bool $writable = true;
            protected bool $appendOnly = false; // NOT append-only

            public function __construct(string $csvPath) {
                $this->path = $csvPath;
                parent::__construct();
            }

            protected function resolvePath($path = ''): string {
                return $this->path;
            }
        };

        // All operations should work when appendOnly is false
        $model->insert(['id' => '6', 'name' => 'New User', 'email' => 'new@example.com', 'active' => 'true']);
        $model->update(['id' => '1'], ['name' => 'Updated Name']);
        $model->delete(['id' => '2']);

        $this->assertTrue(true); // If we get here, all operations worked
    }

    /** @test */
    public function it_works_without_append_only_trait()
    {
        // Test that Writable works without AppendOnly trait (uses default assertMutable)
        $writablePath = $this->writableFixture('users.csv');

        $model = new class($writablePath) extends Model {
            use Writable; // No AppendOnly trait

            protected string $path;
            protected bool $writable = true;

            public function __construct(string $csvPath) {
                $this->path = $csvPath;
                parent::__construct();
            }

            protected function resolvePath($path = ''): string {
                return $this->path;
            }
        };

        // All operations should work (default assertMutable allows everything)
        $model->insert(['id' => '6', 'name' => 'New User', 'email' => 'new@example.com', 'active' => 'true']);
        $model->update(['id' => '1'], ['name' => 'Updated Name']);
        $model->delete(['id' => '2']);

        $this->assertTrue(true); // If we get here, all operations worked
    }
}
