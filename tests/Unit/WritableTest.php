<?php

namespace FlatModel\CsvModel\Tests\Unit;

use FlatModel\CsvModel\Models\Model;
use FlatModel\CsvModel\Traits\Writable;
use FlatModel\CsvModel\Tests\TestCase;
use FlatModel\CsvModel\Exceptions\WriteNotAllowedException;

class WritableTest extends TestCase
{
    /** @test */
    public function it_inserts_new_row()
    {
        $writablePath = $this->writableFixture('users.csv');

        $model = new class($writablePath) extends Model {
            use Writable;

            protected string $path;
            protected bool $writable = true;

            public function __construct(string $csvPath) {
                $this->path = $csvPath;
                parent::__construct();
            }

            protected function resolvePath($path = ''): string {
                return $this->path;
            }

            public function getPublicRows() { return $this->getRows(); }
        };

        $model->insert(['id' => '6', 'name' => 'New User', 'email' => 'new@example.com', 'active' => 'true']);

        $rows = $model->getPublicRows();
        $this->assertCount(6, $rows);
        $this->assertEquals('New User', $rows[5]['name']);
    }

    /** @test */
    public function it_updates_existing_rows_with_array_syntax()
    {
        $writablePath = $this->writableFixture('users.csv');

        $model = new class($writablePath) extends Model {
            use Writable;

            protected string $path;
            protected bool $writable = true;

            public function __construct(string $csvPath) {
                $this->path = $csvPath;
                parent::__construct();
            }

            protected function resolvePath($path = ''): string {
                return $this->path;
            }

            public function getPublicRows() { return $this->getRows(); }
        };

        $model->update(['id' => '1'], ['name' => 'Updated Name']);

        $rows = $model->getPublicRows();
        $this->assertEquals('Updated Name', $rows[0]['name']);
        $this->assertEquals('john@example.com', $rows[0]['email']); // Other fields unchanged
    }

    /** @test */
    public function it_updates_existing_rows_with_callable_syntax()
    {
        $writablePath = $this->writableFixture('users.csv');

        $model = new class($writablePath) extends Model {
            use Writable;

            protected string $path;
            protected bool $writable = true;

            public function __construct(string $csvPath) {
                $this->path = $csvPath;
                parent::__construct();
            }

            protected function resolvePath($path = ''): string {
                return $this->path;
            }

            public function getPublicRows() { return $this->getRows(); }
        };

        $model->update(
            fn($row) => $row['id'] === '1',
            fn($row) => [...$row, 'name' => 'Callable Updated']
        );

        $rows = $model->getPublicRows();
        $this->assertEquals('Callable Updated', $rows[0]['name']);
    }

    /** @test */
    public function it_upserts_updates_existing_row()
    {
        $writablePath = $this->writableFixture('users.csv');

        $model = new class($writablePath) extends Model {
            use Writable;

            protected string $path;
            protected bool $writable = true;

            public function __construct(string $csvPath) {
                $this->path = $csvPath;
                parent::__construct();
            }

            protected function resolvePath($path = ''): string {
                return $this->path;
            }

            public function getPublicRows() { return $this->getRows(); }
        };

        $model->upsert(['id' => '1'], ['id' => '1', 'name' => 'Upserted', 'email' => 'up@example.com', 'active' => 'true']);

        $rows = $model->getPublicRows();
        $this->assertCount(5, $rows); // No new row added
        $this->assertEquals('Upserted', $rows[0]['name']);
    }

    /** @test */
    public function it_upserts_inserts_new_row_when_not_found()
    {
        $writablePath = $this->writableFixture('users.csv');

        $model = new class($writablePath) extends Model {
            use Writable;

            protected string $path;
            protected bool $writable = true;

            public function __construct(string $csvPath) {
                $this->path = $csvPath;
                parent::__construct();
            }

            protected function resolvePath($path = ''): string {
                return $this->path;
            }

            public function getPublicRows() { return $this->getRows(); }
        };

        $model->upsert(['id' => '99'], ['id' => '99', 'name' => 'New via Upsert', 'email' => 'new@example.com', 'active' => 'true']);

        $rows = $model->getPublicRows();
        $this->assertCount(6, $rows); // New row added
        $this->assertEquals('New via Upsert', $rows[5]['name']);
    }

    /** @test */
    public function it_deletes_matching_rows_with_array_syntax()
    {
        $writablePath = $this->writableFixture('users.csv');

        $model = new class($writablePath) extends Model {
            use Writable;

            protected string $path;
            protected bool $writable = true;

            public function __construct(string $csvPath) {
                $this->path = $csvPath;
                parent::__construct();
            }

            protected function resolvePath($path = ''): string {
                return $this->path;
            }

            public function getPublicRows() { return $this->getRows(); }
        };

        $model->delete(['id' => '1']);

        $rows = $model->getPublicRows();
        $this->assertCount(4, $rows);
        $this->assertEquals('Jane Smith', $rows[0]['name']); // First row is now Jane
    }

    /** @test */
    public function it_deletes_matching_rows_with_callable()
    {
        $writablePath = $this->writableFixture('users.csv');

        $model = new class($writablePath) extends Model {
            use Writable;

            protected string $path;
            protected bool $writable = true;

            public function __construct(string $csvPath) {
                $this->path = $csvPath;
                parent::__construct();
            }

            protected function resolvePath($path = ''): string {
                return $this->path;
            }

            public function getPublicRows() { return $this->getRows(); }
        };

        $model->delete(fn($row) => $row['active'] === 'false');

        $rows = $model->getPublicRows();
        $this->assertCount(3, $rows); // Deleted 2 inactive users
        foreach ($rows as $row) {
            $this->assertEquals('true', $row['active']);
        }
    }

    /** @test */
    public function it_flushes_changes_to_disk()
    {
        $writablePath = $this->writableFixture('users.csv');

        $model = new class($writablePath) extends Model {
            use Writable;

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

        $model->insert(['id' => '6', 'name' => 'Persisted User', 'email' => 'persist@example.com', 'active' => 'true']);
        $model->flush();

        // Read file directly to verify persistence
        $content = file_get_contents($writablePath);
        $this->assertStringContainsString('Persisted User', $content);
        $this->assertStringContainsString('persist@example.com', $content);
    }

    /** @test */
    public function it_throws_exception_when_not_writable()
    {
        $writablePath = $this->writableFixture('users.csv');

        $model = new class($writablePath) extends Model {
            use Writable;

            protected string $path;
            protected bool $writable = false; // NOT writable

            public function __construct(string $csvPath) {
                $this->path = $csvPath;
                parent::__construct();
            }

            protected function resolvePath($path = ''): string {
                return $this->path;
            }
        };

        $this->expectException(WriteNotAllowedException::class);

        $model->insert(['id' => '6', 'name' => 'Should Fail', 'email' => 'fail@example.com', 'active' => 'true']);
    }
}
