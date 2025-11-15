<?php

namespace FlatModel\CsvModel\Tests\Unit;

use FlatModel\CsvModel\Models\Model;
use FlatModel\CsvModel\Tests\TestCase;

class HeaderlessCSVTest extends TestCase
{
    /** @test */
    public function it_loads_headerless_csv_with_custom_headers()
    {
        $model = new class extends Model {
            protected string $path;
            protected bool $hasHeaders = false;
            protected array $headers = ['id', 'name', 'email', 'active'];

            public function __construct() {
                $this->path = __DIR__ . '/../Fixtures/users_headerless.csv';
                parent::__construct();
            }

            protected function resolvePath($path = ''): string {
                return $this->path;
            }

            public function getPublicRows() { return $this->getRows(); }
        };

        $rows = $model->getPublicRows();

        $this->assertCount(3, $rows);
        $this->assertEquals('John Doe', $rows[0]['name']);
        $this->assertEquals('jane@example.com', $rows[1]['email']);
        $this->assertEquals('3', $rows[2]['id']);
    }

    /** @test */
    public function it_generates_numeric_headers_for_headerless_csv()
    {
        $model = new class extends Model {
            protected string $path;
            protected bool $hasHeaders = false;

            public function __construct() {
                $this->path = __DIR__ . '/../Fixtures/users_headerless.csv';
                parent::__construct();
            }

            protected function resolvePath($path = ''): string {
                return $this->path;
            }

            public function getPublicRows() { return $this->getRows(); }
        };

        $rows = $model->getPublicRows();
        $headers = $model->getHeaders();

        $this->assertEquals(['0', '1', '2', '3'], $headers);
        $this->assertEquals('John Doe', $rows[0]['1']);
        $this->assertEquals('jane@example.com', $rows[1]['2']);
    }

    /** @test */
    public function it_uses_predefined_headers_when_csv_has_header_row()
    {
        // When hasHeaders=true but headers are predefined,
        // it should use predefined headers and skip first row
        $model = new class extends Model {
            protected string $path;
            protected bool $hasHeaders = true;
            protected array $headers = ['user_id', 'full_name', 'email_address', 'is_active'];

            public function __construct() {
                $this->path = __DIR__ . '/../Fixtures/users.csv';
                parent::__construct();
            }

            protected function resolvePath($path = ''): string {
                return $this->path;
            }

            public function getPublicRows() { return $this->getRows(); }
        };

        $headers = $model->getHeaders();
        $rows = $model->getPublicRows();

        // Should use custom headers
        $this->assertEquals(['user_id', 'full_name', 'email_address', 'is_active'], $headers);

        // First data row (after skipping header)
        $this->assertEquals('John Doe', $rows[0]['full_name']);
        $this->assertCount(5, $rows);
    }
}
