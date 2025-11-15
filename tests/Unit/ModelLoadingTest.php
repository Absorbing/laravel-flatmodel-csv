<?php

namespace FlatModel\CsvModel\Tests\Unit;

use FlatModel\CsvModel\Models\Model;
use FlatModel\CsvModel\Tests\TestCase;
use FlatModel\CsvModel\Exceptions\FileNotFoundException;
use FlatModel\CsvModel\Exceptions\MissingHeaderException;

class ModelLoadingTest extends TestCase
{
    /** @test */
    public function it_loads_csv_with_headers()
    {
        $model = new class extends Model {
            protected string $path;
            public function __construct() {
                $this->path = __DIR__ . '/../Fixtures/users.csv';
                parent::__construct();
            }
            public function getPublicRows() { return $this->getRows(); }
            protected function resolvePath($path = ''): string {
                return $this->path;
            }
        };

        $rows = $model->getPublicRows();

        $this->assertCount(5, $rows);
        $this->assertEquals('John Doe', $rows[0]['name']);
        $this->assertEquals('jane@example.com', $rows[1]['email']);
    }

    /** @test */
    public function it_loads_headers_from_first_row()
    {
        $model = new class extends Model {
            protected string $path;
            public function __construct() {
                $this->path = __DIR__ . '/../Fixtures/users.csv';
                parent::__construct();
            }
            protected function resolvePath($path = ''): string {
                return $this->path;
            }
        };

        $headers = $model->getHeaders();

        $this->assertEquals(['id', 'name', 'email', 'active'], $headers);
    }

    /** @test */
    public function it_throws_exception_when_file_not_found()
    {
        $this->expectException(FileNotFoundException::class);

        new class extends Model {
            protected string $path = 'nonexistent.csv';
            protected function resolvePath($path = ''): string {
                return $this->path;
            }
        };
    }

    /** @test */
    public function it_supports_custom_delimiter()
    {
        // Create a semicolon-delimited CSV
        $path = $this->fixture('temp_semicolon.csv');
        file_put_contents($path, "id;name;value\n1;Test;100\n2;Demo;200");

        $model = new class($path) extends Model {
            protected string $delimiter = ';';
            public function __construct(protected string $csvPath) {
                $this->path = $csvPath;
                parent::__construct();
            }
            protected function resolvePath($path = ''): string {
                return $this->path;
            }
            public function getPublicRows() { return $this->getRows(); }
        };

        $rows = $model->getPublicRows();
        $this->assertCount(2, $rows);
        $this->assertEquals('Test', $rows[0]['name']);
    }

    /** @test */
    public function it_handles_empty_csv_with_headers()
    {
        $model = new class extends Model {
            protected string $path;
            public function __construct() {
                $this->path = __DIR__ . '/../Fixtures/empty.csv';
                parent::__construct();
            }
            protected function resolvePath($path = ''): string {
                return $this->path;
            }
            public function getPublicRows() { return $this->getRows(); }
        };

        $rows = $model->getPublicRows();
        $headers = $model->getHeaders();

        $this->assertCount(0, $rows);
        $this->assertEquals(['id', 'name', 'value'], $headers);
    }
}
